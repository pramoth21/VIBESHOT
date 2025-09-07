<?php
// /admin/admin_dashboard_modern.php
session_start();
if (!isset($_SESSION['admin_id'])) { header("Location: adminlogin.php"); exit; }

function db() {
    $c = new mysqli('localhost','root','','vibeshot_db');
    if ($c->connect_error) die('DB connection failed: '.$c->connect_error);
    return $c;
}
function resolve_media_url($raw, $base, $placeholder) {
    $raw = trim((string)$raw);
    if ($raw === '') return $placeholder;
    if (preg_match('#^(https?://|/)#i', $raw)) return $raw;
    return $base . $raw;
}
$PROFILE_BASE = 'uploads/photographers/';   // relative to /admin/*
$PLACEHOLDER  = '../images/placeholder.png';

$conn = db();

// ---------- STATS ----------
$stats = ['photographers'=>0,'clients'=>0,'bookings'=>0,'paid'=>0,'pending'=>0, 'chat_threads'=>0];

// photographers
$r = $conn->query("SELECT COUNT(*) c FROM Photographer");
$stats['photographers'] = (int)$r->fetch_assoc()['c']; $r->free();

// clients
$r = $conn->query("SELECT COUNT(*) c FROM Client");
$stats['clients'] = (int)$r->fetch_assoc()['c']; $r->free();

// bookings total
$r = $conn->query("SELECT COUNT(*) c FROM Booking");
$stats['bookings'] = (int)$r->fetch_assoc()['c']; $r->free();

// paid revenue (sum of paid)
$r = $conn->query("SELECT COALESCE(SUM(Amount),0) s FROM Booking WHERE PaymentStatus='Paid'");
$stats['paid'] = (float)$r->fetch_assoc()['s']; $r->free();

// pending count
$r = $conn->query("SELECT COUNT(*) c FROM Booking WHERE Status='Pending'");
$stats['pending'] = (int)$r->fetch_assoc()['c']; $r->free();

// chat threads
$r = $conn->query("SELECT COUNT(*) c FROM client_admin_chat_threads");
$stats['chat_threads'] = (int)$r->fetch_assoc()['c']; $r->free();


// ---------- LATEST ACTIVITY (recent bookings) ----------
$latestBookings = [];
$sql = "SELECT b.BookingID,b.SessionType,b.ShootDate,b.StartTime,b.DurationMin,b.Status,b.Amount,b.PaymentStatus,
               c.Name AS ClientName, p.Name AS PhotographerName, t.Name AS ThemeName,
               b.OutdoorCategory,b.Location
        FROM Booking b
        LEFT JOIN Client c ON c.ClientID=b.ClientID
        LEFT JOIN Photographer p ON p.PhotographerID=b.PhotographerID
        LEFT JOIN Theme t ON t.ThemeID=b.ThemeID
        ORDER BY b.BookingID DESC LIMIT 12";
$res = $conn->query($sql);
while($row = $res->fetch_assoc()) $latestBookings[] = $row;
$res->free();

// ---------- LATEST PHOTOGRAPHERS (fix avatar path) ----------
$latest = [];
$res = $conn->query("SELECT PhotographerID, Name, Email, Phone, Gender, Age, ProfilePic, CreatedAt
                     FROM Photographer ORDER BY PhotographerID DESC LIMIT 20");
while ($row = $res->fetch_assoc()) {
    $row['AvatarUrl'] = resolve_media_url($row['ProfilePic'] ?? '', $PROFILE_BASE, $PLACEHOLDER);
    $latest[] = $row;
}
$res->free();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Vibe-Shot | Admin Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Cinzel:wght@700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<link rel="stylesheet" href="admin-dashboard-modern.css"/>
<style>
.badge.status{padding:4px 8px;border-radius:999px;border:1px solid #e6e6e6;font-size:.85rem}
.badge.status.pending{background:#fff5e6}
.badge.status.confirmed{background:#eaf7ff}
.badge.status.completed{background:#eafaf0}
.badge.status.cancelled,.badge.status.declined{background:#fdecef}
.badge.pay.paid{background:#eafaf0}
.badge.pay.unpaid{background:#fff5e6}
.badge.pay.refunded{background:#eaf7ff}
.table.compact td .mini{margin-left:6px}
</style>
</head>
<body>

<header class="topnav elevate">
    <div class="brand">
        <img src="../logo1.png" alt="Vibe-Shot">
        <div class="brand-text">
            <strong>VIBE-SHOT</strong>
            <span>Admin</span>
        </div>
    </div>
    <div class="top-actions">
        <div class="animated-accent"></div>
        <a class="link" href="../index.html"><i class="fa-regular fa-window-maximize"></i> View Site</a>
        <span class="chip"><i class="fa-solid fa-user-shield"></i> <?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?></span>
        <a class="btn-accent ripple" href="../admin/admin_logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </div>
</header>

<div class="subhead elevate">
    <div class="subhead-left">
        <h2>Dashboard</h2>
        <p class="muted">Overview of photographers, clients and bookings.</p>
    </div>
    <div class="subhead-cards">
        <div class="pill stat"><i class="fa-solid fa-camera"></i> <span><?php echo $stats['photographers']; ?></span> Photographers</div>
        <div class="pill stat"><i class="fa-solid fa-users"></i> <span><?php echo $stats['clients']; ?></span> Clients</div>
        <div class="pill stat"><i class="fa-regular fa-calendar-check"></i> <span><?php echo $stats['bookings']; ?></span> Bookings</div>
        <div class="pill stat"><i class="fa-solid fa-sack-dollar"></i> LKR <?php echo number_format($stats['paid'],2); ?> Paid</div>
        <div class="pill stat"><i class="fa-regular fa-clock"></i> <span><?php echo $stats['pending']; ?></span> Pending</div>
    </div>
</div>

<div class="layout">
    <aside class="sidebar elevate">
        <div class="s-group">
            <h4>Navigation</h4>
            <a class="s-link" href="admin_dashboard_modern.php"><i class="fa-solid fa-gauge-high"></i> Overview</a>
            <a class="s-link" href="bookings_manage.php"><i class="fa-regular fa-calendar-check"></i> Bookings</a>
            <a class="s-link" href="photographers_manage.php"><i class="fa-solid fa-camera-retro"></i> Photographers</a>
            <a class="s-link" href="clients_manage.php"><i class="fa-regular fa-id-badge"></i> Clients</a>
            <a class="s-link" href="themes_manage.php"><i class="fa-solid fa-images"></i> Themes</a>
        </div>
        <div class="s-group">
            <h4>Support</h4>
            <a class="s-link" href="#" id="openChatLink"><i class="fa-regular fa-comment-dots"></i> Client Chat
                <span class="badge" id="chatCountBadge"><?php echo $stats['chat_threads']; ?></span>
            </a>
        </div>
    </aside>

    <main class="main">
        <section class="card reveal">
            <div class="card-head">
                <h3>Latest Activity</h3>
                <p class="muted">Recent bookings</p>
            </div>
            <div class="table-wrap">
                <table class="table compact">
                    <thead>
                        <tr>
                            <th>#</th><th>Date</th><th>Time</th><th>Dur</th><th>Type</th><th>Theme / Category</th>
                            <th>Client</th><th>Photographer</th><th>Amount</th><th>Pay</th><th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($latestBookings)): ?>
                            <tr><td colspan="11">No recent bookings.</td></tr>
                        <?php else: foreach($latestBookings as $b): ?>
                            <tr>
                                <td><?php echo (int)$b['BookingID']; ?></td>
                                <td><?php echo htmlspecialchars($b['ShootDate']); ?></td>
                                <td><?php echo htmlspecialchars(substr($b['StartTime'],0,5)); ?></td>
                                <td><?php echo (int)$b['DurationMin']; ?>m</td>
                                <td><?php echo htmlspecialchars($b['SessionType']); ?></td>
                                <td>
                                    <?php
                                        if ($b['SessionType']==='Indoor') echo htmlspecialchars($b['ThemeName'] ?? '');
                                        else echo htmlspecialchars(($b['OutdoorCategory'] ?? '').($b['Location']?' @ '.$b['Location']:''));
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($b['ClientName'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($b['PhotographerName'] ?? ''); ?></td>
                                <td>LKR <?php echo number_format((float)$b['Amount'],2); ?></td>
                                <td><span class="badge pay <?php echo strtolower($b['PaymentStatus']); ?>"><?php echo htmlspecialchars($b['PaymentStatus']); ?></span></td>
                                <td><span class="badge status <?php echo strtolower($b['Status']); ?>"><?php echo htmlspecialchars($b['Status']); ?></span></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card reveal">
            <div class="card-head">
                <h3>Latest Photographers</h3>
                <p class="muted">Most recent registrations (max 20).</p>
            </div>
            <div class="table-wrap">
                <table class="table compact" id="photographerTable">
                    <thead>
                        <tr>
                            <th>ID</th><th>Profile</th><th>Name</th><th>Email</th><th>Phone</th><th>Gender</th><th>Age</th><th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($latest)): ?>
                            <tr><td colspan="8">No photographers yet.</td></tr>
                        <?php else: foreach ($latest as $p): ?>
                            <tr>
                                <td><?php echo (int)$p['PhotographerID']; ?></td>
                                <td>
                                    <span class="badge">
                                        <img src="<?php echo htmlspecialchars($p['AvatarUrl']); ?>" alt="" class="thumb"> Photo
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($p['Name']); ?></td>
                                <td><?php echo htmlspecialchars($p['Email']); ?></td>
                                <td><?php echo htmlspecialchars($p['Phone']); ?></td>
                                <td><?php echo htmlspecialchars($p['Gender']); ?></td>
                                <td><?php echo (int)$p['Age']; ?></td>
                                <td><?php echo htmlspecialchars($p['CreatedAt']); ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>

<div class="chat-modal" id="chatModal" hidden>
    <div class="chat-container">
        <div class="chat-threads" id="chatThreads">
            <div class="chat-threads-header">
                <h3>Client Chats</h3>
                <button class="chat-close-btn" id="modalCloseBtn">&times;</button>
            </div>
            <div class="chat-thread-list" id="threadList">
                <p class="chat-loading-msg">Loading chats...</p>
            </div>
        </div>
        <div class="chat-main" id="chatMain">
            <div class="chat-header">
                <span id="chatClientName">Select a chat...</span>
                <button class="chat-back-btn" id="chatBackBtn"><i class="fa-solid fa-arrow-left"></i></button>
            </div>
            <div class="chat-messages" id="chatMessages">
                <p class="chat-no-selection">Please select a client to start chatting.</p>
            </div>
            <form class="chat-input-form" id="chatForm">
                <input type="text" id="chatInput" placeholder="Type a message..." autocomplete="off">
                <button type="submit" class="chat-send-btn"><i class="fa-solid fa-paper-plane"></i></button>
            </form>
        </div>
    </div>
</div>

<script>
// reveal
const observer = new IntersectionObserver((entries)=>{entries.forEach(e=>{if(e.isIntersecting){e.target.classList.add('in');observer.unobserve(e.target);}})},{threshold:0.15});
document.querySelectorAll('.reveal').forEach(el=>observer.observe(el));
// ripple
document.addEventListener('click',(e)=>{const b=e.target.closest('.ripple');if(!b)return;const s=Math.max(b.offsetWidth,b.offsetHeight);const r=document.createElement('span');r.className='r';r.style.width=r.style.height=s+'px';r.style.left=(e.offsetX-s/2)+'px';r.style.top=(e.offsetY-s/2)+'px';b.appendChild(r);setTimeout(()=>r.remove(),600);});
</script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const openChatLink = document.getElementById('openChatLink');
        const chatModal = document.getElementById('chatModal');
        const modalCloseBtn = document.getElementById('modalCloseBtn');
        const threadList = document.getElementById('threadList');
        const chatMessages = document.getElementById('chatMessages');
        const chatForm = document.getElementById('chatForm');
        const chatInput = document.getElementById('chatInput');
        const chatClientName = document.getElementById('chatClientName');
        const chatMain = document.getElementById('chatMain');
        const chatBackBtn = document.getElementById('chatBackBtn');
        
        let currentThreadID = null;

        // Open chat modal
        openChatLink.addEventListener('click', (e) => {
            e.preventDefault();
            chatModal.hidden = false;
            document.body.classList.add('no-scroll');
            fetchThreads();
        });

        // Close chat modal
        modalCloseBtn.addEventListener('click', () => {
            chatModal.hidden = true;
            document.body.classList.remove('no-scroll');
            // Reset chat view
            chatMain.classList.remove('active');
            chatMessages.innerHTML = '<p class="chat-no-selection">Please select a client to start chatting.</p>';
            chatClientName.textContent = 'Select a chat...';
            currentThreadID = null;
        });

        // Back button for mobile
        chatBackBtn.addEventListener('click', () => {
            chatMain.classList.remove('active');
        });

        // Fetch list of chat threads
        async function fetchThreads() {
            threadList.innerHTML = '<p class="chat-loading-msg">Loading chats...</p>';
            try {
                const response = await fetch('chat/get_threads.php');
                if (!response.ok) throw new Error('Network response was not ok');
                const data = await response.json();
                renderThreads(data.threads);
            } catch (error) {
                console.error('Failed to fetch threads:', error);
                threadList.innerHTML = '<p class="chat-loading-msg">Failed to load chats.</p>';
            }
        }

        // Render the list of chat threads
        function renderThreads(threads) {
            threadList.innerHTML = '';
            if (threads.length === 0) {
                threadList.innerHTML = '<p class="chat-loading-msg">No client chats available.</p>';
                return;
            }
            threads.forEach(thread => {
                const threadEl = document.createElement('div');
                threadEl.classList.add('chat-thread-item');
                threadEl.dataset.threadId = thread.threadID;
                threadEl.innerHTML = `
                    <span class="thread-name">${thread.clientName}</span>
                    <p class="thread-last-msg">${thread.lastMessage || 'Start a conversation...'}</p>
                `;
                threadEl.addEventListener('click', () => {
                    selectThread(thread.threadID, thread.clientName);
                });
                threadList.appendChild(threadEl);
            });
        }

        // Select a thread and load messages
        async function selectThread(threadId, clientName) {
            currentThreadID = threadId;
            chatClientName.textContent = clientName;
            chatMessages.innerHTML = '<p class="chat-loading-msg">Loading messages...</p>';
            chatMain.classList.add('active'); // Show chat on mobile
            try {
                const response = await fetch(`chat/get_messages.php?thread_id=${threadId}`);
                if (!response.ok) throw new Error('Network response was not ok');
                const data = await response.json();
                renderMessages(data.messages);
            } catch (error) {
                console.error('Failed to fetch messages:', error);
                chatMessages.innerHTML = '<p class="chat-loading-msg">Failed to load messages.</p>';
            }
        }

        // Render the messages for a selected thread
        function renderMessages(messages) {
            chatMessages.innerHTML = '';
            messages.forEach(msg => {
                const msgEl = document.createElement('div');
                msgEl.classList.add('message');
                msgEl.classList.add(msg.sender === 'Admin' ? 'self' : 'other');

                const msgBubble = document.createElement('div');
                msgBubble.classList.add('bubble');
                msgBubble.textContent = msg.body;

                const msgTime = document.createElement('span');
                msgTime.classList.add('time');
                msgTime.textContent = msg.time;

                msgEl.appendChild(msgBubble);
                msgEl.appendChild(msgTime);
                chatMessages.appendChild(msgEl);
            });
            chatMessages.scrollTop = chatMessages.scrollHeight; // Auto-scroll to bottom
        }

        // Handle sending a new message
        chatForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const messageBody = chatInput.value.trim();
            if (!messageBody || !currentThreadID) return;

            const formData = new FormData();
            formData.append('thread_id', currentThreadID);
            formData.append('body', messageBody);

            try {
                const response = await fetch('chat/send_message.php', {
                    method: 'POST',
                    body: formData
                });
                if (!response.ok) throw new Error('Failed to send message');

                chatInput.value = '';
                selectThread(currentThreadID, chatClientName.textContent); // Refresh messages
            } catch (error) {
                console.error('Error sending message:', error);
                alert('Failed to send message.');
            }
        });
        
        // Polling for new messages in the currently open chat
        setInterval(() => {
            if (currentThreadID) {
                selectThread(currentThreadID, chatClientName.textContent);
            }
        }, 3000); // Poll every 3 seconds
    });
</script>
</body>
</html>