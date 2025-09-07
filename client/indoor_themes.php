<?php
// /client/indoor_themes.php
session_start();
if (!isset($_SESSION['client_id'])) { header("Location: ../client_login.php"); exit; }

$conn = new mysqli('localhost','root','','vibeshot_db');
if ($conn->connect_error) { die('DB failed: '.$conn->connect_error); }

/** Where theme covers live (from the client folder’s POV) */
$CLIENT_THEME_BASE = '../admin/uploads/theme_covers/';
$PLACEHOLDER       = '../images/placeholder.png';

/** Robust resolver for cover images */
function theme_cover_url($raw, $clientBase, $placeholder){
  $raw = trim((string)$raw);
  if ($raw === '') return $placeholder;

  // already absolute url or root-absolute or contains ../
  if (preg_match('#^(https?://|/|\.\./)#i', $raw)) return $raw;

  // admin-relative like "uploads/theme_covers/xyz.jpg"
  if (preg_match('#^uploads/#i', $raw)) return '../admin/'.$raw;

  // plain filename like "xyz.jpg"
  return $clientBase.$raw;
}

$themes = [];
$stmt = $conn->prepare("SELECT ThemeID, Name, Description, Price, DefaultDurationMin, CoverImage 
                        FROM Theme WHERE Type='Indoor' AND Active=1 ORDER BY ThemeID DESC");
$stmt->execute();
$res = $stmt->get_result();
while($row = $res->fetch_assoc()){
  $row['CoverUrl'] = theme_cover_url($row['CoverImage'] ?? '', $CLIENT_THEME_BASE, $PLACEHOLDER);
  $themes[] = $row;
}
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Indoor Themes | Vibe-Shot</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;800&family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
<meta name="theme-color" content="#8B5E3C">
<link href="indoor_themes.css" rel="stylesheet">
</head>
<body>

<header class="topbar">
  <div class="brand">
    <img src="../logo1.png" alt="Vibe-Shot logo">
    <strong>VIBE-SHOT</strong>
  </div>
  <nav class="nav">
    <a class="link" href="dashboard.php">← Dashboard</a>
    <a class="link" href="my_bookings.php">My Bookings</a>
  </nav>
</header>

<section class="hero">
  <div class="wrap hero-inner">
    <div class="hero-copy">
      <h1 class="hero-title">Indoor Themes</h1>
      <p class="hero-sub">Hand-crafted sets with soft light, warm tones, and timeless props. Pick a theme, then choose your photographer and slot—no pressure, just good vibes.</p>
      <ul class="hero-badges">
        <li><span class="badge">Curated Sets</span></li>
        <li><span class="badge">Premium Lighting</span></li>
        <li><span class="badge">Color-graded</span></li>
      </ul>
    </div>

    <div class="hero-polaroids">
      <figure class="polaroid p1">
        <img src="../client_images/theme2.jpg" alt="Vintage indoor set">
        <figcaption>Vintage Room</figcaption>
      </figure>
      <figure class="polaroid p2">
        <img src="../client_images/theme1.jpg" alt="Studio lights example">
        <figcaption>Studio Lights</figcaption>
      </figure>
    </div>
  </div>
  <svg class="divider" viewBox="0 0 1200 80" preserveAspectRatio="none" aria-hidden="true">
    <path d="M0,40 C200,10 400,70 600,40 C800,10 1000,70 1200,40" fill="none" stroke="rgba(139,94,60,.35)" stroke-width="2"/>
  </svg>
</section>

<main class="wrap">
  <section class="filters">
    <div class="f-group">
      <label for="searchBox">Search</label>
      <input id="searchBox" type="text" placeholder="Search themes (e.g., classic, retro, portrait)">
    </div>
    <div class="f-group">
      <label for="priceRange">Max Price (LKR): <strong id="priceVal">—</strong></label>
      <input id="priceRange" type="range" min="0" max="100000" step="1000" value="100000">
    </div>
    <div class="f-group">
      <label for="durRange">Min Duration (mins): <strong id="durVal">0</strong></label>
      <input id="durRange" type="range" min="0" max="240" step="15" value="0">
    </div>
    <div class="f-group">
      <label for="sortBy">Sort</label>
      <select id="sortBy">
        <option value="new">Newest</option>
        <option value="low">Price: Low → High</option>
        <option value="high">Price: High → Low</option>
        <option value="dur">Duration: Long → Short</option>
        <option value="az">Name: A → Z</option>
      </select>
    </div>
  </section>

  <section class="film">
    <div class="film-strip">
      <img src="../client_images/theme3.jpg" alt="">
      <img src="../client_images/them6.jpg" alt="">
      <img src="../client_images/theme5.jpg" alt="">
      <img src="../client_images/theme8.avif" alt="">
      <img src="../client_images/theme7.avif" alt="">
      <img src="../client_images/theme4.jpg" alt="">
    </div>
  </section>

  <section class="grid" id="themeGrid">
    <?php if(empty($themes)): ?>
      <p class="muted">No indoor themes yet.</p>
    <?php else: foreach($themes as $t): 
      $id   = (int)$t['ThemeID'];
      $nm   = $t['Name'] ?? '';
      $ds   = $t['Description'] ?? '';
      $pr   = (float)$t['Price'];
      $dur  = (int)$t['DefaultDurationMin'];
      $img  = $t['CoverUrl']; // <-- fixed
    ?>
      <article 
        class="card reveal" 
        data-id="<?php echo $id; ?>"
        data-name="<?php echo htmlspecialchars(mb_strtolower($nm)); ?>"
        data-desc="<?php echo htmlspecialchars(mb_strtolower($ds)); ?>"
        data-price="<?php echo $pr; ?>"
        data-duration="<?php echo $dur; ?>"
        aria-label="Theme card: <?php echo htmlspecialchars($nm); ?>"
      >
        <div class="card-media">
          <img src="<?php echo htmlspecialchars($img); ?>" alt="">
          <button class="peek" type="button" data-peek="<?php echo $id; ?>">Quick view</button>
        </div>
        <div class="body">
          <h3 class="t-name"><?php echo htmlspecialchars($nm); ?></h3>
          <p class="muted t-desc"><?php echo htmlspecialchars($ds); ?></p>
          <div class="meta">
            <span class="chip">⏱ <?php echo $dur ?: 60; ?> min</span>
            <span class="chip">LKR <?php echo number_format($pr,2); ?></span>
          </div>
          <div class="row">
            <a class="btn" href="theme_detail.php?themeId=<?php echo $id; ?>">Book</a>
          </div>
        </div>
      </article>
    <?php endforeach; endif; ?>
  </section>

  <p id="emptyState" class="muted empty" hidden>No themes match your filters. Try clearing the search or adjusting ranges.</p>

  <section class="testimonials">
    <h2 class="h2">Guests love our indoor look</h2>
    <div class="t-row">
      <article class="t-card reveal"><p>“Warm tones, elegant props—the Classic Portrait theme is stunning.”</p><span class="who">— Anjaleeka • Portrait</span></article>
      <article class="t-card reveal"><p>“Perfect lighting and quick setup. Our family photos look timeless.”</p><span class="who">— Yasindu • Family</span></article>
      <article class="t-card reveal"><p>“Loved the vintage room. Skin tones and grading are chef’s kiss.”</p><span class="who">— Athma • Solo</span></article>
    </div>
  </section>
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
<!-- Quick-view modal (no backend calls; reuses card info) -->
<div class="modal" id="peekModal" role="dialog" aria-modal="true" aria-labelledby="peekTitle" hidden>
  <div class="sheet" role="document">
    <button class="x" id="peekClose" type="button" aria-label="Close">✕</button>
    <div class="peek-body">
      <div class="peek-media"><img id="peekImg" alt=""></div>
      <div class="peek-text">
        <h3 id="peekTitle">Theme</h3>
        <p id="peekDesc" class="muted"></p>
        <div class="meta" id="peekMeta"></div>
        <a class="btn" id="peekView" href="#">View</a>
      </div>
    </div>
  </div>
</div>

<!-- Page JS (client-side filters, sort, animations, quick-view) -->
<script>
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

  // ---- filters & sort (client-side)
  const searchBox = $('#searchBox');
  const priceRange = $('#priceRange');
  const durRange = $('#durRange');
  const sortBy = $('#sortBy');
  const priceVal = $('#priceVal');
  const durVal = $('#durVal');
  const grid = $('#themeGrid');
  const emptyState = $('#emptyState');

  // set dynamic max price based on data
  const cards = $$('.card');
  const prices = cards.map(c => +c.dataset.price || 0);
  const maxPrice = prices.length ? Math.max(...prices) : 100000;
  priceRange.max = Math.ceil(maxPrice/1000)*1000;
  priceRange.value = priceRange.max;
  priceVal.textContent = parseInt(priceRange.value).toLocaleString('en-LK');

  durVal.textContent = durRange.value;

  function applyFilters(){
    const q = (searchBox.value || '').trim().toLowerCase();
    const pMax = +priceRange.value;
    const dMin = +durRange.value;

    let visible = 0;
    cards.forEach(c=>{
      const name = c.dataset.name || '';
      const desc = c.dataset.desc || '';
      const price = +c.dataset.price;
      const dur = +c.dataset.duration;

      const matchText = !q || name.includes(q) || desc.includes(q);
      const matchPrice = isNaN(price) ? true : (price <= pMax);
      const matchDur = isNaN(dur) ? true : (dur >= dMin);

      const ok = matchText && matchPrice && matchDur;
      c.style.display = ok ? '' : 'none';
      if(ok) visible++;
    });

    emptyState.hidden = visible !== 0;
  }

  function applySort(){
    const items = cards.slice().filter(c => c.style.display !== 'none');
    const by = sortBy.value;

    items.sort((a,b)=>{
      const pa = +a.dataset.price, pb = +b.dataset.price;
      const da = +a.dataset.duration, db = +b.dataset.duration;
      const na = (a.dataset.name || '').localeCompare(b.dataset.name || '');
      switch(by){
        case 'low': return pa - pb;
        case 'high': return pb - pa;
        case 'dur': return db - da; // long to short
        case 'az':  return na;
        default:    return (+b.dataset.id) - (+a.dataset.id); // newest (id desc)
      }
    });

    // re-append in new order
    items.forEach(el => grid.appendChild(el));
  }

  ['input','change'].forEach(evt=>{
    searchBox.addEventListener(evt, applyFilters);
    priceRange.addEventListener(evt, ()=>{ priceVal.textContent = parseInt(priceRange.value).toLocaleString('en-LK'); applyFilters(); });
    durRange.addEventListener(evt, ()=>{ durVal.textContent = durRange.value; applyFilters(); });
    sortBy.addEventListener(evt, applySort);
  });

  // initial run
  applyFilters(); applySort();

  // ---- quick-view modal (no server calls)
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
      const img = card.querySelector('img')?.src || '';
      const name = card.querySelector('.t-name')?.textContent || 'Theme';
      const desc = card.querySelector('.t-desc')?.textContent || '';
      const d = card.dataset.duration || '—';
      const p = (+card.dataset.price || 0).toLocaleString('en-LK', {minimumFractionDigits:2});

      peekImg.src = img;
      peekTitle.textContent = name;
      peekDesc.textContent = desc;
      peekMeta.innerHTML = `<span class="chip">⏱ ${d} min</span> <span class="chip">LKR ${p}</span>`;
      peekView.href = 'theme_detail.php?themeId='+id;

      openModal();
    });
  });
})();
</script>

</body>
</html>
