<?php
// AVELIN Setup Script - Run once to initialize/reset admin password
require_once '../includes/config.php';

$hash = password_hash('password123', PASSWORD_DEFAULT);

// Update or insert admin user
$check = $conn->prepare("SELECT user_id FROM users WHERE username='admin' LIMIT 1");
$check->execute();
$exists = $check->get_result()->num_rows > 0;

if ($exists) {
    $stmt = $conn->prepare("UPDATE users SET password=?, role='admin' WHERE username='admin'");
    $stmt->bind_param("s", $hash);
    $stmt->execute();
    echo "<b>Admin şifresi güncellendi!</b><br>";
} else {
    $stmt = $conn->prepare("INSERT INTO users (username,email,password,first_name,last_name,name,role) VALUES ('admin','admin@avelin.com',?,'Admin','User','Admin User','admin')");
    $stmt->bind_param("s", $hash);
    $stmt->execute();
    echo "<b>Admin kullanıcısı oluşturuldu!</b><br>";
}

// Also ensure test_user exists
$check2 = $conn->prepare("SELECT user_id FROM users WHERE username='test_user' LIMIT 1");
$check2->execute();
if ($check2->get_result()->num_rows === 0) {
    $hash2 = password_hash('password123', PASSWORD_DEFAULT);
    $ins = $conn->prepare("INSERT INTO users (username,email,password,first_name,last_name,name,role) VALUES ('test_user','test@avelin.com',?,'Test','Kullanıcı','Test Kullanıcı','user')");
    $ins->bind_param("s", $hash2);
    $ins->execute();
}

echo "<br>✓ <b>admin</b> / password123 → Admin girişi<br>";
echo "✓ <b>test_user</b> / password123 → Kullanıcı girişi<br><br>";
echo "Oluşturulan hash: <code>" . htmlspecialchars($hash) . "</code><br><br>";
echo '<a href="login.php">→ Login sayfasına git</a> | <a href="index.php">→ Ana sayfaya git</a>';
echo "<br><br><small style='color:red'>GÜVENLİK: Bu dosyayı kullandıktan sonra silin!</small>";
?>
