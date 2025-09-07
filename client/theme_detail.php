<?php
session_start();
if (!isset($_SESSION['client_id'])) { header("Location: ../client_login.php"); exit; }

$themeId = (int)($_GET['themeId'] ?? 0);
if ($themeId <= 0) { header("Location: indoor_themes.php"); exit; }

$CLIENT_THEME_BASE = '../admin/uploads/theme_covers/';
$PLACEHOLDER       = '../images/placeholder.png';

function theme_cover_url($raw, $clientBase, $placeholder){
  $raw = trim((string)$raw);
  if ($raw === '') return $placeholder;
  if (preg_match('#^(https?://|/|\.\./)#i', $raw)) return $raw;
  if (preg_match('#^uploads/#i', $raw)) return '../admin/'.$raw;
  return $clientBase.$raw;
}

$conn = new mysqli('localhost','root','','vibeshot_db');
if ($conn->connect_error) { die('DB failed: '.$conn->connect_error); }

$stmt = $conn->prepare("SELECT ThemeID, Name, Description, Price, DefaultDurationMin, CoverImage 
                        FROM Theme WHERE ThemeID=? AND Active=1");
$stmt->bind_param("i",$themeId);
$stmt->execute();
$theme = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if (!$theme) { header("Location: indoor_themes.php"); exit; }

$coverUrl = theme_cover_url($theme['CoverImage'] ?? '', $CLIENT_THEME_BASE, $PLACEHOLDER);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php echo htmlspecialchars($theme['Name']); ?> | Vibe-Shot</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="theme_detail.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;800&family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
</head>
<body>
<header class="bar">
  <a class="link" href="indoor_themes.php">← All Indoor Themes</a>
  <h1><?php echo htmlspecialchars($theme['Name']); ?></h1>
  <div></div>
</header>

<main class="wrap">
  <section class="hero">
    <div class="image">
      <img src="<?php echo htmlspecialchars($coverUrl); ?>" alt="Theme cover">
    </div>
    <div class="info">
      <h2 class="hero-title"><?php echo htmlspecialchars($theme['Name']); ?></h2>
      <p class="desc"><?php echo nl2br(htmlspecialchars($theme['Description'])); ?></p>
      <ul class="details">
        <li><strong>Price:</strong> LKR <?php echo number_format((float)$theme['Price'], 2); ?></li>
        <li><strong>Duration:</strong> <?php echo (int)$theme['DefaultDurationMin']; ?> minutes</li>
      </ul>
      <a class="btn" href="photographers.php?themeId=<?php echo (int)$theme['ThemeID']; ?>&sessionType=Indoor">Select Photographer</a>
    </div>
  </section>

  <section class="extras">
    <h3 class="h3">What to Expect</h3>
    <ul class="expectations">
      <li>✨ Styled sets and mood lighting</li>
      <li>🎞️ Color-graded, retouched photos</li>
      <li>📸 Friendly expert photographers</li>
      <li>🖼️ Studio props for every vibe</li>
    </ul>
  </section>
</main>

<footer class="footer">
  <div class="wrap">
    <p>© <?php echo date('Y'); ?> Vibe-Shot Studio. All rights reserved.</p>
  </div>
</footer>
</body>
</html>
