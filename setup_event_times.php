<?php
require 'config.php';
$conn->query('ALTER TABLE thong_bao ADD COLUMN thoi_gian_bat_dau DATETIME NULL, ADD COLUMN thoi_gian_ket_thuc DATETIME NULL;');
echo "OK";
?>
