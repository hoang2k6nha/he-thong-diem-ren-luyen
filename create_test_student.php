<?php
require 'config.php';

$mssv = '801240017';
$ho_ten = 'Võ Nguyễn Ngọc Bảo';
$password = md5('123456');
$vai_tro = 'sinh_vien';
$trang_thai = 1;

// Đảm bảo lớp học có tồn tại, nếu không có ID 1 thì insert
$conn->query("INSERT IGNORE INTO khoa (id, ma_khoa, ten_khoa) VALUES (1, 'CNTT', 'Công nghệ Thông tin')");
$conn->query("INSERT IGNORE INTO lop_hoc (id, ma_lop, ten_lop, khoa_id) VALUES (1, 'TC24TH1', 'Lớp TC24TH1', 1)");

$lop_id = 1;

$check = $conn->query("SELECT id FROM tai_khoan WHERE username = '$mssv'");
if ($check && $check->num_rows == 0) {
    $sql = "INSERT INTO tai_khoan (username, password, ho_ten, vai_tro, lop_id, trang_thai) 
            VALUES ('$mssv', '$password', '$ho_ten', '$vai_tro', $lop_id, $trang_thai)";
    if ($conn->query($sql)) {
        echo "Tạo tài khoản thành công: $mssv";
    } else {
        echo "Lỗi: " . $conn->error;
    }
} else {
    echo "Tài khoản $mssv đã tồn tại!";
}
