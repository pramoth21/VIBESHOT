<?php
// /photographer/chat.php
// URL: /photographer/chat.php?bookingId=123
session_start();
if (!isset($_SESSION['photographer_id'])) { header("Location: ../photographer_login.php"); exit; }
$pid = (int)$_SESSION['photographer_id'];

$bookingId = (int)($_GET['bookingId'] ?? 0);
if ($bookingId <= 0) { header("Location: bookings.php"); exit; }

$conn = new mysqli('localhost','root','','vibeshot_db');
if ($conn->connect_error) { die('DB failed: '.$conn->connect_error); }

// verify booking belongs to this photographer & get client
$bk = $conn->prepare("SELECT b.BookingID, b.ClientID, c.Name AS CName
                      FROM Booking b JOIN Client c ON c.ClientID=b.ClientID
                      WHERE b.BookingID=? AND b.PhotographerID=?");
$bk->bind_param("ii",$bookingId,$pid);
$bk->execute();
$book = $bk->get_result()->fetch_assoc();
$bk->close();
if (!$book) { $conn->close(); die('Booking not found'); }
$clientId = (int)$book['ClientID'];

// get or create thread
$threadId = 0;
$q = $conn->prepare("SELECT ThreadID FROM ChatThread WHERE BookingID=?");
$q->bind_param("i",$bookingId); $q->execute(); $q->bind_result($threadId); $q->fetch(); $q->close();
if ($threadId === 0) {
  $ins = $conn->prepare("INSERT INTO ChatThread (BookingID, ClientID, PhotographerID) VALUES (?,?,?)");
  $ins->bind_param("iii",$bookingId,$clientId,$pid);
  if ($ins->execute()) { $threadId = $ins->insert_id; }
  $ins->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Chat with Client | Vibe-Shot</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="../client/chat.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
</head>
<body>
<header class="topbar">
  <div class="brand"><img src="../logo1.png" alt=""><strong>VIBE-SHOT</strong></div>
  <nav class="nav">
    <a class="link" href="bookings.php">← Back to bookings</a>
    <span class="chip">Chat with <?php echo htmlspecialchars($book['CName']); ?></span>
    <a class="btn" href="../photographer_logout.php">Logout</a>
  </nav>
</header>

<main class="chat-wrap">
  <div class="thread" id="thread"></div>

  <form class="composer" id="composer" autocomplete="off">
    <input type="text" id="msg" placeholder="Type a message..." maxlength="2000" required>
    <button type="submit" class="send">Send</button>
  </form>
</main>

<script>
const THREAD_ID = <?php echo (int)$threadId; ?>;
let lastId = 0;
const box = document.getElementById('thread');
const form = document.getElementById('composer');
const input = document.getElementById('msg');

function esc(s){ return s.replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }
function addMsg(m){
  lastId = Math.max(lastId, Number(m.MessageID));
  const mine = m.SenderType === 'Photographer';
  const el = document.createElement('div');
  el.className = 'msg ' + (mine ? 'me':'them');
  el.innerHTML = `
    <div class="bubble">
      <div class="body">${esc(m.Body).replace(/\n/g,'<br>')}</div>
      <div class="meta">${esc(m.SenderType)} • ${esc(m.CreatedAt)}</div>
    </div>`;
  box.appendChild(el);
  box.scrollTop = box.scrollHeight;
}

async function fetchNew(){
  try{
    const url = `../api/chat_fetch.php?threadId=${THREAD_ID}&lastId=${lastId}`;
    const res = await fetch(url);
    const out = await res.json();
    if(out && out.ok && Array.isArray(out.messages)){
      out.messages.forEach(addMsg);
    }
  }catch(e){}
}

form.addEventListener('submit', async (e)=>{
  e.preventDefault();
  const text = input.value.trim();
  if(!text) return;
  addMsg({MessageID:lastId+1, SenderType:'Photographer', Body:text, CreatedAt:new Date().toISOString().slice(0,19).replace('T',' ')});
  input.value = '';
  try{
    await fetch('../api/chat_send.php', {
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({threadId: THREAD_ID, body: text})
    });
  }catch(e){}
});

fetchNew();
setInterval(fetchNew, 2000);
</script>
</body>
</html>
