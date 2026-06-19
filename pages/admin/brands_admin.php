<?php
require_once '../../includes/config.php';
if(!is_logged_in()||!is_admin())redirect('../login.php');
$success='';$error='';$edit_brand=null;

if($_SERVER['REQUEST_METHOD']==='POST'){
    if(isset($_POST['add_brand'])){
        $name=trim(htmlspecialchars($_POST['name']??''));$desc=trim(htmlspecialchars($_POST['description']??''));
        $country=trim(htmlspecialchars($_POST['country']??''));$year=(int)($_POST['founded_year']??0);
        if(empty($name))$error='Marka adı zorunludur.';
        else{$ins=$conn->prepare("INSERT INTO brands (name,description,country,founded_year) VALUES (?,?,?,?)");$ins->bind_param("sssi",$name,$desc,$country,$year);$success=$ins->execute()?"\"$name\" markası eklendi.":'Hata oluştu.';}
    }
    if(isset($_POST['update_brand'])){
        $bid=(int)$_POST['brand_id'];$name=trim(htmlspecialchars($_POST['name']??''));$desc=trim(htmlspecialchars($_POST['description']??''));
        $country=trim(htmlspecialchars($_POST['country']??''));$year=(int)($_POST['founded_year']??0);
        if(empty($name))$error='Marka adı zorunludur.';
        else{$upd=$conn->prepare("UPDATE brands SET name=?,description=?,country=?,founded_year=? WHERE brand_id=?");$upd->bind_param("sssii",$name,$desc,$country,$year,$bid);$success=$upd->execute()?"\"$name\" güncellendi.":'Hata oluştu.';}
    }
    if(isset($_POST['delete_brand'])){
        $bid=(int)$_POST['brand_id'];$del=$conn->prepare("DELETE FROM brands WHERE brand_id=?");$del->bind_param("i",$bid);$success=$del->execute()?'Marka silindi.':'Hata oluştu.';}
}
if(isset($_GET['edit'])){$r=$conn->prepare("SELECT * FROM brands WHERE brand_id=?");$r->bind_param("i",(int)$_GET['edit']);$r->execute();$edit_brand=$r->get_result()->fetch_assoc();}
$brands=$conn->query("SELECT b.*,COUNT(p.product_id) as cnt FROM brands b LEFT JOIN products p ON b.brand_id=p.brand_id GROUP BY b.brand_id ORDER BY b.name");
?>
<!DOCTYPE html><html lang="tr"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Markalar — AVELIN Admin</title>
<link rel="stylesheet" href="../../css/style.css">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
</head><body>
<div class="admin-layout">
<?php include 'sidebar.php'; ?>
<main class="admin-main">
  <div class="admin-page-header"><h1>Markalar</h1></div>
  <?php if($success):?><div class="alert alert-success">✓ <?php echo $success;?></div><?php endif;?>
  <?php if($error):?><div class="alert alert-danger">✕ <?php echo $error;?></div><?php endif;?>
  <div style="display:grid;grid-template-columns:1fr 1.6fr;gap:24px;">
    <div class="admin-form-box">
      <h2><?php echo $edit_brand?'Marka Düzenle':'Yeni Marka Ekle';?></h2>
      <form method="post">
        <?php if($edit_brand):?><input type="hidden" name="brand_id" value="<?php echo $edit_brand['brand_id'];?>"><?php endif;?>
        <div class="form-group"><label>Marka Adı *</label><input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($edit_brand?$edit_brand['name']:'');?>" placeholder="Marka adı"></div>
        <div class="form-group"><label>Açıklama</label><textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($edit_brand?$edit_brand['description']??'':'');?></textarea></div>
        <div class="form-group"><label>Ülke</label><input type="text" name="country" class="form-control" value="<?php echo htmlspecialchars($edit_brand?$edit_brand['country']??'':'');?>" placeholder="Almanya, Fransa..."></div>
        <div class="form-group"><label>Kuruluş Yılı</label><input type="number" name="founded_year" class="form-control" min="1800" max="2030" value="<?php echo $edit_brand?$edit_brand['founded_year']??'':'';?>" placeholder="1990"></div>
        <?php if($edit_brand):?>
        <button type="submit" name="update_brand" class="btn btn-primary btn-block">Güncelle</button>
        <a href="brands_admin.php" class="btn btn-outline btn-block" style="margin-top:8px;">İptal</a>
        <?php else:?>
        <button type="submit" name="add_brand" class="btn btn-primary btn-block">+ Ekle</button>
        <?php endif;?>
      </form>
    </div>
    <div class="admin-table-box">
      <div class="admin-table-header"><h2><?php echo $brands->num_rows;?> Marka</h2></div>
      <table class="admin-table"><thead><tr><th>Marka</th><th>Ülke</th><th>Yıl</th><th>Ürün</th><th>İşlem</th></tr></thead><tbody>
      <?php while($b=$brands->fetch_assoc()):?>
      <tr><td><strong><?php echo htmlspecialchars($b['name']);?></strong></td>
      <td><?php echo htmlspecialchars($b['country']??'');?></td>
      <td><?php echo $b['founded_year']??'-';?></td>
      <td><span class="badge-stock <?php echo $b['cnt']>0?'ok':'zero';?>"><?php echo $b['cnt'];?></span></td>
      <td><div class="td-actions">
        <a href="brands_admin.php?edit=<?php echo $b['brand_id'];?>" class="btn btn-outline btn-sm">✏</a>
        <form method="post" style="display:inline;"><input type="hidden" name="brand_id" value="<?php echo $b['brand_id'];?>"><button type="submit" name="delete_brand" class="btn btn-danger btn-sm" data-confirm="'<?php echo htmlspecialchars($b['name']);?>' silinsin mi?">🗑</button></form>
      </div></td></tr>
      <?php endwhile;?>
      </tbody></table>
    </div>
  </div>
</main>
</div>
<div id="toast-container"></div><script src="../../js/main.js"></script>
</body></html>
