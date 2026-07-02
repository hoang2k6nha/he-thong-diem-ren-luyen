<?php
require 'config.php';

$mssv = '511240169';
$ho_ten = 'Nguyễn Phước Sang';
$password = md5('123456');
$vai_tro = 'sinh_vien';
$trang_thai = 1;
$lop_id = 1;

$check = $conn->query("SELECT id FROM tai_khoan WHERE username = '$mssv'");
if ($check && $check->num_rows == 0) {
    $sql = "INSERT INTO tai_khoan (username, password, ho_ten, vai_tro, lop_id, trang_thai) 
            VALUES ('$mssv', '$password', '$ho_ten', '$vai_tro', $lop_id, $trang_thai)";
    if ($conn->query($sql)) {
        echo "Tạo tài khoản thành công: $mssv";
    }
}
