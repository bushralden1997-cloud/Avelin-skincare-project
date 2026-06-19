<?php
require_once '../../includes/config.php';
if(!is_logged_in()||!is_admin())redirect('../login.php');
$success='';$error='';$edit_cat=null;

if($_SERVER['REQUEST_METHOD']==='POST'){
    if(isset($_POST['add_category'])){
        $name=trim(htmlspecialchars($_POST['name']??''));$desc=trim(htmlspecialchars($_POST['description']??''));
        if(empty($name))$error='Kategori adı zorunludur.';
        else{$ins=$conn->prepare("INSERT INTO categories (name,description) VALUES (?,?)");$ins->bind_param("ss",$name,$desc);$success=$ins->execute()?"\"$name\" kategorisi eklendi.":'Hata oluştu.';}
    }
    if(isset($_POST['update_category'])){
        $cid=(int)$_POST['category_id'];$name=trim(htmlspecialchars($_POST['name']??''));$desc=trim(htmlspecialchars($_POST['description']??''));
        if(empty($name))$error='Kategori adı zorunludur.';
        else{$upd=$conn->prepare("UPDATE categories SET name=?,description=? WHERE category_id=?");$upd->bind_param("ssi",$name,$desc,$cid);$success=$upd->execute()?"\"$name\" güncellendi.":'Hata oluştu.';}
    }
    if(isset($_POST['delete_category'])){
        $cid=(int)$_POST['category_id'];$del=$conn->prepare("DELETE FROM categories WHERE category_id=?");$del->bind_param("i",$cid);$success=$del->execute()?'Kategori silindi.':'Hata oluştu.';}
}
if(isset($_GET['edit'])){$r=$conn->prepare("SELECT * FROM categories WHERE category_id=?");$r->bind_param("i",(int)$_GET['edit']);$r->execute();$edit_cat=$r->get_result()->fetch_assoc();}
$cats=$conn->query("SELECT c.*,COUNT(p.product_id) as cnt FROM categories c LEFT JOIN products p ON c.category_id=p.category_id GROUP BY c.category_id ORDER BY c.name");
?>
<!DOCTYPE html><html lang="tr"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Kategoriler — AVELIN Admin</title>
<link rel="stylesheet" href="../../css/style.css">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
</head><body>
<div class="admin-layout">
<?php include 'sidebar.php'; ?>
<main class="admin-main">
  <div class="admin-page-header"><h1>Kategoriler</h1></div>
  <?php if($success):?><div class="alert alert-success">✓ <?php echo $success;?></div><?php endif;?>
  <?php if($error):?><div class="alert alert-danger">✕ <?php echo $error;?></div><?php endif;?>
  <div style="display:grid;grid-template-columns:1fr 1.6fr;gap:24px;">
    <div class="admin-form-box">
      <h2><?php echo $edit_cat?'Kategori Düzenle':'Yeni Kategori Ekle';?></h2>
      <form method="post">
        <?php if($edit_cat):?><input type="hidden" name="category_id" value="<?php echo $edit_cat['category_id'];?>"><?php endif;?>
        <div class="form-group"><label>Kategori Adı *</label><input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($edit_cat?$edit_cat['name']:($_POST['name']??''));?>" placeholder="Kategori adı"></div>
        <div class="form-group"><label>Açıklama</label><textarea name="description" class="form-control" rows="3" placeholder="Kısa açıklama"><?php echo htmlspecialchars($edit_cat?$edit_cat['description']??'':($_POST['description']??''));?></textarea></div>
        <?php if($edit_cat):?>
        <button type="submit" name="update_category" class="btn btn-primary btn-block">Güncelle</button>
        <a href="categories.php" class="btn btn-outline btn-block" style="margin-top:8px;">İptal</a>
        <?php else:?>
        <button type="submit" name="add_category" class="btn btn-primary btn-block">+ Ekle</button>
        <?php endif;?>
      </form>
    </div>
    <div class="admin-table-box">
      <div class="admin-table-header"><h2><?php echo $cats->num_rows;?> Kategori</h2></div>
      <table class="admin-table"><thead><tr><th>ID</th><th>Ad</th><th>Ürün Sayısı</th><th>İşlem</th></tr></thead><tbody>
      <?php while($c=$cats->fetch_assoc()):?>
      <tr><td><?php echo $c['category_id'];?></td>
      <td><strong><?php echo htmlspecialchars($c['name']);?></strong><?php if($c['description']):?><br><small style="color:var(--gray)"><?php echo htmlspecialchars(mb_substr($c['description'],0,50));?></small><?php endif;?></td>
      <td><span class="badge-stock <?php echo $c['cnt']>0?'ok':'zero';?>"><?php echo $c['cnt'];?></span></td>
      <td><div class="td-actions">
        <a href="categories.php?edit=<?php echo $c['category_id'];?>" class="btn btn-outline btn-sm">✏</a>
        <form method="post" style="display:inline;"><input type="hidden" name="category_id" value="<?php echo $c['category_id'];?>"><button type="submit" name="delete_category" class="btn btn-danger btn-sm" data-confirm="'<?php echo htmlspecialchars($c['name']);?>' silinsin mi?<?php echo $c['cnt']>0?' ('.$c['cnt'].' ürün etkilenir)':'';?>">🗑</button></form>
      </div></td></tr>
      <?php endwhile;?>
      </tbody></table>
    </div>
  </div>
</main>
</div>
<div id="toast-container"></div><script src="../../js/main.js"></script>
</body></html>
