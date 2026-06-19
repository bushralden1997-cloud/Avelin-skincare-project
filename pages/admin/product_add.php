<?php
require_once '../../includes/config.php';
if(!is_logged_in()||!is_admin())redirect('../login.php');
$errors=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
    $name=trim(htmlspecialchars($_POST['name']??''));$desc=trim(htmlspecialchars($_POST['description']??''));
    $short=trim(htmlspecialchars($_POST['short_description']??''));
    $price=filter_input(INPUT_POST,'price',FILTER_VALIDATE_FLOAT);
    $disc=trim($_POST['discount_price']??'');$disc=($disc!==''&&$disc>0)?filter_var($disc,FILTER_VALIDATE_FLOAT):null;
    $stock=(int)($_POST['stock_quantity']??0);$cat_id=(int)($_POST['category_id']??0);$brand_id=(int)($_POST['brand_id']??0);
    $skin=trim(htmlspecialchars($_POST['skin_type']??''));$vol=trim(htmlspecialchars($_POST['volume']??''));
    if(empty($name))$errors[]='Ürün adı zorunludur.';if(!$price||$price<=0)$errors[]='Geçerli bir fiyat girin.';
    if(!$cat_id)$errors[]='Kategori seçiniz.';if(!$brand_id)$errors[]='Marka seçiniz.';
    $image_url='';
    if(!empty($_FILES['image']['name'])&&$_FILES['image']['error']===UPLOAD_ERR_OK){
        $ext=strtolower(pathinfo($_FILES['image']['name'],PATHINFO_EXTENSION));
        if(!in_array($ext,['jpg','jpeg','png','webp']))$errors[]='Geçersiz dosya türü.';
        elseif($_FILES['image']['size']>5*1024*1024)$errors[]='Dosya 5MB\'dan büyük.';
        else{$fn=uniqid('p_').'.'.$ext;if(move_uploaded_file($_FILES['image']['tmp_name'],'../../img/products/'.$fn))$image_url=$fn;else$errors[]='Dosya kaydedilemedi.';}
    }
    if(empty($errors)){
        $s=$conn->prepare("INSERT INTO products (name,description,short_description,price,discount_price,stock_quantity,category_id,brand_id,image_url,skin_type,volume) VALUES(?,?,?,?,?,?,?,?,?,?,?)");
        $s->bind_param("sssddiissss",$name,$desc,$short,$price,$disc,$stock,$cat_id,$brand_id,$image_url,$skin,$vol);
        if($s->execute()){$_SESSION['flash']="\"$name\" eklendi.";redirect('products.php');}
        else $errors[]='Veritabanı hatası: '.$conn->error;
    }
}
$cats=$conn->query("SELECT * FROM categories ORDER BY name");$brands=$conn->query("SELECT * FROM brands ORDER BY name");
?>
<!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Ürün Ekle — AVELIN Admin</title>
<link rel="stylesheet" href="../../css/style.css">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
</head><body>
<div class="admin-layout"><?php include 'sidebar.php';?>
<main class="admin-main">
  <div class="admin-page-header"><h1>Yeni Ürün Ekle</h1><a href="products.php" class="btn btn-outline btn-sm">← Listeye Dön</a></div>
  <?php if($errors):?><div class="alert alert-danger"><?php foreach($errors as $e)echo "<div>• $e</div>";?></div><?php endif;?>
  <form method="post" enctype="multipart/form-data">
    <div style="display:grid;grid-template-columns:2fr 1fr;gap:22px;">
      <div>
        <div class="admin-form-box">
          <h2>Ürün Bilgileri</h2>
          <div class="form-group"><label>Ürün Adı *</label><input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($_POST['name']??'');?>"></div>
          <div class="form-group"><label>Kısa Açıklama</label><input type="text" name="short_description" class="form-control" value="<?php echo htmlspecialchars($_POST['short_description']??'');?>" placeholder="Ürün detay sayfasında özet..."></div>
          <div class="form-group"><label>Tam Açıklama</label><textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($_POST['description']??'');?></textarea></div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="form-group"><label>Cilt Tipi</label>
              <select name="skin_type" class="form-control"><option value="">Seçin...</option>
                <?php foreach(['Tüm Cilt Tipleri','Normal Cilt','Kuru Cilt','Yağlı Cilt','Karma Cilt','Hassas Cilt'] as $st):?>
                <option value="<?php echo $st;?>" <?php echo ($_POST['skin_type']??'')===$st?'selected':'';?>><?php echo $st;?></option>
                <?php endforeach;?>
              </select></div>
            <div class="form-group"><label>İçerik Miktarı</label><input type="text" name="volume" class="form-control" value="<?php echo htmlspecialchars($_POST['volume']??'');?>" placeholder="50 ml, 200 g..."></div>
          </div>
        </div>
      </div>
      <div style="display:flex;flex-direction:column;gap:18px;">
        <div class="admin-form-box">
          <h2>Görsel</h2>
          <div style="text-align:center;">
            <div class="img-preview" id="imgPreview" style="width:100%;height:175px;margin-bottom:12px;">✦</div>
            <input type="file" id="imageInput" name="image" accept="image/*" style="display:none;">
            <button type="button" onclick="document.getElementById('imageInput').click()" class="btn btn-outline btn-sm btn-block">Görsel Seç</button>
            <div class="form-hint" style="margin-top:8px;">JPG,PNG,WEBP — Maks. 5MB</div>
          </div>
        </div>
        <div class="admin-form-box">
          <h2>Sınıflandırma</h2>
          <div class="form-group"><label>Kategori *</label>
            <select name="category_id" class="form-control" required><option value="">Seçin...</option>
              <?php while($c=$cats->fetch_assoc()):?><option value="<?php echo $c['category_id'];?>" <?php echo ($_POST['category_id']??'')==$c['category_id']?'selected':'';?>><?php echo htmlspecialchars($c['name']);?></option><?php endwhile;?>
            </select></div>
          <div class="form-group"><label>Marka *</label>
            <select name="brand_id" class="form-control" required><option value="">Seçin...</option>
              <?php while($b=$brands->fetch_assoc()):?><option value="<?php echo $b['brand_id'];?>" <?php echo ($_POST['brand_id']??'')==$b['brand_id']?'selected':'';?>><?php echo htmlspecialchars($b['name']);?></option><?php endwhile;?>
            </select></div>
        </div>
        <div class="admin-form-box">
          <h2>Fiyat & Stok</h2>
          <div class="form-group"><label>Normal Fiyat (TL) *</label><input type="number" name="price" class="form-control" required step="0.01" min="0" value="<?php echo htmlspecialchars($_POST['price']??'');?>" placeholder="0.00"></div>
          <div class="form-group"><label>İndirimli Fiyat</label><input type="number" name="discount_price" class="form-control" step="0.01" min="0" value="<?php echo htmlspecialchars($_POST['discount_price']??'');?>" placeholder="Boş bırakılabilir"></div>
          <div class="form-group"><label>Stok Miktarı *</label><input type="number" name="stock_quantity" class="form-control" required min="0" value="<?php echo htmlspecialchars($_POST['stock_quantity']??'0');?>"></div>
        </div>
        <button type="submit" class="btn btn-primary btn-block btn-lg">✓ Ürünü Kaydet</button>
      </div>
    </div>
  </form>
</main></div>
<div id="toast-container"></div><script src="../../js/main.js"></script></body></html>
