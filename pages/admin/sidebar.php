<aside class="admin-sidebar">
  <div class="admin-sidebar-header">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
      <img src="../../img/Gold-swan.img16.PNG" alt="" style="width:34px;height:34px;filter:brightness(1.4);">
      <div><div class="admin-logo-name">AVELIN</div><div class="admin-logo-sub">SKINCARE</div></div>
    </div>
    <div class="admin-subtitle">Yönetici Paneli</div>
  </div>
  <nav class="admin-nav">
    <?php $cur = basename($_SERVER['PHP_SELF']); ?>
    <div class="nav-section">Genel</div>
    <a href="index.php" class="<?php echo $cur==='index.php'?'active':''; ?>"><span class="nav-icon">◈</span> Dashboard</a>
    <div class="nav-section">Siparişler</div>
    <a href="orders.php" class="<?php echo $cur==='orders.php'?'active':''; ?>"><span class="nav-icon">📦</span> Siparişler</a>
    <div class="nav-section">Ürünler</div>
    <a href="products.php" class="<?php echo $cur==='products.php'?'active':''; ?>"><span class="nav-icon">◻</span> Ürün Listesi</a>
    <a href="product_add.php" class="<?php echo $cur==='product_add.php'?'active':''; ?>"><span class="nav-icon">+</span> Ürün Ekle</a>
    <div class="nav-section">Katalog</div>
    <a href="categories.php" class="<?php echo $cur==='categories.php'?'active':''; ?>"><span class="nav-icon">◇</span> Kategoriler</a>
    <a href="brands_admin.php" class="<?php echo $cur==='brands_admin.php'?'active':''; ?>"><span class="nav-icon">✦</span> Markalar</a>
    <div class="nav-section">Kullanıcılar</div>
    <a href="users.php" class="<?php echo $cur==='users.php'?'active':''; ?>"><span class="nav-icon">◉</span> Kullanıcılar</a>
    <div class="nav-section">Site</div>
    <a href="../index.php"><span class="nav-icon">←</span> Siteye Dön</a>
    <a href="../logout.php"><span class="nav-icon">⊘</span> Çıkış Yap</a>
  </nav>
</aside>
