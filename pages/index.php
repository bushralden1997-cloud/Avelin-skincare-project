<?php
require_once '../includes/config.php';
$page_title = 'Ana Sayfa';
$cats  = $conn->query("SELECT c.*,COUNT(p.product_id) as cnt FROM categories c LEFT JOIN products p ON c.category_id=p.category_id WHERE c.category_id<=4 GROUP BY c.category_id ORDER BY c.category_id");
$feat  = $conn->query("SELECT p.*,b.name as bn FROM products p JOIN brands b ON p.brand_id=b.brand_id ORDER BY p.rating DESC LIMIT 8");
$brnds = $conn->query("SELECT * FROM brands ORDER BY name");
$ci    = [1=>'🧴',2=>'👁',3=>'☀',4=>'🛁'];
$favs  = [];
if (!empty($_SESSION['user_id'])) {
    $q = $conn->prepare("SELECT product_id FROM favorites WHERE user_id=?");
    $q->bind_param("i", $_SESSION['user_id']); $q->execute();
    $r = $q->get_result();
    while ($row = $r->fetch_assoc()) { $favs[] = $row['product_id']; }
    $r->free(); $q->close();
}
include '../includes/header.php';
?>

<!-- HERO -->
<section class="hero">
  <div class="hero-floral hero-floral-l">❀</div>
  <div class="hero-floral hero-floral-r">✿</div>
  <div class="hero-pearl"></div>
  <div class="container">
    <div class="hero-inner">
      <div class="hero-content">
        <div class="hero-eyebrow">Glow with Avelin Skincare</div>
        <h1>Cilt Bakımında<br><em>Yeni Standart</em></h1>
        <p class="hero-desc">La Roche-Posay, Bioderma, Cerave ve daha fazlası. Güvenilir markalar, doğru ürünler, kusursuz cilt.</p>
        <div class="hero-btns">
          <a href="products.php" class="btn btn-gold btn-lg">Ürünleri Keşfet &rsaquo;</a>
          <a href="about.php" class="btn btn-outline-wh btn-lg">Hakkımızda</a>
        </div>
      </div>
      <div class="hero-visual">

    <div class="hero-swan-box">
        <img src="../img/Gold-swan.img16.PNG" alt="AVELIN SWAN" class="hero-swan-img">
    </div>

    <img src="../img/Gold-flower.png" class="hero-flower-img" alt="Flower">

    <img src="../img/Gold-flower.png" class="hero-flower-left" alt="Flower">

    <img src="../img/PRODUCT-GOLD.PNG" class="hero-product" alt="Product">

    
    <div class="hero-floor"></div> 
  </div> 

    </div>
  </div> 
 </section>

<!-- KATEGORİLER -->
<section class="categories-strip">
  <div class="container">
    <div class="section-title">
      <h2>Kategoriler</h2>
      <div class="gold-line"><span class="gold-line-icon">✦</span></div>
    </div>
    <div class="categories-grid">
      <?php while ($c = $cats->fetch_assoc()): ?>
      <a href="products.php?category=<?php echo $c['category_id']; ?>" class="category-card">
        <span class="cat-icon"><?php echo $ci[$c['category_id']] ?? '✦'; ?></span>
        <h3><?php echo htmlspecialchars($c['name']); ?></h3>
        <p><?php echo (int)$c['cnt']; ?> ürün</p>
      </a>
      <?php endwhile; ?>
    </div>
  </div>
</section>

<!-- ÖNE ÇIKAN ÜRÜNLER -->
<section class="featured-section">
  <div class="container">
    <div class="section-title">
      <h2>Öne Çıkan Ürünler</h2>
      <div class="gold-line"><span class="gold-line-icon">✦</span></div>
      <p>En yüksek puanlı ürünler</p>
    </div>
    <div class="products-grid">
    <?php while ($p = $feat->fetch_assoc()):
      $fav  = in_array($p['product_id'], $favs);
      $disc = $p['discount_price'] && $p['discount_price'] < $p['price'];
      $disp = $disc ? $p['discount_price'] : $p['price'];
      $dp   = $disc ? round(($p['price']-$p['discount_price'])/$p['price']*100) : 0;
      $img  = '../img/products/' . $p['image_url'];
      $has  = !empty($p['image_url']) && file_exists($img);
    ?>
    <div class="product-card">
      <div class="product-card-img">
        <?php if ($has): ?><img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($p['name']); ?>" loading="lazy"><?php else: ?><div class="img-placeholder">✦</div><?php endif; ?>
        <?php if ($disc): ?><span class="discount-badge">%<?php echo $dp; ?></span><?php endif; ?>
        <button class="fav-btn<?php echo $fav?' active':''; ?>" data-product-id="<?php echo $p['product_id']; ?>"><?php echo $fav?'♥':'♡'; ?></button>
      </div>
      <div class="product-card-body">
        <div class="product-brand"><?php echo htmlspecialchars($p['bn']); ?></div>
        <h3 class="product-name"><a href="product.php?id=<?php echo $p['product_id']; ?>"><?php echo htmlspecialchars($p['name']); ?></a></h3>
        <div class="rating-row"><span class="stars"><?php $r=round($p['rating']);for($i=1;$i<=5;$i++) echo $i<=$r?'★':'☆'; ?></span><span class="rating-count"><?php echo number_format($p['rating'],1); ?></span></div>
        <div class="price-row">
          <span class="price-current"><?php echo number_format($disp,2,',','.'); ?> TL</span>
          <?php if ($disc): ?><span class="price-old"><?php echo number_format($p['price'],2,',','.'); ?> TL</span><?php endif; ?>
        </div>
        <a href="product.php?id=<?php echo $p['product_id']; ?>" class="btn btn-primary btn-sm btn-block">İncele</a>
      </div>
    </div>
    <?php endwhile; ?>
    </div>
    <div style="text-align:center;margin-top:38px;"><a href="products.php" class="btn btn-outline btn-lg">Tüm Ürünleri Gör</a></div>
  </div>
</section>

<!-- MARKALAR -->
<section class="brands-strip">
  <div class="container">
    <div class="section-title" style="margin-bottom:26px;"><h2>Markalar</h2><div class="gold-line"><span class="gold-line-icon">✦</span></div></div>
    <div class="brands-logos">
      <?php while ($b = $brnds->fetch_assoc()): ?>
      <a href="products.php?brand=<?php echo $b['brand_id']; ?>" class="brand-logo-card"><?php echo htmlspecialchars($b['name']); ?></a>
      <?php endwhile; ?>
    </div>
  </div>
</section>

<!-- NEDEN AVELIN -->
<section class="why-us">
  <div class="container">
    <div class="section-title" style="color:#fff;">
      <h2 style="color:#fff;">Neden AVELIN?</h2>
      <div class="gold-line" style="opacity:.5;"><span class="gold-line-icon">✦</span></div>
    </div>
    <div class="features-grid">
      <div class="feature-card"><div class="feature-icon">✦</div><h3>Seçkin Ürünler</h3><p>Dünyaca tanınan dermatoloji markaları, doğrulanmış formüller.</p></div>
      <div class="feature-card"><div class="feature-icon">♡</div><h3>Favoriler</h3><p>Beğendiğiniz ürünleri kaydedin, istediğinizde kolayca bulun.</p></div>
      <div class="feature-card"><div class="feature-icon">◻</div><h3>Kolay Sepet</h3><p>Miktar yönetimi, favori taşıma ve anlık toplam güncelleme.</p></div>
      <div class="feature-card"><div class="feature-icon">⚙</div><h3>Admin Paneli</h3><p>Ürün, kategori ve marka yönetimi tam kapsamlı kontrol.</p></div>
    </div>
  </div>
</section>

<?php include '../includes/footer.php'; ?>
