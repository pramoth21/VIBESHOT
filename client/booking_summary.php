<?php
// /client/booking_summary.php
session_start();
if (!isset($_SESSION['client_id'])) { header("Location: ../client_login.php"); exit; }

$clientId = (int)$_SESSION['client_id'];
$clientName = $_SESSION['client_name'] ?? 'Client';
$pid = (int)($_GET['pid'] ?? 0);
$sessionType = $_GET['sessionType'] ?? 'Indoor';
$themeId = isset($_GET['themeId']) ? (int)$_GET['themeId'] : null;
$outdoorCategory = $_GET['outdoorCategory'] ?? null;
$location = $_GET['location'] ?? null;
$shootDate = $_GET['shootDate'] ?? null;
$startTime = $_GET['startTime'] ?? null;
$durationMin = (int)($_GET['durationMin'] ?? 60);

if($pid <= 0 || !$shootDate || !$startTime){ header("Location: dashboard.php"); exit; }

$conn = new mysqli('localhost','root','','vibeshot_db');
if ($conn->connect_error) { die('DB failed: '.$conn->connect_error); }

// load prices
$amount = 0.00; $themeName = null;
if ($sessionType === 'Indoor' && $themeId){
  $s = $conn->prepare("SELECT Name, Price, DefaultDurationMin FROM Theme WHERE ThemeID=?");
  $s->bind_param("i", $themeId); $s->execute();
  if($tt = $s->get_result()->fetch_assoc()){
    $amount = (float)$tt['Price'];
    $themeName = $tt['Name'];
  }
  $s->close();
} else {
  // outdoor base amount
  $amount = 20000.00;
}

$photographer = ['Name'=>''];
$p = $conn->prepare("SELECT Name FROM Photographer WHERE PhotographerID=?");
$p->bind_param("i", $pid); $p->execute();
$photographer = $p->get_result()->fetch_assoc() ?: $photographer;
$p->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Booking Summary | Vibe-Shot</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;800&family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
  <link href="booking_summary.css" rel="stylesheet">
</head>
<body>
<header class="topbar">
  <div class="brand">
    <img src="../logo1.png" alt="Vibe-Shot logo">
    <strong>VIBE-SHOT</strong>
  </div>
  <nav class="nav">
    <span class="chip">Hi, <?php echo htmlspecialchars($clientName); ?></span>
    <a href="../clientlogout.php" class="btn">Logout</a>
  </nav>
</header>

<main class="wrap">
  <section class="intro">
    <h2 class="h2">Booking Summary</h2>
    <p class="sub">Review your session details before confirmation</p>
  </section>

  <div class="card reveal">
    <div class="summary">
      <h3 class="summary-title">Session Details</h3>
      <dl class="summary-grid">
        <div class="summary-item">
          <dt>Session Type</dt>
          <dd><?php echo htmlspecialchars($sessionType); ?></dd>
        </div>
        <?php if($sessionType === 'Indoor' && $themeName): ?>
        <div class="summary-item">
          <dt>Theme</dt>
          <dd><?php echo htmlspecialchars($themeName); ?></dd>
        </div>
        <?php else: ?>
        <div class="summary-item">
          <dt>Outdoor Category</dt>
          <dd><?php echo htmlspecialchars($outdoorCategory ?: '-'); ?></dd>
        </div>
        <div class="summary-item">
          <dt>Location</dt>
          <dd><?php echo htmlspecialchars($location ?: '-'); ?></dd>
        </div>
        <?php endif; ?>
        <div class="summary-item">
          <dt>Photographer</dt>
          <dd><?php echo htmlspecialchars($photographer['Name']); ?></dd>
        </div>
        <div class="summary-item">
          <dt>Date</dt>
          <dd><?php echo htmlspecialchars($shootDate); ?></dd>
        </div>
        <div class="summary-item">
          <dt>Start Time</dt>
          <dd><?php echo htmlspecialchars($startTime); ?></dd>
        </div>
        <div class="summary-item">
          <dt>Duration</dt>
          <dd><?php echo (int)$durationMin; ?> min</dd>
        </div>
      </dl>
    </div>

    <div class="payment-section">
      <h3 class="payment-title">Payment Details</h3>
      
      <div class="payment-methods">
        <div class="payment-method selected" data-method="card">
          <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
            <line x1="1" y1="10" x2="23" y2="10"></line>
          </svg>
          <span>Credit/Debit Card</span>
        </div>
        <div class="payment-method" data-method="paypal">
          <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14 9l5-5-5-5"></path>
            <path d="M20 4v5h-5"></path>
            <path d="M4 20l5-5H4v-5l5 5h5v-5l5 5v5h-5l-5-5v5z"></path>
          </svg>
          <span>PayPal</span>
        </div>
        <div class="payment-method" data-method="bank">
          <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="4" width="20" height="16" rx="2"></rect>
            <line x1="12" y1="10" x2="12" y2="16"></line>
            <line x1="8" y1="10" x2="8" y2="16"></line>
            <line x1="16" y1="10" x2="16" y2="16"></line>
          </svg>
          <span>Bank Transfer</span>
        </div>
      </div>

      <div class="payment-total">
        <div class="payment-line">
          <span>Subtotal</span>
          <strong>LKR <?php echo number_format($amount, 2); ?></strong>
        </div>
        <div class="payment-line small">
          <span>Taxes & Fees</span>
          <strong>LKR 0.00</strong>
        </div>
        <div class="payment-line total">
          <span>Total</span>
          <strong>LKR <?php echo number_format($amount, 2); ?></strong>
        </div>

        <button class="btn cta" id="confirmPay">
          <span class="btn-text">Confirm & Pay</span>
          <span class="btn-loading" hidden>
            <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 12a9 9 0 1 1-6.219-8.56"></path>
            </svg>
          </span>
        </button>
        <div id="msg" class="msg" hidden></div>
      </div>
    </div>
  </div>
</main>

<footer class="footer">
  <div class="wrap footer-inner">
    <p class="foot-left">© <?php echo date('Y'); ?> Vibe-Shot Studio. All rights reserved.</p>
  </div>
</footer>

<script>
// Payment method selection
document.querySelectorAll('.payment-method').forEach(method => {
  method.addEventListener('click', () => {
    document.querySelector('.payment-method.selected')?.classList.remove('selected');
    method.classList.add('selected');
  });
});

// Confirm payment button
document.getElementById('confirmPay').addEventListener('click', async () => {
  const btn = document.getElementById('confirmPay');
  const btnText = btn.querySelector('.btn-text');
  const btnLoading = btn.querySelector('.btn-loading');
  const msg = document.getElementById('msg');
  
  // Get selected payment method
  const paymentMethod = document.querySelector('.payment-method.selected').dataset.method;
  
  // Show loading state
  btn.disabled = true;
  btnText.hidden = true;
  btnLoading.hidden = false;
  msg.hidden = false;
  msg.textContent = `Processing ${paymentMethod} payment...`;
  msg.className = 'msg processing';

  const payload = {
    clientId: <?php echo (int)$clientId; ?>,
    photographerId: <?php echo (int)$pid; ?>,
    themeId: <?php echo $themeId ? (int)$themeId : 'null'; ?>,
    sessionType: "<?php echo htmlspecialchars($sessionType); ?>",
    outdoorCategory: "<?php echo htmlspecialchars($outdoorCategory ?? ''); ?>",
    location: "<?php echo htmlspecialchars($location ?? ''); ?>",
    shootDate: "<?php echo htmlspecialchars($shootDate); ?>",
    startTime: "<?php echo htmlspecialchars($startTime); ?>",
    durationMin: <?php echo (int)$durationMin; ?>,
    amount: <?php echo (float)$amount; ?>,
    paymentMethod: paymentMethod
  };

  try {
    const res = await fetch('../api/create_booking.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    
    const out = await res.json();
    
    if(out && out.ok) {
      msg.className = 'msg success';
      msg.textContent = 'Payment successful! Redirecting...';
      setTimeout(() => {
        window.location.href = 'payment_result.php?status=success&ref=' + 
          encodeURIComponent(out.paymentRef) + '&method=' + paymentMethod;
      }, 1500);
    } else {
      msg.className = 'msg error';
      msg.textContent = out?.error || 'Payment failed. Please try again.';
      btn.disabled = false;
      btnText.hidden = false;
      btnLoading.hidden = true;
    }
  } catch(e) {
    msg.className = 'msg error';
    msg.textContent = 'Network error. Please check your connection.';
    btn.disabled = false;
    btnText.hidden = false;
    btnLoading.hidden = true;
    console.error(e);
  }
});

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
</script>
</body>
</html>