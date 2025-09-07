<?php
session_start();
if (!isset($_SESSION['client_id'])) { header("Location: ../client_login.php"); exit; }

$clientId = (int)$_SESSION['client_id'];
$clientName = $_SESSION['client_name'] ?? 'Client';
$pid = (int)($_GET['pid'] ?? 0);
$themeId = isset($_GET['themeId']) ? (int)$_GET['themeId'] : null;
$sessionType = $_GET['sessionType'] ?? ($themeId ? 'Indoor' : 'Outdoor');
$outdoorCategory = $_GET['outdoorCategory'] ?? null;
$location = $_GET['location'] ?? null;

if ($pid <= 0) { header("Location: photographers.php"); exit; }

// --- DB: get photographer name & default duration (theme if indoor) ---
$conn = new mysqli('localhost','root','','vibeshot_db');
if ($conn->connect_error) { die('DB connection failed: '.$conn->connect_error); }

$photographer = ['Name'=>''];
$stmt = $conn->prepare("SELECT Name FROM Photographer WHERE PhotographerID=?");
$stmt->bind_param("i", $pid);
$stmt->execute();
$photographer = $stmt->get_result()->fetch_assoc() ?: $photographer;
$stmt->close();

$defaultDuration = 60;
if ($sessionType === 'Indoor' && $themeId) {
  $s = $conn->prepare("SELECT Name, DefaultDurationMin FROM Theme WHERE ThemeID=?");
  $s->bind_param("i", $themeId);
  $s->execute();
  if ($tt = $s->get_result()->fetch_assoc()) {
    $defaultDuration = (int)$tt['DefaultDurationMin'];
    $themeName = $tt['Name'];
  }
  $s->close();
}
$conn->close();

// Persist context for redirect to summary
$persist = ['pid'=>$pid, 'sessionType'=>$sessionType];
if ($themeId)         $persist['themeId'] = $themeId;
if ($outdoorCategory) $persist['outdoorCategory'] = $outdoorCategory;
if ($location)        $persist['location'] = $location;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Choose Slot | Vibe-Shot</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;800&family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
  <link href="choose_slot.css" rel="stylesheet">
</head>
<body>
<header class="topbar">
  <div class="brand">
    <img src="../logo1.png" alt="Vibe-Shot logo">
    <strong>VIBE-SHOT</strong>
  </div>
  <nav class="nav">
    <span class="chip">Hi, <?php echo htmlspecialchars($clientName); ?></span>
    <a href="../client/clientlogout.php" class="btn">Logout</a>
  </nav>
</header>

<main class="wrap">
  <section class="intro">
    <h2 class="h2">Choose Your Time Slot</h2>
    <p class="sub">Select an available time or suggest your preferred start time</p>
  </section>

  <div class="card reveal">
    <div class="photographer-info">
      <div class="photographer-name">
        <span class="label">Photographer</span>
        <h3><?php echo htmlspecialchars($photographer['Name']); ?></h3>
      </div>
      <div class="session-details">
        <div class="detail">
          <span class="label">Session Type</span>
          <strong><?php echo htmlspecialchars($sessionType); ?></strong>
        </div>
        <?php if($sessionType === 'Indoor' && !empty($themeName)): ?>
        <div class="detail">
          <span class="label">Theme</span>
          <strong><?php echo htmlspecialchars($themeName); ?></strong>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="booking-form">
      <div class="form-grid">
        <div class="form-group">
          <label for="shootDate">Shoot Date</label>
          <input type="date" id="shootDate" min="<?php echo date('Y-m-d'); ?>">
          <small class="hint">Select your preferred date</small>
        </div>

        <div class="form-group">
          <label for="intervals">Available Intervals</label>
          <select id="intervals">
            <option value="" selected disabled>Select date first</option>
          </select>
          <small class="hint" id="slotHint" hidden>
            No slots for this date. You can enter a time below.
          </small>
        </div>

        <div class="form-group">
          <label for="duration">Duration (minutes)</label>
          <input type="number" id="duration" value="<?php echo (int)$defaultDuration; ?>" min="15" step="15">
          <small class="hint">Minimum 15 minutes, in 15-min increments</small>
        </div>

        <div class="form-divider">
          <span>or</span>
        </div>

        <div class="form-group span-2">
          <label for="manualStart">Enter Start Time</label>
          <input type="time" id="manualStart" step="900" placeholder="HH:MM">
          <small class="hint">Use this if no slots are available (e.g. 09:00)</small>
        </div>
      </div>

      <div class="form-actions">
        <button class="btn cta" id="goNext">
          <span class="btn-text">Continue to Payment</span>
          <svg class="btn-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M5 12h14M12 5l7 7-7 7"></path>
          </svg>
        </button>
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
// ---- load available intervals via API when date changes ----
const pid    = <?php echo (int)$pid; ?>;
const dateEl = document.getElementById('shootDate');
const list   = document.getElementById('intervals');
const hint   = document.getElementById('slotHint');

async function loadSlots(){
  if(!dateEl.value){ return; }
  list.innerHTML = '<option>Loading…</option>';
  hint.hidden = true;
  try{
    const url = `../api/photographer_slots.php?id=${pid}&date=${encodeURIComponent(dateEl.value)}`;
    const res = await fetch(url);
    const data = await res.json();
    list.innerHTML = '';
    if(!Array.isArray(data) || data.length===0){
      list.innerHTML = '<option disabled>No slots found</option>';
      hint.hidden = false;
      return;
    }
    const def = document.createElement('option');
    def.textContent='Select an interval'; def.disabled=true; def.selected=true;
    list.appendChild(def);
    data.forEach(s=>{
      const opt = document.createElement('option');
      opt.value = JSON.stringify(s); // {start,end}
      opt.textContent = `${s.start} → ${s.end}`;
      list.appendChild(opt);
    });
  }catch(e){
    list.innerHTML = '<option disabled>Error loading</option>';
    hint.hidden = false;
  }
}

dateEl.addEventListener('change', loadSlots);

// set default date to today and trigger load
dateEl.value = new Date().toISOString().slice(0,10);
loadSlots();

// ---- continue: use chosen interval OR manual time ----
document.getElementById('goNext').addEventListener('click', ()=>{
  const d   = dateEl.value;
  const dur = Number(document.getElementById('duration').value);
  const manual = (document.getElementById('manualStart').value || '').trim();

  if(!d){ alert('Please select a date'); return; }
  if(dur<=0 || dur > 480){ alert('Duration must be between 15-480 minutes'); return; }

  let start = '';
  if (list.value) {
    try { start = JSON.parse(list.value).start; } catch(e){}
  }
  if (!start) { start = manual; }

  if(!start){
    alert('Please choose an available interval OR enter a start time.');
    return;
  }

  // pack context from PHP ($persist) + current selections
  const params = new URLSearchParams({
    <?php foreach($persist as $k=>$v){ echo "'$k':'".rawurlencode($v)."',"; } ?>
    shootDate: d,
    startTime: start,
    durationMin: dur
  });
  window.location.href = 'booking_summary.php?' + params.toString();
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

// Button ripple effect
(function(){
  const btn = document.getElementById('goNext');
  btn.style.overflow = 'hidden';
  btn.addEventListener('click', function(ev){
    const r = btn.getBoundingClientRect();
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
    btn.appendChild(span);
    requestAnimationFrame(()=>{ 
      span.style.transform = 'scale(1.6)'; 
      span.style.opacity='0'; 
    });
    setTimeout(()=> span.remove(), 600);
  }, {passive:true});
})();
</script>
</body>
</html>