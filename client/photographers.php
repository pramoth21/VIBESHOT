<?php
// /client/photographers.php
session_start();
if (!isset($_SESSION['client_id'])) { header("Location: ../client_login.php"); exit; }

$themeId        = isset($_GET['themeId']) ? (int)$_GET['themeId'] : null;
$sessionType    = $_GET['sessionType'] ?? ($themeId ? 'Indoor' : 'Outdoor');
$outdoorCategory= $_GET['outdoorCategory'] ?? null;
$location       = $_GET['location'] ?? null;

// Base for profile pictures
$PROFILE_BASE   = '../admin/uploads/photographers/';
$PLACEHOLDER    = '../images/placeholder.png';

function encode_path_for_web($path) {
  $path = str_replace('\\','/', trim((string)$path));
  if ($path === '') return '';
  $parts = explode('/', $path);
  $parts = array_map('rawurlencode', $parts);
  return implode('/', $parts);
}

function build_avatar_url($raw, $PROFILE_BASE, $PLACEHOLDER) {
  $raw = trim((string)$raw);
  if ($raw === '') return $PLACEHOLDER;

  $p = str_replace('\\','/',$raw);
  if (preg_match('#^(https?://|/)#i', $p)) return $p;
  return $PROFILE_BASE . encode_path_for_web($p);
}

function build_gallery_url($pid, $raw) {
  $raw = trim((string)$raw);
  if ($raw === '') return '../images/placeholder.png';

  $p = str_replace('\\','/',$raw);
  if (preg_match('#^(https?://|/)#i', $p)) return $p;

  if (preg_match('#^(uploads\W?photographers)(/|$)#i', $p)) {
    return '../' . encode_path_for_web($p);
  }

  $base = "uploads photographers/$pid/gallery/" . $p;
  return '../' . encode_path_for_web($base);
}

$conn = new mysqli('localhost','root','','vibeshot_db');
if ($conn->connect_error) { die('DB failed: '.$conn->connect_error); }

$sql = "
SELECT p.PhotographerID, p.Name, p.ProfilePic, p.Bio,
       COALESCE(ROUND(AVG(r.Rating),1), 0) AS AvgRating,
       COUNT(r.ReviewID) AS RatingCount
FROM Photographer p
LEFT JOIN Review r ON r.PhotographerID = p.PhotographerID
GROUP BY p.PhotographerID, p.Name, p.ProfilePic, p.Bio
ORDER BY AvgRating DESC, RatingCount DESC, p.PhotographerID DESC";
$res = $conn->query($sql);
if (!$res) { die('SQL error: '.$conn->error); }

$photographers = [];
while($row = $res->fetch_assoc()){
  $pid = (int)$row['PhotographerID'];

  $thumbs = [];
  $g = $conn->prepare("SELECT ImagePath FROM PhotographerGallery WHERE PhotographerID=? ORDER BY GalleryID DESC LIMIT 2");
  $g->bind_param("i", $pid);
  $g->execute();
  $gr = $g->get_result();
  while($gi = $gr->fetch_assoc()){
    $thumbs[] = build_gallery_url($pid, $gi['ImagePath'] ?? '');
  }
  $g->close();

  $row['AvatarUrl'] = build_avatar_url($row['ProfilePic'] ?? '', $PROFILE_BASE, $PLACEHOLDER);
  $row['Thumbs']    = $thumbs;
  $photographers[]  = $row;
}
$conn->close();

$qs = [];
if ($themeId)        $qs['themeId'] = $themeId;
if ($sessionType)    $qs['sessionType'] = $sessionType;
if ($outdoorCategory)$qs['outdoorCategory'] = $outdoorCategory;
if ($location)       $qs['location'] = $location;
$baseNext = 'photographer_profile.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Choose Photographer | Vibe-Shot</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;800&family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
<meta name="theme-color" content="#8B5E3C">
<link href="photographers.css" rel="stylesheet">
</head>
<body>

<header class="topbar">
  <div class="brand">
    <img src="../logo1.png" alt="Vibe-Shot logo">
    <strong>VIBE-SHOT</strong>
  </div>
  <nav class="nav">
    <a class="link" href="<?php echo $themeId ? 'theme_detail.php?themeId='.$themeId : 'dashboard.php'; ?>">← Back</a>
    <a class="link" href="my_bookings.php">My Bookings</a>
  </nav>
</header>

<section class="hero">
  <div class="wrap hero-inner">
    <div class="hero-copy">
      <h1 class="hero-title">Our Photographers</h1>
      <p class="hero-sub">Talented artists who'll capture your moments with style. Each brings unique vision and expertise to your session.</p>
      <ul class="hero-badges">
        <li><span class="badge">Professional</span></li>
        <li><span class="badge">Creative</span></li>
        <li><span class="badge">Experienced</span></li>
      </ul>
    </div>

    <div class="hero-polaroids">
      <figure class="polaroid p1">
        <img src="../client_images/ph1.jpg" alt="Photographer">
        <figcaption>Portrait Session</figcaption>
      </figure>
      <figure class="polaroid p2">
        <img src="../client_images/ph2.jpg" alt="Photographer shooting">
        <figcaption>Best Photographers</figcaption>
      </figure>
    </div>
  </div>
  <svg class="divider" viewBox="0 0 1200 80" preserveAspectRatio="none" aria-hidden="true">
    <path d="M0,40 C200,10 400,70 600,40 C800,10 1000,70 1200,40" fill="none" stroke="rgba(139,94,60,.35)" stroke-width="2"/>
  </svg>
</section>

<main class="wrap">
  <?php if($sessionType==='Outdoor'): ?>
    <div class="pill">Outdoor: <strong><?php echo htmlspecialchars($outdoorCategory ?? ''); ?></strong><?php if($location) echo ' • '.htmlspecialchars($location); ?></div>
  <?php else: ?>
    <div class="pill">Indoor session</div>
  <?php endif; ?>

<div class="photographer-grid">
    <?php foreach($photographers as $p):
      $query = $qs; $query['id'] = (int)$p['PhotographerID'];
      $href = $baseNext.'?'.http_build_query($query);
    ?>
      <div class="photographer-card">
        <div class="profile-header">
          <div class="profile-pic">
            <img src="<?php echo htmlspecialchars($p['AvatarUrl']); ?>" alt="<?php echo htmlspecialchars($p['Name']); ?>">
          </div>
          <div class="profile-info">
            <h3><?php echo htmlspecialchars($p['Name']); ?></h3>
            <div class="rating">
              <span class="stars">★★★★★</span>
              <span class="score"><?php echo number_format((float)$p['AvgRating'],1); ?></span>
              <span class="reviews">(<?php echo (int)$p['RatingCount']; ?> reviews)</span>
            </div>
          </div>
        </div>
        
        <?php if(!empty($p['Bio'])): ?>
          <p class="bio"><?php echo htmlspecialchars($p['Bio']); ?></p>
        <?php endif; ?>
        
        <?php if (!empty($p['Thumbs'])): ?>
        <div class="gallery-preview">
          <?php foreach($p['Thumbs'] as $t): ?>
            <div class="gallery-item">
              <img src="<?php echo htmlspecialchars($t); ?>" alt="">
            </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <a class="select-btn" href="<?php echo $href; ?>">
          Select Photographer
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M5 12h14M12 5l7 7-7 7"/>
          </svg>
        </a>
      </div>
    <?php endforeach; ?>
  </div>

  <p id="emptyState" class="muted empty" hidden>No photographers available. Please try different filters.</p>
</main>

<footer class="footer">
  <div class="wrap footer-inner">
    <p class="foot-left">© <?php echo date('Y'); ?> Vibe-Shot Studio</p>
    <ul class="foot-links">
      <li><a href="dashboard.php">Dashboard</a></li>
      <li><a href="my_bookings.php">My Bookings</a></li>
    </ul>
  </div>
</footer>

<!-- Quick-view modal -->
<div class="modal" id="peekModal" role="dialog" aria-modal="true" aria-labelledby="peekTitle" hidden>
  <div class="sheet" role="document">
    <button class="x" id="peekClose" type="button" aria-label="Close">✕</button>
    <div class="peek-body">
      <div class="peek-media">
        <div class="profile-circle-lg">
          <img id="peekImg" alt="">
        </div>
      </div>
      <div class="peek-text">
        <h3 id="peekTitle">Photographer</h3>
        <p id="peekDesc" class="muted"></p>
        <div class="meta" id="peekMeta"></div>
        <a class="btn" id="peekView" href="#">View Profile</a>
      </div>
    </div>
  </div>
</div>


<script>
// ... (keep the exact same JavaScript as in your original file) ...
(function(){
  // ---- helpers
  const $ = s => document.querySelector(s);
  const $$ = s => Array.from(document.querySelectorAll(s));

  // ---- reveal on scroll
  const io = new IntersectionObserver((entries)=>{
    entries.forEach(e=>{
      if (e.isIntersecting){ e.target.classList.add('is-visible'); io.unobserve(e.target); }
    });
  }, {threshold:.12});
  $$('.reveal, .card').forEach(el=>{ el.classList.add('reveal'); io.observe(el); });

  // ---- tilt effect on cards
  $$('.card').forEach(card=>{
    card.addEventListener('mousemove', e=>{
      const r = card.getBoundingClientRect();
      const rx = ((e.clientY - r.top)/r.height - .5) * -3;
      const ry = ((e.clientX - r.left)/r.width - .5) *  3;
      card.style.transform = `translateY(-4px) rotateX(${rx}deg) rotateY(${ry}deg)`;
    });
    card.addEventListener('mouseleave', ()=> card.style.transform = '' );
  });

  // ---- ripple on buttons
  function addRipple(el){
    el.style.overflow = 'hidden';
    el.addEventListener('click', function(ev){
      const r = el.getBoundingClientRect();
      const span = document.createElement('span');
      const size = Math.max(r.width, r.height);
      span.style.position='absolute';
      span.style.width=span.style.height=size+'px';
      span.style.left=(ev.clientX - r.left - size/2)+'px';
      span.style.top =(ev.clientY - r.top  - size/2)+'px';
      span.style.borderRadius='50%';
      span.style.background='rgba(255,255,255,.35)';
      span.style.transform='scale(0)';
      span.style.transition='transform .5s ease, opacity .6s ease';
      el.appendChild(span);
      requestAnimationFrame(()=>{ span.style.transform='scale(1.6)'; span.style.opacity='0'; });
      setTimeout(()=> span.remove(), 600);
    }, {passive:true});
  }
  $$('.btn, .peek').forEach(addRipple);

  // ---- quick-view modal
  const modal = $('#peekModal');
  const peekClose = $('#peekClose');
  const peekImg = $('#peekImg');
  const peekTitle = $('#peekTitle');
  const peekDesc = $('#peekDesc');
  const peekMeta = $('#peekMeta');
  const peekView = $('#peekView');

  function openModal(){ modal.hidden = false; document.body.classList.add('no-scroll'); }
  function closeModal(){ modal.hidden = true; document.body.classList.remove('no-scroll'); }

  peekClose?.addEventListener('click', closeModal);
  modal?.addEventListener('click', e => { if (e.target === modal) closeModal(); });
  document.addEventListener('keydown', e => { if (e.key === 'Escape' && !modal.hidden) closeModal(); });

  $$('.peek').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const id = btn.dataset.peek;
      const card = btn.closest('.card');
      const img = card.querySelector('.card-media img')?.src || '';
      const name = card.querySelector('.t-name')?.textContent || 'Photographer';
      const desc = card.querySelector('.t-desc')?.textContent || '';
      const rating = card.querySelector('.chip')?.textContent || '';

      peekImg.src = img;
      peekTitle.textContent = name;
      peekDesc.textContent = desc;
      peekMeta.innerHTML = `<span class="chip">${rating}</span>`;
      peekView.href = 'photographer_profile.php?id='+id;

      openModal();
    });
  });
})();
</script>
</body>
</html>