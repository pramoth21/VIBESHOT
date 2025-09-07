<?php
// /photographer/dashboard.php
session_start();
if (!isset($_SESSION['photographer_id'])) {
    header("Location: ../photographer_login.php");
    exit;
}

$pid = (int)$_SESSION['photographer_id'];
$pname = $_SESSION['photographer_name'] ?? 'Photographer';

$conn = new mysqli('localhost', 'root', '', 'vibeshot_db');
if ($conn->connect_error) {
    die('DB connection failed: ' . $conn->connect_error);
}

// Get today's bookings count
$today = 0;
$stmt = $conn->prepare("SELECT COUNT(*) FROM Booking WHERE PhotographerID=? AND ShootDate=CURDATE()");
$stmt->bind_param("i", $pid);
$stmt->execute();
$stmt->bind_result($today);
$stmt->fetch();
$stmt->close();

// Get this week's bookings count
$week = 0;
$stmt = $conn->prepare("SELECT COUNT(*) FROM Booking WHERE PhotographerID=? AND YEARWEEK(ShootDate,1)=YEARWEEK(CURDATE(),1)");
$stmt->bind_param("i", $pid);
$stmt->execute();
$stmt->bind_result($week);
$stmt->fetch();
$stmt->close();

// Get pending bookings count
$pending = 0;
$stmt = $conn->prepare("SELECT COUNT(*) FROM Booking WHERE PhotographerID=? AND Status='Pending'");
$stmt->bind_param("i", $pid);
$stmt->execute();
$stmt->bind_result($pending);
$stmt->fetch();
$stmt->close();

// Get total earnings (sum of paid bookings)
$earnings = 0;
$stmt = $conn->prepare("SELECT COALESCE(SUM(Amount), 0) FROM Booking WHERE PhotographerID=? AND PaymentStatus='Paid'");
$stmt->bind_param("i", $pid);
$stmt->execute();
$stmt->bind_result($earnings);
$stmt->fetch();
$stmt->close();

// Get upcoming bookings (next 10)
$upcoming = [];
$stmt = $conn->prepare("SELECT 
    b.BookingID, 
    b.SessionType, 
    b.ShootDate, 
    b.StartTime, 
    b.DurationMin, 
    b.Status, 
    b.Amount,
    b.PaymentStatus,
    c.Name AS ClientName,
    t.Name AS ThemeName,
    b.OutdoorCategory,
    b.Location
FROM Booking b
LEFT JOIN Client c ON c.ClientID = b.ClientID
LEFT JOIN Theme t ON t.ThemeID = b.ThemeID
WHERE b.PhotographerID = ? 
  AND b.ShootDate >= CURDATE() 
  AND b.Status IN ('Pending', 'Confirmed')
ORDER BY b.ShootDate, b.StartTime
LIMIT 10");
$stmt->bind_param("i", $pid);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $upcoming[] = $row;
}
$stmt->close();

$conn->close();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Photographer Dashboard | Vibe-Shot</title>
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
    <a class="link active" href="dashboard.php"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
    <a class="link" href="slots_manage.php"><i class="fa-regular fa-calendar-days"></i> Slots</a>
    <a class="link" href="gallery_manage.php"><i class="fa-solid fa-images"></i> Gallery</a>
    <a class="link" href="bookings.php"><i class="fa-regular fa-calendar-check"></i> Bookings</a>
    <span class="chip"><i class="fa-solid fa-camera-retro"></i> <?php echo htmlspecialchars($pname); ?></span>
    <a class="btn-accent ripple" href="../photographer_logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
  </nav>
</header>

<div class="layout">
  <main class="main">
    <section class="subhead elevate">
      <div class="subhead-left">
        <h2>Dashboard</h2>
        <p class="muted">Overview of your photography sessions</p>
      </div>
      <div class="subhead-cards">
        <div class="pill stat"><i class="fa-solid fa-sun"></i> <span><?php echo $today; ?></span> Today</div>
        <div class="pill stat"><i class="fa-solid fa-calendar-week"></i> <span><?php echo $week; ?></span> This Week</div>
        <div class="pill stat"><i class="fa-regular fa-clock"></i> <span><?php echo $pending; ?></span> Pending</div>
        <div class="pill stat"><i class="fa-solid fa-sack-dollar"></i> LKR <?php echo number_format($earnings, 2); ?></div>
      </div>
    </section>

    <!-- Upcoming Sessions -->
    <section class="card reveal">
      <div class="card-head">
        <h3>Upcoming Sessions</h3>
        <a class="mini" href="bookings.php">View all <i class="fa-solid fa-arrow-right"></i></a>
      </div>
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
            </tr>
          </thead>
          <tbody>
            <?php if (empty($upcoming)): ?>
              <tr><td colspan="10" class="text-center">No upcoming sessions scheduled</td></tr>
            <?php else: ?>
              <?php foreach ($upcoming as $booking): ?>
                <tr>
                  <td><?php echo $booking['BookingID']; ?></td>
                  <td><?php echo htmlspecialchars($booking['ShootDate']); ?></td>
                  <td><?php echo substr($booking['StartTime'], 0, 5); ?></td>
                  <td><?php echo $booking['DurationMin']; ?> min</td>
                  <td><?php echo htmlspecialchars($booking['SessionType']); ?></td>
                  <td>
                    <?php if ($booking['SessionType'] === 'Indoor'): ?>
                      <?php echo htmlspecialchars($booking['ThemeName'] ?? 'N/A'); ?>
                    <?php else: ?>
                      <?php echo htmlspecialchars($booking['OutdoorCategory'] ?? 'Outdoor'); ?>
                      <?php if (!empty($booking['Location'])): ?>
                        @ <?php echo htmlspecialchars($booking['Location']); ?>
                      <?php endif; ?>
                    <?php endif; ?>
                  </td>
                  <td><?php echo htmlspecialchars($booking['ClientName']); ?></td>
                  <td>LKR <?php echo number_format($booking['Amount'], 2); ?></td>
                  <td>
                    <span class="badge pay <?php echo strtolower($booking['PaymentStatus']); ?>">
                      <?php echo htmlspecialchars($booking['PaymentStatus']); ?>
                    </span>
                  </td>
                  <td>
                    <span class="badge status <?php echo strtolower($booking['Status']); ?>">
                      <?php echo htmlspecialchars($booking['Status']); ?>
                    </span>
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
</script>
</body>
</html>