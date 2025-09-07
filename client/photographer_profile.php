<?php
session_start();
if (!isset($_SESSION['client_id'])) { header("Location: ../client_login.php"); exit; }

$pid           = (int)($_GET['id'] ?? 0);
$themeId       = isset($_GET['themeId']) ? (int)$_GET['themeId'] : null;
$sessionType   = $_GET['sessionType'] ?? ($themeId ? 'Indoor' : 'Outdoor');
$outdoorCategory = $_GET['outdoorCategory'] ?? null;
$location        = $_GET['location'] ?? null;
$clientId       = (int)$_SESSION['client_id'];

if ($pid <= 0) { header("Location: photographers.php"); exit; }

$PROFILE_BASE = '../admin/uploads/photographers/';
$PLACEHOLDER  = '../images/placeholder.png';

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

$stmt = $conn->prepare("SELECT PhotographerID, Name, Bio, ProfilePic FROM Photographer WHERE PhotographerID=?");
$stmt->bind_param("i",$pid);
$stmt->execute();
$prof = $stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$prof){ $conn->close(); header("Location: photographers.php"); exit; }

// Get average rating and review count
$avgR = 0; $rc = 0;
$check = $conn->query("SHOW TABLES LIKE 'Review'");
if ($check && $check->num_rows) {
  $r = $conn->prepare("SELECT ROUND(AVG(Rating),1) AS avgR, COUNT(*) AS c FROM Review WHERE PhotographerID=?");
  $r->bind_param("i", $pid);
  $r->execute();
  $res = $r->get_result()->fetch_assoc();
  $avgR = (float)($res['avgR'] ?? 0);
  $rc   = (int)($res['c'] ?? 0);
  $r->close();
}

// Check if client has completed bookings with this photographer to leave review
$canReview = false;
$completedBookings = [];
$bq = $conn->prepare("SELECT BookingID FROM Booking WHERE ClientID=? AND PhotographerID=? AND Status='Completed'");
$bq->bind_param("ii", $clientId, $pid);
$bq->execute();
$br = $bq->get_result();
while($booking = $br->fetch_assoc()) {
    $completedBookings[] = $booking['BookingID'];
}
$bq->close();

// Check if client has already reviewed each booking
$unreviewedBookings = [];
foreach($completedBookings as $bookingId) {
    $rq = $conn->prepare("SELECT ReviewID FROM Review WHERE BookingID=? AND ClientID=?");
    $rq->bind_param("ii", $bookingId, $clientId);
    $rq->execute();
    if($rq->get_result()->num_rows == 0) {
        $unreviewedBookings[] = $bookingId;
    }
    $rq->close();
}

$canReview = !empty($unreviewedBookings);

// Get gallery images
$gal = [];
$gq = $conn->prepare("SELECT ImagePath, Caption FROM PhotographerGallery WHERE PhotographerID=? ORDER BY GalleryID DESC");
$gq->bind_param("i",$pid);
$gq->execute();
$gr = $gq->get_result();
while($gi = $gr->fetch_assoc()){
  $gi['ImageUrl'] = build_gallery_url($pid, $gi['ImagePath'] ?? '');
  $gal[] = $gi;
}
$gq->close();

// Handle review submission
$reviewSubmitted = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $bookingId = (int)$_POST['booking_id'];
    $rating = (int)$_POST['rating'];
    $comment = $conn->real_escape_string($_POST['comment'] ?? '');
    
    // Validate booking belongs to this client and photographer
    if (in_array($bookingId, $unreviewedBookings)) {
        $insert = $conn->prepare("INSERT INTO Review (BookingID, ClientID, PhotographerID, Rating, Comment) VALUES (?, ?, ?, ?, ?)");
        $insert->bind_param("iiiis", $bookingId, $clientId, $pid, $rating, $comment);
        if ($insert->execute()) {
            $reviewSubmitted = true;
            // Refresh page to show updated reviews
            header("Location: ?id=$pid");
            exit;
        }
        $insert->close();
    }
}

$conn->close();

$qs = [];
if ($themeId)           $qs['themeId'] = $themeId;
$qs['sessionType'] = $sessionType;
if ($outdoorCategory)   $qs['outdoorCategory'] = $outdoorCategory;
if ($location)          $qs['location'] = $location;
$qs['pid'] = $pid;
$hrefNext = 'choose_slot.php?'.http_build_query($qs);
$avatar = build_avatar_url($prof['ProfilePic'] ?? '', $PROFILE_BASE, $PLACEHOLDER);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php echo htmlspecialchars($prof['Name']); ?> | Photographer</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="photographer_profile.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<header class="bar">
  <a class="link" href="javascript:history.back()">← Back</a>
  <h1><?php echo htmlspecialchars($prof['Name']); ?></h1>
  <div></div>
</header>

<main class="wrap">
  <section class="head">
    <img class="avatar" src="<?php echo htmlspecialchars($avatar); ?>" alt="">
    <div class="meta">
      <div class="rate">★ <?php echo number_format($avgR,1); ?> (<?php echo (int)$rc; ?> reviews)</div>
      <p class="bio"><?php echo nl2br(htmlspecialchars($prof['Bio'] ?? '')); ?></p>
      <div class="actions">
        <a class="btn" href="<?php echo $hrefNext; ?>">Choose & Continue</a>
        <?php if ($canReview): ?>
          <button class="btn btn-review" onclick="document.getElementById('reviewModal').style.display='flex'">Add Review</button>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <?php if (!empty($gal)): ?>
  <h3>Gallery</h3>
  <div class="gallery-masonry">
    <?php foreach($gal as $gi): ?>
      <div class="gallery-item">
        <img src="<?php echo htmlspecialchars($gi['ImageUrl']); ?>" alt="">
        <?php if (!empty($gi['Caption'])): ?>
          <div class="caption"><?php echo htmlspecialchars($gi['Caption']); ?></div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</main>

<!-- Review Modal -->
<div id="reviewModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="document.getElementById('reviewModal').style.display='none'">&times;</span>
    <h2>Add Your Review</h2>
    <form method="POST" action="">
      <div class="form-group">
        <label>Booking</label>
        <select name="booking_id" required>
          <?php foreach($unreviewedBookings as $bookingId): ?>
            <option value="<?php echo $bookingId; ?>">Booking #<?php echo $bookingId; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      
      <div class="form-group">
        <label>Rating</label>
        <div class="rating-stars">
          <?php for($i = 1; $i <= 5; $i++): ?>
            <i class="fa-star far" data-rating="<?php echo $i; ?>" onclick="setRating(this)"></i>
          <?php endfor; ?>
          <input type="hidden" name="rating" id="ratingValue" required>
        </div>
      </div>
      
      <div class="form-group">
        <label>Comment</label>
        <textarea name="comment" rows="4" placeholder="Share your experience..."></textarea>
      </div>
      
      <button type="submit" name="submit_review" class="btn">Submit Review</button>
    </form>
  </div>
</div>

<script>
// Rating stars functionality
function setRating(star) {
  const rating = parseInt(star.getAttribute('data-rating'));
  document.getElementById('ratingValue').value = rating;
  
  const stars = document.querySelectorAll('.rating-stars .fa-star');
  stars.forEach((s, index) => {
    if (index < rating) {
      s.classList.add('fas');
      s.classList.remove('far');
    } else {
      s.classList.add('far');
      s.classList.remove('fas');
    }
  });
}

// Close modal when clicking outside
window.onclick = function(event) {
  const modal = document.getElementById('reviewModal');
  if (event.target == modal) {
    modal.style.display = "none";
  }
}
</script>
</body>
</html>