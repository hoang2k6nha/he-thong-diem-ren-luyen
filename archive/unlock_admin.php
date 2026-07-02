<?php
$conn = new mysqli('localhost', 'root', '', 'itc_diemrenluyen');
$conn->query("UPDATE tai_khoan SET trang_thai = 1 WHERE vai_tro = 'admin'");
echo "Admin unlocked!";
