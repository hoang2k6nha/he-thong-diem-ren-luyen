<?php
require 'config.php';
$conn->query("ALTER TABLE chi_tiet_diem ADD diem_tru INT DEFAULT 0 AFTER tieu_chi_id, ADD ghi_chu_tru VARCHAR(255) DEFAULT NULL AFTER diem_tru");
echo $conn->error ? $conn->error : "OK";
