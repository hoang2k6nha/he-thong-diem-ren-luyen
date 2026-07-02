<?php
require_once 'config.php';
$_SESSION['user_id'] = 4; // sv001
$_SESSION['role'] = 'sinh_vien';
$_SESSION['ho_ten'] = 'Sinh vien test';
ob_start();
require 'dashboard.php';
$html = ob_get_clean();
echo "Dashboard OK. Size: " . strlen($html) . "\n";
ob_start();
require 'notifications.php';
$html = ob_get_clean();
echo "Notifications OK. Size: " . strlen($html) . "\n";
