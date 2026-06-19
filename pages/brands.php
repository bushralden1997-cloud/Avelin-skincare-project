<?php
require_once '../includes/config.php';
$page_title='Markalar';
$brands_q=$conn->query("SELECT b.*,COUNT(p.product_id) as cnt FROM brands b LEFT JOIN products p ON b.brand_id=p.brand_id GROUP BY b.brand_id ORDER BY b.name");
$styles=['Neutrogena'=>['bg'=>'#D4EBF8','color'=>'#1A5A8A'],'La Roche-Posay'=>['bg'=>'#F5EAED','color'=>'#8B2A3A'],'Bioderma'=>['bg'=>'#F0ECE3','color'=>'#7A6040'],'Cerave'=>['bg'=>'#E8F0E8','color'=>'#2A5030'],'Avène'=>['bg'=>'#E3EFF5','color'=>'#2A6070'],'Vichy'=>['bg'=>'#EDE8F0','color'=>'#5A3070'],'The Ordinary'=>['bg'=>'#ECEBEB','color'=>'#444444']];
$descs=['Neutrogena'=>'Dermatolojik test edilmiş formülleriyle tanınan Amerikan markası.','La Roche-Posay'=>'Hassas ve sorunlu ciltler için La Roche-Posay termal kaynağından beslenen dermatolojik marka.','Bioderma'=>'Biyolojik uyumu temel alan Fransız dermatoloji markası. Sensibio H2O ile dünya lideri.','Cerave'=>'Dermatologlar tarafından geliştirilen seramid içerikli marka. MVE teknolojisi ile cilt bariyeri.','Avène'=>'Avène termal kaynağının sakinleştirici suyunu içeren ürünleriyle hassas ciltlerin tercihi.','Vichy'=>'Volkanik mineralleştirilmiş termal suyunu kullanan dermatoloji markası.','The Ordinary'=>'İçerik şeffaflığı ve klinik formüller üzerine kurulu İngiliz markası.'];
include '../includes/header.php';
?>
<div class="breadcrumb"><div class="container"><a href="index.php">Ana Sayfa</a><span class="sep">/</span><span class="current">Markalar</span></div></div>
<div class="about-hero" style="padding:55px 0;"><div class="container" style="position:relative;z-index:1;"><h1 style="font-size:38px;">Markalar</h1><p>Platformda yer alan dermatoloji markaları</p></div></div>
<section class="brands-section"><div class="container"><div class="brands-full-grid">
<?php while($b=$brands_q->fetch_assoc()):$s=$styles[$b['name']]??['bg'=>'#F0F0F0','color'=>'#444'];$d=$descs[$b['name']]??'';?>
<div class="brand-card-full">
  <div class="brand-card-header" style="background:<?php echo $s['bg'];?>;color:<?php echo $s['color'];?>;"><?php echo htmlspecialchars($b['name']);?></div>
  <div class="brand-card-body">
    <?php if($b['country']):?><div class="brand-origin"><?php echo htmlspecialchars($b['country']);?><?php if($b['founded_year']): ?> · <?php echo $b['founded_year'];?>'den beri<?php endif;?></div><?php endif;?>
    <h3><?php echo htmlspecialchars($b['name']);?></h3>
    <p><?php echo htmlspecialchars($d);?></p>
    <div style="display:flex;justify-content:space-between;align-items:center;">
      <span style="font-size:12px;color:var(--gray);"><?php echo (int)$b['cnt'];?> ürün</span>
      <a href="products.php?brand=<?php echo $b['brand_id'];?>" class="btn btn-outline btn-sm">Ürünleri Gör</a>
    </div>
  </div>
</div>
<?php endwhile;?>
</div></div></section>
<?php include '../includes/footer.php';?>
