<?php
// إعدادات الاتصال بقاعدة البيانات
$db_host = 'localhost';
$db_user = 'root';
$db_password = '';
$db_name = 'ciltbakim_db';

// إنشاء اتصال بقاعدة البيانات
$conn = new mysqli($db_host, $db_user, $db_password, $db_name);

// التحقق من الاتصال
if ($conn->connect_error) {
    die("Veritabanı bağlantısı başarısız: " . $conn->connect_error);
}

// تعيين ترميز الاتصال
$conn->set_charset("utf8");

// بدء الجلسة
session_start();

// دالة لتنظيف المدخلات
function clean_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    // استخدام real_escape_string بدلاً من الدوال المتوافقة مع PHP 7+ فقط
    $data = $conn->real_escape_string($data);
    return $data;
}

// دالة للتحقق من تسجيل دخول المستخدم
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// دالة للتحقق من صلاحيات المسؤول
function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin';
}

// دالة لإعادة التوجيه
function redirect($url) {
    header("Location: $url");
    exit();
}

// دالة لإنشاء رسالة تنبيه
function set_alert($type, $message) {
    $_SESSION['alert_type'] = $type;
    $_SESSION['alert_message'] = $message;
}

// دالة لعرض رسالة التنبيه
function display_alert() {
    if (isset($_SESSION['alert_message'])) {
        $type = $_SESSION['alert_type'];
        $message = $_SESSION['alert_message'];
        
        echo "<div class='alert alert-$type'>$message</div>";
        
        // حذف رسالة التنبيه بعد عرضها
        unset($_SESSION['alert_type']);
        unset($_SESSION['alert_message']);
    }
}

// دالة لتحويل التاريخ إلى التنسيق التركي
function format_date($date) {
    $timestamp = strtotime($date);
    return date('d.m.Y', $timestamp);
}

// دالة لتحويل السعر إلى التنسيق التركي
function format_price($price) {
    return number_format($price, 2, ',', '.') . ' TL';
}

// دالة للتحقق من وجود منتج في المفضلة
function is_favorite($user_id, $product_id) {
    global $conn;
    $query = "SELECT favorite_id FROM favorites WHERE user_id = ? AND product_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

// دالة لجلب عدد عناصر سلة التسوق
function get_cart_count($user_id = null) {
    global $conn;
    
    if ($user_id) {
        // التحقق من وجود سلة تسوق للمستخدم
        $cart_check_query = "SELECT cart_id FROM cart WHERE user_id = ?";
        $cart_check_stmt = $conn->prepare($cart_check_query);
        $cart_check_stmt->bind_param("i", $user_id);
        $cart_check_stmt->execute();
        $cart_check_result = $cart_check_stmt->get_result();
        
        if ($cart_check_result->num_rows == 0) {
            return 0;
        }
        
        $cart_row = $cart_check_result->fetch_assoc();
        $cart_id = $cart_row['cart_id'];
        
        // جلب عدد العناصر
        $count_query = "SELECT SUM(quantity) as total FROM cart_items WHERE cart_id = ?";
        $count_stmt = $conn->prepare($count_query);
        $count_stmt->bind_param("i", $cart_id);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $count_row = $count_result->fetch_assoc();
        
        return $count_row['total'] ? $count_row['total'] : 0;
    } else {
        // إذا لم يكن المستخدم مسجل الدخول، جلب عدد العناصر من الجلسة
        if (!isset($_SESSION['cart'])) {
            return 0;
        }
        
        $total = 0;
        foreach ($_SESSION['cart'] as $quantity) {
            $total += $quantity;
        }
        
        return $total;
    }
}
?>
