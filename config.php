<?php
// config.php - Tệp cấu hình cơ sở dữ liệu
$db_host = 'localhost';
$db_user = 'root'; // User mặc định của XAMPP/WAMP
$db_pass = '';     // Password mặc định
$db_name = 'itc_diemrenluyen';

// Tạo kết nối bằng MySQLi Object-Oriented
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Thiết lập Charset để tránh lỗi font Tiếng Việt
$conn->set_charset("utf8mb4");

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối cơ sở dữ liệu thất bại. Vui lòng kiểm tra lại XAMPP/MySQL. Lỗi: " . $conn->connect_error);
}

// Bắt đầu Session nếu chưa được start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id']) && !array_key_exists('khoa_id', $_SESSION)) {
    $uid = (int)$_SESSION['user_id'];
    $res = $conn->query("SELECT khoa_id, lop_id FROM tai_khoan WHERE id = $uid");
    if ($res && $res->num_rows > 0) {
        $u = $res->fetch_assoc();
        $_SESSION['khoa_id'] = $u['khoa_id'];
        $_SESSION['lop_id'] = $u['lop_id'];
    }
}

// Hàm kiểm tra quyền
function has_permission($perm) {
    global $conn;
    if (!isset($_SESSION['user_id'])) return false;
    $uid = (int)$_SESSION['user_id'];
    $res = $conn->query("SELECT permissions FROM tai_khoan WHERE id = $uid");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $perms = $row['permissions'] ? explode(',', $row['permissions']) : [];
        return in_array($perm, $perms);
    }
    return false;
}

// Hàm hỗ trợ điều hướng trang
function redirect($url) {
    header("Location: $url");
    exit();
}
?>
