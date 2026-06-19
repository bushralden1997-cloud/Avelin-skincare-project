<?php
require_once '../includes/config.php';
$page_title='Favorilerim';
if(!is_logged_in()){$_SESSION['redirect_after_login']='favorites.php';redirect('login.php');}
$user_id=(int)$_SESSION['user_id'];$success='';$error='';

if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['remove_favorite'],$_POST['product_id'])){
    $pid=(int)$_POST['product_id'];$del=$conn->prepare("DELETE FROM favorites WHERE user_id=? AND product_id=?");$del->bind_param("ii",$user_id,$pid);$success=$del->execute()?'Favorilerden kaldırıldı.':'Hata oluştu.';}
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['add_to_cart'],$_POST['product_id'])){
    $pid=(int)$_POST['product_id'];
    $cs=$conn->prepare("SELECT cart_id FROM cart WHERE user_id=?");$cs->bind_param("i",$user_id);$cs->execute();
    $cart=$cs->get_result()->fetch_assoc();
    if(!$cart){$nc=$conn->prepare("INSERT INTO cart (user_id) VALUES (?)");$nc->bind_param("i",$user_id);$nc->execute();$cart_id=$conn->insert_id;}else $cart_id=$cart['cart_id'];
    $ci=$conn->prepare("SELECT cart_item_id FROM cart_items WHERE cart_id=? AND product_id=?");$ci->bind_param("ii",$cart_id,$pid);$ci->execute();
    $item=$ci->get_result()->fetch_assoc();
    if($item){$upd=$conn->prepare("UPDATE cart_items SET quantity=quantity+1 WHERE cart_item_id=?");$upd->bind_param("i",$item['cart_item_id']);$upd->execute();}
    else{$ins=$conn->prepare("INSERT INTO cart_items (cart_id,product_id,quantity) VALUES (?,?,1)");$ins->bind_param("ii",$cart_id,$pid);$ins->execute();}
    $success='Ürün sepete eklendi!';}

$fav_stmt=$conn->prepare("SELECT f.*,p.product_id,p.name,p.price,p.discount_price,p.rating,p.stock_quantity,p.image_url,b.name as brand_name,c.name as cat_name FROM favorites f JOIN products p ON f.product_id=p.product_id JOIN brands b ON p.brand_id=b.brand_id JOIN categories c ON p.category_id=c.category_id WHERE f.user_id=? ORDER BY f.added_at DESC");
$fav_stmt->bind_param("i",$user_id);$fav_stmt->execute();$favs=$fav_stmt->get_result();$fav_count=$favs->num_rows;
include '../includes/header.php';
?>
<div class="breadcrumb"><div class="container"><a href="index.php">Ana Sayfa</a><span class="sep">/</span><span class="current">Favorilerim</span></div></div>
<main class="favorites-page"><div class="container">
  <?php if($success):?><div class="alert alert-success">✓ <?php echo $success;?></div><?php endif;?>
  <?php if($error):?><div class="alert alert-danger">✕ <?php echo $error;?></div><?php endif;?>
  <div class="page-header-bar"><h1>♡ Favorilerim</h1><?php if($fav_count>0):?><span class="results-badge"><?php echo $fav_count;?> ürün</span><?php endif;?></div>
  <?php if($fav_count>0):?>
  <div class="favorites-grid">
  <?php while($fav=$favs->fetch_assoc()):
    $has_disc=$fav['discount_price']&&$fav['discount_price']<$fav['price'];
    $disp=$has_disc?$fav['discount_price']:$fav['price'];
    $disc_pct=$has_disc?round(($fav['price']-$fav['discount_price'])/$fav['price']*100):0;
    $img="../img/products/".$fav['image_url'];$has_img=!empty($fav['image_url'])&&file_exists($img);
  ?>
  <div class="fav-card">
    <div class="fav-card-img">
      <?php if($has_img):?><img src="<?php echo $img;?>" alt="<?php echo htmlspecialchars($fav['name']);?>" loading="lazy"><?php else:?><div class="img-placeholder">✦</div><?php endif;?>
      <?php if($has_disc):?><span class="discount-badge">%<?php echo $disc_pct;?></span><?php endif;?>
    </div>
    <div class="fav-card-body">
      <div class="product-brand"><?php echo htmlspecialchars($fav['brand_name']);?></div>
      <div class="product-name" style="min-height:0;margin-bottom:8px;"><?php echo htmlspecialchars($fav['name']);?></div>
      <div class="rating-row"><span class="stars"><?php $r=round($fav['rating']);for($i=1;$i<=5;$i++)echo $i<=$r?'★':'☆';?></span></div>
      <div class="price-row"><span class="price-current"><?php echo number_format($disp,2,',','.');?> TL</span><?php if($has_disc):?><span class="price-old"><?php echo number_format($fav['price'],2,',','.');?> TL</span><?php endif;?></div>
      <?php if($fav['stock_quantity']>10):?><span class="stock-badge in-stock" style="display:inline-block;margin-bottom:10px;">✓ Stokta</span>
      <?php elseif($fav['stock_quantity']>0):?><span class="stock-badge low-stock" style="display:inline-block;margin-bottom:10px;">⚠ Sınırlı</span>
      <?php else:?><span class="stock-badge out-stock" style="display:inline-block;margin-bottom:10px;">✕ Stok Yok</span><?php endif;?>
      <div class="fav-card-actions">
        <a href="product.php?id=<?php echo $fav['product_id'];?>" class="btn btn-outline btn-sm">İncele</a>
        <form method="post" style="display:inline;">
          <input type="hidden" name="product_id" value="<?php echo $fav['product_id'];?>">
          <button type="submit" name="remove_favorite" class="btn btn-danger btn-sm" data-confirm="Favorilerden kaldır?">🗑</button>
        </form>
        <?php if($fav['stock_quantity']>0):?>
        <form method="post" style="grid-column:span 2;">
          <input type="hidden" name="product_id" value="<?php echo $fav['product_id'];?>">
          <button type="submit" name="add_to_cart" class="btn btn-primary btn-sm btn-block">Sepete Ekle</button>
        </form>
        <?php endif;?>
      </div>
    </div>
  </div>
  <?php endwhile;?>
  </div>
  <?php else:?>
  <div class="empty-state"><div class="empty-icon">♡</div><h2>Favori Listeniz Boş</h2><p>Beğendiğiniz ürünleri favorilerinize ekleyin.</p><a href="products.php" class="btn btn-primary btn-lg">Alışverişe Başla</a></div>
  <?php endif;?>
</div></main>
<?php include '../includes/footer.php';?>
