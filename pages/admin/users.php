<?php
require_once '../../includes/config.php';
if(!is_logged_in()||!is_admin())redirect('../login.php');
$success='';$error='';
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['toggle_role'])){
    $uid=(int)($_POST['user_id']??0);
    if($uid===$_SESSION['user_id']){$error='Kendi rolünüzü değiştiremezsiniz.';}
    else{$role=($_POST['current_role']??'user')==='admin'?'user':'admin';$upd=$conn->prepare("UPDATE users SET role=? WHERE user_id=?");$upd->bind_param("si",$role,$uid);$success=$upd->execute()?"Kullanıcı rolü '$role' olarak güncellendi.":'Hata oluştu.';}
}
$users=$conn->query("SELECT * FROM users ORDER BY registration_date DESC");
?>
<!DOCTYPE html><html lang="tr"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Kullanıcılar — AVELIN Admin</title>
<link rel="stylesheet" href="../../css/style.css">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
</head><body>
<div class="admin-layout">
<?php include 'sidebar.php'; ?>
<main class="admin-main">
  <div class="admin-page-header"><h1>Kullanıcılar</h1></div>
  <?php if($success):?><div class="alert alert-success">✓ <?php echo $success;?></div><?php endif;?>
  <?php if($error):?><div class="alert alert-danger">✕ <?php echo $error;?></div><?php endif;?>
  <div class="admin-table-box">
    <div class="admin-table-header"><h2><?php echo $users->num_rows;?> Kullanıcı</h2></div>
    <div style="overflow-x:auto;"><table class="admin-table"><thead><tr><th>Kullanıcı</th><th>E-posta</th><th>Rol</th><th>Kayıt Tarihi</th><th>İşlem</th></tr></thead><tbody>
    <?php while($u=$users->fetch_assoc()):?>
    <tr>
      <td><strong><?php echo htmlspecialchars($u['name']?:$u['username']);?></strong><br><small style="color:var(--gray)">@<?php echo htmlspecialchars($u['username']);?></small></td>
      <td><?php echo htmlspecialchars($u['email']);?></td>
      <td><?php echo $u['role']==='admin'?'<span class="badge-stock low">👑 Admin</span>':'<span class="badge-stock ok">Kullanıcı</span>';?></td>
      <td><?php echo format_date($u['registration_date']);?></td>
      <td><?php if($u['user_id']!==$_SESSION['user_id']):?>
        <form method="post" style="display:inline;"><input type="hidden" name="user_id" value="<?php echo $u['user_id'];?>"><input type="hidden" name="current_role" value="<?php echo $u['role'];?>"><button type="submit" name="toggle_role" class="btn btn-outline btn-sm" data-confirm="Rolü değiştirmek istiyor musunuz?"><?php echo $u['role']==='admin'?'Kullanıcı Yap':'Admin Yap';?></button></form>
        <?php else:?><span style="font-size:11px;color:var(--gray)">Mevcut hesap</span><?php endif;?>
      </td>
    </tr>
    <?php endwhile;?>
    </tbody></table></div>
  </div>
</main>
</div>
<div id="toast-container"></div><script src="../../js/main.js"></script>
</body></html>
