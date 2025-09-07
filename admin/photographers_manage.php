<?php

session_start();
if (!isset($_SESSION['admin_id'])) { header("Location: adminlogin.php"); exit; }

function db(){
  $c = new mysqli('localhost','root','','vibeshot_db');
  if ($c->connect_error) die('DB failed: '.$c->connect_error);
  return $c;
}

function resolve_media_url($raw, $base, $placeholder){
  $raw = trim((string)$raw);
  if ($raw === '') return $placeholder;


  if (preg_match('#^(https?://|/|\./|\.\./)#i', $raw) || strpos($raw, '/') !== false || strpos($raw, '\\') !== false) {
    return $raw;
  }


  return rtrim($base,'/').'/'.$raw;
}

$PROFILE_BASE = 'uploads/photographers/';
$PLACEHOLDER  = '../images/placeholder.png';

$conn = db();

$conn->query("ALTER TABLE Photographer ADD COLUMN IF NOT EXISTS IsActive TINYINT(1) NOT NULL DEFAULT 1");

$flash = '';


if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && $_POST['action']==='add') {
  $name   = trim($_POST['name']??'');
  $phone  = trim($_POST['phone']??'');
  $gender = $_POST['gender']??'';
  $age    = (int)($_POST['age']??0);
  $bio    = trim($_POST['bio']??'');
  $email  = trim($_POST['email']??'');
  $passRaw= $_POST['password']??'';

  $errors=[];
  if($name==='')                     $errors[]='Name required';
  if($phone==='')                    $errors[]='Phone required';
  if(!in_array($gender,['Male','Female','Other'])) $errors[]='Gender invalid';
  if($age<=0)                        $errors[]='Age must be positive';
  if(!filter_var($email,FILTER_VALIDATE_EMAIL))    $errors[]='Email invalid';
  if(strlen($passRaw)<6)             $errors[]='Password must be ≥ 6 chars';

  
  $profileFileName = null;
  if(!empty($_FILES['profile_pic']['name'])){
    $f = $_FILES['profile_pic'];
    if($f['error']===UPLOAD_ERR_OK){
      $allowed = ['jpg','jpeg','png','webp'];
      $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
      if(!in_array($ext,$allowed))            $errors[]='Image must be JPG/PNG/WEBP';
      elseif($f['size']>3*1024*1024)          $errors[]='Image too large (max 3MB)';
      else{
        $dir = __DIR__.'/uploads/photographers/';
        if(!is_dir($dir)) @mkdir($dir,0775,true);
        $safe = preg_replace('/[^a-zA-Z0-9_\-]/','_', pathinfo($f['name'],PATHINFO_FILENAME));
        $profileFileName = $safe.'_'.uniqid().'.'.$ext;
        if(!move_uploaded_file($f['tmp_name'],$dir.$profileFileName)){
          $errors[]='Failed to save image'; $profileFileName=null;
        }
      }
    } else {
      $errors[] = 'Upload error code '.$f['error'];
    }
  }

  if(empty($errors)){
    $hash = password_hash($passRaw, PASSWORD_DEFAULT);
    $stmt = $conn->prepare(
      "INSERT INTO Photographer (Name,Phone,Gender,Age,Bio,ProfilePic,Email,Password,IsActive)
       VALUES (?,?,?,?,?,?,?,?,1)"
    );
    $stmt->bind_param("sssissss", $name,$phone,$gender,$age,$bio,$profileFileName,$email,$hash);
    if($stmt->execute()){
      $flash='Photographer added.';
    } else {
      $flash='DB error: '.$conn->error;
      if($profileFileName) @unlink(__DIR__.'/uploads/photographers/'.$profileFileName);
    }
    $stmt->close();
  } else {
    $flash = implode(' | ', $errors);
  }
}


if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && $_POST['action']==='update') {
  $id = (int)$_POST['id'];
  $name   = trim($_POST['name']??'');
  $phone  = trim($_POST['phone']??'');
  $gender = $_POST['gender']??'';
  $age    = (int)($_POST['age']??0);
  $bio    = trim($_POST['bio']??'');
  $email  = trim($_POST['email']??'');
  $passRaw= $_POST['password']??'';


  $cur = $conn->query("SELECT ProfilePic FROM Photographer WHERE PhotographerID=".$id)->fetch_assoc();
  $profileFileName = $cur ? ($cur['ProfilePic'] ?? null) : null;

  if(!empty($_FILES['profile_pic']['name'])){
    $f = $_FILES['profile_pic'];
    if($f['error']===UPLOAD_ERR_OK){
      $allowed = ['jpg','jpeg','png','webp'];
      $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
      if(in_array($ext,$allowed)){
        $dir = __DIR__.'/uploads/photographers/'; if(!is_dir($dir)) @mkdir($dir,0775,true);
        $safe = preg_replace('/[^a-zA-Z0-9_\-]/','_', pathinfo($f['name'],PATHINFO_FILENAME));
        $new  = $safe.'_'.uniqid().'.'.$ext;
        if(move_uploaded_file($f['tmp_name'],$dir.$new)){
          if($profileFileName) @unlink($dir.$profileFileName);
          $profileFileName = $new;
        }
      }
    }
  }

  if($passRaw!==''){
    $hash = password_hash($passRaw, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE Photographer
                            SET Name=?,Phone=?,Gender=?,Age=?,Bio=?,ProfilePic=?,Email=?,Password=?
                            WHERE PhotographerID=?");
    $stmt->bind_param("sssissssi",$name,$phone,$gender,$age,$bio,$profileFileName,$email,$hash,$id);
  } else {
    $stmt = $conn->prepare("UPDATE Photographer
                            SET Name=?,Phone=?,Gender=?,Age=?,Bio=?,ProfilePic=?,Email=?
                            WHERE PhotographerID=?");
    $stmt->bind_param("sssisssi",$name,$phone,$gender,$age,$bio,$profileFileName,$email,$id);
  }
  $stmt->execute(); $stmt->close();
  $flash='Photographer updated.';
}


if (isset($_GET['toggle'])) {
  $id=(int)$_GET['toggle'];
  $conn->query("UPDATE Photographer SET IsActive=1-IsActive WHERE PhotographerID=".$id);
  header("Location: photographers_manage.php"); exit;
}


$edit=null;
if (isset($_GET['edit'])) {
  $id=(int)$_GET['edit'];
  $r=$conn->query("SELECT * FROM Photographer WHERE PhotographerID=".$id);
  $edit=$r->fetch_assoc(); $r->free();
}


$list=[];
$r=$conn->query("SELECT PhotographerID,Name,Email,Phone,Gender,Age,ProfilePic,IsActive,CreatedAt
                 FROM Photographer ORDER BY PhotographerID DESC");
while($row=$r->fetch_assoc()){
  $row['AvatarUrl'] = resolve_media_url($row['ProfilePic'] ?? '', $PROFILE_BASE, $PLACEHOLDER);
  $list[]=$row;
}
$r->free();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Photographers | Vibe-Shot Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Cinzel:wght@700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="admin-dashboard-modern.css"/>
<link rel="stylesheet" href="admin-manage.css"/>
<style>.badge.active{background:#eafaf0}.badge.inactive{background:#fdecef}</style>
</head>
<body>
<header class="topnav elevate">
  <div class="brand"><img src="../logo1.png" alt=""><div class="brand-text"><strong>VIBE-SHOT</strong><span>Admin</span></div></div>
  <div class="top-actions">
    <a class="link" href="admin_dashboard_modern.php">Dashboard</a>
    <a class="btn-accent ripple" href="admin_logout.php">Logout</a>
  </div>
</header>

<div class="subhead elevate"><div class="subhead-left"><h2>Photographers</h2><p class="muted">Add, edit or toggle active state.</p></div></div>

<div class="layout">
  <main class="main">
    <!-- Add / Edit form -->
    <section class="card">
      <div class="card-head"><h3><?php echo $edit?'Edit Photographer':'Add Photographer'; ?></h3></div>
      <form class="form" method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="<?php echo $edit?'update':'add'; ?>">
        <?php if($edit): ?><input type="hidden" name="id" value="<?php echo (int)$edit['PhotographerID']; ?>"><?php endif; ?>

        <label class="f"><span class="label">Name</span><input class="input" name="name" value="<?php echo htmlspecialchars($edit['Name'] ?? ''); ?>" required></label>
        <label class="f"><span class="label">Phone</span><input class="input" name="phone" value="<?php echo htmlspecialchars($edit['Phone'] ?? ''); ?>" required></label>
        <label class="f">
          <span class="label">Gender</span>
          <select class="select" name="gender" required>
            <?php foreach(['Male','Female','Other'] as $g): ?>
              <option <?php if(($edit['Gender'] ?? '')===$g) echo 'selected'; ?>><?php echo $g; ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label class="f"><span class="label">Age</span><input class="input" type="number" name="age" min="16" max="100" value="<?php echo htmlspecialchars($edit['Age'] ?? ''); ?>" required></label>

        <label class="f span-12"><span class="label">Bio</span><textarea class="textarea" name="bio"><?php echo htmlspecialchars($edit['Bio'] ?? ''); ?></textarea></label>

        <label class="f span-12">
          <span class="label">Profile Picture</span>
          <input class="file" type="file" name="profile_pic" accept=".jpg,.jpeg,.png,.webp">
          <?php if(!empty($edit['ProfilePic'])): ?>
            <div class="preview" style="margin-top:8px">
              <img src="<?php echo htmlspecialchars(resolve_media_url($edit['ProfilePic'],$PROFILE_BASE,$PLACEHOLDER)); ?>" style="width:60px;height:60px;border-radius:10px;object-fit:cover">
            </div>
          <?php endif; ?>
        </label>

        <label class="f"><span class="label">Email</span><input class="input" type="email" name="email" value="<?php echo htmlspecialchars($edit['Email'] ?? ''); ?>" required></label>
        <label class="f">
          <span class="label"><?php echo $edit?'Reset Password (optional)':'Password'; ?></span>
          <input class="input" type="password" name="password" placeholder="<?php echo $edit?'Leave blank to keep current':''; ?>">
        </label>

        <div class="actions"><button class="btn-accent ripple" type="submit"><?php echo $edit?'Update':'Create'; ?></button></div>
      </form>
      <?php if($flash): ?><div class="toast ok show" style="position:static;margin-top:10px"><?php echo htmlspecialchars($flash); ?></div><?php endif; ?>
    </section>

    <!-- List -->
    <section class="card">
      <div class="card-head"><h3>All Photographers</h3></div>
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>ID</th><th>Profile</th><th>Name</th><th>Email</th><th>Phone</th><th>Gender</th><th>Age</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
          <tbody>
            <?php if(empty($list)): ?>
              <tr><td colspan="10">No photographers.</td></tr>
            <?php else: foreach($list as $p): ?>
              <tr>
                <td><?php echo (int)$p['PhotographerID']; ?></td>
                <td><img src="<?php echo htmlspecialchars($p['AvatarUrl']); ?>" alt="" class="thumb"></td>
                <td><?php echo htmlspecialchars($p['Name']); ?></td>
                <td><?php echo htmlspecialchars($p['Email']); ?></td>
                <td><?php echo htmlspecialchars($p['Phone']); ?></td>
                <td><?php echo htmlspecialchars($p['Gender']); ?></td>
                <td><?php echo (int)$p['Age']; ?></td>
                <td><span class="badge <?php echo $p['IsActive']?'active':'inactive'; ?>"><?php echo $p['IsActive']?'Active':'Inactive'; ?></span></td>
                <td><?php echo htmlspecialchars($p['CreatedAt']); ?></td>
                <td class="acts">
                  <a class="mini" href="?edit=<?php echo (int)$p['PhotographerID']; ?>">Edit</a>
                  <a class="mini" href="?toggle=<?php echo (int)$p['PhotographerID']; ?>" onclick="return confirm('Toggle active?')"><?php echo $p['IsActive']?'Deactivate':'Activate'; ?></a>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
</div>
</body>
</html>
