<?php
header('Content-Type: application/json');

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) { echo json_encode(['ok'=>false,'error'=>'Bad JSON']); exit; }

$clientId       = (int)($data['clientId'] ?? 0);
$photographerId = (int)($data['photographerId'] ?? 0);
$themeId        = isset($data['themeId']) ? (int)$data['themeId'] : null;
$sessionType    = trim($data['sessionType'] ?? 'Indoor');
$outdoorCategory= trim($data['outdoorCategory'] ?? '');
$location       = trim($data['location'] ?? '');
$shootDate      = trim($data['shootDate'] ?? '');
$startTimeIn    = trim($data['startTime'] ?? '');
$durationMin    = (int)($data['durationMin'] ?? 60);
$amount         = (float)($data['amount'] ?? 0);

if ($clientId<=0 || $photographerId<=0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/',$shootDate)) {
  echo json_encode(['ok'=>false,'error'=>'Invalid input']); exit;
}

if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $startTimeIn)) {
  echo json_encode(['ok'=>false,'error'=>'Invalid start time']); exit;
}
if ($durationMin <= 0) { echo json_encode(['ok'=>false,'error'=>'Invalid duration']); exit; }


$startDT = DateTime::createFromFormat(strlen($startTimeIn)==5?'H:i':'H:i:s', $startTimeIn);
$endDT   = clone $startDT; $endDT->modify("+{$durationMin} minutes");
$startTime = $startDT->format('H:i:s');
$endTime   = $endDT->format('H:i:s');

$conn = new mysqli('localhost','root','','vibeshot_db');
if ($conn->connect_error) { echo json_encode(['ok'=>false,'error'=>'DB failed']); exit; }


$conf = $conn->prepare("
  SELECT COUNT(*) 
  FROM Booking 
  WHERE PhotographerID=? AND ShootDate=? 
    AND Status IN ('Pending','Confirmed','Completed')
    AND NOT (ADDTIME(StartTime, SEC_TO_TIME(DurationMin*60)) <= ? OR StartTime >= ?)
");
$conf->bind_param("isss", $photographerId, $shootDate, $startTime, $endTime);
$conf->execute(); $conf->bind_result($cnt); $conf->fetch(); $conf->close();

if ($cnt > 0) {
  echo json_encode(['ok'=>false, 'error'=>'Selected time is already booked for this photographer. Please choose another time.']); 
  $conn->close(); exit;
}

$conn->begin_transaction();

try {
  $status        = 'Confirmed';         
  $paymentStatus = 'Paid';
  $paymentRef    = 'VS'.date('YmdHis').'-'.substr(bin2hex(random_bytes(4)),0,8);

  $stmt = $conn->prepare("
    INSERT INTO Booking
      (ClientID, PhotographerID, ThemeID, SessionType,
       OutdoorCategory, Location, ShootDate, StartTime, DurationMin,
       Status, Amount, PaymentStatus, PaymentRef)
    VALUES
      (?,?,?,?,?,?,?,?,?,?,?, ?, ?)
  ");

  // ThemeID may be null
  $themeBind = $themeId ?: null;
  $stmt->bind_param(
    "iiisssssissss",
    $clientId, $photographerId, $themeBind, $sessionType,
    $outdoorCategory, $location, $shootDate, $startTime, $durationMin,
    $status, $amount, $paymentStatus, $paymentRef
  );
  $ok = $stmt->execute();
  if (!$ok) throw new Exception('Insert booking failed: '.$stmt->error);
  $bookingId = $stmt->insert_id;
  $stmt->close();

  $ins = $conn->prepare("
    INSERT INTO PhotographerSlot (PhotographerID, SlotDate, StartTime, EndTime, IsAvailable)
    VALUES (?, ?, ?, ?, 0)
    ON DUPLICATE KEY UPDATE IsAvailable=0
  ");
  $ins->bind_param("isss", $photographerId, $shootDate, $startTime, $endTime);
  $ins->execute(); $ins->close();

  $conn->commit();
  echo json_encode(['ok'=>true,'bookingId'=>$bookingId,'paymentRef'=>$paymentRef]);
} catch (Exception $e) {
  $conn->rollback();
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}

$conn->close();
