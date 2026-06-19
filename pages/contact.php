<?php
require_once '../includes/config.php';
$page_title='İletişim';$success='';$error='';
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['send_message'])){
    $name=trim(htmlspecialchars($_POST['name']??''));$email=trim(filter_var($_POST['email']??'',FILTER_SANITIZE_EMAIL));
    $subject=trim(htmlspecialchars($_POST['subject']??''));$message=trim(htmlspecialchars($_POST['message']??''));$phone=trim(htmlspecialchars($_POST['phone']??''));
    if(strlen($name)<2)$error='Lütfen geçerli bir ad soyad girin.';
    elseif(!filter_var($email,FILTER_VALIDATE_EMAIL))$error='Lütfen geçerli bir e-posta girin.';
    elseif(empty($subject))$error='Lütfen bir konu seçin.';
    elseif(strlen($message)<10)$error='Mesajınız en az 10 karakter olmalıdır.';
    else{$stmt=$conn->prepare("INSERT INTO contact_messages (name,email,phone,subject,message,created_at) VALUES (?,?,?,?,?,NOW())");if($stmt){$stmt->bind_param("sssss",$name,$email,$phone,$subject,$message);$stmt->execute();}$success='Mesajınız alındı! En kısa sürede dönüş yapacağız.';}
}
include '../includes/header.php';
?>
<div class="breadcrumb"><div class="container"><a href="index.php">Ana Sayfa</a><span class="sep">/</span><span class="current">İletişim</span></div></div>
<div class="about-hero" style="padding:58px 0;"><div class="container" style="position:relative;z-index:1;"><h1 style="font-size:38px;">İletişim</h1><p>Sorularınız için formu doldurun veya doğrudan ulaşın.</p></div></div>
<section class="contact-section"><div class="container">
  <?php if($success):?><div class="alert alert-success">✓ <?php echo $success;?></div><?php endif;?>
  <?php if($error):?><div class="alert alert-danger">✕ <?php echo $error;?></div><?php endif;?>
  <div class="contact-grid">
    <div class="contact-info">
      <div class="contact-card"><div class="contact-card-icon">📍</div><div><h3>Adres</h3><p>Karataş Mah. 103415 Nolu Sk. No:8A<br>Şahinbey / Gaziantep, Türkiye</p></div></div>
      <div class="contact-card"><div class="contact-card-icon">📞</div><div><h3>Telefon</h3><a href="tel:+905370306359">0537 030 63 59</a></div></div>
      <div class="contact-card"><div class="contact-card-icon">✉</div><div><h3>E-posta</h3><a href="mailto:info@ciltbakim.com">info@ciltbakim.com</a></div></div>
      <div class="contact-card"><div class="contact-card-icon">📱</div><div><h3>Sosyal Medya</h3><p>@bushra.alden</p></div></div>
    </div>
    <div class="contact-form-box">
      <h2>Mesaj Gönderin</h2><p class="form-subtitle">Formu doldurun, size en kısa sürede yanıt verelim.</p>
      <form method="post">
        <div class="form-row">
          <div class="form-group"><label>Ad Soyad *</label><input type="text" name="name" class="form-control" required placeholder="Adınız Soyadınız" value="<?php echo htmlspecialchars($_POST['name']??'');?>"></div>
          <div class="form-group"><label>E-posta *</label><input type="email" name="email" class="form-control" required placeholder="email@ornek.com" value="<?php echo htmlspecialchars($_POST['email']??'');?>"></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Telefon</label><input type="tel" name="phone" class="form-control" placeholder="05__ ___ ____" value="<?php echo htmlspecialchars($_POST['phone']??'');?>"></div>
          <div class="form-group"><label>Konu *</label>
            <select name="subject" class="form-control" required><option value="">Seçin...</option><option>Ürün Bilgisi</option><option>Sipariş Takip</option><option>İade ve Değişim</option><option>Teknik Destek</option><option>Diğer</option></select>
          </div>
        </div>
        <div class="form-group"><label>Mesajınız *</label><textarea name="message" class="form-control" rows="5" required placeholder="Mesajınızı buraya yazın..."><?php echo htmlspecialchars($_POST['message']??'');?></textarea></div>
        <button type="submit" name="send_message" class="btn btn-primary btn-block btn-lg">Mesaj Gönder</button>
      </form>
    </div>
  </div>
</div></section>
<?php include '../includes/footer.php';?>
