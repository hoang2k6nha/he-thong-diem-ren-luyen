<?php
$conn = new mysqli('localhost', 'root', '', 'itc_diemrenluyen');
$conn->query("ALTER TABLE tai_khoan ADD COLUMN trang_thai ENUM('hoat_dong', 'da_khoa') DEFAULT 'hoat_dong' AFTER vai_tro");
echo $conn->error ? $conn->error : 'Success';
