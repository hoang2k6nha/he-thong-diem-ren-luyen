<?php
require_once 'config.php';
$conn->set_charset("utf8mb4");

// Cập nhật lại tiếng Việt chuẩn cho Tài khoản
$conn->query("UPDATE tai_khoan SET ho_ten = 'Quản Trị Viên Hệ Thống' WHERE username = 'admin'");
$conn->query("UPDATE tai_khoan SET ho_ten = 'Nguyễn Văn A (CVHT)' WHERE username = 'cvht_cntt'");
$conn->query("UPDATE tai_khoan SET ho_ten = 'Trần Thị Sinh Viên' WHERE username = '2000123'");

// Cập nhật lại tiếng Việt cho Lớp & Khoa
$conn->query("UPDATE khoa SET ten_khoa = 'Công nghệ thông tin' WHERE ma_khoa = 'CNTT'");
$conn->query("UPDATE khoa SET ten_khoa = 'Quản trị kinh doanh' WHERE ma_khoa = 'QTKD'");
$conn->query("UPDATE lop_hoc SET ten_lop = 'CĐ Cao đẳng Phần mềm 1' WHERE ma_lop = 'CD20PM1'");

// Cập nhật lại tiếng Việt cho Đợt đánh giá
$conn->query("UPDATE dot_danh_gia SET ten_dot = 'Đánh giá Điểm rèn luyện HK1 2023-2024' WHERE id = 1");

// Cập nhật lại tiếng Việt cho Nhóm Tiêu Chí
$conn->query("UPDATE nhom_tieu_chi SET ten_nhom = 'I. Đánh giá về ý thức tham gia học tập' WHERE thu_tu = 1");
$conn->query("UPDATE nhom_tieu_chi SET ten_nhom = 'II. Đánh giá về ý thức chấp hành nội quy, quy chế, quy định trong nhà trường' WHERE thu_tu = 2");
$conn->query("UPDATE nhom_tieu_chi SET ten_nhom = 'III. Đánh giá về ý thức tham gia các hoạt động chính trị, xã hội, văn hóa, văn nghệ...' WHERE thu_tu = 3");

echo "<h2>Đã sửa lỗi font chữ tiếng Việt thành công!</h2>";
echo "<p>Bạn có thể đóng trang này và F5 lại trang Dashboard (hoặc trang Đăng nhập) để xem kết quả nhé.</p>";
echo "<a href='dashboard.php'>Quay lại Dashboard</a>";
?>
