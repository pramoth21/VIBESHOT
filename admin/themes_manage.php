<?php

session_start();
if (!isset($_SESSION['admin_id'])) { header("Location: adminlogin.php"); exit; }

function db(){ $c=new mysqli('localhost','root','','vibeshot_db'); if($c->connect_error) die('DB failed: '.$c->connect_error); return $c; }
$conn = db();


$THEME_BASE  = 'uploads/theme_covers/';
$PLACEHOLDER = '../images/placeholder.png';

function resolve_media_url($raw, $base, $placeholder) {
  $raw = trim((string)$raw);
  if ($raw === '') return $placeholder;


  if (preg_match('#^(https?://|/|\.\./)#i', $raw)) return $raw;

 
  if (preg_match('#^uploads/#i', $raw)) return $raw;


  return $base . $raw;
}

$flash='';

// Add
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='add') {
  $name = trim($_POST['name']??'');
  $type = $_POST['type'] ?? 'Indoor';
  $desc = trim($_POST['description']??'');
  $price= (float)($_POST['price']??0);
  $dur  = (int)($_POST['duration']??60);
  $cover = null;

  if(!empty($_FILES['cover']['name'])){
    $f=$_FILES['cover'];
    if($f['error']===UPLOAD_ERR_OK){
      $allowed=['jpg','jpeg','png','webp'];
      $ext=strtolower(pathinfo($f['name'],PATHINFO_EXTENSION));
      if(in_array($ext,$allowed)){
        $dir=__DIR__.'/uploads/theme_covers/'; if(!is_dir($dir)) @mkdir($dir,0775,true);
        $safe=preg_replace('/[^a-zA-Z0-9_\-]/','_', pathinfo($f['name'],PATHINFO_FILENAME));
        $cover=$safe.'_'.uniqid().'.'.$ext;
        move_uploaded_file($f['tmp_name'],$dir.$cover);
      }
    }
  }

  $st=$conn->prepare("INSERT INTO Theme (Name,Type,Description,Price,DefaultDurationMin,CoverImage,Active) VALUES (?,?,?,?,?,?,1)");
  $st->bind_param("sssdis",$name,$type,$desc,$price,$dur,$cover);
  $st->execute(); $st->close();
  $flash='Theme added.';
}


if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='update') {
  $id=(int)$_POST['id'];
  $name=trim($_POST['name']??''); $type=$_POST['type']??'Indoor'; $desc=trim($_POST['description']??'');
  $price=(float)($_POST['price']??0); $dur=(int)($_POST['duration']??60);

  $cur=$conn->query("SELECT CoverImage FROM Theme WHERE ThemeID=".$id)->fetch_assoc();
  $cover=$cur['CoverImage'] ?? null;

  if(!empty($_FILES['cover']['name'])){
    $f=$_FILES['cover'];
    if($f['error']===UPLOAD_ERR_OK){
      $allowed=['jpg','jpeg','png','webp'];
      $ext=strtolower(pathinfo($f['name'],PATHINFO_EXTENSION));
      if(in_array($ext,$allowed)){
        $dir=__DIR__.'/uploads/theme_covers/'; if(!is_dir($dir)) @mkdir($dir,0775,true);
        $safe=preg_replace('/[^a-zA-Z0-9_\-]/','_', pathinfo($f['name'],PATHINFO_FILENAME));
        $new=$safe.'_'.uniqid().'.'.$ext;
        if(move_uploaded_file($f['tmp_name'],$dir.$new)){
          if($cover) @unlink($dir.$cover);
          $cover=$new;
        }
      }
    }
  }

  $st=$conn->prepare("UPDATE Theme SET Name=?,Type=?,Description=?,Price=?,DefaultDurationMin=?,CoverImage=? WHERE ThemeID=?");
  $st->bind_param("sssdisi",$name,$type,$desc,$price,$dur,$cover,$id);
  $st->execute(); $st->close();
  $flash='Theme updated.';
}


if (isset($_GET['toggle'])) {
  $id=(int)$_GET['toggle']; $conn->query("UPDATE Theme SET Active=1-Active WHERE ThemeID=".$id);
  header("Location: themes_manage.php"); exit;
}


$edit=null;
if (isset($_GET['edit'])) {
  $id=(int)$_GET['edit']; $r=$conn->query("SELECT * FROM Theme WHERE ThemeID=".$id); $edit=$r->fetch_assoc(); $r->free();
}


$list=[];
$r=$conn->query("SELECT * FROM Theme ORDER BY ThemeID DESC");
while($row=$r->fetch_assoc()){
  $row['CoverUrl']=resolve_media_url($row['CoverImage'] ?? '', $THEME_BASE, $PLACEHOLDER);
  $list[]=$row;
}
$r->free(); $conn->close();

function val($a,$k,$d=''){ return isset($a[$k])?$a[$k]:$d; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Themes | Vibe-Shot Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Cinzel:wght@700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="admin-dashboard-modern.css"/>
<link rel="stylesheet" href="admin-manage.css"/>
<style>.thumb-lg{width:64px;height:48px;object-fit:cover;border-radius:8px;border:1px solid #e6e6e6}</style>
</head>
<body>
<header class="topnav elevate">
  <div class="brand"><img src="../logo1.png" alt=""><div class="brand-text"><strong>VIBE-SHOT</strong><span>Admin</span></div></div>
  <div class="top-actions">
    <a class="link" href="admin_dashboard_modern.php">Dashboard</a>
    <a class="btn-accent ripple" href="admin_logout.php">Logout</a>
  </div>
</header>

<div class="subhead elevate"><div class="subhead-left"><h2>Themes</h2><p class="muted">Create and manage Indoor/Outdoor themes.</p></div></div>

<div class="layout">
  <main class="main">
    <section class="card">
      <div class="card-head"><h3><?php echo $edit?'Edit Theme':'Add Theme'; ?></h3></div>
      <form class="form" method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="<?php echo $edit?'update':'add'; ?>">
        <?php if($edit): ?><input type="hidden" name="id" value="<?php echo (int)$edit['ThemeID']; ?>"><?php endif; ?>

        <label class="f"><span class="label">Name</span><input class="input" name="name" value="<?php echo htmlspecialchars(val($edit,'Name')); ?>" required></label>
        <label class="f">
          <span class="label">Type</span>
          <select class="select" name="type" required>
            <?php foreach(['Indoor','Outdoor'] as $t): ?><option <?php if(val($edit,'Type','Indoor')===$t) echo 'selected'; ?>><?php echo $t; ?></option><?php endforeach; ?>
          </select>
        </label>
        <label class="f span-12"><span class="label">Description</span><textarea class="textarea" name="description"><?php echo htmlspecialchars(val($edit,'Description')); ?></textarea></label>
        <label class="f"><span class="label">Price (LKR)</span><input class="input" type="number" name="price" step="0.01" value="<?php echo htmlspecialchars(val($edit,'Price',0)); ?>" required></label>
        <label class="f"><span class="label">Default Duration (min)</span><input class="input" type="number" name="duration" min="15" step="15" value="<?php echo htmlspecialchars(val($edit,'DefaultDurationMin',60)); ?>" required></label>
        <label class="f span-12"><span class="label">Cover Image</span><input class="file" type="file" name="cover" accept=".jpg,.jpeg,.png,.webp">
          <?php if($edit && $edit['CoverImage']): ?>
            <div class="preview" style="margin-top:8px"><img class="thumb-lg" src="<?php echo resolve_media_url($edit['CoverImage'],$THEME_BASE,$PLACEHOLDER); ?>"></div>
          <?php endif; ?>
        </label>
        <div class="actions"><button class="btn-accent ripple"><?php echo $edit?'Update':'Create'; ?></button></div>
      </form>
      <?php if(!empty($flash)): ?><div class="toast ok show" style="position:static;margin-top:10px"><?php echo htmlspecialchars($flash); ?></div><?php endif; ?>
    </section>

    <section class="card">
      <div class="card-head"><h3>All Themes</h3></div>
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>ID</th><th>Cover</th><th>Name</th><th>Type</th><th>Price</th><th>Duration</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
          <tbody>
            <?php if(empty($list)): ?>
              <tr><td colspan="9">No themes.</td></tr>
            <?php else: foreach($list as $t): ?>
              <tr>
                <td><?php echo (int)$t['ThemeID']; ?></td>
                <td><img class="thumb-lg" src="<?php echo htmlspecialchars($t['CoverUrl']); ?>"></td>
                <td><?php echo htmlspecialchars($t['Name']); ?></td>
                <td><?php echo htmlspecialchars($t['Type']); ?></td>
                <td>LKR <?php echo number_format((float)$t['Price'],2); ?></td>
                <td><?php echo (int)$t['DefaultDurationMin']; ?> min</td>
                <td><span class="badge <?php echo $t['Active']?'active':'inactive'; ?>"><?php echo $t['Active']?'Active':'Inactive'; ?></span></td>
                <td><?php echo htmlspecialchars($t['CreatedAt']); ?></td>
                <td class="acts">
                  <a class="mini" href="?edit=<?php echo (int)$t['ThemeID']; ?>">Edit</a>
                  <a class="mini" href="?toggle=<?php echo (int)$t['ThemeID']; ?>" onclick="return confirm('Toggle active?')"><?php echo $t['Active']?'Deactivate':'Activate'; ?></a>
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
