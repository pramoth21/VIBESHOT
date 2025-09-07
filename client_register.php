<?php
session_start();

$err = '';
$ok  = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
 
    $conn = new mysqli("localhost", "root", "", "vibeshot_db");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }


    $name   = trim($_POST['name'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $phone  = trim($_POST['phone'] ?? '');
    $pw     = $_POST['password'] ?? '';
    $cpw    = $_POST['confirm_password'] ?? '';


    $validGenders = ['Male','Female','Other'];
    if ($name === '' || $email === '' || $phone === '' || $pw === '' || $cpw === '' || $gender === '') {
        $err = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = 'Please enter a valid email address.';
    } elseif (!in_array($gender, $validGenders, true)) {
        $err = 'Please select a valid gender.';
    } elseif (!preg_match('/^\d{10}$/', $phone)) {
        $err = 'Phone must be 10 digits (numbers only).';
    } elseif ($pw !== $cpw) {
        $err = 'Passwords do not match.';
    } else {
        
        $check = $conn->prepare("SELECT 1 FROM client WHERE Email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $err = 'An account with this email already exists.';
        } else {
           
            $hash = password_hash($pw, PASSWORD_DEFAULT);
            $registerDate = date('Y-m-d');

            $stmt = $conn->prepare("
                INSERT INTO client (Name, Gender, Email, Password, Phone, RegisterDate)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("ssssss", $name, $gender, $email, $hash, $phone, $registerDate);

            if ($stmt->execute()) {
                $ok = 'Registration successful! Redirecting to login…';
            } else {
                $err = 'Error creating the account. Please try again.';
            }
            $stmt->close();
        }
        $check->close();
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width,initial-scale=1.0" />
<title>Vibe‑Shot | Client Register</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Cinzel:wght@700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<link rel="stylesheet" href="client_register.css">
</head>
<body>


<nav>
  <div class="nav-container">
    <img src="logo1.png" class="logo1" alt="Logo">
    <div class="menu">
      <ul>
        <li><a href="home.html">HOME</a></li>
        <li><a href="about.html">ABOUT US</a></li>
        <li><a href="blog.html">BLOG</a></li>
        <li><a href="contact.html">CONTACT</a></li>
      </ul>
    </div>
  </div>
  <div class="nav-buttons">
    <a href="logindash.html" class="login-btn">LOGIN</a>
  </div>
</nav>


<div class="stage">
  <div class="grain" aria-hidden="true"></div>
  <div class="orb a" aria-hidden="true"></div>
  <div class="orb b" aria-hidden="true"></div>

  <div class="overlay">
     <div class="card">
   
      <div class="left">
        <div class="brand">
          <img src="logo1.png" alt="Vibe‑Shot Logo">
          <h1>VIBE‑SHOT</h1>
        </div>
        <div class="tag">CREATE YOUR ACCOUNT</div>

        <p class="pitch">
          Join our community to book <span class="high">themed sessions</span>, manage
          <span class="high">appointments</span>, and chat with our studio team.
        </p>

        <ul class="bullets">
          <li>Browse indoor themes & outdoor packages</li>
          <li>Real‑time booking & confirmations</li>
          <li>Secure account & history</li>
        </ul>
      </div>

 
      <div class="right">
        <form class="form" method="POST" autocomplete="on" novalidate onsubmit="return validateForm();">
          <h2>Create Account</h2>
          <div class="sub">It’s completely free</div>

          <div class="input">
            <label class="label" for="name">Name</label>
            <div class="field">
              <i class="fas fa-user"></i>
              <input type="text" id="name" name="name" placeholder="Your full name" required />
            </div>
          </div>

          <div class="input">
            <label class="label" for="gender">Gender</label>
            <div class="field select">
              <i class="fas fa-venus-mars"></i>
              <select id="gender" name="gender" required>
                <option value="" disabled selected>Select gender</option>
                <option>Male</option>
                <option>Female</option>
                <option>Other</option>
              </select>
            </div>
          </div>

          <div class="input">
            <label class="label" for="email">Email</label>
            <div class="field">
              <i class="fas fa-envelope"></i>
              <input type="email" id="email" name="email" placeholder="you@example.com" required />
            </div>
          </div>

          <div class="input">
            <label class="label" for="phone">Phone (10 digits)</label>
            <div class="field">
              <i class="fas fa-phone"></i>
              <input type="tel" id="phone" name="phone" pattern="\d{10}" placeholder="07XXXXXXXX" required />
            </div>
          </div>

          <div class="input">
            <label class="label" for="password">Password</label>
            <div class="field">
              <i class="fas fa-lock"></i>
              <input type="password" id="password" name="password" placeholder="••••••••" minlength="6" required />
              <button type="button" class="toggle" id="togglePw" title="Show/Hide" aria-label="Toggle password">👁️</button>
            </div>
          </div>

          <div class="input">
            <label class="label" for="confirm_password">Confirm Password</label>
            <div class="field">
              <i class="fas fa-check-circle"></i>
              <input type="password" id="confirm_password" name="confirm_password" placeholder="••••••••" minlength="6" required />
              <button type="button" class="toggle" id="toggleCpw" title="Show/Hide" aria-label="Toggle confirm password">👁️</button>
            </div>
          </div>

          <div class="actions">
            <a class="link" href="clientlogin.php">Already have an account? Login</a>
            <button class="btn" type="submit">CREATE ACCOUNT</button>
          </div>

          <div class="note">By creating an account you agree to our Terms & Privacy.</div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php if (!empty($err) || !empty($ok)): ?>
  <div class="toast <?php echo $ok ? 'ok' : 'err'; ?>" id="toast">
    <?php echo htmlspecialchars($ok ?: $err); ?>
  </div>
  <script>
    const t=document.getElementById('toast'); t.classList.add('show');
    <?php if ($ok): ?>
      setTimeout(()=>{ window.location.href = 'clientlogin.php'; }, 1800);
    <?php else: ?>
      setTimeout(()=> t.classList.remove('show'), 4000);
    <?php endif; ?>
  </script>
<?php endif; ?>

<script>

const pw  = document.getElementById('password');
const cpw = document.getElementById('confirm_password');
document.getElementById('togglePw') ?.addEventListener('click', ()=> pw.type  = pw.type  === 'password' ? 'text' : 'password');
document.getElementById('toggleCpw')?.addEventListener('click', ()=> cpw.type = cpw.type === 'password' ? 'text' : 'password');


function validateForm(){
  if (pw.value !== cpw.value){
    alert('Passwords do not match!');
    return false;
  }
  if (!/^\d{10}$/.test(document.getElementById('phone').value)){
    alert('Phone must be 10 digits (numbers only).');
    return false;
  }
  return true;
}
</script>
</body>
</html>
