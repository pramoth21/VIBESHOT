<?php
header('Content-Type: application/json');

$id   = isset($_GET['id'])   ? (int)$_GET['id']   : 0;
$date = isset($_GET['date']) ? trim($_GET['date']) : '';

if ($id <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
  echo json_encode([]); exit;
}

$conn = new mysqli('localhost','root','','vibeshot_db');
if ($conn->connect_error) { http_response_code(500); echo json_encode([]); exit; }


$slots = [];
$q = $conn->prepare("SELECT TIME_FORMAT(StartTime,'%H:%i') s, TIME_FORMAT(EndTime,'%H:%i') e
                     FROM PhotographerSlot
                     WHERE PhotographerID=? AND SlotDate=? AND IsAvailable=1
                     ORDER BY StartTime");
$q->bind_param("is", $id, $date);
$q->execute();
$r = $q->get_result();
while($row = $r->fetch_assoc()) $slots[] = $row;
$q->close();


$busy = [];
$q2 = $conn->prepare("SELECT TIME_FORMAT(StartTime,'%H:%i') s,
                             TIME_FORMAT(ADDTIME(StartTime, SEC_TO_TIME(DurationMin*60)),'%H:%i') e
                      FROM Booking
                      WHERE PhotographerID=? AND ShootDate=? 
                        AND Status IN ('Pending','Confirmed','Completed')");
$q2->bind_param("is", $id, $date);
$q2->execute();
$r2 = $q2->get_result();
while($row = $r2->fetch_assoc()) $busy[] = $row;
$q2->close();
$conn->close();


$overlaps = function($aS,$aE,$bS,$bE){
  return !($aE <= $bS || $aS >= $bE);
};

$out = [];
foreach($slots as $s){
  $ok = true;
  foreach($busy as $b){
    if ($overlaps($s['s'],$s['e'],$b['s'],$b['e'])) { $ok = false; break; }
  }
  if ($ok) $out[] = ['start'=>$s['s'], 'end'=>$s['e']];
}

echo json_encode($out);
