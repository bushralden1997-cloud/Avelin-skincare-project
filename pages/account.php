<?php
require_once '../includes/config.php';
if (!is_logged_in()) {
    $_SESSION['redirect_after_login'] = 'account.php';
    redirect('login.php');
}

$user_id = (int)$_SESSION['user_id'];
$success = '';
$error   = '';

// Load user
$us = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$us->bind_param("i", $user_id); $us->execute();
$user = $us->get_result()->fetch_assoc();

// Update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name    = trim(htmlspecialchars($_POST['name'] ?? ''));
    $email   = trim(filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL));
    $phone   = trim(htmlspecialchars($_POST['phone'] ?? ''));
    $address = trim(htmlspecialchars($_POST['address'] ?? ''));
    if (!$name || !$email) {
        $error = 'Ad ve e-posta zorunludur.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Geçerli bir e-posta girin.';
    } else {
        $chk = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $chk->bind_param("si", $email, $user_id); $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $error = 'Bu e-posta başka bir kullanıcıya ait.';
        } else {
            $upd = $conn->prepare("UPDATE users SET name=?, email=?, phone=?, address=? WHERE user_id=?");
            $upd->bind_param("ssssi", $name, $email, $phone, $address, $user_id);
            if ($upd->execute()) {
                $success = 'Profil bilgileriniz güncellendi.';
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $us->execute(); $user = $us->get_result()->fetch_assoc();
            } else { $error = 'Güncelleme başarısız. Lütfen tekrar deneyin.'; }
        }
    }
}

// Change password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $cur  = $_POST['current_password'] ?? '';
    $new  = $_POST['new_password'] ?? '';
    $con  = $_POST['confirm_password'] ?? '';
    if (!$cur || !$new || !$con) { $error = 'Tüm şifre alanlarını doldurun.'; }
    elseif ($new !== $con)        { $error = 'Yeni şifreler eşleşmiyor.'; }
    elseif (strlen($new) < 6)     { $error = 'Şifre en az 6 karakter olmalıdır.'; }
    elseif (!password_verify($cur, $user['password'])) { $error = 'Mevcut şifre yanlış.'; }
    else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $upd = $conn->prepare("UPDATE users SET password=? WHERE user_id=?");
        $upd->bind_param("si", $hash, $user_id);
        if ($upd->execute()) { $success = 'Şifreniz başarıyla güncellendi.'; }
        else { $error = 'Şifre güncellenemedi. Lütfen tekrar deneyin.'; }
    }
}

// Load orders
$ord_stmt = $conn->prepare("SELECT o.*, COUNT(oi.order_item_id) as item_count FROM orders o LEFT JOIN order_items oi ON o.order_id = oi.order_id WHERE o.user_id = ? GROUP BY o.order_id ORDER BY o.order_date DESC");
$ord_stmt->bind_param("i", $user_id); $ord_stmt->execute();
$orders_all = $ord_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Load favorites
$fav_stmt = $conn->prepare("SELECT f.favorite_id, p.product_id, p.name, p.price, p.discount_price, p.image_url, p.stock_quantity, b.name as brand_name FROM favorites f JOIN products p ON f.product_id = p.product_id JOIN brands b ON p.brand_id = b.brand_id WHERE f.user_id = ? ORDER BY f.added_at DESC");
$fav_stmt->bind_param("i", $user_id); $fav_stmt->execute();
$favorites = $fav_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Status helper
function order_status_badge($s) {
    $map = [
        'Beklemede'       => ['#FFF8E1','#E65100','⏳'],
        'İşleniyor'       => ['#E3F2FD','#1565C0','⚙'],
        'Kargoya Verildi' => ['#E8F5E9','#2E7D32','🚚'],
        'Teslim Edildi'   => ['#E8F5E9','#1B5E20','✓'],
        'İptal Edildi'    => ['#FFEBEE','#B71C1C','✕'],
    ];
    $d = $map[$s] ?? ['#F5EDD8','#906828','?'];
    return "<span style=\"background:{$d[0]};color:{$d[1]};padding:3px 11px;border-radius:14px;font-size:11px;font-weight:700;white-space:nowrap;\">{$d[2]} " . htmlspecialchars($s) . "</span>";
}

$page_title = 'Hesabım';
include '../includes/header.php';
?>

<div class="breadcrumb"><div class="container">
  <a href="index.php">Ana Sayfa</a><span class="sep">/</span>
  <span class="current">Hesabım</span>
</div></div>

<main style="padding:32px 0 60px;background:var(--ivory);">
<div class="container">

<?php if ($success): ?><div class="alert alert-success">✓ <?php echo $success; ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-danger">✕ <?php echo $error; ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:240px 1fr;gap:26px;align-items:start;">

  <!-- SIDEBAR -->
  <div>
    <div style="background:var(--white);border-radius:var(--r-lg);overflow:hidden;border:1px solid var(--border);box-shadow:var(--sh-xs);margin-bottom:14px;">
      <div style="background:linear-gradient(135deg,var(--burg-dk),var(--burg));padding:24px;text-align:center;">
        <div style="width:60px;height:60px;border-radius:50%;background:rgba(200,168,80,.25);border:2px solid rgba(200,168,80,.4);display:flex;align-items:center;justify-content:center;font-family:var(--serif);font-size:24px;font-weight:700;color:var(--gold-lt);margin:0 auto 12px;">
          <?php echo mb_strtoupper(mb_substr($user['name'] ?: $user['username'], 0, 1, 'UTF-8')); ?>
        </div>
        <div style="color:#fff;font-weight:600;font-size:15px;margin-bottom:3px;"><?php echo htmlspecialchars($user['name'] ?: $user['username']); ?></div>
        <div style="color:rgba(255,255,255,.6);font-size:11px;"><?php echo htmlspecialchars($user['email']); ?></div>
      </div>
      <nav style="padding:10px 0;">
        <?php
        $nav_items = [
          ['tab'=>'dashboard',  'icon'=>'◈', 'label'=>'Gösterge Paneli'],
          ['tab'=>'orders',     'icon'=>'📦', 'label'=>'Siparişlerim', 'badge'=> count($orders_all)],
          ['tab'=>'favorites',  'icon'=>'♡',  'label'=>'Favorilerim',  'badge'=> count($favorites)],
          ['tab'=>'profile',    'icon'=>'✏',  'label'=>'Profil Bilgileri'],
          ['tab'=>'password',   'icon'=>'🔒', 'label'=>'Şifre Değiştir'],
        ];
        foreach ($nav_items as $ni): ?>
        <a href="#" data-tab="<?php echo $ni['tab']; ?>" class="acct-nav-link" style="display:flex;align-items:center;gap:10px;padding:10px 18px;font-size:12px;color:var(--mid);transition:var(--ease);text-decoration:none;">
          <span style="font-size:15px;width:18px;flex-shrink:0;"><?php echo $ni['icon']; ?></span>
          <span style="flex:1;"><?php echo $ni['label']; ?></span>
          <?php if (!empty($ni['badge']) && $ni['badge'] > 0): ?>
          <span style="background:var(--burg);color:#fff;border-radius:10px;padding:1px 7px;font-size:10px;font-weight:700;"><?php echo $ni['badge']; ?></span>
          <?php endif; ?>
        </a>
        <?php endforeach; ?>
        <div style="height:1px;background:var(--border);margin:6px 0;"></div>
        <a href="logout.php" style="display:flex;align-items:center;gap:10px;padding:10px 18px;font-size:12px;color:#B71C1C;text-decoration:none;font-weight:600;">
          <span>⊘</span> Çıkış Yap
        </a>
      </nav>
    </div>
  </div>

  <!-- CONTENT -->
  <div id="acct-content">

    <!-- DASHBOARD -->
    <div class="acct-panel" id="panel-dashboard">
      <div class="admin-form-box" style="margin-bottom:20px;">
        <h2 style="font-family:var(--serif);font-size:18px;font-weight:700;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--ivory-dk);">Genel Bakış</h2>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:0;">
          <div style="background:var(--ivory);border-radius:var(--r-lg);padding:18px;border:1px solid var(--border);text-align:center;">
            <div style="font-size:28px;margin-bottom:6px;">📦</div>
            <div style="font-family:var(--serif);font-size:26px;font-weight:700;color:var(--burg);line-height:1;"><?php echo count($orders_all); ?></div>
            <div style="font-size:10px;letter-spacing:.8px;text-transform:uppercase;color:var(--gray);margin-top:4px;">Toplam Sipariş</div>
          </div>
          <div style="background:var(--ivory);border-radius:var(--r-lg);padding:18px;border:1px solid var(--border);text-align:center;">
            <div style="font-size:28px;margin-bottom:6px;">♡</div>
            <div style="font-family:var(--serif);font-size:26px;font-weight:700;color:var(--burg);line-height:1;"><?php echo count($favorites); ?></div>
            <div style="font-size:10px;letter-spacing:.8px;text-transform:uppercase;color:var(--gray);margin-top:4px;">Favori Ürün</div>
          </div>
          <div style="background:var(--ivory);border-radius:var(--r-lg);padding:18px;border:1px solid var(--border);text-align:center;">
            <div style="font-size:28px;margin-bottom:6px;">✓</div>
            <div style="font-family:var(--serif);font-size:26px;font-weight:700;color:var(--burg);line-height:1;"><?php echo count(array_filter($orders_all, fn($o) => ($o['order_status'] ?? '') === 'Teslim Edildi')); ?></div>
            <div style="font-size:10px;letter-spacing:.8px;text-transform:uppercase;color:var(--gray);margin-top:4px;">Teslim Edilen</div>
          </div>
        </div>
      </div>

      <?php if (!empty($orders_all)): ?>
      <div class="admin-table-box">
        <div class="admin-table-header">
          <h2>Son Siparişler</h2>
          <a href="#" data-tab="orders" class="acct-nav-link btn btn-outline btn-sm">Tümünü Gör →</a>
        </div>
        <div style="overflow-x:auto;">
        <table class="admin-table">
          <thead><tr><th>Sipariş No</th><th>Tarih</th><th>Ürün</th><th>Toplam</th><th>Durum</th><th></th></tr></thead>
          <tbody>
          <?php foreach (array_slice($orders_all, 0, 5) as $o): ?>
          <tr>
            <td><strong>#<?php echo str_pad($o['order_id'], 5, '0', STR_PAD_LEFT); ?></strong></td>
            <td><?php echo format_date($o['order_date']); ?></td>
            <td><?php echo (int)($o['item_count'] ?? 0); ?> ürün</td>
            <td style="font-weight:700;color:var(--burg);"><?php echo number_format($o['total_amount'], 2, ',', '.'); ?> TL</td>
            <td><?php echo order_status_badge($o['order_status'] ?? 'Beklemede'); ?></td>
            <td><a href="order_detail.php?id=<?php echo $o['order_id']; ?>" class="btn btn-outline btn-sm">Detay</a></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        </div>
      </div>
      <?php else: ?>
      <div class="empty-state">
        <div class="empty-icon">📦</div>
        <h2>Henüz Sipariş Yok</h2>
        <p>İlk siparişinizi vermek ister misiniz?</p>
        <a href="products.php" class="btn btn-primary btn-lg">Alışverişe Başla</a>
      </div>
      <?php endif; ?>
    </div>

    <!-- ORDERS -->
    <div class="acct-panel" id="panel-orders" style="display:none;">
      <div class="admin-table-box">
        <div class="admin-table-header">
          <h2>Siparişlerim</h2>
          <span class="results-badge"><?php echo count($orders_all); ?> sipariş</span>
        </div>
        <?php if (!empty($orders_all)): ?>
        <div style="overflow-x:auto;">
        <table class="admin-table">
          <thead><tr><th>Sipariş No</th><th>Tarih</th><th>Ürün Sayısı</th><th>Ödeme Yöntemi</th><th>Toplam</th><th>Durum</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($orders_all as $o): ?>
          <tr>
            <td><strong>#<?php echo str_pad($o['order_id'], 5, '0', STR_PAD_LEFT); ?></strong></td>
            <td><?php echo format_date($o['order_date']); ?></td>
            <td><?php echo (int)($o['item_count'] ?? 0); ?> ürün</td>
            <td style="font-size:12px;"><?php echo htmlspecialchars($o['payment_method'] ?? '—'); ?></td>
            <td style="font-weight:700;color:var(--burg);"><?php echo number_format($o['total_amount'], 2, ',', '.'); ?> TL</td>
            <td><?php echo order_status_badge($o['order_status'] ?? 'Beklemede'); ?></td>
            <td><a href="order_detail.php?id=<?php echo $o['order_id']; ?>" class="btn btn-outline btn-sm">Detay</a></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
          <div class="empty-icon">📦</div>
          <h2>Henüz Sipariş Yok</h2>
          <p>Henüz siparişiniz bulunmamaktadır.</p>
          <a href="products.php" class="btn btn-primary btn-lg">Alışverişe Başla</a>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- FAVORITES -->
    <div class="acct-panel" id="panel-favorites" style="display:none;">
      <div class="page-header-bar" style="margin-bottom:20px;">
        <h1 style="font-family:var(--serif);font-size:24px;">♡ Favorilerim</h1>
        <?php if (!empty($favorites)): ?><span class="results-badge"><?php echo count($favorites); ?> ürün</span><?php endif; ?>
      </div>
      <?php if (!empty($favorites)): ?>
      <div class="favorites-grid">
        <?php foreach ($favorites as $fav):
          $has_disc = $fav['discount_price'] && $fav['discount_price'] < $fav['price'];
          $disp = $has_disc ? $fav['discount_price'] : $fav['price'];
          $img  = '../img/products/' . ($fav['image_url'] ?? '');
          $has_img = !empty($fav['image_url']) && file_exists($img);
        ?>
        <div class="fav-card">
          <div class="fav-card-img">
            <?php if ($has_img): ?><img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($fav['name']); ?>"><?php else: ?><div class="img-placeholder">✦</div><?php endif; ?>
            <?php if ($has_disc): $pct = round(($fav['price']-$fav['discount_price'])/$fav['price']*100); ?><span class="discount-badge">%<?php echo $pct; ?></span><?php endif; ?>
          </div>
          <div class="fav-card-body">
            <div class="product-brand"><?php echo htmlspecialchars($fav['brand_name']); ?></div>
            <div class="product-name" style="min-height:0;margin-bottom:8px;font-size:13px;font-weight:600;"><?php echo htmlspecialchars($fav['name']); ?></div>
            <div class="price-row">
              <span class="price-current"><?php echo number_format($disp, 2, ',', '.'); ?> TL</span>
              <?php if ($has_disc): ?><span class="price-old"><?php echo number_format($fav['price'], 2, ',', '.'); ?> TL</span><?php endif; ?>
            </div>
            <?php if ($fav['stock_quantity'] > 0): ?><span class="stock-badge in-stock" style="display:inline-block;margin-bottom:10px;">✓ Stokta</span><?php else: ?><span class="stock-badge out-stock" style="display:inline-block;margin-bottom:10px;">✕ Stok Yok</span><?php endif; ?>
            <div class="fav-card-actions">
              <a href="product.php?id=<?php echo $fav['product_id']; ?>" class="btn btn-outline btn-sm">İncele</a>
              <form method="post" action="favorites.php" style="display:inline;">
                <input type="hidden" name="product_id" value="<?php echo $fav['product_id']; ?>">
                <button type="submit" name="remove_favorite" class="btn btn-danger btn-sm" data-confirm="Favorilerden kaldır?">🗑</button>
              </form>
              <?php if ($fav['stock_quantity'] > 0): ?>
              <form method="post" action="favorites.php" style="grid-column:span 2;">
                <input type="hidden" name="product_id" value="<?php echo $fav['product_id']; ?>">
                <button type="submit" name="add_to_cart" class="btn btn-primary btn-sm btn-block">Sepete Ekle</button>
              </form>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="empty-state">
        <div class="empty-icon">♡</div>
        <h2>Favori Listeniz Boş</h2>
        <p>Beğendiğiniz ürünleri favorilerinize ekleyin.</p>
        <a href="products.php" class="btn btn-primary btn-lg">Ürünlere Göz At</a>
      </div>
      <?php endif; ?>
    </div>

    <!-- PROFILE -->
    <div class="acct-panel" id="panel-profile" style="display:none;">
      <div class="admin-form-box">
        <h2 style="font-family:var(--serif);font-size:18px;font-weight:700;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid var(--ivory-dk);">Profil Bilgileri</h2>
        <form method="post" style="max-width:560px;">
          <div class="form-row">
            <div class="form-group">
              <label>Ad Soyad *</label>
              <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
              <label>E-posta *</label>
              <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
            </div>
          </div>
          <div class="form-group">
            <label>Telefon</label>
            <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="05XX XXX XX XX">
          </div>
          <div class="form-group">
            <label>Adres</label>
            <textarea name="address" class="form-control" rows="3" placeholder="Teslimat adresiniz..."><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
          </div>
          <button type="submit" name="update_profile" class="btn btn-primary">✓ Bilgileri Güncelle</button>
        </form>
      </div>
    </div>

    <!-- PASSWORD -->
    <div class="acct-panel" id="panel-password" style="display:none;">
      <div class="admin-form-box">
        <h2 style="font-family:var(--serif);font-size:18px;font-weight:700;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid var(--ivory-dk);">Şifre Değiştir</h2>
        <form method="post" style="max-width:440px;">
          <div class="form-group">
            <label>Mevcut Şifre *</label>
            <input type="password" name="current_password" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Yeni Şifre *</label>
            <input type="password" name="new_password" class="form-control" required placeholder="En az 6 karakter">
          </div>
          <div class="form-group">
            <label>Yeni Şifre Tekrar *</label>
            <input type="password" name="confirm_password" class="form-control" required>
          </div>
          <button type="submit" name="change_password" class="btn btn-primary">🔒 Şifreyi Güncelle</button>
        </form>
      </div>
    </div>

  </div><!-- /acct-content -->
</div>
</div>
</main>

<style>
.acct-nav-link:hover { background: rgba(74,0,16,.06); color: var(--burg) !important; }
.acct-nav-link.active-nav { background: rgba(74,0,16,.08); color: var(--burg) !important; font-weight: 700; }
@media(max-width:768px){ #acct-content { margin-top: 16px; } }
@media(max-width:900px){ .account-grid { grid-template-columns: 1fr !important; } }
</style>

<script>
(function() {
  var panels = document.querySelectorAll('.acct-panel');
  var links  = document.querySelectorAll('[data-tab]');

  function showPanel(id) {
    panels.forEach(function(p){ p.style.display = 'none'; });
    links.forEach(function(l){ l.classList.remove('active-nav'); });
    var panel = document.getElementById('panel-' + id);
    if (panel) panel.style.display = 'block';
    links.forEach(function(l){
      if (l.dataset.tab === id) l.classList.add('active-nav');
    });
    history.replaceState(null,'','#' + id);
  }

  links.forEach(function(link) {
    link.addEventListener('click', function(e) {
      e.preventDefault();
      showPanel(this.dataset.tab);
    });
  });

  // Init from hash or default
  var hash = (location.hash || '#dashboard').replace('#','');
  var valid = ['dashboard','orders','favorites','profile','password'];
  showPanel(valid.includes(hash) ? hash : 'dashboard');
})();
</script>

<?php include '../includes/footer.php'; ?>
