<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header("Location: adminlogin.php"); exit; }

function db(){ $c=new mysqli('localhost','root','','vibeshot_db'); if($c->connect_error) die('DB failed: '.$c->connect_error); return $c; }
$conn = db();

$statuses = ['All','Pending','Confirmed','Completed','Cancelled','Declined'];


$status = $_GET['status'] ?? 'All';
$photographerId = (int)($_GET['photographerId'] ?? 0);
$clientId = (int)($_GET['clientId'] ?? 0);
$dateFrom = $_GET['from'] ?? '';
$dateTo   = $_GET['to'] ?? '';
$search   = trim($_GET['q'] ?? '');


$photographers = [];
$r=$conn->query("SELECT PhotographerID,Name FROM Photographer ORDER BY Name");
while($row=$r->fetch_assoc()) $photographers[]=$row; $r->free();

$clients = [];
$r=$conn->query("SELECT ClientID,Name FROM Client ORDER BY Name");
while($row=$r->fetch_assoc()) $clients[]=$row; $r->free();

$where = ["1=1"];
$types = ""; $vals = [];

if ($status !== 'All' && in_array($status,$statuses)) { $where[]="b.Status=?"; $types.="s"; $vals[]=$status; }
if ($photographerId>0){ $where[]="b.PhotographerID=?"; $types.="i"; $vals[]=$photographerId; }
if ($clientId>0){ $where[]="b.ClientID=?"; $types.="i"; $vals[]=$clientId; }
if ($dateFrom!=='' && preg_match('/^\d{4}-\d{2}-\d{2}$/',$dateFrom)){ $where[]="b.ShootDate>=?"; $types.="s"; $vals[]=$dateFrom; }
if ($dateTo!=='' && preg_match('/^\d{4}-\d{2}-\d{2}$/',$dateTo)){ $where[]="b.ShootDate<=?"; $types.="s"; $vals[]=$dateTo; }
if ($search!==''){
  $where[]="(b.BookingID = ? OR c.Name LIKE CONCAT('%',?,'%') OR p.Name LIKE CONCAT('%',?,'%'))";
  $types.="iss"; $vals[]=(int)$search; $vals[]=$search; $vals[]=$search;
}

$sql = "SELECT b.BookingID,b.SessionType,b.ShootDate,b.StartTime,b.DurationMin,b.Status,
               b.Amount,b.PaymentStatus,b.PaymentRef,
               c.Name AS ClientName, p.Name AS PhotographerName, t.Name AS ThemeName,
               b.OutdoorCategory,b.Location
        FROM Booking b
        LEFT JOIN Client c ON c.ClientID=b.ClientID
        LEFT JOIN Photographer p ON p.PhotographerID=b.PhotographerID
        LEFT JOIN Theme t ON t.ThemeID=b.ThemeID
        WHERE ".implode(' AND ', $where)."
        ORDER BY b.ShootDate DESC, b.StartTime DESC";

$stmt = $conn->prepare($sql);
if ($types!=='') { $stmt->bind_param($types, ...$vals); }
$stmt->execute();
$res = $stmt->get_result();
$rows = [];
while($r=$res->fetch_assoc()) $rows[]=$r;
$stmt->close();


$flash='';
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['booking_id'],$_POST['new_status'])) {
  $bid = (int)$_POST['booking_id'];
  $new = $_POST['new_status'];
  if (in_array($new,['Pending','Confirmed','Completed','Cancelled','Declined'])) {
    $u = $conn->prepare("UPDATE Booking SET Status=? WHERE BookingID=?");
    $u->bind_param("si",$new,$bid);
    $u->execute(); $u->close();
    $flash = "Booking #$bid updated to $new.";
    header("Location: bookings_manage.php?" . http_build_query($_GET)); exit;
  }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Manage Bookings | Vibe-Shot Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Cinzel:wght@700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="admin-dashboard-modern.css"/>
<style>
.badge{padding:4px 8px;border-radius:999px;border:1px solid #e6e6e6;font-size:.85rem}
.badge.pay.paid{background:#eafaf0}.badge.pay.unpaid{background:#fff5e6}.badge.pay.refunded{background:#eaf7ff}
.badge.status.pending{background:#fff5e6}.badge.status.confirmed{background:#eaf7ff}.badge.status.completed{background:#eafaf0}
.badge.status.cancelled,.badge.status.declined{background:#fdecef}
.controls{display:flex;gap:8px;flex-wrap:wrap}
.controls .input, .controls .select{padding:10px;border:1px solid #e6e6e6;border-radius:10px}
</style>
</head>
<body>
<header class="topnav elevate">
  <div class="brand"><img src="../logo1.png" alt=""><div class="brand-text"><strong>VIBE-SHOT</strong><span>Admin</span></div></div>
  <div class="top-actions">
    <a class="link" href="admin_dashboard_modern.php">Dashboard</a>
    <a class="btn-accent ripple" href="admin_logout.php">Logout</a>
  </div>
</header>

<div class="subhead elevate">
  <div class="subhead-left"><h2>Bookings</h2><p class="muted">Filter, review & update statuses.</p></div>
</div>

<div class="layout" style="grid-template-columns:1fr;">
  <main class="main">
    <section class="card">
      <form class="controls" method="get">
        <input class="input" type="text" name="q" placeholder="Search #ID / client / photographer" value="<?php echo htmlspecialchars($search); ?>">
        <select class="select" name="status">
          <?php foreach($statuses as $s): ?>
            <option <?php if($status===$s) echo 'selected'; ?>><?php echo $s; ?></option>
          <?php endforeach; ?>
        </select>
        <select class="select" name="photographerId">
          <option value="0">All Photographers</option>
          <?php foreach($photographers as $p): ?>
            <option value="<?php echo (int)$p['PhotographerID']; ?>" <?php if($photographerId==(int)$p['PhotographerID']) echo 'selected'; ?>>
              <?php echo htmlspecialchars($p['Name']); ?>
            </option>
          <?php endforeach; ?>
        </select>
        <select class="select" name="clientId">
          <option value="0">All Clients</option>
          <?php foreach($clients as $c): ?>
            <option value="<?php echo (int)$c['ClientID']; ?>" <?php if($clientId==(int)$c['ClientID']) echo 'selected'; ?>>
              <?php echo htmlspecialchars($c['Name']); ?>
            </option>
          <?php endforeach; ?>
        </select>
        <input class="input" type="date" name="from" value="<?php echo htmlspecialchars($dateFrom); ?>">
        <input class="input" type="date" name="to"   value="<?php echo htmlspecialchars($dateTo); ?>">
        <button class="btn-accent ripple" type="submit">Apply</button>
      </form>
      <?php if(!empty($flash)): ?><div class="toast ok show" style="position:static;margin-top:10px"><?php echo htmlspecialchars($flash); ?></div><?php endif; ?>
    </section>

    <section class="card">
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th>#</th><th>Date</th><th>Time</th><th>Dur</th><th>Type</th><th>Theme/Category</th>
              <th>Client</th><th>Photographer</th><th>Amount</th><th>Pay</th><th>Status</th><th>Update</th>
            </tr>
          </thead>
          <tbody>
            <?php if(empty($rows)): ?>
              <tr><td colspan="12">No results.</td></tr>
            <?php else: foreach($rows as $b): ?>
              <tr>
                <td><?php echo (int)$b['BookingID']; ?></td>
                <td><?php echo htmlspecialchars($b['ShootDate']); ?></td>
                <td><?php echo htmlspecialchars(substr($b['StartTime'],0,5)); ?></td>
                <td><?php echo (int)$b['DurationMin']; ?>m</td>
                <td><?php echo htmlspecialchars($b['SessionType']); ?></td>
                <td>
                  <?php
                    if ($b['SessionType']==='Indoor') echo htmlspecialchars($b['ThemeName'] ?? '');
                    else echo htmlspecialchars(($b['OutdoorCategory'] ?? '').($b['Location']?' @ '.$b['Location']:''));
                  ?>
                </td>
                <td><?php echo htmlspecialchars($b['ClientName'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($b['PhotographerName'] ?? ''); ?></td>
                <td>LKR <?php echo number_format((float)$b['Amount'],2); ?></td>
                <td><span class="badge pay <?php echo strtolower($b['PaymentStatus']); ?>"><?php echo htmlspecialchars($b['PaymentStatus']); ?></span></td>
                <td><span class="badge status <?php echo strtolower($b['Status']); ?>"><?php echo htmlspecialchars($b['Status']); ?></span></td>
                <td>
                  <form method="post">
                    <input type="hidden" name="booking_id" value="<?php echo (int)$b['BookingID']; ?>">
                    <select name="new_status">
                      <?php foreach(['Pending','Confirmed','Completed','Cancelled','Declined'] as $st): ?>
                        <option <?php if($b['Status']===$st) echo 'selected'; ?>><?php echo $st; ?></option>
                      <?php endforeach; ?>
                    </select>
                    <button class="btn-mini ripple" type="submit">Save</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
</div>
</body>
</html>
