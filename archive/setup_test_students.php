<?php
require 'config.php';
$students = [
    ['506240146', 'Mai Chí Bảo'],
    ['506240154', 'Phan Trung Du'],
    ['506240323', 'Huỳnh Lê Hải'],
    ['506240069', 'Lưu Kiến Hòa'],
    ['506240082', 'Nguyễn Thanh Huy'],
    ['506240056', 'Tạ Văn Hưng'],
    ['506240053', 'Lê Vi Khang Hy'],
    ['506240426', 'Nguyễn Võ Trung Kiên'],
    ['506240191', 'Nguyễn Tuấn Kiệt'],
    ['506240051', 'Lê Quang Luật'],
    ['506240381', 'Lê Tấn Lực'],
    ['506240378', 'Mai Cao Phát'],
    ['506240417', 'Nguyễn Phạm Đăng Phi'],
    ['506240091', 'Nguyễn Phước Sang'],
    ['506240141', 'Huỳnh Tấn Tài'],
    ['506240277', 'Võ Đức Quốc Thắng'],
    ['506240238', 'Trương Thành Vinh']
];
$password = md5('123456');

// Tạo khoa và lớp nếu chưa có
$conn->query("INSERT IGNORE INTO khoa (id, ma_khoa, ten_khoa) VALUES (2, 'CD24CM1', 'Công nghệ kỹ thuật máy tính')");
$conn->query("INSERT IGNORE INTO lop_hoc (id, ma_lop, ten_lop, khoa_id) VALUES (2, 'CD24CM1', 'Lớp CD24CM1', 2)");

foreach ($students as $s) {
    $mssv = $s[0];
    $ho_ten = $s[1];
    $sql = "INSERT IGNORE INTO tai_khoan (username, password, ho_ten, vai_tro, lop_id, trang_thai) VALUES ('$mssv', '$password', '$ho_ten', 'sinh_vien', 2, 1)";
    if ($conn->query($sql)) {
        echo 'Created/Verified ' . $mssv . ' - ' . $ho_ten . "\n";
    }
}
echo "Hoàn thành!\n";
