<?php
require_once 'config.php';

$accounts = [
    [
        'username' => 'sv001',
        'password' => md5('123456'),
        'ho_ten' => 'Nguyễn Văn Sinh Viên',
        'vai_tro' => 'sinh_vien',
        'lop_id' => 1,
        'trang_thai' => 1
    ],
    [
        'username' => 'cvht001',
        'password' => md5('123456'),
        'ho_ten' => 'ThS. Trần Cố Vấn',
        'vai_tro' => 'cvht',
        'lop_id' => NULL,
        'trang_thai' => 1
    ],
    [
        'username' => 'khoa001',
        'password' => md5('123456'),
        'ho_ten' => 'Ban Chủ nhiệm Khoa CNTT',
        'vai_tro' => 'khoa',
        'lop_id' => NULL,
        'trang_thai' => 1
    ],
    [
        'username' => 'admin',
        'password' => md5('123456'),
        'ho_ten' => 'Quản trị viên Hệ thống',
        'vai_tro' => 'admin',
        'lop_id' => NULL,
        'trang_thai' => 1
    ]
];

echo "Bắt đầu kiểm tra và tạo tài khoản...\n";

// Đảm bảo có lớp học (nếu chưa có)
$conn->query("INSERT IGNORE INTO khoa (id, ma_khoa, ten_khoa) VALUES (1, 'CNTT', 'Công nghệ Thông tin')");
$conn->query("INSERT IGNORE INTO lop_hoc (id, ma_lop, ten_lop, khoa_id, cvht_id) VALUES (1, 'CD21CT1', 'Cao đẳng CNTT 21', 1, 2)");

foreach ($accounts as $acc) {
    $uname = $acc['username'];
    $check = $conn->query("SELECT * FROM tai_khoan WHERE username = '$uname'");
    if ($check->num_rows == 0) {
        $lop_id = $acc['lop_id'] ? $acc['lop_id'] : "NULL";
        $sql = "INSERT INTO tai_khoan (username, password, ho_ten, vai_tro, lop_id, trang_thai) 
                VALUES ('$uname', '{$acc['password']}', '{$acc['ho_ten']}', '{$acc['vai_tro']}', $lop_id, {$acc['trang_thai']})";
        if ($conn->query($sql)) {
            echo "Đã tạo: $uname (Vai trò: {$acc['vai_tro']})\n";
            // Nếu là CVHT thì update cvht_id cho lop_hoc
            if ($acc['vai_tro'] == 'cvht') {
                $cvht_id = $conn->insert_id;
                $conn->query("UPDATE lop_hoc SET cvht_id = $cvht_id WHERE id = 1");
            }
        } else {
            echo "Lỗi tạo $uname: " . $conn->error . "\n";
        }
    } else {
        echo "Tài khoản $uname đã tồn tại.\n";
        // Update password về 123456 để test cho dễ
        $conn->query("UPDATE tai_khoan SET password = '{$acc['password']}' WHERE username = '$uname'");
    }
}

echo "Hoàn tất.\n";
?>
