<?php

session_start();
if (!isset($_SESSION['admin_id'])) { header("Location: adminlogin.php"); exit; }

function db(){ $c=new mysqli('localhost','root','','vibeshot_db'); if($c->connect_error) die('DB failed: '.$c->connect_error); return $c; }
$conn = db();

$conn->query("ALTER TABLE Client ADD COLUMN IF NOT EXISTS IsActive TINYINT(1) NOT NULL DEFAULT 1");

$flash='';
if (isset($_GET['toggle'])) {
  $id=(int)$_GET['toggle'];
  $conn->query("UPDATE Client SET IsActive=1-IsActive WHERE ClientID=".$id);
  header("Location: clients_manage.php"); exit;
}

$list=[];
$r=$conn->query("SELECT c.ClientID,c.Name,c.Email,c.Phone,c.Gender,c.RegisterDate,c.IsActive,
                        (SELECT COUNT(*) FROM Booking b WHERE b.ClientID=c.ClientID) AS Bookings
                 FROM Client c ORDER BY c.ClientID DESC");
while($row=$r->fetch_assoc()) $list[]=$row;
$r->free(); $conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Clients | Vibe-Shot Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Cinzel:wght@700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="admin-dashboard-modern.css"/>
<style>.badge.active{background:#eafaf0}.badge.inactive{background:#fdecef}</style>
</head>
<body>
<header class="topnav elevate">
  <div class="brand"><img src="../logo1.png" alt=""><div class="brand-text"><strong>VIBE-SHOT</strong><span>Admin</span></div></div>
  <div class="top-actions">
    <a class="link" href="admin_dashboard_modern.php">Dashboard</a>
    <a class="btn-accent ripple" href="admin_logout.php">Logout</a>
  </div>
</header>

<div class="subhead elevate"><div class="subhead-left"><h2>Clients</h2><p class="muted">View & toggle active state.</p></div></div>

<div class="layout" style="grid-template-columns:1fr;">
  <main class="main">
    <section class="card">
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Gender</th><th>Registered</th><th>Bookings</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>
            <?php if(empty($list)): ?>
              <tr><td colspan="9">No clients.</td></tr>
            <?php else: foreach($list as $c): ?>
              <tr>
                <td><?php echo (int)$c['ClientID']; ?></td>
                <td><?php echo htmlspecialchars($c['Name']); ?></td>
                <td><?php echo htmlspecialchars($c['Email']); ?></td>
                <td><?php echo htmlspecialchars($c['Phone']); ?></td>
                <td><?php echo htmlspecialchars($c['Gender']); ?></td>
                <td><?php echo htmlspecialchars($c['RegisterDate']); ?></td>
                <td><?php echo (int)$c['Bookings']; ?></td>
                <td><span class="badge <?php echo $c['IsActive']?'active':'inactive'; ?>"><?php echo $c['IsActive']?'Active':'Inactive'; ?></span></td>
                <td><a class="mini" href="?toggle=<?php echo (int)$c['ClientID']; ?>" onclick="return confirm('Toggle active?')"><?php echo $c['IsActive']?'Deactivate':'Activate'; ?></a></td>
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
