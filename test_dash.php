<?php
require_once 'config.php';
$_SESSION['user_id'] = 3; // 2000123 (sinh_vien)
$_SESSION['role'] = 'sinh_vien';
$_SESSION['ho_ten'] = 'Test SV';
ob_start();
require 'dashboard.php';
$html = ob_get_clean();
if (strpos($html, 'Được Cấp Thêm') !== false) {
    echo "EXTRA FEATURES ARE SHOWING!\n";
} else {
    echo "No extra features.\n";
}
