<?php
require_once 'config.php';

// Kiểm tra nếu chưa đăng nhập
if (!isset($_SESSION['user_id'])) {
    redirect('index.php');
}

$role = $_SESSION['role'];
$ho_ten = $_SESSION['ho_ten'];
$user_id = (int)$_SESSION['user_id'];

// Không cần tính lại $unread_count ở đây vì layout_header.php đã xử lý việc đó (Global)

// Logic cho role display name
$role_display = '';
if ($role == 'admin') $role_display = 'Quản trị viên (Admin)';
elseif ($role == 'khoa') $role_display = 'Phụ trách Khoa / CTSV';
elseif ($role == 'cvht') $role_display = 'Cố Vấn Học Tập';
else $role_display = 'Sinh Viên';

date_default_timezone_set('Asia/Ho_Chi_Minh');
$hour = date('H');
$greeting = 'Xin chào';
if ($hour >= 5 && $hour < 12) $greeting = 'Chào buổi sáng';
elseif ($hour >= 12 && $hour < 18) $greeting = 'Chào buổi chiều';
else $greeting = 'Chào buổi tối';

$page_title = 'Dashboard - Hệ Thống Điểm Rèn Luyện ITC';
require_once 'layout_header.php';
?>
<style>
.welcome-card {
    background: url('bg_itc.jpg') top center/cover no-repeat;
    border-radius: var(--radius); padding: 3rem; color: var(--white);
    display: flex; justify-content: space-between; align-items: center;
    position: relative; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0, 77, 204, 0.2);
    margin-bottom: 3rem;
}
.welcome-card::before {
    content: ''; position: absolute; inset: 0;
    background: linear-gradient(135deg, rgba(0, 77, 204, 0.6) 0%, rgba(51, 119, 255, 0.7) 100%);
    backdrop-filter: blur(2px);
    z-index: 1;
}
.welcome-content { position: relative; z-index: 2; }
.welcome-greeting {
    display: inline-flex; align-items: center; gap: 8px; color: var(--secondary);
    font-weight: 700; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px;
    background: rgba(255,255,255,0.1); padding: 0.4rem 1rem; border-radius: 50px;
    margin-bottom: 1rem;
}
.welcome-content h1 { font-size: 2.5rem; margin-bottom: 0.5rem; }
.welcome-content p { font-size: 1.1rem; opacity: 0.9; margin-bottom: 1.5rem; max-width: 500px; }
.role-badge {
    display: inline-flex; align-items: center; gap: 8px; background: var(--white); color: var(--primary);
    padding: 0.5rem 1.25rem; border-radius: 50px; font-weight: 700; font-size: 0.95rem;
    box-shadow: var(--shadow);
}
.welcome-visual { position: relative; z-index: 2; opacity: 0.2; transform: rotate(15deg); }
.welcome-visual i { font-size: 12rem; }

.section-title { font-size: 1.5rem; color: var(--dark); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 12px; }
.section-title i { color: var(--primary); background: #EFF6FF; padding: 10px; border-radius: 12px; }

.features-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem; }
.feature-card {
    background: var(--white); border: 1px solid var(--border); border-radius: var(--radius);
    padding: 2rem 1.5rem; text-align: center; display: flex; flex-direction: column; align-items: center;
    transition: all 0.3s; position: relative; overflow: hidden; height: 100%;
}
.feature-card:hover { transform: translateY(-8px); border-color: transparent; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }
.feature-card::after {
    content: ''; position: absolute; bottom: 0; left: 0; width: 100%; height: 4px;
    background: linear-gradient(90deg, var(--primary), var(--secondary)); opacity: 0; transition: all 0.3s;
}
.feature-card:hover::after { opacity: 1; }
.feature-icon-wrapper {
    width: 70px; height: 70px; border-radius: 18px;
    background: #F1F5F9; color: var(--primary); font-size: 1.75rem;
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 1.5rem; transition: all 0.3s;
}
.feature-card:hover .feature-icon-wrapper {
    background: var(--primary); color: var(--white); transform: scale(1.1) rotate(5deg);
    box-shadow: 0 10px 20px rgba(0, 77, 204, 0.2);
}
.feature-card h3 { font-size: 1.2rem; color: var(--dark); margin-bottom: 0.5rem; }
.feature-card p { font-size: 0.9rem; color: var(--gray); margin-top: auto; }
.noti-badge {
    position: absolute; top: 1.5rem; right: 1.5rem;
    background: #EF4444; color: var(--white); font-size: 0.8rem; font-weight: 700;
    width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;
    border-radius: 50%; border: 2px solid var(--white);
    box-shadow: 0 4px 6px rgba(239, 68, 68, 0.3); animation: pulse 2s infinite;
}
@keyframes pulse {
    0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
    70% { transform: scale(1.05); box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
    100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
}
.extra-perms-container { margin-top: 4rem; padding-top: 2.5rem; border-top: 1px dashed var(--border); position: relative; }
.extra-perms-badge {
    position: absolute; top: -15px; left: 0; background: #FEF3C7; color: #B45309;
    padding: 0.25rem 1rem; border-radius: 50px; font-weight: 700; font-size: 0.85rem;
    border: 1px solid #FDE68A; display: flex; align-items: center; gap: 6px;
}
@media (max-width: 768px) {
    .welcome-card { padding: 2rem; text-align: center; flex-direction: column; }
    .welcome-visual { display: none; }
}
</style>

<div class="welcome-card">
        <div class="welcome-content">
            <div class="welcome-greeting"><i class="fas fa-sun"></i> <?php echo $greeting; ?></div>
            <h1><?php echo htmlspecialchars($ho_ten); ?></h1>
            <p>Chào mừng quay lại không gian làm việc. Khám phá các tính năng được cá nhân hóa dành cho bạn.</p>
            <div class="role-badge"><i class="fas fa-shield-check"></i> <?php echo $role_display; ?></div>
        </div>
        <div class="welcome-visual">
            <i class="fas fa-chart-line"></i>
        </div>
    </div>
    
    <div class="section-title"><i class="fas fa-layer-group"></i> <span>Tính Năng Trọng Tâm</span></div>
    
    <div class="features-grid">
        <?php if ($role == 'sinh_vien'): ?>
            <a href="student_assessment.php" class="feature-card">
                <div class="feature-icon-wrapper"><i class="fas fa-clipboard-list"></i></div>
                <h3>Tự Đánh Giá</h3>
                <p>Thực hiện chấm điểm rèn luyện và nộp minh chứng</p>
            </a>
            <a href="student_history.php" class="feature-card">
                <div class="feature-icon-wrapper"><i class="fas fa-history"></i></div>
                <h3>Lịch Sử Điểm</h3>
                <p>Xem lại kết quả các học kỳ đã qua</p>
            </a>
            <a href="notifications.php" class="feature-card">
                <?php if($unread_count > 0): ?><span class="noti-badge"><?php echo $unread_count; ?></span><?php endif; ?>
                <div class="feature-icon-wrapper"><i class="fas fa-bell"></i></div>
                <h3>Thông Báo</h3>
                <p>Xem thông báo mới nhất từ Khoa/CVHT</p>
            </a>
            <a href="student_complaints.php" class="feature-card">
                <div class="feature-icon-wrapper"><i class="fas fa-comments"></i></div>
                <h3>Hỏi Đáp & Khiếu Nại</h3>
                <p>Gửi câu hỏi hoặc khiếu nại điểm</p>
            </a>
            
        <?php elseif ($role == 'cvht'): ?>
            <a href="advisor_manage_class.php" class="feature-card">
                <div class="feature-icon-wrapper"><i class="fas fa-users"></i></div>
                <h3>Quản Lý Lớp</h3>
                <p>Danh sách sinh viên lớp chủ nhiệm</p>
            </a>
            <a href="advisor_classes.php" class="feature-card">
                <div class="feature-icon-wrapper"><i class="fas fa-check-double"></i></div>
                <h3>Duyệt Điểm</h3>
                <p>Chấm điểm và chốt sổ lớp học kỳ</p>
            </a>
            <a href="advisor_reports.php" class="feature-card">
                <div class="feature-icon-wrapper"><i class="fas fa-chart-line"></i></div>
                <h3>Báo Cáo Lớp</h3>
                <p>Xem kết quả ĐRL và xuất báo cáo</p>
            </a>
            <a href="notifications.php" class="feature-card">
                <?php if($unread_count > 0): ?><span class="noti-badge"><?php echo $unread_count; ?></span><?php endif; ?>
                <div class="feature-icon-wrapper"><i class="fas fa-bullhorn"></i></div>
                <h3>Quản Lý Thông Báo</h3>
                <p>Đăng thông báo cho lớp</p>
            </a>
            <a href="advisor_complaints.php" class="feature-card">
                <div class="feature-icon-wrapper"><i class="fas fa-envelope-open-text"></i></div>
                <h3>Quản Lý Khiếu Nại</h3>
                <p>Giải quyết thắc mắc của sinh viên</p>
            </a>
            
        <?php elseif ($role == 'admin'): ?>
            <a href="admin_cycles.php" class="feature-card">
                <div class="feature-icon-wrapper"><i class="fas fa-calendar-alt"></i></div>
                <h3>Đợt Đánh Giá</h3>
                <p>Mở/Đóng các đợt chấm điểm</p>
            </a>
            <a href="admin_criteria.php" class="feature-card">
                <div class="feature-icon-wrapper"><i class="fas fa-tasks"></i></div>
                <h3>Bộ Tiêu Chí</h3>
                <p>Cấu hình khung điểm rèn luyện</p>
            </a>
            <a href="admin_accounts.php" class="feature-card">
                <div class="feature-icon-wrapper"><i class="fas fa-users-cog"></i></div>
                <h3>Tài Khoản</h3>
                <p>Quản lý người dùng hệ thống</p>
            </a>
            <a href="admin_permissions.php" class="feature-card">
                <div class="feature-icon-wrapper"><i class="fas fa-user-lock"></i></div>
                <h3>Phân Quyền</h3>
                <p>Cấp quyền sử dụng tính năng</p>
            </a>
            <a href="admin_assignments.php" class="feature-card">
                <div class="feature-icon-wrapper"><i class="fas fa-chalkboard-teacher"></i></div>
                <h3>Phân Công CVHT</h3>
                <p>Gán CVHT cho lớp theo học kỳ</p>
            </a>
            <a href="manage_notifications.php" class="feature-card">
                <div class="feature-icon-wrapper"><i class="fas fa-broadcast-tower"></i></div>
                <h3>Thông Báo Hệ Thống</h3>
                <p>Quản lý thông báo toàn trường</p>
            </a>
            
        <?php elseif ($role == 'khoa'): ?>
            <a href="department_classes.php" class="feature-card">
                <div class="feature-icon-wrapper"><i class="fas fa-check-circle"></i></div>
                <h3>Duyệt Điểm Khoa</h3>
                <p>Kiểm tra và phê duyệt cuối cùng</p>
            </a>
            <a href="department_reports.php" class="feature-card">
                <div class="feature-icon-wrapper"><i class="fas fa-chart-bar"></i></div>
                <h3>Báo Cáo Thống Kê</h3>
                <p>Xuất dữ liệu khen thưởng/kỷ luật</p>
            </a>
            <a href="notifications.php" class="feature-card">
                <?php if($unread_count > 0): ?><span class="noti-badge"><?php echo $unread_count; ?></span><?php endif; ?>
                <div class="feature-icon-wrapper"><i class="fas fa-bell"></i></div>
                <h3>Phát Thông Báo</h3>
                <p>Gửi thông báo toàn khoa</p>
            </a>
            <a href="department_complaints.php" class="feature-card">
                <div class="feature-icon-wrapper"><i class="fas fa-envelope-open-text"></i></div>
                <h3>Quản Lý Khiếu Nại</h3>
                <p>Giải quyết khiếu nại cấp Khoa</p>
            </a>
            <a href="khoa_import_attendance.php" class="feature-card">
                <div class="feature-icon-wrapper"><i class="fas fa-file-import"></i></div>
                <h3>Import Điểm Danh</h3>
                <p>Trừ điểm vắng/trễ từ file Excel</p>
            </a>
        <?php endif; ?>

        <?php
        // Lấy quyền được cấp thêm của user
        $res_perms = $conn->query("SELECT permissions FROM tai_khoan WHERE id = " . (int)$_SESSION['user_id']);
        $user_perms = [];
        if ($res_perms && $res_perms->num_rows > 0) {
            $row_perms = $res_perms->fetch_assoc();
            $user_perms = $row_perms['permissions'] ? explode(',', $row_perms['permissions']) : [];
        }

        // Khởi tạo các URL đã hiển thị theo vai trò mặc định
        $shown_urls = [];
        if ($role == 'sinh_vien') {
            $shown_urls = ['student_assessment.php', 'student_history.php', 'notifications.php', 'student_complaints.php'];
        } elseif ($role == 'cvht') {
            $shown_urls = ['advisor_manage_class.php', 'advisor_classes.php', 'advisor_reports.php', 'notifications.php', 'advisor_complaints.php'];
        } elseif ($role == 'admin') {
            $shown_urls = ['admin_cycles.php', 'admin_criteria.php', 'admin_accounts.php', 'admin_permissions.php', 'admin_assignments.php', 'manage_notifications.php'];
        } elseif ($role == 'khoa') {
            $shown_urls = ['department_classes.php', 'department_reports.php', 'notifications.php', 'department_complaints.php', 'khoa_import_attendance.php'];
        }

        // Định nghĩa tất cả các tính năng có thể cấp thêm
        $all_feature_cards = [
            'manage_cycles' => ['url' => 'admin_cycles.php', 'icon' => 'fa-calendar-alt', 'title' => 'Đợt Đánh Giá', 'desc' => 'Mở/Đóng các đợt chấm điểm'],
            'manage_criteria' => ['url' => 'admin_criteria.php', 'icon' => 'fa-tasks', 'title' => 'Bộ Tiêu Chí', 'desc' => 'Cấu hình khung điểm rèn luyện'],
            'manage_accounts' => ['url' => 'admin_accounts.php', 'icon' => 'fa-users-cog', 'title' => 'Tài Khoản', 'desc' => 'Quản lý người dùng hệ thống'],
            'manage_permissions' => ['url' => 'admin_permissions.php', 'icon' => 'fa-user-lock', 'title' => 'Phân Quyền', 'desc' => 'Cấp quyền sử dụng tính năng'],
            'manage_assignments' => ['url' => 'admin_assignments.php', 'icon' => 'fa-chalkboard-teacher', 'title' => 'Phân Công CVHT', 'desc' => 'Gán CVHT cho lớp theo học kỳ'],
            'advisor_manage_class' => ['url' => 'advisor_manage_class.php', 'icon' => 'fa-users', 'title' => 'Quản Lý Lớp', 'desc' => 'Danh sách sinh viên lớp chủ nhiệm'],
            'grade_advisor' => ['url' => 'advisor_classes.php', 'icon' => 'fa-check-double', 'title' => 'Duyệt Điểm (CVHT)', 'desc' => 'Chấm điểm và chốt sổ lớp'],
            'view_reports_advisor' => ['url' => 'advisor_reports.php', 'icon' => 'fa-chart-line', 'title' => 'Báo Cáo Lớp', 'desc' => 'Xem kết quả ĐRL và xuất báo cáo'],
            'advisor_complaints' => ['url' => 'advisor_complaints.php', 'icon' => 'fa-envelope-open-text', 'title' => 'Khiếu Nại (CVHT)', 'desc' => 'Giải quyết thắc mắc của sinh viên'],
            'grade_department' => ['url' => 'department_classes.php', 'icon' => 'fa-check-circle', 'title' => 'Duyệt Điểm Khoa', 'desc' => 'Kiểm tra và phê duyệt cuối cùng'],
            'view_reports_department' => ['url' => 'department_reports.php', 'icon' => 'fa-chart-bar', 'title' => 'Báo Cáo Thống Kê', 'desc' => 'Xuất dữ liệu khen thưởng/kỷ luật'],
            'department_complaints' => ['url' => 'department_complaints.php', 'icon' => 'fa-envelope-open-text', 'title' => 'Khiếu Nại Khoa', 'desc' => 'Giải quyết khiếu nại cấp Khoa'],
            'notifications' => ['url' => 'notifications.php', 'icon' => 'fa-bell', 'title' => 'Thông Báo', 'desc' => 'Hệ thống thông báo'],
            'student_history' => ['url' => 'student_history.php', 'icon' => 'fa-history', 'title' => 'Lịch Sử Điểm', 'desc' => 'Xem lại kết quả các học kỳ trước'],
            'student_complaints' => ['url' => 'student_complaints.php', 'icon' => 'fa-comments', 'title' => 'Hỏi Đáp & Khiếu Nại', 'desc' => 'Gửi câu hỏi hoặc khiếu nại điểm']
        ];

        // Hiển thị các tính năng được cấp thêm
        $has_extra = false;
        ob_start();
        foreach ($user_perms as $perm) {
            if (isset($all_feature_cards[$perm])) {
                $card = $all_feature_cards[$perm];
                if (!in_array($card['url'], $shown_urls)) {
                    $shown_urls[] = $card['url'];
                    $has_extra = true;
                    echo '<a href="'.$card['url'].'" class="feature-card" style="border: 1px dashed #F59E0B; background: #FFFBEB;">
                            <div class="feature-icon-wrapper" style="background: #FEF3C7; color: #D97706;"><i class="fas '.$card['icon'].'"></i></div>
                            <h3>'.$card['title'].'</h3>
                            <p>'.$card['desc'].'</p>
                        </a>';
                }
            }
        }
        $extra_cards = ob_get_clean();

        if ($has_extra) {
            echo '</div>'; // Đóng grid chính
            echo '<div class="extra-perms-container">';
            echo '<div class="extra-perms-badge"><i class="fas fa-star"></i> Mở rộng phân quyền</div>';
            echo '<div class="section-title" style="font-size: 1.25rem;"><i class="fas fa-gift" style="background: #FEF3C7; color: #D97706;"></i> <span>Được Cấp Thêm</span></div>';
            echo '<div class="features-grid">';
            echo $extra_cards;
        }
        ?>
    </div>
</div>

<script>
document.querySelectorAll('.feature-card[href="notifications.php"]').forEach(function(link) {
    link.addEventListener('click', function() {
        var badge = this.querySelector('.noti-badge');
        if (badge) {
            badge.style.display = 'none';
        }
    });
});

window.addEventListener("pageshow", function (event) {
    if (event.persisted) {
        window.location.reload();
    }
});
</script>

<?php require_once 'layout_footer.php'; ?>
