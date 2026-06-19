<?php
require_once '../includes/config.php';
// Already logged in - redirect appropriately
if (is_logged_in()) {
    if (is_admin()) redirect('admin/index.php');
    else redirect('index.php');
}
$page_title='Giriş / Kayıt';
$login_err=$reg_err=$reg_ok=''; $active='login';

// ── LOGIN ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['login'])) {
    $u = trim($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';
    if (!$u || !$p) {
        $login_err = 'Kullanıcı adı ve şifre zorunludur.';
    } else {
        $stmt = $conn->prepare("SELECT user_id, username, password, role, name, first_name FROM users WHERE username=? OR email=? LIMIT 1");
        $stmt->bind_param("ss", $u, $u);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        if ($user) {
            $_SESSION['user_id']   = $user['user_id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['name'] ?: $user['first_name'] ?: $user['username'];
            $conn->query("UPDATE users SET last_login=NOW() WHERE user_id={$user['user_id']}");
            if ($user['role'] === 'admin') redirect('admin/index.php');
            else {
                $redir = $_SESSION['redirect_after_login'] ?? 'index.php';
                unset($_SESSION['redirect_after_login']);
                redirect($redir);
            }
        } else {
            $login_err = 'Kullanıcı adı veya şifre hatalı.';
        }
    }
}

// ── REGISTER ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['register'])) {
    $active = 'register';
    $fn  = trim(htmlspecialchars($_POST['first_name'] ?? ''));
    $ln  = trim(htmlspecialchars($_POST['last_name'] ?? ''));
    $un  = trim($_POST['username'] ?? '');
    $em  = trim(filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL));
    $pw  = $_POST['password'] ?? '';
    $pw2 = $_POST['password2'] ?? '';
    $full= trim("$fn $ln");
    if (strlen($fn)<2 || strlen($ln)<2) $reg_err = 'Ad ve soyad zorunludur.';
    elseif (strlen($un)<3)              $reg_err = 'Kullanıcı adı en az 3 karakter olmalıdır.';
    elseif (!filter_var($em, FILTER_VALIDATE_EMAIL)) $reg_err = 'Geçerli bir e-posta girin.';
    elseif (strlen($pw)<6)              $reg_err = 'Şifre en az 6 karakter olmalıdır.';
    elseif ($pw !== $pw2)               $reg_err = 'Şifreler uyuşmuyor.';
    else {
        $chk = $conn->prepare("SELECT user_id FROM users WHERE username=? OR email=? LIMIT 1");
        $chk->bind_param("ss", $un, $em); $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $reg_err = 'Bu kullanıcı adı veya e-posta zaten kayıtlı.';
        } else {
            $hash = password_hash($pw, PASSWORD_DEFAULT);
            $ins  = $conn->prepare("INSERT INTO users (username,email,password,first_name,last_name,name,role) VALUES (?,?,?,?,?,?,'user')");
            $ins->bind_param("ssssss", $un, $em, $hash, $fn, $ln, $full);
            if ($ins->execute()) { $reg_ok='Kayıt başarılı! Giriş yapabilirsiniz.'; $active='login'; }
            else $reg_err = 'Kayıt sırasında bir hata oluştu: '.$conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Giriş — AVELIN Skincare</title>
<link rel="stylesheet" href="../css/style.css">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<div id="toast-container"></div>
<div class="auth-page">
  <div class="auth-box">
    <div class="auth-header">
      <img src="../img/Gold-swan.img16.PNG" alt="AVELIN" style="width:46px;height:46px;margin:0 auto 10px;filter:brightness(1.5);">
      <div class="auth-logo-name">AVELIN</div>
      <div class="auth-logo-sub">Skincare</div>
      <p>Hesabınıza giriş yapın veya yeni hesap oluşturun</p>
    </div>
    <div class="auth-tabs">
      <button data-pane="login-pane" class="<?php echo $active==='login'?'active':'';?>">Giriş Yap</button>
      <button data-pane="register-pane" class="<?php echo $active==='register'?'active':'';?>">Kayıt Ol</button>
    </div>
    <div class="auth-content">
      <?php if($reg_ok):?><div class="alert alert-success">✓ <?php echo $reg_ok;?></div><?php endif;?>

      <!-- GİRİŞ -->
      <div id="login-pane" class="auth-pane" style="display:<?php echo $active==='login'?'block':'none';?>">
        <?php if($login_err):?><div class="alert alert-danger">✕ <?php echo $login_err;?></div><?php endif;?>
        <form method="post" action="login.php">
          <div class="form-group">
            <label>Kullanıcı Adı veya E-posta</label>
            <input type="text" name="username" class="form-control" required autocomplete="username"
                   value="<?php echo htmlspecialchars($_POST['username']??'');?>" placeholder="kullanici_adi veya email">
          </div>
          <div class="form-group">
            <label>Şifre</label>
            <input type="password" name="password" class="form-control" required autocomplete="current-password" placeholder="••••••">
          </div>
          <button type="submit" name="login" class="btn btn-primary btn-block btn-lg" style="margin-top:6px;">Giriş Yap</button>
        </form>
        <div style="margin-top:16px;padding:11px;background:var(--gold-pale);border-radius:var(--r);border:1px solid rgba(200,168,80,.3);">
          <p style="font-size:11px;text-align:center;color:var(--gold-deep);">
            <strong>Admin:</strong> admin / password123<br>
            <strong>Test Kullanıcı:</strong> test_user / password123
          </p>
        </div>
      </div>

      <!-- KAYIT -->
      <div id="register-pane" class="auth-pane" style="display:<?php echo $active==='register'?'block':'none';?>">
        <?php if($reg_err):?><div class="alert alert-danger">✕ <?php echo $reg_err;?></div><?php endif;?>
        <form method="post" action="login.php">
          <div class="form-row">
            <div class="form-group"><label>Ad *</label><input type="text" name="first_name" class="form-control" required placeholder="Adınız" value="<?php echo htmlspecialchars($_POST['first_name']??'');?>"></div>
            <div class="form-group"><label>Soyad *</label><input type="text" name="last_name" class="form-control" required placeholder="Soyadınız" value="<?php echo htmlspecialchars($_POST['last_name']??'');?>"></div>
          </div>
          <div class="form-group"><label>Kullanıcı Adı *</label><input type="text" name="username" class="form-control" required placeholder="En az 3 karakter" minlength="3" value="<?php echo htmlspecialchars($_POST['username']??'');?>"></div>
          <div class="form-group"><label>E-posta *</label><input type="email" name="email" class="form-control" required placeholder="email@ornek.com" value="<?php echo htmlspecialchars($_POST['email']??'');?>"></div>
          <div class="form-group"><label>Şifre *</label><input type="password" name="password" class="form-control" required placeholder="En az 6 karakter" minlength="6"></div>
          <div class="form-group"><label>Şifre Tekrar *</label><input type="password" name="password2" class="form-control" required placeholder="Şifrenizi tekrar girin"></div>
          <button type="submit" name="register" class="btn btn-primary btn-block btn-lg">Kayıt Ol</button>
        </form>
      </div>
    </div>
    <div style="text-align:center;padding:13px;border-top:1px solid var(--border);">
      <a href="index.php" style="font-size:12px;color:var(--gray);">← Ana Sayfaya Dön</a>
    </div>
  </div>
</div>
<script src="../js/main.js"></script>
</body>
</html>
