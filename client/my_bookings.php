<?php
session_start();
if (!isset($_SESSION['client_id'])) { header("Location: ../client_login.php"); exit; }
$clientId = (int)$_SESSION['client_id'];
$clientName = $_SESSION['client_name'] ?? 'Client';

$conn = new mysqli('localhost','root','','vibeshot_db');
if ($conn->connect_error) { die('DB failed: '.$conn->connect_error); }

$sql = "
SELECT b.BookingID, b.SessionType, b.ShootDate, b.StartTime, b.DurationMin, 
       b.Status, b.Amount, b.PaymentStatus, b.PaymentRef,
       t.Name AS ThemeName,
       p.Name AS PhotographerName
FROM Booking b
LEFT JOIN Theme t ON b.ThemeID = t.ThemeID
INNER JOIN Photographer p ON b.PhotographerID = p.PhotographerID
WHERE b.ClientID=?
ORDER BY b.BookingID DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i",$clientId);
$stmt->execute();
$res = $stmt->get_result();
$list = [];
while($row = $res->fetch_assoc()) $list[] = $row;
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Bookings | Vibe-Shot</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;800&family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
  <link href="my_bookings.css" rel="stylesheet">
</head>
<body>
<header class="topbar">
  <div class="brand">
    <img src="../logo1.png" alt="Vibe-Shot logo">
    <strong>VIBE-SHOT</strong>
  </div>
  <nav class="nav">
    <a href="dashboard.php" class="link">Dashboard</a>
    <span class="chip">Hi, <?php echo htmlspecialchars($clientName); ?></span>
    <a href="../clientlogout.php" class="btn">Logout</a>
  </nav>
</header>

<main class="wrap">
  <section class="intro">
    <h2 class="h2">My Bookings</h2>
    <p class="sub">Your scheduled and past photography sessions with Vibe-Shot</p>
  </section>

  <div class="table-container reveal">
    <div class="table-scroll">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Type</th>
            <th>Theme</th>
            <th>Photographer</th>
            <th>Date</th>
            <th>Time</th>
            <th>Duration</th>
            <th>Status</th>
            <th>Payment</th>
            <th>Amount</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if(empty($list)): ?>
            <tr class="no-bookings">
              <td colspan="11">
                <div class="empty-state">
                  <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                  </svg>
                  <p>No bookings yet</p>
                  <a href="dashboard.php" class="btn ghost">Book a session</a>
                </div>
              </td>
            </tr>
          <?php else: foreach($list as $b): ?>
            <tr class="reveal">
              <td><?php echo (int)$b['BookingID']; ?></td>
              <td><?php echo htmlspecialchars($b['SessionType']); ?></td>
              <td><?php echo htmlspecialchars($b['ThemeName'] ?? '-'); ?></td>
              <td><?php echo htmlspecialchars($b['PhotographerName']); ?></td>
              <td><?php echo htmlspecialchars($b['ShootDate']); ?></td>
              <td><?php echo htmlspecialchars(substr($b['StartTime'],0,5)); ?></td>
              <td><?php echo (int)$b['DurationMin']; ?> min</td>
              <td><span class="badge <?php echo strtolower($b['Status']); ?>"><?php echo htmlspecialchars($b['Status']); ?></span></td>
              <td><span class="badge pay <?php echo strtolower($b['PaymentStatus']); ?>"><?php echo htmlspecialchars($b['PaymentStatus']); ?></span></td>
              <td>LKR <?php echo number_format((float)$b['Amount'],2); ?></td>
              <td class="acts">
                <a class="mini btn ghost" href="chat.php?bookingId=<?php echo (int)$b['BookingID']; ?>">Message</a>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>

<footer class="footer">
  <div class="wrap footer-inner">
    <p class="foot-left">© <?php echo date('Y'); ?> Vibe-Shot Studio. All rights reserved.</p>
  </div>
</footer>

<script>
// Scroll reveal animation
(function(){
  const revealEls = document.querySelectorAll('.reveal');
  const io = new IntersectionObserver((entries)=>{
    entries.forEach(e=>{
      if(e.isIntersecting){ 
        e.target.classList.add('is-visible'); 
        io.unobserve(e.target); 
      }
    });
  }, {threshold: 0.1});
  
  revealEls.forEach(el=>{ 
    el.classList.add('reveal'); 
    io.observe(el); 
  });
})();

// Button ripple effect
(function(){
  const els = document.querySelectorAll('.btn, .cta, .mini');
  els.forEach(el=>{
    el.style.overflow = 'hidden';
    el.addEventListener('click', function(ev){
      if(ev.target.tagName === 'A' && ev.target.getAttribute('href') === '#') {
        ev.preventDefault();
      }
      const r = el.getBoundingClientRect();
      const span = document.createElement('span');
      const size = Math.max(r.width, r.height);
      span.style.position = 'absolute';
      span.style.width = span.style.height = size + 'px';
      span.style.left = (ev.clientX - r.left - size/2) + 'px';
      span.style.top  = (ev.clientY - r.top  - size/2) + 'px';
      span.style.borderRadius = '50%';
      span.style.background = 'rgba(255,255,255,.35)';
      span.style.transform = 'scale(0)';
      span.style.transition = 'transform .5s ease, opacity .6s ease';
      el.appendChild(span);
      requestAnimationFrame(()=>{ 
        span.style.transform = 'scale(1.6)'; 
        span.style.opacity='0'; 
      });
      setTimeout(()=> span.remove(), 600);
    }, {passive:true});
  });
})();
</script>
</body>
</html>