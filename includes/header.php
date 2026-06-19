<?php
if (!isset($page_title)) $page_title = 'AVELIN Skincare';

// Cart count
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $cc = $conn->prepare("SELECT COALESCE(SUM(ci.quantity),0) as t FROM cart c JOIN cart_items ci ON c.cart_id=ci.cart_id WHERE c.user_id=?");
    $cc->bind_param("i", $_SESSION['user_id']); $cc->execute();
    $cart_count = (int)$cc->get_result()->fetch_assoc()['t'];
} elseif (isset($_SESSION['cart'])) {
    $cart_count = array_sum($_SESSION['cart']);
}
// Fav count
$fav_count = 0;
if (isset($_SESSION['user_id'])) {
    $fq = $conn->prepare("SELECT COUNT(*) as t FROM favorites WHERE user_id=?");
    $fq->bind_param("i", $_SESSION['user_id']); $fq->execute();
    $fav_count = (int)$fq->get_result()->fetch_assoc()['t'];
}
$cur = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($page_title); ?> — AVELIN Skincare</title>
<link rel="stylesheet" href="../css/style.css?v=999">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<header class="site-header">
  <div class="top-bar">
    <div class="container">
      <span>🦢 Glow with Avelin Skincare ✨</span>
      <span>|</span>
      <span>🚚 150 TL üzeri siparişlerde ücretsiz kargo</span>
    </div>
  </div>
  <div class="header-main">
    <div class="container header-inner">
      <a href="index.php" class="logo">
        <img src="../img/Gold-swan.img16.PNG" alt="AVELIN" class="logo-swan">
        <div class="logo-text">
          <span class="logo-name">AVELIN</span>
          <span class="logo-sub">SKINCARE</span>
        </div>
      </a>

      <div class="header-actions">
        <a href="index.php" class="action-link <?php echo $cur==='index.php'?'active':''; ?>">
          <span class="action-label">ANA SAYFA</span>
        </a>
        <a href="products.php" class="action-link <?php echo $cur==='products.php'?'active':''; ?>">
          <span class="action-label">ÜRÜNLER</span>
        </a>
        <a href="brands.php" class="action-link <?php echo $cur==='brands.php'?'active':''; ?>">
          <span class="action-label">MARKALAR</span>
        </a>
        <a href="about.php" class="action-link <?php echo $cur==='about.php'?'active':''; ?>">
          <span class="action-label">HAKKIMIZDA</span>
        </a>
        <a href="contact.php" class="action-link <?php echo $cur==='contact.php'?'active':''; ?>">
          <span class="action-label">İLETİŞİM</span>
        </a>

        <form action="products.php" method="get" style="display:flex;align-items:center;gap:6px;margin:0 6px;">
          <input type="text" name="search" placeholder="Ürün Ara..." style="padding:7px 12px;border:1.5px solid var(--border);border-radius:20px;width:150px;font-size:12px;outline:none;font-family:var(--sans);">
          <button type="submit" style="background:none;border:none;cursor:pointer;font-size:16px;line-height:1;">🔎</button>
        </form>

        <a href="favorites.php" class="action-link <?php echo $cur==='favorites.php'?'active':''; ?>">
          <span class="action-icon">♡</span>
          <span class="action-label">Favoriler</span>
          <?php if ($fav_count > 0): ?><span class="badge"><?php echo $fav_count; ?></span><?php endif; ?>
        </a>
        <a href="cart.php" class="action-link <?php echo $cur==='cart.php'?'active':''; ?>">
          <span class="action-icon">◻</span>
          <span class="action-label">Sepet</span>
          <?php if ($cart_count > 0): ?><span class="badge"><?php echo $cart_count; ?></span><?php endif; ?>
        </a>
        <?php if (isset($_SESSION['user_id'])): ?>
          <a href="account.php" class="action-link <?php echo $cur==='account.php'?'active':''; ?>">
            <span class="action-icon">◉</span>
            <span class="action-label">Hesabım</span>
          </a>
          <?php if (is_admin()): ?>
          <a href="admin/index.php" class="action-link admin-link">
            <span class="action-icon">⚙</span>
            <span class="action-label">Admin</span>
          </a>
          <?php endif; ?>
          <a href="logout.php" class="action-link">
            <span class="action-label">Çıkış</span>
          </a>
        <?php else: ?>
          <a href="login.php" class="action-link <?php echo $cur==='login.php'?'active':''; ?>">
            <span class="action-icon">◉</span>
            <span class="action-label">Giriş</span>
          </a>
        <?php endif; ?>
        <button class="mobile-menu-btn" id="mobileMenuBtn">☰</button>
      </div>
    </div>
  </div>
</header>
