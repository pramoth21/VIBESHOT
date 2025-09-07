<?php

session_start();

// If already logged in as photographer -> go to dashboard
if (isset($_SESSION['photographer_id'])) {
    header("Location: photographer/dashboard.php");
    exit;
}

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // DB connect
    $conn = new mysqli('localhost', 'root', '', 'vibeshot_db');
    if ($conn->connect_error) {
        die('DB connection failed: ' . $conn->connect_error);
    }

    // Inputs
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $err = 'Please enter your email and password.';
    } else {
        // prepared query (table: Photographer)
        $stmt = $conn->prepare("SELECT PhotographerID, Name, Password FROM Photographer WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($row = $res->fetch_assoc()) {
            if (password_verify($password, $row['Password'])) {
                // success
                session_regenerate_id(true);
                $_SESSION['photographer_id']    = (int)$row['PhotographerID'];
                $_SESSION['photographer_name']  = $row['Name'];
                $_SESSION['photographer_email'] = $email;

                header("Location: photographer/dashboard.php");
                exit;
            } else {
                $err = 'Incorrect email or password.';
            }
        } else {
            $err = 'Account not found.';
        }
        $stmt->close();
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width,initial-scale=1.0" />
<title>Vibe-Shot | Photographer Login</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Cinzel:wght@700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<link rel="stylesheet" href="photographer_login.css">
</head>
<body>
<div class="stage">
  <!-- background layers -->
  <div class="grain" aria-hidden="true"></div>
  <div class="orb a" aria-hidden="true"></div>
  <div class="orb b" aria-hidden="true"></div>

  <div class="overlay">
    <!-- top bar -->
    <div class="nav-links">
      <a class="back-link" href="home.html"><i class="fas fa-arrow-left"></i> Home</a>
      <span class="badge"><i class="fas fa-camera-retro"></i> Photographer Portal</span>
    </div>

    <!-- glass card -->
    <div class="card">
      <div class="left">
        <div class="brand">
          <img src="logo1.png" alt="Vibe-Shot Logo">
          <h1>VIBE-SHOT</h1>
        </div>
        <div class="tag">PHOTOGRAPHER LOGIN</div>
        <p class="pitch">Access your assignments, manage availability, and keep your profile fresh.</p>
        <ul class="mini-points">
          <li><i class="fa-regular fa-calendar-check"></i> Update availability</li>
          <li><i class="fa-solid fa-briefcase"></i> View assigned bookings</li>
          <li><i class="fa-regular fa-image"></i> Add portfolio photos</li>
        </ul>
      </div>

      <div class="right">
        <form class="form" method="POST" novalidate>
          <h2>Sign In</h2>
          <div class="sub">Use your photographer email & password</div>

          <div class="input">
            <label class="label" for="email">Email</label>
            <div class="field">
              <i class="fas fa-envelope"></i>
              <input type="email" id="email" name="email" placeholder="photographer@vibeshot.com" required />
            </div>
          </div>

          <div class="input">
            <label class="label" for="password">Password</label>
            <div class="field">
              <i class="fas fa-lock"></i>
              <input type="password" id="password" name="password" placeholder="••••••••" required />
              <button type="button" class="toggle" id="togglePw" aria-label="Toggle password">👁️</button>
            </div>
          </div>

          <div class="actions">
            <a class="link" href="adminlogin.php">Admin Login</a>
            <button class="btn" type="submit">LOGIN</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php if (!empty($err)): ?>
<div class="toast" id="toast"><?php echo htmlspecialchars($err); ?></div>
<script>
  const t=document.getElementById('toast'); t.classList.add('show');
  setTimeout(()=> t.classList.remove('show'), 4000);
</script>
<?php endif; ?>

<script>
// show/hide password
const toggle=document.getElementById('togglePw');
const pw=document.getElementById('password');
toggle?.addEventListener('click', ()=> pw.type = pw.type === 'password' ? 'text' : 'password');
</script>
</body>
</html>
