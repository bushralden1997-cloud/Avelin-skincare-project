<?php
require_once '../../includes/config.php';
if(!is_logged_in()||!is_admin())redirect('../login.php');

$tp=(int)$conn->query("SELECT COUNT(*) as c FROM products")->fetch_assoc()['c'];
$tu=(int)$conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$tc=(int)$conn->query("SELECT COUNT(*) as c FROM categories")->fetch_assoc()['c'];
$ls=(int)$conn->query("SELECT COUNT(*) as c FROM products WHERE stock_quantity<=5")->fetch_assoc()['c'];
$recent=$conn->query("SELECT p.*,b.name as bn FROM products p JOIN brands b ON p.brand_id=b.brand_id ORDER BY p.created_at DESC LIMIT 10");
?>
<!DOCTYPE html><html lang="tr"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Dashboard — AVELIN Admin</title>
<link rel="stylesheet" href="../../css/style.css">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
</head><body>
<div class="admin-layout">
<?php include 'sidebar.php';?>
<main class="admin-main">
  <div class="admin-page-header">
    <h1>Dashboard</h1>
    <span style="font-size:12px;color:var(--gray);">Hoş geldiniz, <strong><?php echo htmlspecialchars($_SESSION['user_name']??'Admin');?></strong></span>
  </div>
  <div class="admin-stats">
    <div class="admin-stat-card"><div class="admin-stat-icon burg">📦</div><div><div class="admin-stat-value"><?php echo $tp;?></div><div class="admin-stat-label">Toplam Ürün</div></div></div>
    <div class="admin-stat-card"><div class="admin-stat-icon gold">◉</div><div><div class="admin-stat-value"><?php echo $tu;?></div><div class="admin-stat-label">Kullanıcı</div></div></div>
    <div class="admin-stat-card"><div class="admin-stat-icon ivory">◇</div><div><div class="admin-stat-value"><?php echo $tc;?></div><div class="admin-stat-label">Kategori</div></div></div>
    <div class="admin-stat-card"><div class="admin-stat-icon warn">⚠</div><div><div class="admin-stat-value" style="color:var(--danger);"><?php echo $ls;?></div><div class="admin-stat-label">Düşük Stok</div></div></div>
  </div>
  <div class="admin-table-box">
    <div class="admin-table-header">
      <h2>Son Ürünler</h2>
      <div style="display:flex;gap:8px;">
        <a href="product_add.php" class="btn btn-gold btn-sm">+ Yeni Ürün</a>
        <a href="products.php" class="btn btn-outline btn-sm">Tüm Ürünler</a>
      </div>
    </div>
    <div style="overflow-x:auto;">
    <table class="admin-table"><thead><tr><th>Görsel</th><th>Ürün</th><th>Marka</th><th>Fiyat</th><th>Stok</th><th>İşlem</th></tr></thead><tbody>
    <?php while($p=$recent->fetch_assoc()):$img="../../img/products/".$p['image_url'];?>
    <tr>
      <td><?php if(!empty($p['image_url'])&&file_exists($img)):?><img src="<?php echo $img;?>" class="td-img" alt=""><?php else:?><div class="td-img">✦</div><?php endif;?></td>
      <td><strong><?php echo htmlspecialchars($p['name']);?></strong><br><small style="color:var(--gray);">★<?php echo $p['rating'];?></small></td>
      <td><?php echo htmlspecialchars($p['bn']);?></td>
      <td style="color:var(--burg);font-weight:700;"><?php echo number_format($p['discount_price']?:$p['price'],2,',','.');?> TL</td>
      <td><span class="badge-stock <?php echo $p['stock_quantity']>10?'ok':($p['stock_quantity']>0?'low':'zero');?>"><?php echo $p['stock_quantity'];?></span></td>
      <td><div class="td-actions">
        <a href="product_edit.php?id=<?php echo $p['product_id'];?>" class="btn btn-outline btn-sm">✏</a>
        <a href="product_delete.php?id=<?php echo $p['product_id'];?>" class="btn btn-danger btn-sm" data-confirm="Silmek istiyor musunuz?">🗑</a>
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
