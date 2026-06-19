<?php
require_once '../includes/config.php';

$is_logged_in = is_logged_in();
$user_id = $is_logged_in ? (int)$_SESSION['user_id'] : null;

// --- Build cart ---
$cart_items = [];
$cart_id = null;

if ($is_logged_in) {
    $cs = $conn->prepare("SELECT cart_id FROM cart WHERE user_id = ?");
    $cs->bind_param("i", $user_id); $cs->execute();
    $crow = $cs->get_result()->fetch_assoc();
    if ($crow) {
        $cart_id = $crow['cart_id'];
        $ci = $conn->prepare("SELECT ci.cart_item_id, ci.quantity, p.product_id, p.name, p.price, p.discount_price, p.image_url, p.stock_quantity, b.name as brand_name FROM cart_items ci JOIN products p ON ci.product_id = p.product_id JOIN brands b ON p.brand_id = b.brand_id WHERE ci.cart_id = ?");
        $ci->bind_param("i", $cart_id); $ci->execute();
        $res = $ci->get_result();
        while ($r = $res->fetch_assoc()) { $cart_items[] = $r; }
    }
} elseif (!empty($_SESSION['cart'])) {
    $pids = array_map('intval', array_keys($_SESSION['cart']));
    $in = implode(',', $pids);
    $res = $conn->query("SELECT p.*, b.name as brand_name FROM products p JOIN brands b ON p.brand_id = b.brand_id WHERE p.product_id IN ($in)");
    while ($r = $res->fetch_assoc()) {
        $qty = (int)$_SESSION['cart'][$r['product_id']];
        $r['cart_item_id'] = $r['product_id'];
        $r['quantity'] = $qty;
        $cart_items[] = $r;
    }
}

if (empty($cart_items)) { redirect('cart.php'); }

// --- Calculate totals ---
$cart_total = 0;
foreach ($cart_items as &$item) {
    $item['unit'] = ($item['discount_price'] && $item['discount_price'] < $item['price']) ? (float)$item['discount_price'] : (float)$item['price'];
    $item['line'] = $item['unit'] * $item['quantity'];
    $cart_total += $item['line'];
}
unset($item);
$shipping_cost = 0.00;
$discount = ($cart_total >= 150) ? round($cart_total * 0.10, 2) : 0.00;
$final_total = $cart_total - $discount + $shipping_cost;

// --- Process order ---
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $name         = trim(htmlspecialchars($_POST['name'] ?? ''));
    $email        = trim(filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL));
    $phone        = trim(htmlspecialchars($_POST['phone'] ?? ''));
    $address      = trim(htmlspecialchars($_POST['address'] ?? ''));
    $city         = trim(htmlspecialchars($_POST['city'] ?? ''));
    $postal_code  = trim(htmlspecialchars($_POST['postal_code'] ?? ''));
    $payment_method = trim(htmlspecialchars($_POST['payment_method'] ?? ''));

    if (!$name || !$email || !$phone || !$address || !$city || !$postal_code || !$payment_method) {
        $error = 'Lütfen tüm alanları eksiksiz doldurun.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Geçerli bir e-posta adresi girin.';
    } else {
        $stmt = $conn->prepare("INSERT INTO orders (user_id, name, email, phone, shipping_address, city, postal_code, payment_method, order_status, total_amount, shipping_cost, discount, final_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Beklemede', ?, ?, ?, ?)");
         $stmt->bind_param(
    "isssssssdddd",
    $user_id,
    $name,
    $email,
    $phone,
    $address,
    $city,
    $postal_code,
    $payment_method,
    $cart_total,
    $shipping_cost,
    $discount,
    $final_total
);
        if ($stmt->execute()) {
            $order_id = $conn->insert_id;
            $ok = true;
            foreach ($cart_items as $item) {
                $oi = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $oi->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $item['unit']);
                if (!$oi->execute()) { $ok = false; break; }
                $upd = $conn->prepare("UPDATE products SET stock_quantity = GREATEST(0, stock_quantity - ?) WHERE product_id = ?");
                $upd->bind_param("ii", $item['quantity'], $item['product_id']); $upd->execute();
            }
            if ($ok) {
                // Clear cart
                if ($is_logged_in && $cart_id) {
                    $del = $conn->prepare("DELETE FROM cart_items WHERE cart_id = ?");
                    $del->bind_param("i", $cart_id); $del->execute();
                } else {
                    $_SESSION['cart'] = [];
                }
                $_SESSION['order_id'] = $order_id;
                redirect('order_confirmation.php');
            } else {
                $error = 'Sipariş kalemleri işlenirken bir hata oluştu. Lütfen tekrar deneyin.';
            }
        } else {
            $error = 'Sipariş oluşturulamadı: ' . $conn->error;
        }
    }
}

// Load user info for pre-fill
$user = null;
if ($is_logged_in) {
    $us = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $us->bind_param("i", $user_id); $us->execute();
    $user = $us->get_result()->fetch_assoc();
}

$page_title = 'Güvenli Ödeme';
include '../includes/header.php';
?>

<div class="breadcrumb"><div class="container">
  <a href="index.php">Ana Sayfa</a><span class="sep">/</span>
  <a href="cart.php">Sepetim</a><span class="sep">/</span>
  <span class="current">Ödeme</span>
</div></div>

<main style="padding: 32px 0 60px; background: var(--ivory);">
<div class="container">

  <!-- Page title -->
  <div class="page-header-bar" style="margin-bottom:28px;">
    <h1 style="font-family:var(--serif);font-size:30px;font-weight:700;color:var(--burg);">🔒 Güvenli Ödeme</h1>
    <span class="results-badge"><?php echo count($cart_items); ?> ürün</span>
  </div>

  <?php if ($error): ?>
  <div class="alert alert-danger" style="margin-bottom:20px;">✕ <?php echo $error; ?></div>
  <?php endif; ?>

  <!-- Checkout steps -->
  <div style="display:flex;align-items:center;justify-content:center;gap:0;margin-bottom:32px;max-width:500px;margin-left:auto;margin-right:auto;">
    <div style="display:flex;align-items:center;gap:8px;">
      <div style="width:30px;height:30px;border-radius:50%;background:var(--gold);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;">1</div>
      <span style="font-size:12px;font-weight:600;color:var(--burg);">Teslimat</span>
    </div>
    <div style="flex:1;height:1px;background:var(--border);margin:0 10px;max-width:80px;"></div>
    <div style="display:flex;align-items:center;gap:8px;">
      <div style="width:30px;height:30px;border-radius:50%;background:var(--burg);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;">2</div>
      <span style="font-size:12px;font-weight:600;color:var(--burg);">Ödeme</span>
    </div>
    <div style="flex:1;height:1px;background:var(--border);margin:0 10px;max-width:80px;"></div>
    <div style="display:flex;align-items:center;gap:8px;">
      <div style="width:30px;height:30px;border-radius:50%;background:var(--border);color:var(--mid);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;">3</div>
      <span style="font-size:12px;font-weight:600;color:var(--gray);">Onay</span>
    </div>
  </div>

  <form method="post" id="checkoutForm" onsubmit="return validateCheckout();">
  <div style="display:grid;grid-template-columns:1fr 340px;gap:26px;align-items:start;">

    <!-- LEFT: Forms -->
    <div>

      <!-- Delivery info -->
      <div class="admin-form-box" style="margin-bottom:20px;">
        <h2 style="font-family:var(--serif);font-size:18px;font-weight:700;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid var(--ivory-dk);display:flex;align-items:center;gap:10px;">
          <span>📦</span> Teslimat Bilgileri
        </h2>
        <div class="form-group">
          <label>Ad Soyad *</label>
          <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" placeholder="Adınız ve soyadınız" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>E-posta *</label>
            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" placeholder="ornek@mail.com" required>
          </div>
          <div class="form-group">
            <label>Telefon *</label>
            <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="05XX XXX XX XX" required>
          </div>
        </div>
        <div class="form-group">
          <label>Açık Adres *</label>
          <textarea name="address" class="form-control" rows="3" placeholder="Sokak, mahalle, bina no, daire..." required><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Şehir *</label>
            <input type="text" name="city" class="form-control" placeholder="İstanbul" required>
          </div>
          <div class="form-group">
            <label>Posta Kodu *</label>
            <input type="text" name="postal_code" class="form-control" placeholder="34000" required>
          </div>
        </div>
      </div>

      <!-- Payment method -->
      <div class="admin-form-box" style="margin-bottom:20px;">
        <h2 style="font-family:var(--serif);font-size:18px;font-weight:700;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid var(--ivory-dk);display:flex;align-items:center;gap:10px;">
          <span>💳</span> Ödeme Yöntemi
        </h2>

        <!-- Credit Card option -->
        <label id="opt-credit" style="display:flex;align-items:center;gap:14px;padding:14px 16px;border:1.5px solid var(--burg);border-radius:var(--r-lg);margin-bottom:10px;cursor:pointer;background:rgba(74,0,16,.04);">
          <input type="radio" name="payment_method" value="Kredi Kartı" checked onchange="showPaymentForm('credit')" style="accent-color:var(--burg);width:16px;height:16px;">
          <span style="font-size:22px;">💳</span>
          <div>
            <div style="font-weight:600;font-size:13px;color:var(--charcoal);">Kredi / Banka Kartı</div>
            <div style="font-size:11px;color:var(--gray);">Tüm kartlar güvenle kabul edilir</div>
          </div>
        </label>

        <!-- Bank Transfer option -->
        <label id="opt-transfer" style="display:flex;align-items:center;gap:14px;padding:14px 16px;border:1.5px solid var(--border);border-radius:var(--r-lg);margin-bottom:10px;cursor:pointer;transition:border-color .2s;">
          <input type="radio" name="payment_method" value="Havale / EFT" onchange="showPaymentForm('transfer')" style="accent-color:var(--burg);width:16px;height:16px;">
          <span style="font-size:22px;">🏦</span>
          <div>
            <div style="font-weight:600;font-size:13px;color:var(--charcoal);">Havale / EFT</div>
            <div style="font-size:11px;color:var(--gray);">Banka havalesiyle güvenli ödeme</div>
          </div>
        </label>

        <!-- Cash on Delivery option -->
        <label id="opt-cod" style="display:flex;align-items:center;gap:14px;padding:14px 16px;border:1.5px solid var(--border);border-radius:var(--r-lg);margin-bottom:16px;cursor:pointer;transition:border-color .2s;">
          <input type="radio" name="payment_method" value="Kapıda Ödeme" onchange="showPaymentForm('cod')" style="accent-color:var(--burg);width:16px;height:16px;">
          <span style="font-size:22px;">💵</span>
          <div>
            <div style="font-weight:600;font-size:13px;color:var(--charcoal);">Kapıda Ödeme</div>
            <div style="font-size:11px;color:var(--gray);">Teslimatta nakit veya kart ile öde</div>
          </div>
        </label>

        <!-- CREDIT CARD FORM -->
        <div id="form-credit" style="background:var(--ivory);border-radius:var(--r-lg);padding:20px;border:1px solid var(--border);">
          <!-- Visual card -->
          <div style="background:linear-gradient(135deg,#4A0010 0%,#7A1030 100%);border-radius:14px;padding:20px 24px;color:#fff;margin-bottom:20px;position:relative;overflow:hidden;min-height:140px;">
            <div style="position:absolute;top:-20px;right:-20px;width:100px;height:100px;border-radius:50%;background:rgba(200,168,80,.15);"></div>
            <div style="position:absolute;bottom:-15px;right:30px;width:70px;height:70px;border-radius:50%;background:rgba(200,168,80,.10);"></div>
            <div style="width:36px;height:26px;background:var(--gold);border-radius:4px;margin-bottom:14px;"></div>
            <div id="display-number" style="font-size:15px;letter-spacing:3px;font-family:monospace;margin-bottom:14px;color:rgba(255,255,255,.9);">•••• •••• •••• ••••</div>
            <div style="display:flex;justify-content:space-between;align-items:flex-end;">
              <div><div style="font-size:9px;letter-spacing:1.5px;text-transform:uppercase;color:rgba(255,255,255,.55);margin-bottom:2px;">Kart Sahibi</div><div id="display-name" style="font-size:13px;font-weight:600;">AD SOYAD</div></div>
              <div><div style="font-size:9px;letter-spacing:1.5px;text-transform:uppercase;color:rgba(255,255,255,.55);margin-bottom:2px;">Son Kullanma</div><div id="display-expiry" style="font-size:13px;font-weight:600;">MM/YY</div></div>
            </div>
          </div>
          <div class="form-group">
            <label>Kart Üzerindeki Ad *</label>
            <input type="text" id="cc-name" class="form-control" placeholder="AD SOYAD" oninput="document.getElementById('display-name').textContent=this.value.toUpperCase()||'AD SOYAD'">
          </div>
          <div class="form-group">
            <label>Kart Numarası *</label>
            <input type="text" id="cc-number" class="form-control" placeholder="0000 0000 0000 0000" maxlength="19" oninput="formatCardNum(this)">
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Son Kullanma (AA/YY) *</label>
              <input type="text" id="cc-expiry" class="form-control" placeholder="MM/YY" maxlength="5" oninput="formatExp(this)">
            </div>
            <div class="form-group">
              <label>CVV *</label>
              <input type="text" id="cc-cvv" class="form-control" placeholder="•••" maxlength="4">
            </div>
          </div>
          <div style="font-size:11px;color:var(--gray);">🔒 Kart bilgileriniz 256-bit SSL ile şifrelenmektedir. Bu bir demo projesidir — gerçek ödeme alınmaz.</div>
        </div>

        <!-- BANK TRANSFER FORM -->
        <div id="form-transfer" style="display:none;background:var(--ivory);border-radius:var(--r-lg);padding:20px;border:1px solid var(--border);">
          <p style="font-size:13px;color:var(--mid);margin-bottom:16px;line-height:1.7;">Aşağıdaki banka hesabına havale/EFT yapabilirsiniz. Sipariş onaylandıktan sonra açıklama kısmına sipariş numaranızı yazın.</p>
          <?php
          $bank_rows = [
            ['Banka', 'Ziraat Bankası'],
            ['Alıcı', 'AVELİN Kozmetik A.Ş.'],
            ['IBAN', 'TR12 0001 0025 0000 0012 3456 78'],
            ['Şube', 'Gaziantep / Şahinbey'],
          ];
          foreach ($bank_rows as $br): ?>
          <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid var(--border);">
            <span style="font-size:11px;color:var(--gray);font-weight:700;text-transform:uppercase;letter-spacing:.7px;"><?php echo $br[0]; ?></span>
            <div style="display:flex;align-items:center;gap:8px;">
              <span style="font-size:13px;font-weight:600;color:var(--charcoal);"><?php echo $br[1]; ?></span>
              <?php if ($br[0] === 'IBAN'): ?><button type="button" onclick="navigator.clipboard.writeText('TR12 0001 0025 0000 0012 3456 78').then(()=>showToast('IBAN kopyalandı!','success'))" style="background:var(--gold-pale);border:none;color:var(--gold-dk);font-size:11px;padding:3px 9px;border-radius:4px;cursor:pointer;font-weight:700;">Kopyala</button><?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
          <div class="form-group" style="margin-top:16px;">
            <label>Transfer Referans No (isteğe bağlı)</label>
            <input type="text" name="transfer_ref" class="form-control" placeholder="Banka dekontu veya referans numarası">
          </div>
        </div>

        <!-- CASH ON DELIVERY -->
        <div id="form-cod" style="display:none;background:var(--ivory);border-radius:var(--r-lg);padding:20px;border:1px solid var(--border);">
          <p style="font-size:13px;color:var(--mid);line-height:1.8;">🚚 Siparişiniz kapınıza teslim edileceğinde nakit veya POS cihazıyla ödeme yapabilirsiniz. Herhangi bir ek ücret alınmaz.</p>
        </div>
      </div>

      <!-- Submit button -->
      <button type="submit" name="place_order" class="btn btn-primary btn-lg btn-block" style="font-size:15px;padding:16px;">
        🔒 Siparişi Tamamla
      </button>
    </div>

    <!-- RIGHT: Order summary -->
    <div style="position:sticky;top:90px;">
      <div class="admin-form-box">
        <h2 style="font-family:var(--serif);font-size:18px;font-weight:700;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--ivory-dk);display:flex;align-items:center;gap:10px;">
          <span>🛍</span> Sipariş Özeti
        </h2>

        <?php foreach ($cart_items as $item):
          $img = '../img/products/' . ($item['image_url'] ?? '');
          $has = !empty($item['image_url']) && file_exists($img);
        ?>
        <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--ivory-dk);">
          <div style="width:52px;height:52px;border-radius:var(--r);overflow:hidden;flex-shrink:0;background:var(--ivory);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;">
            <?php if ($has): ?><img src="<?php echo $img; ?>" alt="" style="width:100%;height:100%;object-fit:cover;"><?php else: ?><span style="font-size:18px;color:var(--border);">✦</span><?php endif; ?>
          </div>
          <div style="flex:1;min-width:0;">
            <div style="font-size:12px;font-weight:600;color:var(--charcoal);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo htmlspecialchars($item['name']); ?></div>
            <div style="font-size:11px;color:var(--gray);"><?php echo (int)$item['quantity']; ?> adet × <?php echo number_format($item['unit'], 2, ',', '.'); ?> TL</div>
          </div>
          <div style="font-weight:700;font-size:13px;color:var(--burg);flex-shrink:0;"><?php echo number_format($item['line'], 2, ',', '.'); ?> TL</div>
        </div>
        <?php endforeach; ?>

        <div style="margin-top:16px;">
          <div style="display:flex;justify-content:space-between;padding:7px 0;font-size:13px;border-bottom:1px solid var(--ivory-dk);">
            <span style="color:var(--gray);">Ara Toplam</span>
            <span style="font-weight:600;"><?php echo number_format($cart_total, 2, ',', '.'); ?> TL</span>
          </div>
          <div style="display:flex;justify-content:space-between;padding:7px 0;font-size:13px;border-bottom:1px solid var(--ivory-dk);">
            <span style="color:var(--gray);">Kargo</span>
            <span style="color:#2A5A2A;font-weight:600;">Ücretsiz ✓</span>
          </div>
          <?php if ($discount > 0): ?>
          <div style="display:flex;justify-content:space-between;padding:7px 0;font-size:13px;border-bottom:1px solid var(--ivory-dk);">
            <span style="color:var(--gray);">İndirim (%10)</span>
            <span style="color:#2A5A2A;font-weight:600;">−<?php echo number_format($discount, 2, ',', '.'); ?> TL</span>
          </div>
          <?php endif; ?>
          <div style="display:flex;justify-content:space-between;padding:12px 0 0;font-size:17px;font-weight:700;color:var(--burg);">
            <span style="color:var(--charcoal);">Genel Toplam</span>
            <span><?php echo number_format($final_total, 2, ',', '.'); ?> TL</span>
          </div>
        </div>

        <?php if ($cart_total < 150): ?>
        <div style="background:var(--gold-pale);border-radius:var(--r);padding:10px 14px;font-size:12px;color:var(--mid);text-align:center;margin-top:14px;border:1px solid rgba(200,168,80,.3);">
          💡 <?php echo number_format(150 - $cart_total, 2, ',', '.'); ?> TL daha alın, %10 indirim kazanın!
        </div>
        <?php endif; ?>
      </div>

      <a href="cart.php" class="btn btn-outline btn-sm btn-block" style="margin-top:10px;text-align:center;">← Sepete Dön</a>
    </div>
  </div>
  </form>
</div>
</main>

<script>
function showPaymentForm(type) {
  ['credit','transfer','cod'].forEach(t => {
    document.getElementById('form-' + t).style.display = (t === type) ? 'block' : 'none';
    const opt = document.getElementById('opt-' + t);
    if (opt) {
      opt.style.borderColor = (t === type) ? 'var(--burg)' : 'var(--border)';
      opt.style.background  = (t === type) ? 'rgba(74,0,16,.04)' : '';
    }
  });
}
function formatCardNum(inp) {
  let v = inp.value.replace(/\D/g,'').substring(0,16);
  let f = v.match(/.{1,4}/g)?.join(' ') || v;
  inp.value = f;
  document.getElementById('display-number').textContent = f.padEnd(19,'•').substring(0,19);
}
function formatExp(inp) {
  let v = inp.value.replace(/\D/g,'').substring(0,4);
  if (v.length >= 2) v = v.substring(0,2)+'/'+v.substring(2);
  inp.value = v;
  document.getElementById('display-expiry').textContent = v || 'MM/YY';
}
function validateCheckout() {
  var pm = document.querySelector('input[name=payment_method]:checked')?.value;
  if (pm === 'Kredi Kartı') {
    var num = document.getElementById('cc-number').value.replace(/\s/g,'');
    var exp = document.getElementById('cc-expiry').value;
    var cvv = document.getElementById('cc-cvv').value;
    var nm  = document.getElementById('cc-name').value;
    if (!nm.trim() || num.length < 16 || exp.length < 5 || cvv.length < 3) {
      alert('Lütfen tüm kart bilgilerini eksiksiz doldurun.');
      return false;
    }
  }
  return true;
}
// Init payment option border
document.querySelectorAll('input[name=payment_method]').forEach(function(radio) {
  radio.addEventListener('change', function() {
    document.querySelectorAll('label[id^=opt-]').forEach(function(l){ l.style.borderColor='var(--border)'; l.style.background=''; });
    var lbl = document.getElementById('opt-' + (this.value==='Kredi Kartı'?'credit':this.value==='Havale / EFT'?'transfer':'cod'));
    if(lbl){ lbl.style.borderColor='var(--burg)'; lbl.style.background='rgba(74,0,16,.04)'; }
  });
});
</script>

<?php include '../includes/footer.php'; ?>
