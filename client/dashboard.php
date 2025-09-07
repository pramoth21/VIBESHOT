<?php
// /client/client_dashboard.php
session_start();
if (!isset($_SESSION['client_id'])) {
    header("Location: ../client_login.php");
    exit;
}
$clientName = $_SESSION['client_name'] ?? 'Client';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Vibe-Shot | Client Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;800&family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <meta name="theme-color" content="#8B5E3C">
    <link href="dashboard.css" rel="stylesheet">
</head>
<body>
<header class="topbar">
    <div class="brand">
        <img src="../logo1.png" alt="Vibe-Shot logo">
        <strong>VIBE-SHOT</strong>
    </div>
    <nav class="nav">
        <a href="my_bookings.php" class="link">My Bookings</a>
        <span class="chip">Hi, <?php echo htmlspecialchars($clientName); ?></span>
        <a href="../client/clientlogout.php" class="btn">Logout</a>
    </nav>
</header>

<section class="hero">
    <div class="wrap hero-inner">
        <div class="hero-copy">
            <h1 class="title hero-title">Craft timeless moments</h1>
            <p class="hero-sub">Vibe-Shot is your studio for elegant portraits, intimate stories, and cinematic outdoor sessions—designed with a vintage soul and a modern eye.</p>
            <div class="hero-ctas">
                <a class="btn" href="indoor_themes.php">Explore Indoor Themes</a>
                <button class="btn ghost" id="outdoorBtnTop" type="button">Start Outdoor</button>
            </div>
            <ul class="hero-badges">
                <li><span class="badge">Est. 2016</span></li>
                <li><span class="badge">5★ Rated Studio</span></li>
                <li><span class="badge">Premium Retouch</span></li>
            </ul>
        </div>
        <div class="hero-polaroids">
            <figure class="polaroid p1">
                <img src="../client_images/cdash2.avif" alt="Studio portrait">
                <figcaption>Studio Portrait</figcaption>
            </figure>
            <figure class="polaroid p2">
                <img src="../client_images/cdash1.jpg" alt="Outdoor couple">
                <figcaption>Outdoor Couple</figcaption>
            </figure>
        </div>
    </div>
    <svg class="divider" viewBox="0 0 1200 80" preserveAspectRatio="none" aria-hidden="true">
        <path d="M0,40 C200,10 400,70 600,40 C800,10 1000,70 1200,40" fill="none" stroke="rgba(139,94,60,.35)" stroke-width="2"/>
    </svg>
</section>

<main class="wrap">
    <section class="intro">
        <h2 class="h2">Choose your session</h2>
        <p class="sub">Whether you love the control of a styled set or the energy of the outdoors, pick your vibe below. You can always switch later.</p>
    </section>
    <section class="cards">
        <article class="card" aria-label="Indoor Booking Card">
            <img src="../images/indoor.jpg" alt="Indoor studio rooms">
            <div class="c-body">
                <h3>Indoor Booking</h3>
                <p>Explore our themed studio rooms and pick a photographer.</p>
                <a class="cta" href="indoor_themes.php">Browse Themes</a>
            </div>
        </article>
        <article class="card" aria-label="Outdoor Booking Card">
            <img src="../images/outdoor.jpg" alt="Outdoor photography">
            <div class="c-body">
                <h3>Outdoor Booking</h3>
                <p>Pick your category (Pre-Wedding, Family, Portrait, Event), then choose photographer & slot.</p>
                <button class="cta" id="outdoorBtn" type="button">Start Outdoor</button>
            </div>
        </article>
    </section>
    <section class="hiw">
        <h2 class="h2">How it works</h2>
        <ol class="steps">
            <li>
                <span class="step-num">1</span>
                <h4>Plan your vibe</h4>
                <p>Select <strong>Indoor</strong> or <strong>Outdoor</strong>, choose a theme or category, and share your ideas.</p>
            </li>
            <li>
                <span class="step-num">2</span>
                <h4>Book with ease</h4>
                <p>Pick a photographer & time slot. We’ll confirm and send a friendly reminder.</p>
            </li>
            <li>
                <span class="step-num">3</span>
                <h4>Shine on set</h4>
                <p>Enjoy a relaxed shoot. We color-grade & retouch for that signature Vibe-Shot finish.</p>
            </li>
        </ol>
    </section>
    <section class="film">
        <div class="film-strip" aria-label="Featured highlights">
            <img src="../client_images/cdash8.jpg" alt="">
            <img src="../client_images/cdash3.jpg" alt="">
            <img src="../client_images/cdash4.avif" alt="">
            <img src="../client_images/cdash7.jpg" alt="">
            <img src="../client_images/cdash6.jpg" alt="">
            <img src="../client_images/cdash5.jpg" alt="">
        </div>
    </section>
<section class="testimonials">
    <h2 class="h2">Guests love the vibe</h2>
    <div class="t-carousel" id="tCarousel">
        <article class="t-card">
            <p>“The indoor vintage room is gorgeous—and the skin tones are flawless. We got our edits in 48 hours!”</p>
            <span class="who">— Roshel Sonali • Portrait</span>
        </article>
        <article class="t-card">
            <p>“Friendly team, classy studio props, and the final album feels timeless.”</p>
            <span class="who">— Inakshi Anjaleeka • Family</span>
        </article>
        <article class="t-card">
            <p>“What impressed me most was their attention to lighting and angles. True artists at work.”</p>
            <span class="who">— Leshan Chanaka • Solo Shoot</span>
        </article>
        </div>
    <div class="t-nav">
        <button class="btn ghost t-prev" type="button" aria-label="Previous">‹</button>
        <button class="btn ghost t-next" type="button" aria-label="Next">›</button>
    </div>
</section>
</main>
<footer class="footer">
    <div class="wrap footer-inner">
        <p class="foot-left">© <?php echo date('Y'); ?> Vibe-Shot Studio. All rights reserved.</p>
    </div>
</footer>

<div class="modal" id="outdoorModal" role="dialog" aria-modal="true" aria-labelledby="outdoorTitle" hidden>
    <div class="sheet" role="document">
        <h3 id="outdoorTitle">Outdoor session details</h3>
        <label class="f">
            <span>Category</span>
            <select id="outdoorCategory" aria-label="Outdoor category">
                <option value="" selected disabled>Choose category</option>
                <option>Pre-Wedding</option>
                <option>Family</option>
                <option>Portrait</option>
                <option>Event</option>
            </select>
        </label>
        <label class="f">
            <span>Location (optional)</span>
            <input type="text" id="outdoorLocation" placeholder="e.g., Galle Face Green" aria-label="Outdoor location (optional)">
        </label>
        <div class="row">
            <a class="btn ghost" href="indoor_themes.php" title="Go to indoor themes">Switch to Indoor</a>
            <button class="btn ghost" id="closeModal" type="button">Cancel</button>
            <button class="btn" id="goOutdoor" type="button">Continue</button>
        </div>
        <button class="x" id="xClose" type="button" aria-label="Close">✕</button>
    </div>
</div>

<button class="chat-fab" id="chatFab" aria-label="Open Chat">
    <i class="fas fa-comment-dots"></i>
    <span class="chat-badge" id="chatBadge" hidden>0</span>
</button>

<div class="chat-box" id="chatBox" hidden>
    <div class="chat-header">
        <span class="chat-title">Chat with Admin</span>
        <button class="chat-close" id="chatClose" aria-label="Close Chat">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div class="chat-messages" id="chatMessages">
        </div>
    <form class="chat-input" id="chatForm">
        <input type="text" id="chatInput" placeholder="Type a message..." autocomplete="off">
        <button type="submit" class="chat-send" id="chatSend">
            <i class="fas fa-paper-plane"></i>
        </button>
    </form>
</div>

<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
<script>
// Your existing scripts here...
(function(){
    const openButtons = [document.getElementById('outdoorBtn'), document.getElementById('outdoorBtnTop')];
    const modal      = document.getElementById('outdoorModal');
    const closeBtn   = document.getElementById('closeModal');
    const xClose     = document.getElementById('xClose');
    const goOutdoor  = document.getElementById('goOutdoor');

    const openModal = () => {
        if (!modal) return;
        modal.hidden = false;
        document.body.classList.add('no-scroll');
        document.getElementById('outdoorCategory')?.focus();
    };
    const closeModal = () => {
        if (!modal) return;
        modal.hidden = true;
        document.body.classList.remove('no-scroll');
    };

    openButtons.forEach(b => b?.addEventListener('click', openModal));
    closeBtn?.addEventListener('click', closeModal);
    xClose?.addEventListener('click', closeModal);
    modal?.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !modal.hidden) closeModal(); });

    goOutdoor?.addEventListener('click', () => {
        const cat = document.getElementById('outdoorCategory').value;
        const loc = document.getElementById('outdoorLocation').value.trim();
        if (!cat) { alert('Please choose a category'); return; }
        const params = new URLSearchParams({ sessionType: 'Outdoor', outdoorCategory: cat, location: loc });
        window.location.href = 'photographers.php?' + params.toString();
    });
})();

(function(){
    const revealEls = document.querySelectorAll('.hero-title,.hero-sub,.hero-ctas,.hero-badges,.polaroid,.card,.h2,.steps li,.film-strip,.t-card');
    const io = new IntersectionObserver((entries)=>{
        entries.forEach(e=>{
            if(e.isIntersecting){ e.target.classList.add('is-visible'); io.unobserve(e.target); }
        });
    }, {threshold: .1});
    revealEls.forEach(el=>{ el.classList.add('reveal'); io.observe(el); });
})();

(function(){
    const cards = document.querySelectorAll('.card');
    cards.forEach(card=>{
        card.addEventListener('mousemove', (e)=>{
            const r = card.getBoundingClientRect();
            const rx = ((e.clientY - r.top)/r.height - .5) * -3;
            const ry = ((e.clientX - r.left)/r.width - .5) * 3;
            card.style.transform = `translateY(-4px) rotateX(${rx}deg) rotateY(${ry}deg)`;
        });
        card.addEventListener('mouseleave', ()=>{ card.style.transform = ''; });
    });
})();

(function(){
    const els = document.querySelectorAll('.btn, .cta');
    els.forEach(el=>{
        el.style.overflow = 'hidden';
        el.addEventListener('click', function(ev){
            const r = el.getBoundingClientRect();
            const span = document.createElement('span');
            const size = Math.max(r.width, r.height);
            span.style.position = 'absolute';
            span.style.width = span.style.height = size + 'px';
            span.style.left = (ev.clientX - r.left - size/2) + 'px';
            span.style.top = (ev.clientY - r.top - size/2) + 'px';
            span.style.borderRadius = '50%';
            span.style.background = 'rgba(255,255,255,.35)';
            span.style.transform = 'scale(0)';
            span.style.transition = 'transform .5s ease, opacity .6s ease';
            el.appendChild(span);
            requestAnimationFrame(()=>{ span.style.transform = 'scale(1.6)'; span.style.opacity='0'; });
            setTimeout(()=> span.remove(), 600);
        }, {passive:true});
    });
})();

/* Tiny testimonial carousel */
(function(){
    const root = document.getElementById('tCarousel');
    if(!root) return;
    const prev = document.querySelector('.t-prev');
    const next = document.querySelector('.t-next');
    let i = 0;
    const cards = Array.from(root.children);
    function update(){
        cards.forEach((c, idx)=> c.style.transform = `translateX(${(idx - i)*100}%)`);
    }
    update();
    prev?.addEventListener('click', ()=>{ i = (i - 1 + cards.length) % cards.length; update(); });
    next?.addEventListener('click', ()=>{ i = (i + 1) % cards.length; update(); });
})();
</script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const chatFab = document.getElementById('chatFab');
        const chatBox = document.getElementById('chatBox');
        const chatClose = document.getElementById('chatClose');
        const chatForm = document.getElementById('chatForm');
        const chatInput = document.getElementById('chatInput');
        const chatMessages = document.getElementById('chatMessages');

        let chatThreadID = null;
        let lastMessageCount = 0;

        function renderMessage(message, isSelf) {
            const msgEl = document.createElement('div');
            msgEl.classList.add('message');
            msgEl.classList.add(isSelf ? 'self' : 'other');
            
            const msgBubble = document.createElement('div');
            msgBubble.classList.add('bubble');
            msgBubble.textContent = message.body;

            const msgTime = document.createElement('span');
            msgTime.classList.add('time');
            msgTime.textContent = message.time;

            msgEl.appendChild(msgBubble);
            msgEl.appendChild(msgTime);
            chatMessages.appendChild(msgEl);
        }

        async function fetchMessages() {
            try {
                const response = await fetch('chat/get_messages.php');
                if (!response.ok) throw new Error('Network response was not ok');
                const data = await response.json();
                
                chatThreadID = data.threadID;

                if (data.messages.length > lastMessageCount) {
                    chatMessages.innerHTML = '';
                    data.messages.forEach(msg => {
                        const isSelf = msg.sender === 'Client';
                        renderMessage(msg, isSelf);
                    });
                    lastMessageCount = data.messages.length;
                    chatMessages.scrollTop = chatMessages.scrollHeight; 
                }
            } catch (error) {
                console.error('Failed to fetch messages:', error);
            }
        }

        async function sendMessage(body) {
            if (!chatThreadID) {
                console.error('Chat thread not initialized.');
                return;
            }

            const formData = new FormData();
            formData.append('thread_id', chatThreadID);
            formData.append('body', body);

            try {
                const response = await fetch('chat/send_message.php', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.error || 'Failed to send message');
                }
                
                chatInput.value = '';
                await fetchMessages(); 
            } catch (error) {
                console.error('Error sending message:', error);
                alert('Failed to send message. Please try again.');
            }
        }

        chatFab.addEventListener('click', () => {
            chatBox.hidden = !chatBox.hidden;
            if (!chatBox.hidden) {
                fetchMessages(); 
                chatInput.focus();
            }
        });

        chatClose.addEventListener('click', () => {
            chatBox.hidden = true;
        });

        chatForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const messageBody = chatInput.value.trim();
            if (messageBody) {
                sendMessage(messageBody);
            }
        });

        
        fetchMessages();
        setInterval(fetchMessages, 3000); 
    });
</script>
</body>
</html>