<?php
// /photographer/bookings.php
session_start();
if (!isset($_SESSION['photographer_id'])) { 
    header("Location: ../photographer_login.php"); 
    exit; 
}
$pid = (int)$_SESSION['photographer_id'];
$pname = $_SESSION['photographer_name'] ?? 'Photographer';

$conn = new mysqli('localhost','root','','vibeshot_db');
if ($conn->connect_error) { die('DB failed: '.$conn->connect_error); }

$flash = [];

// Handle booking actions
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $bid = (int)$_POST['booking_id'];
    $action = $_POST['action'] ?? '';
    $ok = false; 
    $statusNow = '';
    
    $st = $conn->prepare("SELECT Status FROM Booking WHERE BookingID=? AND PhotographerID=?");
    $st->bind_param("ii",$bid,$pid); 
    $st->execute();
    $st->bind_result($statusNow); 
    if ($st->fetch()) $ok = true; 
    $st->close();

    if ($ok) {
        if ($action==='confirm' && $statusNow==='Pending') {
            $u = $conn->prepare("UPDATE Booking SET Status='Confirmed' WHERE BookingID=? AND PhotographerID=?");
            $u->bind_param("ii",$bid,$pid); 
            $u->execute(); 
            $u->close(); 
            $flash = ['type'=>'success', 'msg'=>'Booking confirmed'];
        } 
        elseif ($action==='decline' && in_array($statusNow,['Pending','Confirmed'])) {
            $u = $conn->prepare("UPDATE Booking SET Status='Declined' WHERE BookingID=? AND PhotographerID=?");
            $u->bind_param("ii",$bid,$pid); 
            $u->execute(); 
            $u->close(); 
            $flash = ['type'=>'success', 'msg'=>'Booking declined'];
        } 
        elseif ($action==='complete' && $statusNow==='Confirmed') {
            $u = $conn->prepare("UPDATE Booking SET Status='Completed' WHERE BookingID=? AND PhotographerID=?");
            $u->bind_param("ii",$bid,$pid); 
            $u->execute(); 
            $u->close(); 
            $flash = ['type'=>'success', 'msg'=>'Booking marked completed'];
        } 
        else {
            $flash = ['type'=>'error', 'msg'=>'Action not allowed for current status'];
        }
    }
}

// Get unread message counts for each booking
$unreadCounts = [];
$msgStmt = $conn->prepare("SELECT ThreadID, COUNT(*) as unread 
                          FROM ChatMessage 
                          WHERE ThreadID IN (
                              SELECT ThreadID FROM ChatThread WHERE PhotographerID=?
                          ) 
                          AND SenderType='Client' 
                          AND IsRead=0 
                          GROUP BY ThreadID");
$msgStmt->bind_param("i", $pid);
$msgStmt->execute();
$msgResult = $msgStmt->get_result();
while ($row = $msgResult->fetch_assoc()) {
    $unreadCounts[$row['ThreadID']] = $row['unread'];
}
$msgStmt->close();

// Get thread IDs for bookings
$threadIds = [];
$threadStmt = $conn->prepare("SELECT ThreadID, BookingID FROM ChatThread WHERE PhotographerID=?");
$threadStmt->bind_param("i", $pid);
$threadStmt->execute();
$threadResult = $threadStmt->get_result();
while ($row = $threadResult->fetch_assoc()) {
    $threadIds[$row['BookingID']] = $row['ThreadID'];
}
$threadStmt->close();

// Filter bookings
$filter = $_GET['status'] ?? 'All';
$allowed = ['All','Pending','Confirmed','Completed','Cancelled','Declined'];
if (!in_array($filter,$allowed)) $filter = 'All';

// Fetch bookings
$sql = "SELECT b.BookingID, b.SessionType, b.ShootDate, b.StartTime, b.DurationMin, b.Status, 
               b.Amount, b.PaymentStatus,
               c.Name AS ClientName,
               t.Name AS ThemeName, b.OutdoorCategory, b.Location
        FROM Booking b
        LEFT JOIN Client c ON c.ClientID=b.ClientID
        LEFT JOIN Theme t ON t.ThemeID=b.ThemeID
        WHERE b.PhotographerID=?";
if ($filter!=='All') $sql .= " AND b.Status='".$conn->real_escape_string($filter)."'";
$sql .= " ORDER BY b.ShootDate DESC, b.StartTime DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i",$pid);
$stmt->execute();
$res = $stmt->get_result();
$bookings = [];
while($r = $res->fetch_assoc()) {
    $r['unread'] = 0;
    if (isset($threadIds[$r['BookingID']])) {
        $threadId = $threadIds[$r['BookingID']];
        if (isset($unreadCounts[$threadId])) {
            $r['unread'] = $unreadCounts[$threadId];
        }
    }
    $bookings[] = $r;
}
$stmt->close();

$conn->close();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Bookings | Vibe-Shot</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Cinzel:wght@700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<link rel="stylesheet" href="dashboard.css">
</head>
<body>

<header class="topnav elevate">
  <div class="brand">
    <img src="../logo1.png" alt="Vibe-Shot">
    <div class="brand-text">
      <strong>VIBE-SHOT</strong>
      <span>Photographer</span>
    </div>
  </div>
  <nav class="nav">
    <a class="link" href="dashboard.php"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
    <a class="link" href="slots_manage.php"><i class="fa-regular fa-calendar-days"></i> Slots</a>
    <a class="link" href="gallery_manage.php"><i class="fa-solid fa-images"></i> Gallery</a>
    <a class="link active" href="bookings.php"><i class="fa-regular fa-calendar-check"></i> Bookings</a>
    <span class="chip"><i class="fa-solid fa-camera-retro"></i> <?php echo htmlspecialchars($pname); ?></span>
    <a class="btn-accent ripple" href="../photographer_logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
  </nav>
</header>

<div class="layout">
  <main class="main">
    <section class="subhead elevate">
      <div class="subhead-left">
        <h2>Bookings Management</h2>
        <p class="muted">View and manage your photography bookings</p>
      </div>
      <form method="get" class="filter-form">
        <div class="select-wrap">
          <select name="status" onchange="this.form.submit()" class="select">
            <?php foreach($allowed as $s): ?>
              <option value="<?php echo htmlspecialchars($s); ?>" <?php if($filter===$s) echo 'selected'; ?>>
                <?php echo htmlspecialchars($s); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <i class="fa-solid fa-chevron-down"></i>
        </div>
      </form>
    </section>

    <section class="card reveal">
      <div class="table-wrap">
        <table class="table compact">
          <thead>
            <tr>
              <th>#</th>
              <th>Date</th>
              <th>Time</th>
              <th>Duration</th>
              <th>Type</th>
              <th>Details</th>
              <th>Client</th>
              <th>Amount</th>
              <th>Payment</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if(empty($bookings)): ?>
              <tr>
                <td colspan="11" class="text-center">No bookings found</td>
              </tr>
            <?php else: ?>
              <?php foreach($bookings as $b): ?>
                <tr>
                  <td><?php echo (int)$b['BookingID']; ?></td>
                  <td><?php echo htmlspecialchars($b['ShootDate']); ?></td>
                  <td><?php echo htmlspecialchars(substr($b['StartTime'],0,5)); ?></td>
                  <td><?php echo (int)$b['DurationMin']; ?>m</td>
                  <td><?php echo htmlspecialchars($b['SessionType']); ?></td>
                  <td>
                    <?php if ($b['SessionType']==='Indoor'): ?>
                      <?php echo htmlspecialchars($b['ThemeName'] ?? 'N/A'); ?>
                    <?php else: ?>
                      <?php echo htmlspecialchars($b['OutdoorCategory'] ?? 'Outdoor'); ?>
                      <?php if (!empty($b['Location'])): ?>
                        @ <?php echo htmlspecialchars($b['Location']); ?>
                      <?php endif; ?>
                    <?php endif; ?>
                  </td>
                  <td><?php echo htmlspecialchars($b['ClientName'] ?? ''); ?></td>
                  <td>Rs. <?php echo number_format((float)$b['Amount'],2); ?></td>
                  <td>
                    <span class="badge pay <?php echo strtolower($b['PaymentStatus']); ?>">
                      <?php echo htmlspecialchars($b['PaymentStatus']); ?>
                    </span>
                  </td>
                  <td>
                    <span class="badge status <?php echo strtolower($b['Status']); ?>">
                      <?php echo htmlspecialchars($b['Status']); ?>
                    </span>
                  </td>
                  <td class="acts">
                    <div class="acts-inner">
                      <a href="chat.php?bookingId=<?php echo (int)$b['BookingID']; ?>" class="mini ripple message-btn">
                        <i class="fa-solid fa-message"></i>
                        <?php if ($b['unread'] > 0): ?>
                          <span class="unread-count"><?php echo $b['unread']; ?></span>
                        <?php endif; ?>
                      </a>
                      <form method="post" class="inline-form">
                        <input type="hidden" name="booking_id" value="<?php echo (int)$b['BookingID']; ?>">
                        <?php if ($b['Status']==='Pending'): ?>
                          <button type="submit" name="action" value="confirm" class="mini ok ripple">
                            <i class="fa-solid fa-check"></i>
                          </button>
                          <button type="submit" name="action" value="decline" class="mini danger ripple">
                            <i class="fa-solid fa-xmark"></i>
                          </button>
                        <?php elseif ($b['Status']==='Confirmed'): ?>
                          <button type="submit" name="action" value="complete" class="mini ok ripple">
                            <i class="fa-solid fa-check-double"></i>
                          </button>
                          <button type="submit" name="action" value="decline" class="mini danger ripple">
                            <i class="fa-solid fa-xmark"></i>
                          </button>
                        <?php else: ?>
                          <span class="muted">—</span>
                        <?php endif; ?>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
</div>

<?php if(!empty($flash)): ?>
  <div class="toast <?php echo $flash['type'] === 'success' ? 'ok' : 'error'; ?> show">
    <i class="fa-solid <?php echo $flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
    <?php echo htmlspecialchars($flash['msg']); ?>
  </div>
<?php endif; ?>

<script>
// Reveal animation
const observer = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.classList.add('in');
      observer.unobserve(entry.target);
    }
  });
}, { threshold: 0.15 });

document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

// Ripple effect
document.addEventListener('click', (e) => {
  const button = e.target.closest('.ripple');
  if (!button) return;
  
  const size = Math.max(button.offsetWidth, button.offsetHeight);
  const ripple = document.createElement('span');
  ripple.className = 'r';
  ripple.style.width = ripple.style.height = size + 'px';
  ripple.style.left = (e.offsetX - size / 2) + 'px';
  ripple.style.top = (e.offsetY - size / 2) + 'px';
  
  button.appendChild(ripple);
  setTimeout(() => ripple.remove(), 600);
});

// Auto-hide flash messages
setTimeout(() => {
  const flash = document.querySelector('.toast');
  if (flash) {
    flash.classList.remove('show');
    setTimeout(() => flash.remove(), 300);
  }
}, 5000);
</script>
</body>
</html>