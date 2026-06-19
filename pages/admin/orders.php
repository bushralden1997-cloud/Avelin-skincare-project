<?php
require_once '../../includes/config.php';
if (!is_logged_in() || !is_admin()) redirect('../login.php');

$flash = '';

// Update order status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $oid = (int)($_POST['order_id'] ?? 0);
    $ns  = trim($_POST['new_status'] ?? '');
    $allowed = ['Beklemede','İşleniyor','Kargoya Verildi','Teslim Edildi','İptal Edildi'];
    if ($oid > 0 && in_array($ns, $allowed)) {
        $upd = $conn->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
        $upd->bind_param("si", $ns, $oid);
        if ($upd->execute()) {
            $_SESSION['flash'] = 'Sipariş durumu güncellendi.';
        }
    }
    header("Location: orders.php"); exit;
}

$flash = $_SESSION['flash'] ?? ''; unset($_SESSION['flash']);

// Filters
$search  = trim($_GET['search'] ?? '');
$sort    = ($_GET['sort'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
$status_f = trim($_GET['status'] ?? '');

$where = "WHERE 1=1";
$params = []; $types = '';

if ($search !== '') {
    $like = "%$search%";
    $where .= " AND (o.order_id LIKE ? OR o.name LIKE ? OR o.email LIKE ? OR o.phone LIKE ?)";
    $params = array_merge($params, [$like,$like,$like,$like]);
    $types .= 'ssss';
}
if ($status_f !== '') {
    $where .= " AND o.order_status = ?";
    $params[] = $status_f; $types .= 's';
}

$sql = "SELECT o.*, u.username, u.email as u_email, COUNT(oi.order_item_id) as item_count,
        GROUP_CONCAT(CONCAT(p.name, ' (', oi.quantity, ')') SEPARATOR ', ') as products_summary
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.user_id
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.product_id
        $where
        GROUP BY o.order_id
        ORDER BY o.order_date " . strtoupper($sort);

if ($types) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $orders = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
}

// Summary counts
$all_sql = "SELECT order_status, COUNT(*) as cnt FROM orders GROUP BY order_status";
$status_counts = [];
$sr = $conn->query($all_sql);
while ($row = $sr->fetch_assoc()) { $status_counts[$row['order_status']] = (int)$row['cnt']; }
$total_orders = array_sum($status_counts);
$total_revenue = $conn->query("SELECT COALESCE(SUM(total_amount),0) as t FROM orders")->fetch_assoc()['t'];

$statuses = ['Beklemede','İşleniyor','Kargoya Verildi','Teslim Edildi','İptal Edildi'];
function sbadge($s) {
    $m=['Beklemede'=>['#FFF8E1','#E65100'],'İşleniyor'=>['#E3F2FD','#1565C0'],'Kargoya Verildi'=>['#E8F5E9','#2E7D32'],'Teslim Edildi'=>['#E8F5E9','#1B5E20'],'İptal Edildi'=>['#FFEBEE','#B71C1C']];
    $d=$m[$s]??['#F5EDD8','#906828'];
    return "<span style=\"background:{$d[0]};color:{$d[1]};padding:3px 10px;border-radius:14px;font-size:11px;font-weight:700;\">".htmlspecialchars($s)."</span>";
}
?>
<!DOCTYPE html><html lang="tr"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Siparişler — AVELIN Admin</title>
<link rel="stylesheet" href="../../css/style.css">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
.filter-bar{display:flex;gap:10px;align-items:center;flex-wrap:wrap;}
.filter-bar input,.filter-bar select{padding:9px 13px;border:1.5px solid var(--border);border-radius:var(--r);font-size:12px;background:var(--white);font-family:var(--sans);outline:none;}
.filter-bar input:focus,.filter-bar select:focus{border-color:var(--gold);}
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9000;align-items:flex-start;justify-content:center;padding:40px 16px;overflow-y:auto;}
.modal-overlay.open{display:flex;}
.modal-box{background:var(--white);border-radius:var(--r-lg);max-width:600px;width:100%;box-shadow:0 20px 60px rgba(0,0,0,.25);margin:auto;}
.modal-head{background:linear-gradient(135deg,var(--burg-dk),var(--burg));padding:18px 24px;border-radius:var(--r-lg) var(--r-lg) 0 0;display:flex;justify-content:space-between;align-items:center;}
.modal-head h3{color:#fff;font-family:var(--serif);font-size:18px;margin:0;}
.modal-close{background:none;border:none;color:rgba(255,255,255,.8);font-size:22px;cursor:pointer;line-height:1;}
.modal-body{padding:22px 24px;}
.mrow{display:flex;justify-content:space-between;padding:9px 0;border-bottom:1px solid var(--ivory-dk);font-size:13px;}
.mrow:last-child{border-bottom:none;}
.mlabel{color:var(--gray);font-weight:600;flex-shrink:0;}
.mvalue{color:var(--charcoal);font-weight:500;text-align:right;max-width:65%;}
.stat-mini{background:var(--white);border:1px solid var(--border);border-radius:var(--r-lg);padding:14px 18px;display:flex;align-items:center;gap:12px;}
.stat-mini-icon{width:40px;height:40px;border-radius:var(--r);display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;}
</style>
</head><body>
<div class="admin-layout">
<?php include 'sidebar.php'; ?>
<main class="admin-main">
  <div class="admin-page-header">
    <h1>Sipariş Yönetimi</h1>
    <?php if ($flash): ?>
    <span class="alert alert-success" style="margin:0;padding:7px 14px;">✓ <?php echo htmlspecialchars($flash); ?></span>
    <?php endif; ?>
  </div>

  <!-- Stats -->
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px;margin-bottom:20px;">
    <div class="stat-mini">
      <div class="stat-mini-icon" style="background:rgba(74,0,16,.09);">📦</div>
      <div><div class="admin-stat-value"><?php echo $total_orders; ?></div><div class="admin-stat-label">Toplam Sipariş</div></div>
    </div>
    <div class="stat-mini">
      <div class="stat-mini-icon" style="background:var(--gold-pale);">💰</div>
      <div><div class="admin-stat-value" style="font-size:16px;"><?php echo number_format((float)$total_revenue,2,',','.'); ?> TL</div><div class="admin-stat-label">Toplam Ciro</div></div>
    </div>
    <div class="stat-mini">
      <div class="stat-mini-icon" style="background:#FFF8E1;">⏳</div>
      <div><div class="admin-stat-value"><?php echo $status_counts['Beklemede'] ?? 0; ?></div><div class="admin-stat-label">Beklemede</div></div>
    </div>
    <div class="stat-mini">
      <div class="stat-mini-icon" style="background:#E3F2FD;">⚙</div>
      <div><div class="admin-stat-value"><?php echo $status_counts['İşleniyor'] ?? 0; ?></div><div class="admin-stat-label">İşleniyor</div></div>
    </div>
    <div class="stat-mini">
      <div class="stat-mini-icon" style="background:#E8F5E9;">🚚</div>
      <div><div class="admin-stat-value"><?php echo $status_counts['Kargoya Verildi'] ?? 0; ?></div><div class="admin-stat-label">Kargoda</div></div>
    </div>
    <div class="stat-mini">
      <div class="stat-mini-icon" style="background:#E8F5E9;">✓</div>
      <div><div class="admin-stat-value"><?php echo $status_counts['Teslim Edildi'] ?? 0; ?></div><div class="admin-stat-label">Teslim Edildi</div></div>
    </div>
  </div>

  <!-- Filter -->
  <div class="admin-table-box" style="margin-bottom:16px;padding:14px 20px;">
    <form method="get" class="filter-bar" id="filterForm">
      <input type="text" name="search" placeholder="Sipariş No, Ad, E-posta, Tel..." value="<?php echo htmlspecialchars($search); ?>" style="flex:1;min-width:200px;">
      <select name="status">
        <option value="">Tüm Durumlar</option>
        <?php foreach ($statuses as $st): ?>
        <option value="<?php echo $st; ?>" <?php echo $status_f===$st?'selected':''; ?>><?php echo $st; ?></option>
        <?php endforeach; ?>
      </select>
      <select name="sort">
        <option value="desc" <?php echo $sort==='desc'?'selected':''; ?>>En Yeni</option>
        <option value="asc"  <?php echo $sort==='asc' ?'selected':''; ?>>En Eski</option>
      </select>
      <button type="submit" class="btn btn-primary btn-sm">Filtrele</button>
      <a href="orders.php" class="btn btn-outline btn-sm">Temizle</a>
    </form>
  </div>

  <!-- Table -->
  <div class="admin-table-box">
    <div class="admin-table-header">
      <h2>Siparişler <span style="color:var(--gray);font-size:13px;font-weight:400;">(<?php echo count($orders); ?> kayıt)</span></h2>
    </div>
    <div style="overflow-x:auto;">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Sipariş No</th>
          <th>Müşteri</th>
          <th>Tarih</th>
          <th>Ürünler</th>
          <th>Toplam</th>
          <th>Ödeme</th>
          <th>Durum</th>
          <th>İşlem</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($orders)): ?>
        <tr><td colspan="8" style="text-align:center;padding:36px;color:var(--gray);">Sipariş bulunamadı</td></tr>
      <?php else: foreach ($orders as $o): ?>
        <tr>
          <td><strong>#<?php echo str_pad($o['order_id'],5,'0',STR_PAD_LEFT); ?></strong></td>
          <td>
            <div style="font-weight:600;font-size:13px;"><?php echo htmlspecialchars($o['name'] ?: ($o['username'] ?? '—')); ?></div>
            <small style="color:var(--gray);"><?php echo htmlspecialchars($o['email'] ?: $o['u_email'] ?: ''); ?></small><br>
            <?php if (!empty($o['phone'])): ?><small style="color:var(--gray);"><?php echo htmlspecialchars($o['phone']); ?></small><?php endif; ?>
          </td>
          <td style="font-size:12px;"><?php echo format_date($o['order_date']); ?></td>
          <td style="font-size:11px;color:var(--mid);max-width:160px;">
            <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?php echo htmlspecialchars($o['products_summary']??''); ?>">
              <?php echo htmlspecialchars(mb_strimwidth($o['products_summary'] ?? '—', 0, 50, '…')); ?>
            </div>
            <small style="color:var(--gray);"><?php echo (int)($o['item_count']??0); ?> ürün</small>
          </td>
          <td style="font-weight:700;color:var(--burg);"><?php echo number_format($o['total_amount'],2,',','.'); ?> TL</td>
          <td style="font-size:12px;"><?php echo htmlspecialchars($o['payment_method']??'—'); ?></td>
          <td>
            <form method="post" style="display:flex;align-items:center;gap:5px;">
              <input type="hidden" name="order_id" value="<?php echo $o['order_id']; ?>">
              <select name="new_status" style="padding:4px 7px;border:1.5px solid var(--border);border-radius:var(--r);font-size:11px;background:var(--white);outline:none;">
                <?php foreach ($statuses as $st): ?>
                <option value="<?php echo $st; ?>" <?php echo ($o['order_status']??'')===$st?'selected':''; ?>><?php echo $st; ?></option>
                <?php endforeach; ?>
              </select>
              <button type="submit" name="update_status" class="btn btn-gold btn-sm" title="Durumu Güncelle">✓</button>
            </form>
          </td>
          <td>
            <div class="td-actions">
              <button class="btn btn-outline btn-sm" onclick="openModal(<?php echo htmlspecialchars(json_encode($o)); ?>)">Detay</button>
            </div>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
    </div>
  </div>
</main>
</div>

<!-- Modal -->
<div class="modal-overlay" id="detailModal">
  <div class="modal-box">
    <div class="modal-head">
      <h3 id="modal-title">Sipariş Detayı</h3>
      <button class="modal-close" onclick="closeModal()">✕</button>
    </div>
    <div class="modal-body" id="modal-body"></div>
  </div>
</div>

<div id="toast-container"></div>
<script src="../../js/main.js"></script>
<script>
function esc(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
function openModal(o) {
  var statusMap={'Beklemede':['#FFF8E1','#E65100'],'İşleniyor':['#E3F2FD','#1565C0'],'Kargoya Verildi':['#E8F5E9','#2E7D32'],'Teslim Edildi':['#E8F5E9','#1B5E20'],'İptal Edildi':['#FFEBEE','#B71C1C']};
  var s=o.order_status||'Beklemede', d=statusMap[s]||['#F5EDD8','#906828'];
  var badge='<span style="background:'+d[0]+';color:'+d[1]+';padding:3px 10px;border-radius:14px;font-size:11px;font-weight:700;">'+esc(s)+'</span>';
  var orderDate=o.order_date?o.order_date.substring(0,10).split('-').reverse().join('.'):'—';
  var total=parseFloat(o.total_amount||0).toFixed(2).replace('.',',')+' TL';
  document.getElementById('modal-title').textContent='Sipariş #'+String(o.order_id).padStart(5,'0');
  document.getElementById('modal-body').innerHTML=
    '<div class="mrow"><span class="mlabel">Müşteri</span><span class="mvalue">'+esc(o.name||o.username||'—')+'</span></div>'+
    '<div class="mrow"><span class="mlabel">E-posta</span><span class="mvalue">'+esc(o.email||o.u_email||'—')+'</span></div>'+
    '<div class="mrow"><span class="mlabel">Telefon</span><span class="mvalue">'+esc(o.phone||'—')+'</span></div>'+
    '<div class="mrow"><span class="mlabel">Adres</span><span class="mvalue">'+esc(o.shipping_address||'—')+'</span></div>'+
    (o.city?'<div class="mrow"><span class="mlabel">Şehir</span><span class="mvalue">'+esc(o.city+(o.postal_code?' / '+o.postal_code:''))+'</span></div>':'')+
    '<div class="mrow"><span class="mlabel">Tarih</span><span class="mvalue">'+orderDate+'</span></div>'+
    '<div class="mrow"><span class="mlabel">Ödeme</span><span class="mvalue">'+esc(o.payment_method||'—')+'</span></div>'+
    '<div class="mrow"><span class="mlabel">Durum</span><span class="mvalue">'+badge+'</span></div>'+
    '<div class="mrow"><span class="mlabel">Toplam</span><span class="mvalue" style="color:var(--burg);font-weight:700;">'+total+'</span></div>'+
    '<div style="padding-top:14px;border-top:2px solid var(--border);margin-top:4px;"><strong style="font-size:11px;text-transform:uppercase;letter-spacing:.8px;color:var(--gray);">Ürünler</strong><div style="font-size:13px;color:var(--charcoal);margin-top:8px;line-height:1.7;">'+esc(o.products_summary||'—')+'</div></div>';
  document.getElementById('detailModal').classList.add('open');
}
function closeModal(){ document.getElementById('detailModal').classList.remove('open'); }
document.getElementById('detailModal').addEventListener('click',function(e){if(e.target===this)closeModal();});
</script>
</body></html>
