<?php
require_once '../includes/config.php';
if (!is_logged_in()) {
    $_SESSION['redirect_after_login'] = 'account.php#orders';
    redirect('login.php');
}
$user_id  = (int)$_SESSION['user_id'];
$order_id = (int)($_GET['id'] ?? 0);
if (!$order_id) { redirect('account.php'); }

$o = $conn->prepare("SELECT * FROM orders WHERE order_id = ? AND user_id = ?");
$o->bind_param("ii", $order_id, $user_id); $o->execute();
$order = $o->get_result()->fetch_assoc();
if (!$order) { redirect('account.php'); }

$i_stmt = $conn->prepare("SELECT oi.*, p.name, p.image_url, b.name as brand_name FROM order_items oi JOIN products p ON oi.product_id = p.product_id JOIN brands b ON p.brand_id = b.brand_id WHERE oi.order_id = ?");
$i_stmt->bind_param("i", $order_id); $i_stmt->execute();
$items = $i_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

function pm_lbl($v){$m=['Kredi Kartı'=>'💳 Kredi / Banka Kartı','Havale / EFT'=>'🏦 Havale / EFT','Kapıda Ödeme'=>'💵 Kapıda Ödeme'];return $m[$v]??htmlspecialchars($v?:'—');}
function sb($s){$m=['Beklemede'=>['#FFF8E1','#E65100'],'İşleniyor'=>['#E3F2FD','#1565C0'],'Kargoya Verildi'=>['#E8F5E9','#2E7D32'],'Teslim Edildi'=>['#E8F5E9','#1B5E20'],'İptal Edildi'=>['#FFEBEE','#B71C1C']];$d=$m[$s]??['#F5EDD8','#906828'];return "<span style=\"background:{$d[0]};color:{$d[1]};padding:3px 11px;border-radius:14px;font-size:11px;font-weight:700;\">".htmlspecialchars($s)."</span>";}

$total_display = (!empty($order['final_amount']) && $order['final_amount'] > 0) ? $order['final_amount'] : $order['total_amount'];
$page_title = 'Sipariş Detayı';
include '../includes/header.php';
?>

<div class="breadcrumb"><div class="container">
  <a href="index.php">Ana Sayfa</a><span class="sep">/</span>
  <a href="account.php#orders">Siparişlerim</a><span class="sep">/</span>
  <span class="current">Sipariş #<?php echo str_pad($order['order_id'],5,'0',STR_PAD_LEFT); ?></span>
</div></div>

<main style="padding:32px 0 60px;background:var(--ivory);">
<div class="container" style="max-width:800px;">

  <div class="page-header-bar" style="margin-bottom:24px;">
    <h1 style="font-family:var(--serif);font-size:26px;color:var(--burg);">Sipariş #<?php echo str_pad($order['order_id'],5,'0',STR_PAD_LEFT); ?></h1>
    <a href="account.php#orders" class="btn btn-outline btn-sm">← Siparişlerime Dön</a>
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:18px;">
    <div style="background:var(--white);border-radius:var(--r-lg);border:1px solid var(--border);padding:20px 22px;border-top:3px solid var(--gold);">
      <div style="font-size:10px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:var(--gold-dk);margin-bottom:12px;">📋 Sipariş Bilgileri</div>
      <?php foreach([['Sipariş Tarihi',format_date($order['order_date'])],['Ödeme',pm_lbl($order['payment_method']??'')]] as $r): ?>
      <div style="display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px solid var(--ivory-dk);font-size:13px;"><span style="color:var(--gray);"><?php echo $r[0];?></span><span style="font-weight:600;"><?php echo $r[1];?></span></div>
      <?php endforeach; ?>
      <div style="display:flex;justify-content:space-between;padding:7px 0;font-size:13px;"><span style="color:var(--gray);">Durum</span><span><?php echo sb($order['order_status']??'Beklemede');?></span></div>
    </div>
    <div style="background:var(--white);border-radius:var(--r-lg);border:1px solid var(--border);padding:20px 22px;border-top:3px solid var(--gold);">
      <div style="font-size:10px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:var(--gold-dk);margin-bottom:12px;">📍 Teslimat Bilgileri</div>
      <?php
      $rows = [];
      if (!empty($order['name']))            $rows[]=[$order['name']];
      if (!empty($order['phone']))           $rows[]=['📞 '.$order['phone']];
      if (!empty($order['shipping_address']))$rows[]=[htmlspecialchars($order['shipping_address'])];
      if (!empty($order['city'])){$cs=$order['city'];if(!empty($order['postal_code']))$cs.=' / '.$order['postal_code'];$rows[]=[$cs];}
      if(empty($rows))$rows[]=['Adres bilgisi kaydedildi.'];
      foreach($rows as $i=>$r):$last=$i===count($rows)-1;?>
      <div style="padding:5px 0;font-size:13px;font-weight:<?php echo $i===0?'600':'400';?>;color:<?php echo $i===0?'var(--charcoal)':'var(--mid)';?>;<?php echo $last?'':'border-bottom:1px solid var(--ivory-dk);'?>"><?php echo htmlspecialchars($r[0]);?></div>
      <?php endforeach; ?>
    </div>
  </div>

  <div style="background:var(--white);border-radius:var(--r-lg);border:1px solid var(--border);padding:20px 22px;margin-bottom:18px;">
    <div style="font-size:10px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:var(--gold-dk);margin-bottom:14px;">🛍 Sipariş Edilen Ürünler</div>
    <?php foreach($items as $idx=>$item):$img='../img/products/'.($item['image_url']??'');$has=!empty($item['image_url'])&&file_exists($img);$last=$idx===count($items)-1;?>
    <div style="display:flex;align-items:center;gap:14px;padding:12px 0;<?php echo $last?'':'border-bottom:1px solid var(--ivory-dk);'?>">
      <div style="width:56px;height:56px;border-radius:var(--r);overflow:hidden;flex-shrink:0;background:var(--ivory);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;">
        <?php if($has):?><img src="<?php echo $img;?>" alt="" style="width:100%;height:100%;object-fit:cover;"><?php else:?><span style="font-size:18px;color:var(--border);">✦</span><?php endif;?>
      </div>
      <div style="flex:1;">
        <div style="font-size:13px;font-weight:600;"><?php echo htmlspecialchars($item['name']);?></div>
        <div style="font-size:11px;color:var(--gray);"><?php echo htmlspecialchars($item['brand_name']);?></div>
        <div style="font-size:12px;color:var(--mid);margin-top:2px;"><?php echo (int)$item['quantity'];?> adet × <?php echo number_format($item['price'],2,',','.');?> TL</div>
      </div>
      <div style="font-weight:700;color:var(--burg);font-size:14px;flex-shrink:0;"><?php echo number_format($item['price']*$item['quantity'],2,',','.');?> TL</div>
    </div>
    <?php endforeach;?>
  </div>

  <div style="background:var(--gold-pale);border-radius:var(--r-lg);padding:16px 22px;margin-bottom:24px;border:1px solid rgba(200,168,80,.3);">
    <div style="display:flex;justify-content:space-between;padding:6px 0;font-size:13px;border-bottom:1px solid rgba(200,168,80,.2);"><span style="color:var(--mid);">Ara Toplam</span><span style="font-weight:600;"><?php echo number_format($order['total_amount'],2,',','.');?> TL</span></div>
    <div style="display:flex;justify-content:space-between;padding:6px 0;font-size:13px;border-bottom:1px solid rgba(200,168,80,.2);"><span style="color:var(--mid);">Kargo</span><span style="color:#2A5A2A;font-weight:600;">Ücretsiz ✓</span></div>
    <?php if(!empty($order['discount'])&&$order['discount']>0):?>
    <div style="display:flex;justify-content:space-between;padding:6px 0;font-size:13px;border-bottom:1px solid rgba(200,168,80,.2);"><span style="color:var(--mid);">İndirim</span><span style="color:#2A5A2A;font-weight:600;">−<?php echo number_format($order['discount'],2,',','.');?> TL</span></div>
    <?php endif;?>
    <div style="display:flex;justify-content:space-between;padding:10px 0 0;font-size:17px;font-weight:700;color:var(--burg);"><span style="color:var(--charcoal);">Genel Toplam</span><span><?php echo number_format($total_display,2,',','.');?> TL</span></div>
  </div>

  <div style="display:flex;gap:12px;flex-wrap:wrap;">
    <a href="account.php#orders" class="btn btn-primary">← Siparişlerime Dön</a>
    <a href="products.php" class="btn btn-outline">🛍 Alışverişe Devam Et</a>
  </div>
</div>
</main>

<?php include '../includes/footer.php'; ?>
