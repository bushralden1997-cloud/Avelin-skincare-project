<?php
require_once '../includes/config.php';
if(!isset($_GET['id'])||!is_numeric($_GET['id']))redirect('products.php');
$product_id=(int)$_GET['id'];

$stmt=$conn->prepare("SELECT p.*,b.name as brand_name,c.name as cat_name FROM products p JOIN brands b ON p.brand_id=b.brand_id JOIN categories c ON p.category_id=c.category_id WHERE p.product_id=?");
$stmt->bind_param("i",$product_id);$stmt->execute();
$product=$stmt->get_result()->fetch_assoc();
if(!$product)redirect('products.php');

$is_logged_in=is_logged_in();$user_id=$is_logged_in?(int)$_SESSION['user_id']:null;
$success='';$error='';

// Add to favorites
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['add_to_favorites'])){
    if(!$is_logged_in){$_SESSION['redirect_after_login']="product.php?id=$product_id";redirect('login.php');}
    $chk=$conn->prepare("SELECT favorite_id FROM favorites WHERE user_id=? AND product_id=?");$chk->bind_param("ii",$user_id,$product_id);$chk->execute();
    if($chk->get_result()->num_rows>0)$error='Bu ürün zaten favorilerinizde.';
    else{$ins=$conn->prepare("INSERT INTO favorites (user_id,product_id) VALUES (?,?)");$ins->bind_param("ii",$user_id,$product_id);$success=$ins->execute()?'Favorilere eklendi!':'Bir hata oluştu.';}
}

// Add to cart
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['add_to_cart'])){
    $qty=max(1,(int)($_POST['quantity']??1));
    if($qty>$product['stock_quantity'])$error='Stok yetersiz.';
    elseif($is_logged_in){
        $cs=$conn->prepare("SELECT cart_id FROM cart WHERE user_id=?");$cs->bind_param("i",$user_id);$cs->execute();
        $cart=$cs->get_result()->fetch_assoc();
        if(!$cart){$nc=$conn->prepare("INSERT INTO cart (user_id) VALUES (?)");$nc->bind_param("i",$user_id);$nc->execute();$cart_id=$conn->insert_id;}
        else $cart_id=$cart['cart_id'];
        $ci=$conn->prepare("SELECT cart_item_id,quantity FROM cart_items WHERE cart_id=? AND product_id=?");$ci->bind_param("ii",$cart_id,$product_id);$ci->execute();
        $item=$ci->get_result()->fetch_assoc();
        if($item){$nq=min($item['quantity']+$qty,$product['stock_quantity']);$upd=$conn->prepare("UPDATE cart_items SET quantity=? WHERE cart_item_id=?");$upd->bind_param("ii",$nq,$item['cart_item_id']);$upd->execute();}
        else{$ins=$conn->prepare("INSERT INTO cart_items (cart_id,product_id,quantity) VALUES (?,?,?)");$ins->bind_param("iii",$cart_id,$product_id,$qty);$ins->execute();}
        $success='Sepete eklendi!';
    }else{
        if(!isset($_SESSION['cart']))$_SESSION['cart']=[];
        $_SESSION['cart'][$product_id]=min(($_SESSION['cart'][$product_id]??0)+$qty,$product['stock_quantity']);
        $success='Sepete eklendi!';
    }
}

// Add review
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['add_review'])){
    if(!$is_logged_in){$_SESSION['redirect_after_login']="product.php?id=$product_id";redirect('login.php');}
    $rating=(int)($_POST['rating']??0);$comment=trim(htmlspecialchars($_POST['comment']??''));
    if($rating<1||$rating>5)$error='Lütfen bir puan seçin.';
    else{
        $chk=$conn->prepare("SELECT review_id FROM reviews WHERE user_id=? AND product_id=?");$chk->bind_param("ii",$user_id,$product_id);$chk->execute();
        $ex=$chk->get_result()->fetch_assoc();
        if($ex){$upd=$conn->prepare("UPDATE reviews SET rating=?,comment=?,created_at=NOW() WHERE review_id=?");$upd->bind_param("isi",$rating,$comment,$ex['review_id']);$upd->execute();$success='Değerlendirme güncellendi!';}
        else{$ins=$conn->prepare("INSERT INTO reviews (product_id,user_id,rating,comment) VALUES (?,?,?,?)");$ins->bind_param("iiis",$product_id,$user_id,$rating,$comment);$ins->execute();$success='Değerlendirme eklendi!';}
        $conn->query("UPDATE products SET rating=(SELECT AVG(rating) FROM reviews WHERE product_id=$product_id) WHERE product_id=$product_id");
        $stmt->execute();$product=$stmt->get_result()->fetch_assoc();
    }
}

// Load reviews
$rev_stmt=$conn->prepare("SELECT r.*,u.name as uname,u.username FROM reviews r JOIN users u ON r.user_id=u.user_id WHERE r.product_id=? ORDER BY r.created_at DESC");
$rev_stmt->bind_param("i",$product_id);$rev_stmt->execute();
$reviews=$rev_stmt->get_result();$rev_count=$reviews->num_rows;

// Similar
$sim=$conn->prepare("SELECT p.*,b.name as brand_name FROM products p JOIN brands b ON p.brand_id=b.brand_id WHERE p.category_id=? AND p.product_id!=? ORDER BY p.rating DESC LIMIT 4");
$sim->bind_param("ii",$product['category_id'],$product_id);$sim->execute();$similar=$sim->get_result();

$is_fav=$is_logged_in&&is_favorite($user_id,$product_id);
$stock=(int)$product['stock_quantity'];
$img_path="../img/products/".$product['image_url'];
$has_img=true;
$page_title=$product['name'];
include '../includes/header.php';
?>
<div class="breadcrumb"><div class="container">
  <a href="index.php">Ana Sayfa</a><span class="sep">/</span>
  <a href="products.php">Ürünler</a><span class="sep">/</span>
  <a href="products.php?category=<?php echo $product['category_id'];?>"><?php echo htmlspecialchars($product['cat_name']);?></a>
  <span class="sep">/</span><span class="current"><?php echo htmlspecialchars($product['name']);?></span>
</div></div>

<main class="product-detail">
<div class="container">
  <?php if($success):?><div class="alert alert-success">✓ <?php echo $success;?></div><?php endif;?>
  <?php if($error):  ?><div class="alert alert-danger">✕ <?php echo $error;?></div><?php endif;?>

  <div class="product-detail-grid">
    <!-- GÖRSEL -->
    <div>
      <div class="product-gallery-main">
        <?php if($has_img):?><img id="mainProductImg" src="<?php echo $img_path;?>" alt="<?php echo htmlspecialchars($product['name']);?>"><?php else:?><div style="font-size:80px;color:var(--border);">✦</div><?php endif;?>
      </div>
      <?php if($product['discount_price']&&$product['discount_price']<$product['price']):?>
      <?php $disc_pct=round(($product['price']-$product['discount_price'])/$product['price']*100);?>
      <div style="margin-top:12px;"><span class="discount-badge" style="position:static;display:inline-block;">%<?php echo $disc_pct;?> İndirim</span></div>
      <?php endif;?>
    </div>

    <!-- BİLGİLER -->
    <div class="product-info-panel">
      <div class="brand-tag"><?php echo htmlspecialchars($product['brand_name']);?></div>
      <h1><?php echo htmlspecialchars($product['name']);?></h1>
      <div class="rating-row">
        <span class="stars"><?php $r=round($product['rating']);for($i=1;$i<=5;$i++)echo $i<=$r?'★':'☆';?></span>
        <span class="rating-count">(<?php echo $rev_count;?> değerlendirme)</span>
      </div>
      <div class="price-block">
        <?php if($product['discount_price']&&$product['discount_price']<$product['price']):?>
          <span class="price-current"><?php echo format_price($product['discount_price']);?></span>
          <span class="price-old"><?php echo format_price($product['price']);?></span>
        <?php else:?>
          <span class="price-current"><?php echo format_price($product['price']);?></span>
        <?php endif;?>
      </div>
      <?php if($stock>10):?><span class="stock-badge in-stock">✓ Stokta</span>
      <?php elseif($stock>0):?><span class="stock-badge low-stock">⚠ Sınırlı Stok (<?php echo $stock;?> adet)</span>
      <?php else:?><span class="stock-badge out-stock">✕ Stokta Yok</span><?php endif;?>

      <?php if($stock>0):?>
      <form method="post" action="product.php?id=<?php echo $product_id;?>">
        <div class="qty-add-row">
          <div class="qty-input">
            <button type="button" class="qty-dec">−</button>
            <input type="number" name="quantity" value="1" min="1" max="<?php echo $stock;?>">
            <button type="button" class="qty-inc">+</button>
          </div>
          <button type="submit" name="add_to_cart" class="btn btn-primary btn-lg">Sepete Ekle</button>
        </div>
      </form>
      <?php endif;?>

      <form method="post" action="product.php?id=<?php echo $product_id;?>" style="margin-top:8px;">
        <button type="submit" name="add_to_favorites" class="btn <?php echo $is_fav?'btn-dark':'btn-outline';?> btn-block">
          <?php echo $is_fav?'♥ Favorilerde':'♡ Favorilere Ekle';?>
        </button>
      </form>

      <!-- SADECE Bu Bilgiler -->
      <dl class="product-meta-list">
        <dt>Marka</dt><dd><?php echo htmlspecialchars($product['brand_name']);?></dd>
        <dt>Kategori</dt><dd><?php echo htmlspecialchars($product['cat_name']);?></dd>
        <?php if(!empty($product['skin_type'])):?><dt>Cilt Tipi</dt><dd><?php echo htmlspecialchars($product['skin_type']);?></dd><?php endif;?>
        <?php if(!empty($product['volume'])):?><dt>İçerik</dt><dd><?php echo htmlspecialchars($product['volume']);?></dd><?php endif;?>
      </dl>
    </div>
  </div>

  <!-- SEKMELER — Sadece Değerlendirmeler -->
  <div class="product-tabs">
    <div class="tab-nav">
      <button class="active" data-tab="rev-pane">Değerlendirmeler (<?php echo $rev_count;?>)</button>
    </div>
    <div id="rev-pane" class="tab-pane active">
      <?php if($rev_count>0):
        $dist=[0,0,0,0,0];$reviews->data_seek(0);while($rv=$reviews->fetch_assoc())$dist[$rv['rating']-1]++;
      ?>
      <div class="reviews-summary">
        <div class="avg-rating-box">
          <div class="avg-rating-num"><?php echo number_format($product['rating'],1);?></div>
          <div class="avg-rating-stars"><?php $r=round($product['rating']);for($i=1;$i<=5;$i++)echo $i<=$r?'★':'☆';?></div>
          <div class="avg-rating-count"><?php echo $rev_count;?> değerlendirme</div>
        </div>
        <div class="rating-bars">
          <?php for($i=5;$i>=1;$i--):$pct=$rev_count?round($dist[$i-1]/$rev_count*100):0;?>
          <div class="rating-bar-row">
            <span class="rating-bar-label"><?php echo $i;?>★</span>
            <div class="rating-bar-track"><div class="rating-bar-fill" style="width:<?php echo $pct;?>%"></div></div>
            <span class="rating-bar-pct"><?php echo $pct;?>%</span>
          </div>
          <?php endfor;?>
        </div>
      </div>
      <?php endif;?>

      <?php if($is_logged_in):?>
      <div class="review-form-box">
        <h3>Değerlendirme Yap</h3>
        <form method="post" action="product.php?id=<?php echo $product_id;?>">
          <div class="star-rating">
            <?php for($i=5;$i>=1;$i--):?><input type="radio" id="star<?php echo $i;?>" name="rating" value="<?php echo $i;?>"><label for="star<?php echo $i;?>">★</label><?php endfor;?>
          </div>
          <div class="form-group">
            <label>Yorumunuz</label>
            <textarea name="comment" class="form-control" rows="4" required placeholder="Ürün hakkında düşüncelerinizi paylaşın..."></textarea>
          </div>
          <button type="submit" name="add_review" class="btn btn-primary">Gönder</button>
        </form>
      </div>
      <?php else:?>
      <div class="review-form-box" style="text-align:center;padding:24px;">
        <p>Değerlendirme yapmak için <a href="login.php" style="color:var(--burg);font-weight:600;">giriş yapın</a>.</p>
      </div>
      <?php endif;?>

      <div>
        <?php $reviews->data_seek(0);if($rev_count>0):while($rv=$reviews->fetch_assoc()):?>
        <div class="review-item">
          <div class="review-header">
            <span class="reviewer-name"><?php echo htmlspecialchars($rv['uname']?:$rv['username']);?></span>
            <span class="review-date"><?php echo format_date($rv['created_at']);?></span>
          </div>
          <div class="review-stars"><?php for($i=1;$i<=5;$i++)echo $i<=$rv['rating']?'★':'☆';?></div>
          <div class="review-text"><?php echo nl2br(htmlspecialchars($rv['comment']));?></div>
        </div>
        <?php endwhile;else:?><p style="color:var(--gray);padding:16px 0;">Henüz değerlendirme yok. İlk değerlendirmeyi siz yapın!</p><?php endif;?>
      </div>
    </div>
  </div>

  <!-- BENZER ÜRÜNLER -->
  <?php if($similar->num_rows>0):?>
  <div class="similar-section">
    <div class="section-title"><h2>Benzer Ürünler</h2><div class="title-ornament"><span class="ornament-dot">✦</span></div></div>
    <div class="products-grid">
    <?php while($s=$similar->fetch_assoc()):$sd=$s['discount_price']&&$s['discount_price']<$s['price'];$sp=$sd?$s['discount_price']:$s['price'];$si="../img/products/".$s['image_url'];$shi=!empty($s['image_url'])&&file_exists($si);?>
    <div class="product-card">
      <div class="product-card-img"><?php if($shi):?><img src="<?php echo $si;?>" alt="<?php echo htmlspecialchars($s['name']);?>" loading="lazy"><?php else:?><div class="img-placeholder">✦</div><?php endif;?></div>
      <div class="product-card-body">
        <div class="product-brand"><?php echo htmlspecialchars($s['brand_name']);?></div>
        <h3 class="product-name"><a href="product.php?id=<?php echo $s['product_id'];?>"><?php echo htmlspecialchars($s['name']);?></a></h3>
        <div class="price-row"><span class="price-current"><?php echo number_format($sp,2,',','.');?> TL</span><?php if($sd):?><span class="price-old"><?php echo number_format($s['price'],2,',','.');?> TL</span><?php endif;?></div>
        <a href="product.php?id=<?php echo $s['product_id'];?>" class="btn btn-outline btn-sm btn-block">İncele</a>
      </div>
    </div>
    <?php endwhile;?>
    </div>
  </div>
  <?php endif;?>
</div>
</main>
<?php include '../includes/footer.php';?>
