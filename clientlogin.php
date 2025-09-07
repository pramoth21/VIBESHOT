<?php
// --- PHP: handle login ---
session_start();

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // DB connect
    $conn = new mysqli('localhost', 'root', '', 'vibeshot_db');
    if ($conn->connect_error) {
        die('DB connection failed: ' . $conn->connect_error);
    }

    // inputs
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $err = 'Please enter your email and password.';
    } else {
        // prepared query (table: client)
        $stmt = $conn->prepare("SELECT ClientID, Name, Password FROM client WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($row = $res->fetch_assoc()) {
            if (password_verify($password, $row['Password'])) {
                // success
                session_regenerate_id(true);
                $_SESSION['client_id']    = (int)$row['ClientID'];
                $_SESSION['client_name']  = $row['Name'];
                $_SESSION['client_email'] = $email;

                header("Location: client/dashboard.php");
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
<title>Vibe‑Shot | Client Login</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Cinzel:wght@700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<link rel="stylesheet" href="client_login.css">
</head>
<body>
<div class="stage">
  <!-- ambient layers -->
  <div class="grain" aria-hidden="true"></div>
  <div class="orb a" aria-hidden="true"></div>
  <div class="orb b" aria-hidden="true"></div>

  <!-- cinematic bronze + dark wash -->
  <div class="overlay">
    <!-- top bar -->
    <div class="nav-links">
      <a class="back-link" href="logindash.html"><i class="fas fa-arrow-left"></i> Back to Home</a>
      <a class="admin-btn" href="adminlogin.php"><span>Admin Login</span> <i class="fas fa-user-shield"></i></a>
    </div>

    <!-- glass card -->
    <div class="card">
      <!-- Left: brand & pitch -->
      <div class="left">
        <div class="brand">
          <img src="logo1.png" alt="Vibe‑Shot Logo">
          <h1>VIBE‑SHOT</h1>
        </div>
        <div class="tag">CLIENT LOGIN</div>

        <p class="pitch">
          Welcome back! Sign in to manage <span class="high">bookings</span>, explore
          <span class="high">themes</span>, and chat with our studio team.
        </p>

        <ul class="bullets">
          <li>Real‑time booking & confirmations</li>
          <li>Themed indoor rooms & outdoor sessions</li>
          <li>Secure account & history</li>
        </ul>
      </div>

      <!-- Right: login form -->
      <div class="right">
        <form class="form" method="POST" autocomplete="on" novalidate>
          <h2>Sign In</h2>
          <div class="sub">Use your registered email & password</div>

          <div class="input">
            <label class="label" for="email">Email</label>
            <div class="field">
              <i class="fas fa-envelope"></i>
              <input type="email" id="email" name="email" placeholder="you@example.com" required />
            </div>
          </div>

          <div class="input">
            <label class="label" for="password">Password</label>
            <div class="field">
              <i class="fas fa-lock"></i>
              <input type="password" id="password" name="password" placeholder="••••••••" required />
              <button type="button" class="toggle" id="togglePw" title="Show/Hide" aria-label="Toggle password">👁️</button>
            </div>
          </div>

          <div class="actions">
            <a class="link" href="client_register.php">Register here</a>
            <button class="btn" type="submit">LOGIN</button>
          </div>

          <div class="note">By continuing you agree to our Terms & Privacy.</div>
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
if(toggle){
  toggle.addEventListener('click', ()=>{ pw.type = pw.type === 'password' ? 'text' : 'password'; });
}
</script>
</body>
</html>
