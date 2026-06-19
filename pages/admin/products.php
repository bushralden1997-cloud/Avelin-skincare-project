<?php
require_once '../../includes/config.php';
if(!is_logged_in()||!is_admin())redirect('../login.php');
$search=trim($_GET['search']??'');$cat_f=(int)($_GET['category']??0);$brand_f=(int)($_GET['brand']??0);
$where="WHERE 1=1";$params=[];$types='';
if($search){$s="%$search%";$where.=" AND (p.name LIKE ? OR b.name LIKE ?)";$params[]=$s;$params[]=$s;$types.='ss';}
if($cat_f){$where.=" AND p.category_id=?";$params[]=$cat_f;$types.='i';}
if($brand_f){$where.=" AND p.brand_id=?";$params[]=$brand_f;$types.='i';}
$sql="SELECT p.*,b.name as brand_name,c.name as cat_name FROM products p JOIN brands b ON p.brand_id=b.brand_id JOIN categories c ON p.category_id=c.category_id $where ORDER BY p.created_at DESC";
$stmt = $conn->prepare($sql);

if($params){
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$products = $stmt->get_result();
$stmt->close(); 

$cats = $conn->query("SELECT * FROM categories ORDER BY name");
$brands = $conn->query("SELECT * FROM brands ORDER BY name");
$flash=$_SESSION['flash']??'';unset($_SESSION['flash']);
?>
<!DOCTYPE html><html lang="tr"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Ürünler — AVELIN Admin</title>
<link rel="stylesheet" href="../../css/style.css">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
</head><body>
<div class="admin-layout">
<?php include 'sidebar.php'; ?>
<main class="admin-main">
  <div class="admin-page-header"><h1>Ürün Yönetimi</h1><a href="product_add.php" class="btn btn-gold">+ Yeni Ürün</a></div>
  <?php if($flash): ?><div class="alert alert-success" data-flash="<?php echo htmlspecialchars($flash); ?>" data-flash-type="success"></div><?php endif; ?>
  <div class="admin-form-box" style="margin-bottom:20px;padding:18px 24px;">
    <form method="get" style="display:flex;gap:14px;align-items:flex-end;flex-wrap:wrap;">
      <div class="form-group" style="margin:0;flex:1;min-width:180px;"><label>Ara</label><input type="text" name="search" class="form-control" value="<?php echo htmlspecialchars($search); ?>" placeholder="Ürün adı..."></div>
      <div class="form-group" style="margin:0;min-width:160px;"><label>Kategori</label>
        <select name="category" class="form-control"><option value="">Tümü</option><?php while($c=$cats->fetch_assoc()):?><option value="<?php echo $c['category_id'];?>" <?php echo $cat_f==$c['category_id']?'selected':'';?>><?php echo htmlspecialchars($c['name']);?></option><?php endwhile;?></select>
      </div>
      <div class="form-group" style="margin:0;min-width:160px;"><label>Marka</label>
        <select name="brand" class="form-control"><option value="">Tümü</option><?php while($b=$brands->fetch_assoc()):?><option value="<?php echo $b['brand_id'];?>" <?php echo $brand_f==$b['brand_id']?'selected':'';?>><?php echo htmlspecialchars($b['name']);?></option><?php endwhile;?></select>
      </div>
      <button type="submit" class="btn btn-primary btn-sm">Ara</button>
      <a href="products.php" class="btn btn-outline btn-sm">Temizle</a>
    </form>
  </div>
  <div class="admin-table-box">
    <div class="admin-table-header"><h2><?php echo $products->num_rows; ?> Ürün</h2></div>
    <div style="overflow-x:auto;">
    <table class="admin-table"><thead><tr><th>Görsel</th><th>Ürün</th><th>Kategori</th><th>Marka</th><th>Fiyat</th><th>Stok</th><th>İşlem</th></tr></thead><tbody>
    <?php while($p=$products->fetch_assoc()): ?>
    <tr>
      <td><?php $img="../../img/products/".$p['image_url'];if(!empty($p['image_url'])&&file_exists($img)):?><img src="<?php echo $img;?>" class="td-img" alt=""><?php else:?><div class="td-img" style="display:flex;align-items:center;justify-content:center;">✦</div><?php endif;?></td>
      <td><strong><?php echo htmlspecialchars($p['name']);?></strong><br><small style="color:var(--gray);">★ <?php echo $p['rating'];?></small></td>
      <td><?php echo htmlspecialchars($p['cat_name']);?></td>
      <td><?php echo htmlspecialchars($p['brand_name']);?></td>
      <td><?php echo $p['discount_price']?'<span style="color:var(--burgundy);font-weight:700;">'.number_format($p['discount_price'],2,',','.').' TL</span><br><small style="text-decoration:line-through;color:var(--gray-lt);">'.number_format($p['price'],2,',','.').'</small>':number_format($p['price'],2,',','.').' TL';?></td>
      <td><span class="badge-stock <?php echo $p['stock_quantity']>10?'ok':($p['stock_quantity']>0?'low':'zero');?>"><?php echo $p['stock_quantity'];?></span></td>
      <td><div class="td-actions">
        <a href="../product.php?id=<?php echo $p['product_id'];?>" target="_blank" class="btn btn-outline btn-sm">👁</a>
        <a href="product_edit.php?id=<?php echo $p['product_id'];?>" class="btn btn-outline btn-sm">✏</a>
        <a href="product_delete.php?id=<?php echo $p['product_id'];?>" class="btn btn-danger btn-sm" data-confirm="'<?php echo htmlspecialchars($p['name']);?>' silinsin mi?">🗑</a>
      </div></td>
    </tr>
    <?php endwhile;?>
   </tbody></table>
    </div>
  </div>
</main>
</div>
<div id="toast-container"></div><script src="../../js/main.js"></script>
</body></html>
