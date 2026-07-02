<?php
// Bắt đầu session
session_start();

// Hủy tất cả các biến session
$_SESSION = array();

// Hủy session cookie nếu có
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Hủy session
session_destroy();

// Điều hướng về trang chủ
header("Location: index.php");
exit();
?>
