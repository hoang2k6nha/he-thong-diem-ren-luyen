<?php
require 'config.php';
$conn->query("UPDATE chi_tiet_diem SET minh_chung = NULL WHERE minh_chung LIKE 'Bị trừ điểm do vắng/trễ%'");
echo "OK";
