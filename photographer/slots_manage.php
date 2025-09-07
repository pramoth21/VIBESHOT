<?php
// /photographer/slots_manage.php
session_start();
if (!isset($_SESSION['photographer_id'])) { 
    header("Location: ../photographer_login.php"); 
    exit; 
}
$pid = (int)$_SESSION['photographer_id'];
$pname = $_SESSION['photographer_name'] ?? 'Photographer';

$conn = new mysqli('localhost','root','','vibeshot_db');
if ($conn->connect_error) { die('DB failed: '.$conn->connect_error); }

$flash = '';

// ADD slot
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='add') {
    $date  = $_POST['date'] ?? '';
    $start = $_POST['start'] ?? '';
    $end   = $_POST['end'] ?? '';

    // simple validations
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date)) $flash='Invalid date';
    elseif (!preg_match('/^\d{2}:\d{2}$/',$start) || !preg_match('/^\d{2}:\d{2}$/',$end)) $flash='Invalid time';
    elseif (strtotime("$date $start") >= strtotime("$date $end")) $flash='Start must be before End';
    else {
        // prevent overlap: newStart < existingEnd AND newEnd > existingStart
        $stmt = $conn->prepare("SELECT COUNT(*) FROM PhotographerSlot 
                                WHERE PhotographerID=? AND SlotDate=? 
                                AND NOT (EndTime<=? OR StartTime>=?)");
        $stmt->bind_param("isss", $pid, $date, $start, $end);
        $stmt->execute(); $stmt->bind_result($cnt); $stmt->fetch(); $stmt->close();
        if ($cnt>0) {
            $flash = 'Overlaps with an existing slot';
        } else {
            $stmt = $conn->prepare("INSERT INTO PhotographerSlot (PhotographerID, SlotDate, StartTime, EndTime, IsAvailable) 
                                    VALUES (?,?,?,?,1)");
            $stmt->bind_param("isss", $pid, $date, $start, $end);
            if ($stmt->execute()) { 
                $flash='Slot added successfully!'; 
            } else { 
                $flash='Database error: '.$conn->error; 
            }
            $stmt->close();
        }
    }
}

// TOGGLE availability
if (($_GET['toggle'] ?? '') !== '') {
    $sid = (int)$_GET['toggle'];
    $stmt = $conn->prepare("UPDATE PhotographerSlot SET IsAvailable=1-IsAvailable WHERE SlotID=? AND PhotographerID=?");
    $stmt->bind_param("ii",$sid,$pid); $stmt->execute(); $stmt->close();
    header("Location: slots_manage.php"); exit;
}

// DELETE
if (($_GET['delete'] ?? '') !== '') {
    $sid = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM PhotographerSlot WHERE SlotID=? AND PhotographerID=?");
    $stmt->bind_param("ii",$sid,$pid); $stmt->execute(); $stmt->close();
    header("Location: slots_manage.php"); exit;
}

// fetch slots
$slots = [];
$res = $conn->prepare("SELECT SlotID, SlotDate, DATE_FORMAT(StartTime,'%H:%i') s, DATE_FORMAT(EndTime,'%H:%i') e, IsAvailable
                       FROM PhotographerSlot
                       WHERE PhotographerID=?
                       ORDER BY SlotDate DESC, StartTime");
$res->bind_param("i",$pid);
$res->execute();
$list = $res->get_result();
while($r=$list->fetch_assoc()) $slots[]=$r;
$res->close();

$conn->close();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Manage Slots | Vibe-Shot</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Cinzel:wght@700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<link rel="stylesheet" href="slots_manage.css">
</head>
<body>

<header class="topnav elevate">
  <div class="brand">
    <img src="../logo1.png" alt="Vibe-Shot">
    <div class="brand-text">
      <strong>VIBE-SHOT</strong>
      <span>Photographer</span>
    </div>
  </div>
  <nav class="nav">
    <a class="link" href="dashboard.php"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
    <a class="link active" href="slots_manage.php"><i class="fa-regular fa-calendar-days"></i> Slots</a>
    <a class="link" href="gallery_manage.php"><i class="fa-solid fa-images"></i> Gallery</a>
    <a class="link" href="bookings.php"><i class="fa-regular fa-calendar-check"></i> Bookings</a>
    <span class="chip"><i class="fa-solid fa-camera-retro"></i> <?php echo htmlspecialchars($pname); ?></span>
    <a class="btn-accent ripple" href="../photographer_logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
  </nav>
</header>

<div class="layout">
  <main class="main">
    <section class="subhead elevate">
      <div class="subhead-left">
        <h2>Manage Time Slots</h2>
        <p class="muted">Add and manage your available time slots</p>
      </div>
    </section>

    <!-- Add Slot Form -->
    <section class="card reveal">
      <div class="card-head">
        <h3>Add New Slot</h3>
      </div>
      <form class="form" method="post" autocomplete="off">
        <input type="hidden" name="action" value="add">
        <div class="f">
          <label class="label">Date</label>
          <input type="date" class="input" name="date" required min="<?php echo date('Y-m-d'); ?>">
        </div>
        <div class="f">
          <label class="label">Start Time</label>
          <input type="time" class="input" name="start" required step="900">
        </div>
        <div class="f">
          <label class="label">End Time</label>
          <input type="time" class="input" name="end" required step="900">
        </div>
        <div class="f span-12">
          <div class="actions">
            <button class="btn-accent ripple" type="submit"><i class="fa-solid fa-plus"></i> Add Slot</button>
          </div>
        </div>
      </form>
      <?php if($flash): ?>
        <div class="toast <?php echo strpos($flash, 'error') !== false ? 'error' : 'ok'; ?> show">
          <i class="fa-solid <?php echo strpos($flash, 'error') !== false ? 'fa-circle-exclamation' : 'fa-circle-check'; ?>"></i>
          <?php echo htmlspecialchars($flash); ?>
        </div>
      <?php endif; ?>
    </section>

    <!-- Current Slots -->
    <section class="card reveal">
      <div class="card-head">
        <h3>Your Time Slots</h3>
      </div>
      <div class="table-wrap">
        <table class="table compact">
          <thead>
            <tr>
              <th>Date</th>
              <th>Start</th>
              <th>End</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if(empty($slots)): ?>
              <tr>
                <td colspan="5" class="text-center">No slots available yet</td>
              </tr>
            <?php else: ?>
              <?php foreach($slots as $s): ?>
                <tr>
                  <td><?php echo htmlspecialchars($s['SlotDate']); ?></td>
                  <td><?php echo htmlspecialchars($s['s']); ?></td>
                  <td><?php echo htmlspecialchars($s['e']); ?></td>
                  <td>
                    <span class="badge <?php echo $s['IsAvailable'] ? 'status confirmed' : 'status cancelled'; ?>">
                      <?php echo $s['IsAvailable'] ? 'Available' : 'Booked'; ?>
                    </span>
                  </td>
                  <td class="acts">
                    <a class="mini ripple" href="?toggle=<?php echo (int)$s['SlotID']; ?>">
                      <i class="fa-solid fa-toggle-<?php echo $s['IsAvailable'] ? 'on' : 'off'; ?>"></i> Toggle
                    </a>
                    <a class="mini danger ripple" href="?delete=<?php echo (int)$s['SlotID']; ?>" onclick="return confirm('Are you sure you want to delete this slot?')">
                      <i class="fa-solid fa-trash"></i> Delete
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
</div>

<script>
// Reveal animation
const observer = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.classList.add('in');
      observer.unobserve(entry.target);
    }
  });
}, { threshold: 0.15 });

document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

// Ripple effect
document.addEventListener('click', (e) => {
  const button = e.target.closest('.ripple');
  if (!button) return;
  
  const size = Math.max(button.offsetWidth, button.offsetHeight);
  const ripple = document.createElement('span');
  ripple.className = 'r';
  ripple.style.width = ripple.style.height = size + 'px';
  ripple.style.left = (e.offsetX - size / 2) + 'px';
  ripple.style.top = (e.offsetY - size / 2) + 'px';
  
  button.appendChild(ripple);
  setTimeout(() => ripple.remove(), 600);
});

// Auto-hide flash messages
setTimeout(() => {
  const flash = document.querySelector('.toast');
  if (flash) {
    flash.classList.remove('show');
    setTimeout(() => flash.remove(), 300);
  }
}, 5000);
</script>
</body>
</html>