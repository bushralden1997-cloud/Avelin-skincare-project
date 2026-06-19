<?php
require_once '../includes/config.php';
$page_title='Sepetim';$is_logged=$is_logged_in=is_logged_in();$user_id=$is_logged?(int)$_SESSION['user_id']:null;$success='';$error='';

if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['remove_item'])){
    $iid=(int)($_POST['cart_item_id']??0);
    if($is_logged){$del=$conn->prepare("DELETE ci FROM cart_items ci JOIN cart c ON ci.cart_id=c.cart_id WHERE ci.cart_item_id=? AND c.user_id=?");$del->bind_param("ii",$iid,$user_id);$del->execute();}
    else unset($_SESSION['cart'][(int)($_POST['product_id']??0)]);
    $success='Ürün sepetten kaldırıldı.';}
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['update_qty'])&&$is_logged){
    $iid=(int)($_POST['cart_item_id']??0);$qty=max(1,(int)($_POST['quantity']??1));
    $upd=$conn->prepare("UPDATE cart_items ci JOIN cart c ON ci.cart_id=c.cart_id SET ci.quantity=? WHERE ci.cart_item_id=? AND c.user_id=?");$upd->bind_param("iii",$qty,$iid,$user_id);$upd->execute();}
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['move_to_fav'],$_POST['product_id'])&&$is_logged){
    $pid=(int)$_POST['product_id'];$chk=$conn->prepare("SELECT favorite_id FROM favorites WHERE user_id=? AND product_id=?");$chk->bind_param("ii",$user_id,$pid);$chk->execute();
    if($chk->get_result()->num_rows===0){$ins=$conn->prepare("INSERT INTO favorites (user_id,product_id) VALUES (?,?)");$ins->bind_param("ii",$user_id,$pid);$ins->execute();}
    $success='Ürün favorilere taşındı!';}

$items=[];$total=0;
if($is_logged){
    $stmt=$conn->prepare("SELECT ci.cart_item_id,ci.quantity,p.product_id,p.name,p.price,p.discount_price,p.image_url,p.stock_quantity,b.name as brand_name FROM cart c JOIN cart_items ci ON c.cart_id=ci.cart_id JOIN products p ON ci.product_id=p.product_id JOIN brands b ON p.brand_id=b.brand_id WHERE c.user_id=? ORDER BY ci.cart_item_id");
    $stmt->bind_param("i",$user_id);$stmt->execute();$res=$stmt->get_result();
    while($row=$res->fetch_assoc()){$row['unit']=$row['discount_price']&&$row['discount_price']<$row['price']?(float)$row['discount_price']:(float)$row['price'];$row['line']=$row['unit']*$row['quantity'];$total+=$row['line'];$items[]=$row;}
    }elseif(!empty($_SESSION['cart'])){
    $ids=implode(',',array_map('intval',array_keys($_SESSION['cart'])));
    $res=$conn->query("SELECT p.*,b.name as brand_name FROM products p JOIN brands b ON p.brand_id=b.brand_id WHERE p.product_id IN ($ids)");
    while($row=$res->fetch_assoc()){$qty=(int)$_SESSION['cart'][$row['product_id']];$row['quantity']=$qty;$row['cart_item_id']=$row['product_id'];$row['unit']=$row['discount_price']&&$row['discount_price']<$row['price']?(float)$row['discount_price']:(float)$row['price'];$row['line']=$row['unit']*$qty;$total+=$row['line'];$items[]=$row;}}
include '../includes/header.php';
?>
<div class="breadcrumb"><div class="container"><a href="index.php">Ana Sayfa</a><span class="sep">/</span><span class="current">Sepetim</span></div></div>
<main style="padding:30px 0 60px;"><div class="container">
  <?php if($success):?><div class="alert alert-success">✓ <?php echo $success;?></div><?php endif;?>
  <?php if($error):?><div class="alert alert-danger">✕ <?php echo $error;?></div><?php endif;?>
  <div class="page-header-bar"><h1>Sepetim</h1><?php if(count($items)>0):?><span class="results-badge"><?php echo count($items);?> ürün</span><?php endif;?></div>
  <?php if(empty($items)):?>
  <div class="empty-state"><div class="empty-icon">◻</div><h2>Sepetiniz Boş</h2><p>Henüz sepetinize ürün eklemediniz.</p><a href="products.php" class="btn btn-primary btn-lg">Alışverişe Başla</a></div>
  <?php else:?>
  <div style="display:grid;grid-template-columns:1fr 310px;gap:26px;align-items:start;">
    <div>
      <?php foreach($items as $item):$img="../img/products/".$item['image_url'];$hi=!empty($item['image_url'])&&file_exists($img);?>
      <div style="background:var(--white);border:1px solid var(--border);border-radius:var(--r-lg);padding:18px;margin-bottom:14px;display:flex;gap:16px;align-items:center;flex-wrap:wrap;">
        <div style="width:84px;height:84px;border-radius:var(--r);overflow:hidden;flex-shrink:0;background:var(--ivory);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;">
          <?php if($hi):?><img src="<?php echo $img;?>" alt="" style="width:100%;height:100%;object-fit:cover;"><?php else:?><span style="color:var(--border);font-size:26px;">✦</span><?php endif;?>
        </div>
        <div style="flex:1;min-width:160px;">
          <div style="font-size:10px;font-weight:700;color:var(--gold-deep);letter-spacing:1px;text-transform:uppercase;margin-bottom:3px;"><?php echo htmlspecialchars($item['brand_name']);?></div>
          <div style="font-family:var(--serif);font-size:15px;font-weight:600;margin-bottom:5px;"><a href="product.php?id=<?php echo $item['product_id'];?>"><?php echo htmlspecialchars($item['name']);?></a></div>
          <div style="font-size:17px;font-weight:700;color:var(--burg);"><?php echo number_format($item['unit'],2,',','.');?> TL</div>
        </div>
        <div style="display:flex;flex-direction:column;gap:8px;align-items:flex-end;">
          <?php if($is_logged):?>
          <form method="post" style="display:flex;align-items:center;gap:6px;">
            <input type="hidden" name="cart_item_id" value="<?php echo $item['cart_item_id'];?>">
            <div class="qty-input">
              <button type="button" class="qty-dec">−</button>
              <input type="number" name="quantity" value="<?php echo $item['quantity'];?>" min="1" max="<?php echo $item['stock_quantity'];?>" style="width:48px;height:40px;" onchange="this.form.querySelector('[name=update_qty]').click()">
              <button type="button" class="qty-inc">+</button>
            </div>
           <button type="submit" name="update_qty" class="btn btn-sm btn-primary">
         Güncelle
       </button>
          </form>
          <?php else:?><span style="font-size:13px;color:var(--gray);">Adet: <?php echo $item['quantity'];?></span><?php endif;?>
          <div style="font-size:13px;font-weight:700;color:var(--charcoal);"><?php echo number_format($item['line'],2,',','.');?> TL</div>
          <div style="display:flex;gap:6px;">
            <?php if($is_logged):?>
            <form method="post" style="display:inline;"><input type="hidden" name="product_id" value="<?php echo $item['product_id'];?>"><button type="submit" name="move_to_fav" class="btn btn-outline btn-sm" title="Favorilere Taşı">♡</button></form>
            <?php endif;?>
            <form method="post" style="display:inline;"><input type="hidden" name="cart_item_id" value="<?php echo $item['cart_item_id'];?>"><input type="hidden" name="product_id" value="<?php echo $item['product_id'];?>"><button type="submit" name="remove_item" class="btn btn-danger btn-sm" data-confirm="Ürünü sepetten kaldır?">🗑</button></form>
          </div>
        </div>
      </div>
      <?php endforeach;?>
      <a href="products.php" class="btn btn-outline">← Alışverişe Devam</a>
    </div>
    <div style="background:var(--white);border:1px solid var(--border);border-radius:var(--r-lg);padding:26px;position:sticky;top:110px;">
      <h2 style="font-family:var(--serif);font-size:20px;font-weight:700;margin-bottom:16px;padding-bottom:12px;border-bottom:2px solid var(--gold);">Sipariş Özeti</h2>
      <div style="display:flex;justify-content:space-between;margin-bottom:10px;font-size:13px;"><span style="color:var(--gray);">Ara Toplam</span><span><?php echo number_format($total,2,',','.');?> TL</span></div>
      <div style="display:flex;justify-content:space-between;margin-bottom:10px;font-size:13px;"><span style="color:var(--gray);">Kargo</span><span style="color:var(--success);font-weight:600;"><?php echo $total>=150?'Ücretsiz':'29,99 TL';?></span></div>
      <?php if($total<150):?><div style="background:var(--gold-pale);border:1px solid rgba(200,168,80,.3);border-radius:var(--r);padding:9px;font-size:11px;color:var(--gold-deep);margin-bottom:10px;">ℹ <?php echo number_format(150-$total,2,',','.');?> TL daha ekle, ücretsiz kargo!</div><?php endif;?>
      <div style="border-top:2px solid var(--border);padding-top:12px;margin-top:4px;display:flex;justify-content:space-between;font-size:19px;font-weight:700;"><span>Toplam</span><span style="color:var(--burg);"><?php echo number_format($total>=150?$total:$total+29.99,2,',','.');?> TL</span></div>
      <?php if($is_logged):?><a href="checkout.php" class="btn btn-primary btn-block btn-lg" style="margin-top:18px;">Siparişi Tamamla</a>
      <?php else:?><a href="login.php" class="btn btn-primary btn-block btn-lg" style="margin-top:18px;">Giriş Yap & Devam</a><?php endif;?>
    </div>
  </div>
  <?php endif;?>
</div></main>
<?php include '../includes/footer.php';?>
