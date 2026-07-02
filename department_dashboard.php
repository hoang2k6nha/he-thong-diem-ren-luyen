<?php
require_once 'config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'khoa') redirect('index.php');

$page_title = 'Bảng Điều Khiển - KHOA';
require_once 'layout_header.php';
?>

<h1 style="text-align: center; color: var(--primary-blue); margin-bottom: 1rem;">Hệ Thống Quản Lý Điểm Rèn Luyện (Cấp Khoa)</h1>
        
        <div class="grid">
            <a href="khoa_import_grades.php" class="card">
                <i class="fas fa-file-excel icon"></i>
                <h2>Import Điểm Học Tập</h2>
                <p>Nhập điểm học tập từ file Excel của phòng Đào tạo, hệ thống tự động tính ĐTB và áp dụng điểm rèn luyện.</p>
            </a>
            
            <a href="department_classes.php" class="card">
                <i class="fas fa-clipboard-check icon"></i>
                <h2>Duyệt Điểm Các Lớp</h2>
                <p>Xem danh sách các lớp trong Khoa và tiến hành duyệt điểm rèn luyện lần cuối.</p>
            </a>
            
            <a href="department_reports.php" class="card">
                <i class="fas fa-chart-bar icon"></i>
                <h2>Báo Cáo & Thống Kê</h2>
                <p>Xem tổng hợp kết quả điểm rèn luyện, thống kê xếp loại sinh viên của toàn Khoa.</p>
            </a>
            
            <a href="department_complaints.php" class="card">
                <i class="fas fa-envelope-open-text icon"></i>
                <h2>Quản Lý Khiếu Nại</h2>
                <p>Tiếp nhận, giải đáp thắc mắc và khiếu nại về điểm rèn luyện từ sinh viên cấp Khoa.</p>
            </a>
        </div>

<?php require_once 'layout_footer.php'; ?>
