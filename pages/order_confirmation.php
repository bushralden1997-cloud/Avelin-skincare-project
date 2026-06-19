<?php
require_once '../includes/config.php';

if (!isset($_SESSION['order_id'])) { redirect('index.php'); }
$order_id = (int)$_SESSION['order_id'];
unset($_SESSION['order_id']);

$o_stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ?");
$o_stmt->bind_param("i", $order_id); $o_stmt->execute();
$order = $o_stmt->get_result()->fetch_assoc();
if (!$order) { redirect('index.php'); }

$i_stmt = $conn->prepare("SELECT oi.*, p.name, p.image_url, b.name as brand_name FROM order_items oi JOIN products p ON oi.product_id = p.product_id JOIN brands b ON p.brand_id = b.brand_id WHERE oi.order_id = ?");
$i_stmt->bind_param("i", $order_id); $i_stmt->execute();
$items = $i_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

function pm_label($v) {
    $map = ['Kredi Kartı'=>'💳 Kredi / Banka Kartı','Havale / EFT'=>'🏦 Havale / EFT','Kapıda Ödeme'=>'💵 Kapıda Ödeme'];
    return $map[$v] ?? htmlspecialchars($v ?: 'Belirtilmedi');
}
function status_badge($s) {
    $map = ['Beklemede'=>['#FFF8E1','#E65100'],'İşleniyor'=>['#E3F2FD','#1565C0'],'Kargoya Verildi'=>['#E8F5E9','#2E7D32'],'Teslim Edildi'=>['#E8F5E9','#1B5E20'],'İptal Edildi'=>['#FFEBEE','#B71C1C']];
    $d = $map[$s] ?? ['#F5EDD8','#906828'];
    return "<span style=\"background:{$d[0]};color:{$d[1]};padding:4px 12px;border-radius:14px;font-size:12px;font-weight:700;\">" . htmlspecialchars($s) . "</span>";
}

$total_display = (!empty($order['final_amount']) && $order['final_amount'] > 0) ? $order['final_amount'] : $order['total_amount'];

$page_title = 'Sipariş Onayı';
include '../includes/header.php';
?>

<div class="breadcrumb"><div class="container">
  <a href="index.php">Ana Sayfa</a><span class="sep">/</span>
  <span class="current">Sipariş Onayı</span>
</div></div>

<main style="padding:0 0 60px;background:var(--ivory);">

<!-- Success banner -->
<div style="background:linear-gradient(135deg,var(--burg-dk) 0%,var(--burg) 60%,var(--burg-lt) 100%);padding:52px 0;text-align:center;position:relative;overflow:hidden;">
  <div style="position:absolute;top:20px;left:8%;font-size:80px;color:rgba(200,168,80,.08);line-height:1;">✦</div>
  <div style="position:absolute;bottom:10px;right:8%;font-size:60px;color:rgba(200,168,80,.07);line-height:1;">✦</div>
  <div style="width:72px;height:72px;background:rgba(200,168,80,.2);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;font-size:30px;border:2px solid rgba(200,168,80,.35);">✓</div>
  <h1 style="font-family:var(--serif);font-size:32px;color:#fff;margin-bottom:10px;">Siparişinizi Aldık!</h1>
  <p style="color:rgba(255,255,255,.72);font-size:14px;max-width:440px;margin:0 auto 20px;line-height:1.7;">Siparişiniz başarıyla oluşturuldu. En kısa sürede hazırlanıp kargoya verilecektir.</p>
  <div style="display:inline-block;background:rgba(200,168,80,.22);border:1px solid rgba(200,168,80,.45);color:var(--gold-lt);padding:9px 22px;border-radius:30px;font-size:14px;font-weight:700;letter-spacing:1px;">
    Sipariş No: #<?php echo str_pad($order['order_id'], 6, '0', STR_PAD_LEFT); ?>
  </div>
</div>

<div class="container" style="max-width:860px;margin-top:32px;">

  <!-- Info grid -->
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
    <!-- Order info -->
    <div style="background:var(--white);border-radius:var(--r-lg);border:1px solid var(--border);box-shadow:var(--sh-xs);padding:22px 24px;border-top:3px solid var(--gold);">
      <div style="font-size:10px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:var(--gold-dk);margin-bottom:14px;">📋 Sipariş Bilgileri</div>
      <?php
      $info_rows = [
        ['Sipariş No',    '#' . str_pad($order['order_id'], 6, '0', STR_PAD_LEFT)],
        ['Sipariş Tarihi', format_date($order['order_date'])],
        ['Ödeme Yöntemi', pm_label($order['payment_method'] ?? '')],
      ];
      foreach ($info_rows as $row): ?>
      <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--ivory-dk);font-size:13px;">
        <span style="color:var(--gray);"><?php echo $row[0]; ?></span>
        <span style="font-weight:600;text-align:right;max-width:60%;"><?php echo $row[1]; ?></span>
      </div>
      <?php endforeach; ?>
      <div style="display:flex;justify-content:space-between;padding:8px 0;font-size:13px;">
        <span style="color:var(--gray);">Durum</span>
        <span><?php echo status_badge($order['order_status'] ?? 'Beklemede'); ?></span>
      </div>
    </div>

    <!-- Customer info -->
    <div style="background:var(--white);border-radius:var(--r-lg);border:1px solid var(--border);box-shadow:var(--sh-xs);padding:22px 24px;border-top:3px solid var(--gold);">
      <div style="font-size:10px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:var(--gold-dk);margin-bottom:14px;">👤 Teslimat Bilgileri</div>
      <?php
      $delivery_rows = [];
      if (!empty($order['name']))            $delivery_rows[] = ['Ad Soyad',          $order['name']];
      if (!empty($order['email']))           $delivery_rows[] = ['E-posta',            $order['email']];
      if (!empty($order['phone']))           $delivery_rows[] = ['Telefon',            $order['phone']];
      if (!empty($order['shipping_address']))$delivery_rows[] = ['Adres',              $order['shipping_address']];
      if (!empty($order['city'])) {
          $city_str = $order['city'];
          if (!empty($order['postal_code'])) $city_str .= ' / ' . $order['postal_code'];
          $delivery_rows[] = ['Şehir', $city_str];
      }
      if (empty($delivery_rows)) $delivery_rows[] = ['Bilgi', 'Teslimat bilgisi kaydedildi.'];
      foreach ($delivery_rows as $i => $row): $last = $i === count($delivery_rows)-1; ?>
      <div style="display:flex;justify-content:space-between;padding:8px 0;<?php echo $last?'':'border-bottom:1px solid var(--ivory-dk);'?>font-size:13px;">
        <span style="color:var(--gray);flex-shrink:0;"><?php echo $row[0]; ?></span>
        <span style="font-weight:600;text-align:right;max-width:65%;"><?php echo htmlspecialchars($row[1]); ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Products -->
  <div style="background:var(--white);border-radius:var(--r-lg);border:1px solid var(--border);box-shadow:var(--sh-xs);padding:22px 24px;margin-bottom:20px;">
    <div style="font-size:10px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:var(--gold-dk);margin-bottom:16px;">🛍 Sipariş Edilen Ürünler (<?php echo count($items); ?> ürün)</div>
    <?php foreach ($items as $idx => $item):
      $img = '../img/products/' . ($item['image_url'] ?? '');
      $has = !empty($item['image_url']) && file_exists($img);
      $last = $idx === count($items)-1;
    ?>
    <div style="display:flex;align-items:center;gap:14px;padding:13px 0;<?php echo $last?'':'border-bottom:1px solid var(--ivory-dk);'?>">
      <div style="width:58px;height:58px;border-radius:var(--r-lg);overflow:hidden;flex-shrink:0;background:var(--ivory);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;">
        <?php if ($has): ?><img src="<?php echo $img; ?>" alt="" style="width:100%;height:100%;object-fit:cover;"><?php else: ?><span style="font-size:20px;color:var(--border);">✦</span><?php endif; ?>
      </div>
      <div style="flex:1;">
        <div style="font-size:13px;font-weight:600;color:var(--charcoal);margin-bottom:2px;"><?php echo htmlspecialchars($item['name']); ?></div>
        <div style="font-size:11px;color:var(--gray);"><?php echo htmlspecialchars($item['brand_name']); ?></div>
        <div style="font-size:12px;color:var(--mid);margin-top:2px;"><?php echo (int)$item['quantity']; ?> adet × <?php echo number_format($item['price'], 2, ',', '.'); ?> TL</div>
      </div>
      <div style="font-weight:700;color:var(--burg);font-size:14px;flex-shrink:0;"><?php echo number_format($item['price'] * $item['quantity'], 2, ',', '.'); ?> TL</div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Totals -->
  <div style="background:var(--gold-pale);border-radius:var(--r-lg);padding:18px 24px;margin-bottom:28px;border:1px solid rgba(200,168,80,.3);">
    <div style="display:flex;justify-content:space-between;padding:7px 0;font-size:13px;border-bottom:1px solid rgba(200,168,80,.2);">
      <span style="color:var(--mid);">Ara Toplam</span>
      <span style="font-weight:600;"><?php echo number_format($order['total_amount'], 2, ',', '.'); ?> TL</span>
    </div>
    <div style="display:flex;justify-content:space-between;padding:7px 0;font-size:13px;border-bottom:1px solid rgba(200,168,80,.2);">
      <span style="color:var(--mid);">Kargo</span>
      <span style="color:#2A5A2A;font-weight:600;">Ücretsiz ✓</span>
    </div>
    <?php if (!empty($order['discount']) && $order['discount'] > 0): ?>
    <div style="display:flex;justify-content:space-between;padding:7px 0;font-size:13px;border-bottom:1px solid rgba(200,168,80,.2);">
      <span style="color:var(--mid);">İndirim</span>
      <span style="color:#2A5A2A;font-weight:600;">−<?php echo number_format($order['discount'], 2, ',', '.'); ?> TL</span>
    </div>
    <?php endif; ?>
    <div style="display:flex;justify-content:space-between;padding:12px 0 0;font-size:18px;font-weight:700;color:var(--burg);">
      <span style="color:var(--charcoal);">Genel Toplam</span>
      <span><?php echo number_format($total_display, 2, ',', '.'); ?> TL</span>
    </div>
  </div>

  <!-- Action buttons -->
  <div style="display:flex;gap:14px;flex-wrap:wrap;margin-bottom:8px;">
    <?php if (is_logged_in()): ?>
    <a href="account.php#orders" class="btn btn-primary btn-lg">👤 Siparişlerim</a>
    <?php endif; ?>
    <a href="products.php" class="btn btn-outline btn-lg">🛍 Alışverişe Devam Et</a>
    <a href="index.php" class="btn btn-outline btn-lg">🏠 Ana Sayfa</a>
  </div>
</div>
</main>

<?php include '../includes/footer.php'; ?>
