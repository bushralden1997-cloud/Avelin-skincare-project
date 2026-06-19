<?php
 require_once '../includes/config.php';
$cat_id   =isset($_GET['category'])  ?(int)$_GET['category']  :0;
$brand_id =isset($_GET['brand'])     ?(int)$_GET['brand']     :0;
$search   =isset($_GET['search'])    ?trim($_GET['search'])    :'';
$sort_by  =isset($_GET['sort']) ? trim($_GET['sort'])  :'name_asc';
$min_price=isset($_GET['min_price']) ?(float)$_GET['min_price']:0;
$max_price=isset($_GET['max_price']) ?(float)$_GET['max_price']:0;
$skin_type=isset($_GET['skin_type']) ?trim($_GET['skin_type']) :'';
$page     =max(1,(int)($_GET['page']??1));$per=12;

$where='WHERE 1=1';$params=[];$types='';
if($cat_id)  {$where.=" AND p.category_id=?";$params[]=$cat_id;  $types.='i';}
if($brand_id){$where.=" AND p.brand_id=?";   $params[]=$brand_id;$types.='i';}
if($search)  {$s="%$search%";$where.=" AND (p.name LIKE ? OR b.name LIKE ?)";$params[]=$s;$params[]=$s;$types.='ss';}
if($min_price>0){$where.=" AND COALESCE(p.discount_price,p.price)>=?";$params[]=$min_price;$types.='d';}
if($max_price>0){$where.=" AND COALESCE(p.discount_price,p.price)<=?";$params[]=$max_price;$types.='d';}
if($skin_type){$where.=" AND p.skin_type LIKE ?";$params[]="%$skin_type%";$types.='s';}

$order=match($sort_by){
    'price_asc'   =>"ORDER BY COALESCE(p.discount_price,p.price) ASC",
    'price_desc'  =>"ORDER BY COALESCE(p.discount_price,p.price) DESC",
    'name_desc'   =>"ORDER BY p.name DESC",
    'rating_desc' =>"ORDER BY p.rating DESC",
    'newest'      =>"ORDER BY p.created_at DESC",
    default       =>"ORDER BY p.name ASC",
};

$base="FROM products p JOIN brands b ON p.brand_id=b.brand_id JOIN categories c ON p.category_id=c.category_id $where";
$cs=$conn->prepare("SELECT COUNT(*) as t $base");if($params)$cs->bind_param($types,...$params);$cs->execute();
$total=(int)$cs->get_result()->fetch_assoc()['t'];$total_pages=ceil($total/$per);$offset=($page-1)*$per;

$ds=$conn->prepare("SELECT p.*,b.name as brand_name,c.name as cat_name $base $order LIMIT ?,?");
$ap=array_merge($params,[$offset,$per]);$at=$types.'ii';$ds->bind_param($at,...$ap);$ds->execute();
$products=$ds->get_result();
$products_data = $products->fetch_all(MYSQLI_ASSOC);

$products->free();

$ds->close();

$crumb_cat='';
$crumb_brand='';

if($cat_id){
    $r = $conn->prepare("SELECT name FROM categories WHERE category_id=?");
    $r->bind_param("i",$cat_id);
    $r->execute();

    $res = $r->get_result();
    $row = $res->fetch_assoc();

    $crumb_cat = $row['name'] ?? '';

    $res->free();
    $r->close();
}

if($brand_id){
    $r = $conn->prepare("SELECT name FROM brands WHERE brand_id=?");
    $r->bind_param("i",$brand_id);
    $r->execute();

    $res = $r->get_result();
    $row = $res->fetch_assoc();

    $crumb_brand = $row['name'] ?? '';

    $res->free();
    $r->close();
}

$user_favs = [];

if(!empty($_SESSION['user_id'])){
    $fq = $conn->prepare("SELECT product_id FROM favorites WHERE user_id=?");
    $fq->bind_param("i", $_SESSION['user_id']);
    $fq->execute();

    $res = $fq->get_result();

    while($row = $res->fetch_assoc()){
        $user_favs[] = $row['product_id'];
    }

    $res->free();
    $fq->close();
}

function purl($ov=[]){
    $keys=['category','brand','search','sort','min_price','max_price','skin_type','page'];
    $p=[];foreach($keys as $k){if(isset($_GET[$k])&&$_GET[$k]!=='')$p[$k]=$_GET[$k];}
    foreach($ov as $k=>$v){if($v===''||$v===null||$v===0)unset($p[$k]);else $p[$k]=$v;}
    if(!isset($ov['page']))unset($p['page']);if(isset($ov['page']))$p['page']=$ov['page'];
    return 'products.php?'.http_build_query($p);
}
$cats_q = $conn->query("SELECT c.*, COUNT(p.product_id) as cnt FROM categories c LEFT JOIN products p ON c.category_id=p.category_id GROUP BY c.category_id ORDER BY c.name");

$brands_q = $conn->query("SELECT b.*, COUNT(p.product_id) as cnt FROM brands b LEFT JOIN products p ON b.brand_id=p.brand_id GROUP BY b.brand_id ORDER BY b.name");
$page_title=$crumb_cat?:($crumb_brand?:($search?"Arama: $search":'Ürünler'));
include '../includes/header.php';
?>
<div class="breadcrumb"><div class="container">
  <a href="index.php">Ana Sayfa</a><span class="sep">/</span><a href="products.php">Ürünler</a>
  <?php if($crumb_cat):?><span class="sep">/</span><span class="current"><?php echo htmlspecialchars($crumb_cat);?></span><?php endif;?>
  <?php if($crumb_brand):?><span class="sep">/</span><span class="current"><?php echo htmlspecialchars($crumb_brand);?></span><?php endif;?>
</div></div>

<main><div class="container"><div class="products-layout">
<aside class="sidebar">
  <div class="sidebar-title">Filtrele</div>
  <div class="filter-block">
    <h4>Kategoriler</h4>
    <div class="filter-list">
      <a href="<?php echo purl(['category'=>null]);?>" class="<?php echo !$cat_id?'active':'';?>">Tümü</a>
      <?php while($cat=$cats_q->fetch_assoc()):?>
      <a href="<?php echo purl(['category'=>$cat['category_id']]);?>" class="<?php echo $cat_id==$cat['category_id']?'active':'';?>">
        <?php echo htmlspecialchars($cat['name']);?><span class="filter-count"><?php echo $cat['cnt'];?></span>
      </a>
      <?php endwhile;?>
    </div>
  </div>
  <div class="filter-block">
    <h4>Markalar</h4>
    <div class="filter-list">
      <a href="<?php echo purl(['brand'=>null]);?>" class="<?php echo !$brand_id?'active':'';?>">Tümü</a>
      <?php while($b=$brands_q->fetch_assoc()):if(!$b['cnt'])continue;?>
      <a href="<?php echo purl(['brand'=>$b['brand_id']]);?>" class="<?php echo $brand_id==$b['brand_id']?'active':'';?>">
        <?php echo htmlspecialchars($b['name']);?><span class="filter-count"><?php echo $b['cnt'];?></span>
      </a>
      <?php endwhile;?>
    </div>
  </div>
  <div class="filter-block">
    <h4>Cilt Tipi</h4>
    <div class="filter-list">
      <a href="<?php echo purl(['skin_type'=>null]);?>" class="<?php echo !$skin_type?'active':'';?>">Tümü</a>
      <?php foreach(['Normal Cilt','Kuru Cilt','Yağlı Cilt','Karma Cilt','Hassas Cilt'] as $st):?>
      <a href="<?php echo purl(['skin_type'=>$st]);?>" class="<?php echo $skin_type===$st?'active':'';?>"><?php echo $st;?></a>
      <?php endforeach;?>
    </div>
  </div>
  <div class="filter-block">
    <h4>Fiyat Aralığı (TL)</h4>
    <form method="get">
      <?php if($cat_id):?><input type="hidden" name="category" value="<?php echo $cat_id;?>"><?php endif;?>
      <?php if($brand_id):?><input type="hidden" name="brand" value="<?php echo $brand_id;?>"><?php endif;?>
      <?php if($search):?><input type="hidden" name="search" value="<?php echo htmlspecialchars($search);?>"><?php endif;?>
      <?php if($skin_type):?><input type="hidden" name="skin_type" value="<?php echo htmlspecialchars($skin_type);?>"><?php endif;?>
      <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort_by);?>">
      <div style="margin-bottom:12px;">
    <input type="text"
           name="search"
           placeholder="Ürün Ara..."
           value="<?php echo htmlspecialchars($search); ?>"
           class="form-control"
           style="width:100%;">
</div>
      <div class="price-inputs" style="display:flex;flex-direction:column;gap:10px;">
        <input type="number" name="min_price" placeholder="Min" value="<?php echo $min_price?:''?>">
        <span>–</span>
        <input type="number" name="max_price" placeholder="Max" value="<?php echo $max_price?:''?>">
      </div>
      <button type="submit" class="btn btn-primary btn-sm btn-block" style="margin-top:10px;">Uygula</button>
    </form>
  </div>
  <?php if($cat_id||$brand_id||$search||$min_price||$max_price||$skin_type):?>
  <a href="products.php" class="btn btn-outline btn-sm btn-block">Filtreleri Temizle</a>
  <?php endif;?>
</aside>

<div class="products-main">
  <div class="products-toolbar">
    <div class="products-count"><strong><?php echo $total;?></strong> ürün<?php if($search):?> — "<?php echo htmlspecialchars($search);?>"<?php endif;?></div>
    <select id="sortSelect" class="sort-select">
      <option value="name_asc"   <?php echo $sort_by==='name_asc'   ?'selected':'';?>>İsim A→Z</option>
      <option value="name_desc"  <?php echo $sort_by==='name_desc'  ?'selected':'';?>>İsim Z→A</option>
      <option value="price_asc"  <?php echo $sort_by==='price_asc'  ?'selected':'';?>>Fiyat ↑</option>
      <option value="price_desc" <?php echo $sort_by==='price_desc' ?'selected':'';?>>Fiyat ↓</option>
      <option value="rating_desc"<?php echo $sort_by==='rating_desc'?'selected':'';?>>En Yüksek Puan</option>
      <option value="newest"     <?php echo $sort_by==='newest'     ?'selected':'';?>>En Yeniler</option>
    </select>
  </div>

  <?php if($total>0):?>
  <div class="products-grid">
  <?php foreach($products_data as $p): ?>
  <?php
    $is_fav=in_array($p['product_id'],$user_favs);
    $has_disc=$p['discount_price']&&$p['discount_price']<$p['price'];
    $disp=$has_disc?$p['discount_price']:$p['price'];
    $disc_pct=$has_disc?round(($p['price']-$p['discount_price'])/$p['price']*100):0;
    $img="../img/products/".$p['image_url'];$has_img=!empty($p['image_url'])&&file_exists($img);
  ?>
  <div class="product-card">
    <div class="product-card-img">
      <?php if($has_img):?><img src="<?php echo $img;?>" alt="<?php echo htmlspecialchars($p['name']);?>" loading="lazy"><?php else:?><div class="img-placeholder">✦</div><?php endif;?>
      <?php if($has_disc):?><span class="discount-badge">%<?php echo $disc_pct;?></span><?php endif;?>
      <button class="fav-btn <?php echo $is_fav?'active':'';?>" data-product-id="<?php echo $p['product_id'];?>"><?php echo $is_fav?'♥':'♡';?></button>
    </div>
    <div class="product-card-body">
      <div class="product-brand"><?php echo htmlspecialchars($p['brand_name']);?></div>
      <h3 class="product-name"><a href="product.php?id=<?php echo $p['product_id'];?>"><?php echo htmlspecialchars($p['name']);?></a></h3>
      <div class="rating-row"><span class="stars"><?php $r=round($p['rating']);for($i=1;$i<=5;$i++)echo $i<=$r?'★':'☆';?></span><span class="rating-count"><?php echo number_format($p['rating'],1);?></span></div>
      <div class="price-row"><span class="price-current"><?php echo number_format($disp,2,',','.');?> TL</span><?php if($has_disc):?><span class="price-old"><?php echo number_format($p['price'],2,',','.');?> TL</span><?php endif;?></div>
      <a href="product.php?id=<?php echo $p['product_id'];?>" class="btn btn-primary btn-sm btn-block">İncele</a>
    </div>
  </div>
  <?php endforeach;?>
  </div>
  <?php if($total_pages>1):?>
  <div class="pagination">
    <?php if($page>1):?><a href="<?php echo purl(['page'=>$page-1]);?>">‹</a><?php endif;?>
    <?php $s=max(1,$page-2);$e=min($total_pages,$page+2);
    if($s>1){echo '<a href="'.purl(['page'=>1]).'">1</a>';if($s>2)echo '<span>…</span>';}
    for($i=$s;$i<=$e;$i++)echo '<a href="'.purl(['page'=>$i]).'"'.($i==$page?' class="active"':'').'>'.$i.'</a>';
    if($e<$total_pages){if($e<$total_pages-1)echo '<span>…</span>';echo '<a href="'.purl(['page'=>$total_pages]).'">'.$total_pages.'</a>';}
    if($page<$total_pages):?><a href="<?php echo purl(['page'=>$page+1]);?>">›</a><?php endif;?>
  </div>
  <?php endif;?>
  <?php else:?>
  <div class="no-results"><h3>Ürün Bulunamadı</h3><p>Farklı filtreler deneyin.</p><a href="products.php" class="btn btn-primary">Tüm Ürünleri Gör</a></div>
  <?php endif;?>
</div>
</div></div></main>
<?php include '../includes/footer.php';?>
