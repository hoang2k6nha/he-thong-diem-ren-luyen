<?php
$conn = new mysqli('localhost', 'root', '', 'itc_diemrenluyen');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->query("ALTER TABLE khieu_nai MODIFY sinh_vien_id INT NULL");
$conn->query("ALTER TABLE khieu_nai ADD ho_ten_khach VARCHAR(100) NULL AFTER sinh_vien_id");
$conn->query("ALTER TABLE khieu_nai ADD mssv_khach VARCHAR(50) NULL AFTER ho_ten_khach");

echo "Updated khieu_nai table.\n";
