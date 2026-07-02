<?php
require 'config.php';
$conn->query("DELETE FROM tai_khoan WHERE username = 'ctsv'");
$conn->query("ALTER TABLE tai_khoan MODIFY vai_tro ENUM('admin','khoa','cvht','sinh_vien') NOT NULL DEFAULT 'sinh_vien'");
echo "OK";
