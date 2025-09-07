<?php
// /photographer/gallery_manage.php
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

// upload images
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (($_POST['action']??'')==='upload') {
        if (!isset($_FILES['images'])) {
            $flash = ['type'=>'error', 'msg'=>'Please choose images.'];
        } else {
            $baseDir = dirname(__DIR__)."/uploads/photographers/$pid/gallery/";
            if (!is_dir($baseDir)) @mkdir($baseDir, 0775, true);
            $allowed = ['jpg','jpeg','png','webp'];
            $uploaded = 0;
            
            foreach($_FILES['images']['name'] as $i=>$name){
                if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) continue;
                $tmp  = $_FILES['images']['tmp_name'][$i];
                $size = $_FILES['images']['size'][$i];
                $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (!in_array($ext,$allowed) || $size > 4*1024*1024) continue;

                $safe = preg_replace('/[^a-zA-Z0-9_\-]/','_', pathinfo($name, PATHINFO_FILENAME));
                $fname = $safe.'_'.uniqid().'.'.$ext;
                $dest  = $baseDir.$fname;

                if (move_uploaded_file($tmp,$dest)) {
                    $webPath = "uploads/photographers/$pid/gallery/$fname";
                    $stmt = $conn->prepare("INSERT INTO PhotographerGallery (PhotographerID, ImagePath, Caption) VALUES (?,?,?)");
                    $caption = $_POST['captions'][$i] ?? '';
                    $stmt->bind_param("iss",$pid,$webPath,$caption);
                    $stmt->execute(); 
                    $stmt->close();
                    $uploaded++;
                }
            }
            $flash = ['type'=>'success', 'msg'=>"Uploaded $uploaded images."];
        }
    } elseif (($_POST['action']??'')==='update-caption') {
        $gid = (int)($_POST['gallery_id'] ?? 0);
        $caption = $_POST['caption'] ?? '';
        
        $stmt = $conn->prepare("UPDATE PhotographerGallery SET Caption=? WHERE GalleryID=? AND PhotographerID=?");
        $stmt->bind_param("sii", $caption, $gid, $pid);
        if ($stmt->execute()) {
            $flash = ['type'=>'success', 'msg'=>'Caption updated'];
        } else {
            $flash = ['type'=>'error', 'msg'=>'Failed to update caption'];
        }
        $stmt->close();
        header("Location: gallery_manage.php");
        exit;
    }
}

// delete image
if (($_GET['delete'] ?? '') !== '') {
    $gid = (int)$_GET['delete'];
    // get file path
    $stmt = $conn->prepare("SELECT ImagePath FROM PhotographerGallery WHERE GalleryID=? AND PhotographerID=?");
    $stmt->bind_param("ii",$gid,$pid); $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row) {
        $file = dirname(__DIR__).'/'.$row['ImagePath'];
        @unlink($file);
        $stmt = $conn->prepare("DELETE FROM PhotographerGallery WHERE GalleryID=? AND PhotographerID=?");
        $stmt->bind_param("ii",$gid,$pid); $stmt->execute(); $stmt->close();
    }
    header("Location: gallery_manage.php"); exit;
}

// fetch gallery
$images = [];
$stmt = $conn->prepare("SELECT GalleryID, ImagePath, Caption, UploadedAt 
                        FROM PhotographerGallery 
                        WHERE PhotographerID=? 
                        ORDER BY GalleryID DESC");
$stmt->bind_param("i",$pid);
$stmt->execute();
$res = $stmt->get_result();
while($r=$res->fetch_assoc()) $images[] = $r;
$stmt->close();

$conn->close();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Manage Gallery | Vibe-Shot</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Cinzel:wght@700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<link rel="stylesheet" href="dashboard.css">
<style>
.gallery-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 20px;
  margin-top: 20px;
  width: 100%;
}
.gallery-item {
  background: var(--surface);
  border: 1px solid var(--stroke);
  border-radius: var(--r-card);
  overflow: hidden;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  position: relative;
  display: flex;
  flex-direction: column;
}
.gallery-item:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}
.gallery-img {
  width: 100%;
  height: 200px;
  object-fit: cover;
  border-bottom: 1px solid var(--stroke);
}
.gallery-meta {
  padding: 12px;
   flex-grow: 1;
  display: flex;
  flex-direction: column;
}
.gallery-caption {
  margin: 8px 0;
  font-size: 0.9rem;
  color: var(--ink);
  word-break: break-word;
   flex-grow: 1;
}
.gallery-actions {
  display: flex;
  justify-content: space-between;
   align-items: flex-end;
  margin-top: 12px;
  gap: 8px;
}
.caption-form {
  display: flex;
  gap: 8px;
  width: 100%;
  min-width: 0;
}
.caption-input {
  flex: 1;
  padding: 8px 12px;
  border: 1px solid var(--stroke);
  border-radius: var(--r-field);
  font-size: 0.9rem;
  min-width: 0;
}
.action-buttons {
  display: flex;
  gap: 8px;
}
.delete-btn {
  background: #fdecef;
  border: 1px solid #f3c6cc;
  color: #7b1321;
  padding: 8px 12px;
  border-radius: var(--r-field);
  font-size: 0.85rem;
  white-space: nowrap;
  display: flex;
  align-items: center;
  gap: 6px;
  transition: all 0.2s ease;
}
.delete-btn:hover {
  background: #fbd5da;
}
.upload-form {
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.file-input {
  display: none;
}
.file-label {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 20px;
  border: 2px dashed var(--stroke);
  border-radius: var(--r-card);
  cursor: pointer;
  transition: all 0.2s ease;
}
.file-label:hover {
  border-color: var(--accent);
  background: var(--surface-2);
}
.file-label i {
  font-size: 2rem;
  color: var(--accent);
  margin-bottom: 8px;
}
.file-label span {
  color: var(--muted);
}
.preview-container {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
  gap: 8px;
  margin-top: 12px;
}
.preview-item {
  position: relative;
}
.preview-img {
  width: 100%;
  height: 80px;
  object-fit: cover;
  border-radius: 8px;
  border: 1px solid var(--stroke);
}
.preview-caption {
  width: 100%;
  padding: 4px;
  font-size: 0.8rem;
  border: 1px solid var(--stroke);
  border-radius: 4px;
  margin-top: 4px;
}
.remove-preview {
  position: absolute;
  top: -6px;
  right: -6px;
  width: 20px;
  height: 20px;
  background: var(--danger);
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.7rem;
  cursor: pointer;
  border: 1px solid white;
}
.delete-btn {
  white-space: nowrap;
  padding: 6px 8px;
}
</style>
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
    <a class="link" href="slots_manage.php"><i class="fa-regular fa-calendar-days"></i> Slots</a>
    <a class="link active" href="gallery_manage.php"><i class="fa-solid fa-images"></i> Gallery</a>
    <a class="link" href="bookings.php"><i class="fa-regular fa-calendar-check"></i> Bookings</a>
    <span class="chip"><i class="fa-solid fa-camera-retro"></i> <?php echo htmlspecialchars($pname); ?></span>
    <a class="btn-accent ripple" href="../photographer_logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
  </nav>
</header>

<div class="layout">
  <main class="main">
    <section class="subhead elevate">
      <div class="subhead-left">
        <h2>Manage Gallery</h2>
        <p class="muted">Upload and manage your portfolio images</p>
      </div>
    </section>

    <!-- Upload Form -->
    <section class="card reveal">
      <div class="card-head">
        <h3>Upload New Images</h3>
      </div>
      <form class="upload-form" method="post" enctype="multipart/form-data" id="uploadForm">
        <input type="hidden" name="action" value="upload">
        
        <input type="file" name="images[]" id="fileInput" class="file-input" multiple accept=".jpg,.jpeg,.png,.webp" required>
        <label for="fileInput" class="file-label ripple">
          <i class="fa-solid fa-cloud-arrow-up"></i>
          <span>Click to select images or drag & drop</span>
        </label>
        
        <div class="preview-container" id="previewContainer"></div>
        
        <div class="actions">
          <button class="btn-accent ripple" type="submit"><i class="fa-solid fa-upload"></i> Upload Images</button>
        </div>
      </form>
    </section>

    <!-- Gallery Images -->
    <section class="card reveal">
      <div class="card-head">
        <h3>Your Portfolio</h3>
        <p class="muted"><?php echo count($images); ?> images in your gallery</p>
      </div>
      
      <?php if(empty($images)): ?>
        <div class="text-center" style="padding: 20px;">
          <i class="fa-solid fa-image" style="font-size: 2rem; color: var(--muted); margin-bottom: 10px;"></i>
          <p>No images in your gallery yet</p>
        </div>
      <?php else: ?>
        <div class="gallery-grid">
          <?php foreach($images as $im): 
            $src = '../'.ltrim($im['ImagePath'],'/');
          ?>
         <div class="gallery-item">
  <img src="<?php echo htmlspecialchars($src); ?>" alt="Gallery image" class="gallery-img">
  <div class="gallery-meta">
    <?php if($im['Caption']): ?>
      <div class="gallery-caption"><?php echo htmlspecialchars($im['Caption']); ?></div>
    <?php endif; ?>
    <div class="gallery-actions">
      <form method="post" class="caption-form">
        <input type="hidden" name="action" value="update-caption">
        <input type="hidden" name="gallery_id" value="<?php echo $im['GalleryID']; ?>">
        <input type="text" name="caption" class="caption-input" placeholder="Add caption..." value="<?php echo htmlspecialchars($im['Caption'] ?? ''); ?>">
        <button type="submit" class="mini"><i class="fa-solid fa-check"></i></button>
      </form>
      <div class="action-buttons">
        <a class="delete-btn ripple" href="?delete=<?php echo (int)$im['GalleryID']; ?>" onclick="return confirm('Delete this image?')">
          <i class="fa-solid fa-trash"></i>
          <span>Delete</span>
        </a>
      </div>
    </div>
  </div>
</div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </main>
</div>

<?php if($flash): ?>
  <div class="toast <?php echo $flash['type'] === 'success' ? 'ok' : 'error'; ?> show">
    <i class="fa-solid <?php echo $flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
    <?php echo htmlspecialchars($flash['msg']); ?>
  </div>
<?php endif; ?>

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

// File upload preview
const fileInput = document.getElementById('fileInput');
const previewContainer = document.getElementById('previewContainer');
const uploadForm = document.getElementById('uploadForm');

fileInput.addEventListener('change', function() {
  previewContainer.innerHTML = '';
  
  if (this.files) {
    Array.from(this.files).forEach((file, index) => {
      const reader = new FileReader();
      
      reader.onload = function(e) {
        const previewItem = document.createElement('div');
        previewItem.className = 'preview-item';
        
        previewItem.innerHTML = `
          <img src="${e.target.result}" class="preview-img" alt="Preview">
          <input type="text" name="captions[]" class="preview-caption" placeholder="Caption...">
          <span class="remove-preview" data-index="${index}">&times;</span>
        `;
        
        previewContainer.appendChild(previewItem);
        
        // Add remove functionality
        previewItem.querySelector('.remove-preview').addEventListener('click', function() {
          // Create new DataTransfer to remove file from input
          const dataTransfer = new DataTransfer();
          Array.from(fileInput.files).forEach((f, i) => {
            if (i !== parseInt(this.getAttribute('data-index'))) {
              dataTransfer.items.add(f);
            }
          });
          fileInput.files = dataTransfer.files;
          previewItem.remove();
        });
      }
      
      reader.readAsDataURL(file);
    });
  }
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