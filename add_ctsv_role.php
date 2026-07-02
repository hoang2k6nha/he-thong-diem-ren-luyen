<?php
require 'config.php';

$sql = "ALTER TABLE tai_khoan MODIFY vai_tro ENUM('admin','khoa','cvht','sinh_vien','ctsv') NOT NULL DEFAULT 'sinh_vien'";
if ($conn->query($sql)) {
    echo "Altered enum.\n";
} else {
    echo "Error altering enum: " . $conn->error . "\n";
}

$password = md5('123456');
$check = $conn->query("SELECT id FROM tai_khoan WHERE username = 'ctsv'");
if ($check->num_rows == 0) {
    $conn->query("INSERT INTO tai_khoan (username, password, ho_ten, vai_tro, trang_thai) VALUES ('ctsv', '$password', 'Phòng Công Tác Sinh Viên', 'ctsv', 1)");
    echo "Created ctsv account.\n";
}
