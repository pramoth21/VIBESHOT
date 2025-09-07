<?php

session_start();
if (!isset($_SESSION['client_id'])) { header("Location: ../client_login.php"); exit; }
$status = $_GET['status'] ?? 'fail';
$ref = $_GET['ref'] ?? '';
$ok = ($status === 'success');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payment <?php echo $ok?'Success':'Failed'; ?> | Vibe‑Shot</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="payment_result.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
</head>
<body>
<main class="center">
  <div class="card <?php echo $ok?'ok':'bad'; ?>">
    <h1><?php echo $ok?'Payment Successful':'Payment Failed'; ?></h1>
    <?php if($ok): ?>
      <p>Your booking is confirmed. Reference: <strong><?php echo htmlspecialchars($ref); ?></strong></p>
      <a class="btn" href="my_bookings.php">Go to My Bookings</a>
    <?php else: ?>
      <p>Something went wrong. Please try again.</p>
      <a class="btn ghost" href="javascript:history.back()">Back</a>
    <?php endif; ?>
  </div>
</main>
</body>
</html>
