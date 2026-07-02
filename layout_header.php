<?php
// layout_header.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

require_once 'config.php';

$ho_ten = $_SESSION['ho_ten'] ?? 'Người dùng';
$role = $_SESSION['role'] ?? 'sinh_vien';
$page_title = $page_title ?? 'Hệ Thống ĐRL ITC';

// Lấy quyền được cấp thêm
$user_id = (int)($_SESSION['user_id'] ?? 0);
$res_perms = $conn->query("SELECT permissions FROM tai_khoan WHERE id = $user_id");
$user_perms = [];
if ($res_perms && $res_perms->num_rows > 0) {
    $row_perms = $res_perms->fetch_assoc();
    $user_perms = $row_perms['permissions'] ? explode(',', $row_perms['permissions']) : [];
}

// Tính số lượng thông báo mới (Hiển thị Global Sidebar)
$unread_count = 0;
if ($user_id > 0) {
    $sql_user_noti = "SELECT ngay_xem_thong_bao FROM tai_khoan WHERE id = $user_id";
    $res_user_noti = $conn->query($sql_user_noti);
    $ngay_xem = '2000-01-01 00:00:00';
    if ($res_user_noti && $res_user_noti->num_rows > 0) {
        $u_data = $res_user_noti->fetch_assoc();
        if ($u_data['ngay_xem_thong_bao']) {
            $ngay_xem = $u_data['ngay_xem_thong_bao'];
        }
    }
    if ($role == 'sinh_vien') {
        $sql_count = "SELECT COUNT(*) as c FROM thong_bao WHERE doi_tuong = 'tat_ca' AND ngay_tao > '$ngay_xem'";
    } else {
        $sql_count = "SELECT COUNT(*) as c FROM thong_bao WHERE ngay_tao > '$ngay_xem'";
    }
    $res_count = $conn->query($sql_count);
    if ($res_count && $row_c = $res_count->fetch_assoc()) {
        $unread_count = (int)$row_c['c'];
    }
}

$all_features = [
    'sinh_vien' => [
        ['url' => 'student_assessment.php', 'icon' => 'fa-clipboard-list', 'title' => 'Tự Đánh Giá'],
        ['url' => 'student_history.php', 'icon' => 'fa-history', 'title' => 'Lịch Sử Điểm'],
        ['url' => 'notifications.php', 'icon' => 'fa-bell', 'title' => 'Thông Báo'],
        ['url' => 'student_complaints.php', 'icon' => 'fa-comments', 'title' => 'Hỏi Đáp & Khiếu Nại'],
    ],
    'cvht' => [
        ['url' => 'advisor_manage_class.php', 'icon' => 'fa-users', 'title' => 'Quản Lý Lớp'],
        ['url' => 'advisor_classes.php', 'icon' => 'fa-check-double', 'title' => 'Duyệt Điểm'],
        ['url' => 'advisor_reports.php', 'icon' => 'fa-chart-line', 'title' => 'Báo Cáo Lớp'],
        ['url' => 'notifications.php', 'icon' => 'fa-bullhorn', 'title' => 'Quản Lý Thông Báo'],
        ['url' => 'advisor_complaints.php', 'icon' => 'fa-envelope-open-text', 'title' => 'Quản Lý Khiếu Nại'],
    ],
    'khoa' => [
        ['url' => 'khoa_import_attendance.php', 'icon' => 'fa-file-import', 'title' => 'Import Điểm Danh (CTSV)'],
        ['url' => 'khoa_import_grades.php', 'icon' => 'fa-file-excel', 'title' => 'Import Điểm Học Tập'],
        ['url' => 'department_classes.php', 'icon' => 'fa-check-circle', 'title' => 'Duyệt Điểm Khoa'],
        ['url' => 'department_reports.php', 'icon' => 'fa-chart-bar', 'title' => 'Báo Cáo Thống Kê'],
        ['url' => 'notifications.php', 'icon' => 'fa-bell', 'title' => 'Phát Thông Báo'],
        ['url' => 'department_complaints.php', 'icon' => 'fa-envelope-open-text', 'title' => 'Quản Lý Khiếu Nại'],
    ],
    'admin' => [
        ['url' => 'admin_cycles.php', 'icon' => 'fa-calendar-alt', 'title' => 'Đợt Đánh Giá'],
        ['url' => 'admin_criteria.php', 'icon' => 'fa-tasks', 'title' => 'Bộ Tiêu Chí'],
        ['url' => 'admin_accounts.php', 'icon' => 'fa-users-cog', 'title' => 'Tài Khoản'],
        ['url' => 'admin_permissions.php', 'icon' => 'fa-user-lock', 'title' => 'Phân Quyền'],
        ['url' => 'admin_assignments.php', 'icon' => 'fa-chalkboard-teacher', 'title' => 'Phân Công CVHT'],
        ['url' => 'manage_notifications.php', 'icon' => 'fa-broadcast-tower', 'title' => 'Thông Báo'],
    ]
];

$all_feature_cards = [
    'manage_cycles' => ['url' => 'admin_cycles.php', 'icon' => 'fa-calendar-alt', 'title' => 'Đợt Đánh Giá'],
    'manage_criteria' => ['url' => 'admin_criteria.php', 'icon' => 'fa-tasks', 'title' => 'Bộ Tiêu Chí'],
    'manage_accounts' => ['url' => 'admin_accounts.php', 'icon' => 'fa-users-cog', 'title' => 'Tài Khoản'],
    'manage_permissions' => ['url' => 'admin_permissions.php', 'icon' => 'fa-user-lock', 'title' => 'Phân Quyền'],
    'manage_assignments' => ['url' => 'admin_assignments.php', 'icon' => 'fa-chalkboard-teacher', 'title' => 'Phân Công CVHT'],
    'import_attendance' => ['url' => 'khoa_import_attendance.php', 'icon' => 'fa-file-import', 'title' => 'Import Điểm Danh (CTSV)'],
    'import_grades' => ['url' => 'khoa_import_grades.php', 'icon' => 'fa-file-excel', 'title' => 'Import Điểm Học Tập'],
    'advisor_manage_class' => ['url' => 'advisor_manage_class.php', 'icon' => 'fa-users', 'title' => 'Quản Lý Lớp'],
    'grade_advisor' => ['url' => 'advisor_classes.php', 'icon' => 'fa-check-double', 'title' => 'Duyệt Điểm (CVHT)'],
    'view_reports_advisor' => ['url' => 'advisor_reports.php', 'icon' => 'fa-chart-line', 'title' => 'Báo Cáo Lớp'],
    'advisor_complaints' => ['url' => 'advisor_complaints.php', 'icon' => 'fa-envelope-open-text', 'title' => 'Khiếu Nại (CVHT)'],
    'grade_department' => ['url' => 'department_classes.php', 'icon' => 'fa-check-circle', 'title' => 'Duyệt Điểm Khoa'],
    'view_reports_department' => ['url' => 'department_reports.php', 'icon' => 'fa-chart-bar', 'title' => 'Báo Cáo Thống Kê'],
    'department_complaints' => ['url' => 'department_complaints.php', 'icon' => 'fa-envelope-open-text', 'title' => 'Khiếu Nại Khoa'],
    'notifications' => ['url' => 'notifications.php', 'icon' => 'fa-bell', 'title' => 'Thông Báo'],
    'student_history' => ['url' => 'student_history.php', 'icon' => 'fa-history', 'title' => 'Lịch Sử Điểm'],
    'student_complaints' => ['url' => 'student_complaints.php', 'icon' => 'fa-comments', 'title' => 'Hỏi Đáp & Khiếu Nại']
];

$my_features = [];
if (isset($all_features[$role])) {
    $my_features = $all_features[$role];
}
foreach ($user_perms as $perm) {
    if (isset($all_feature_cards[$perm])) {
        // check if already in
        $exists = false;
        foreach ($my_features as $f) {
            if ($f['url'] == $all_feature_cards[$perm]['url']) { $exists = true; break; }
        }
        if (!$exists) {
            $my_features[] = $all_feature_cards[$perm];
        }
    }
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #004DCC;
            --primary-light: #3377FF;
            --secondary: #FFCC00;
            --dark: #1E293B;
            --gray: #64748B;
            --bg: #F8FAFC;
            --white: #FFFFFF;
            --border: #E2E8F0;
            --font-heading: 'Outfit', sans-serif;
            --font-body: 'Plus Jakarta Sans', sans-serif;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            --radius: 16px;
            --sidebar-width: 280px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: var(--font-body); }
        body { background: var(--bg); color: var(--dark); line-height: 1.6; display: flex; height: 100vh; overflow: hidden; }
        h1, h2, h3, h4 { font-family: var(--font-heading); font-weight: 700; }
        a { text-decoration: none; color: inherit; }
        
        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width); background: var(--primary); color: var(--white);
            display: flex; flex-direction: column; height: 100%; flex-shrink: 0;
            box-shadow: 4px 0 10px rgba(0, 77, 204, 0.1); position: relative; z-index: 10;
        }
        .sidebar-header {
            padding: 1.5rem; display: flex; align-items: center; gap: 12px;
            font-family: var(--font-heading); font-size: 1.4rem; font-weight: 800;
            color: var(--secondary); border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .sidebar-header img { height: 40px; border-radius: 8px; background: white; padding: 2px; }
        
        .sidebar-user {
            padding: 1.5rem; border-bottom: 1px solid rgba(255, 255, 255, 0.1); display: flex; align-items: center; gap: 15px;
        }
        .sidebar-user img { width: 45px; height: 45px; border-radius: 50%; border: 2px solid var(--secondary); }
        .sidebar-user-info { flex: 1; overflow: hidden; }
        .sidebar-user-name { font-weight: 700; font-size: 1rem; white-space: nowrap; text-overflow: ellipsis; overflow: hidden; color: var(--white); }
        .sidebar-user-role { font-size: 0.8rem; color: rgba(255, 255, 255, 0.7); text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; }
        
        .sidebar-menu { padding: 1rem 0; flex: 1; overflow-y: auto; }
        .menu-item {
            display: flex; align-items: center; gap: 15px; padding: 12px 1.5rem; color: rgba(255,255,255,0.8);
            text-decoration: none; font-weight: 600; transition: all 0.2s; border-left: 4px solid transparent; margin-bottom: 5px; position: relative;
        }
        .menu-item:hover { background: rgba(255, 255, 255, 0.1); color: var(--white); }
        .menu-item.active { background: rgba(255, 204, 0, 0.15); color: var(--secondary); border-left-color: var(--secondary); }
        .menu-item i { width: 20px; font-size: 1.1rem; text-align: center; }
        
        .sidebar-badge {
            position: absolute; right: 15px; top: 50%; transform: translateY(-50%);
            background: #EF4444; color: var(--white); font-size: 0.75rem; font-weight: 800;
            padding: 2px 8px; border-radius: 50px; border: 2px solid var(--primary);
            box-shadow: 0 0 10px rgba(239, 68, 68, 0.4); animation: pulseSidebar 2s infinite;
        }
        @keyframes pulseSidebar {
            0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
            70% { box-shadow: 0 0 0 8px rgba(239, 68, 68, 0); }
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
        
        .sidebar-footer { padding: 1.5rem; border-top: 1px solid rgba(255, 255, 255, 0.1); }
        .btn-logout {
            display: flex; align-items: center; justify-content: center; gap: 10px; width: 100%;
            background: rgba(255, 255, 255, 0.1); color: var(--white); padding: 10px; border-radius: 8px;
            text-decoration: none; font-weight: 600; transition: all 0.2s; border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .btn-logout:hover { background: #EF4444; border-color: #EF4444; }
        
        /* Main Content */
        .main-wrapper { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
        .top-navbar {
            background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(12px);
            padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid var(--border); box-shadow: var(--shadow);
            position: sticky; top: 0; z-index: 5;
        }
        .menu-toggle { display: none; font-size: 1.5rem; color: var(--primary); cursor: pointer; }
        .page-header-title { font-family: var(--font-heading); font-size: 1.4rem; color: var(--primary); font-weight: 700; }
        
        .main-content { flex: 1; overflow-y: auto; padding: 2rem; }
        
        /* Unified Global Components */
        .card { background: var(--white); border-radius: var(--radius); box-shadow: var(--shadow); border: 1px solid var(--border); margin-bottom: 2rem; overflow: hidden; }
        .card-header { padding: 1.5rem 2rem; background: linear-gradient(to right, #F8FAFC, #FFFFFF); border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .card-title { color: var(--primary); font-size: 1.5rem; margin: 0; display: flex; align-items: center; gap: 10px; }
        .card-body { padding: 2rem; }
        
        .table-responsive { width: 100%; overflow-x: auto; border: 2px solid var(--dark); border-radius: 12px; box-shadow: 0 15px 30px -5px rgba(0,0,0,0.2); background: var(--white); }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { background: var(--dark); color: var(--white); font-family: var(--font-heading); font-weight: 600; padding: 1.25rem 1.5rem; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #0F172A; white-space: nowrap; }
        td { padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border); vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: #F8FAFC; }
        
        .badge { display: inline-flex; align-items: center; gap: 5px; white-space: nowrap; padding: 4px 10px; border-radius: 50px; font-size: 0.8rem; font-weight: 600; font-family: var(--font-body); border: 1px solid transparent; }
        .badge-gray { background: #F1F5F9; color: #475569; border-color: #E2E8F0; }
        .badge-yellow { background: #FFFBEB; color: #B45309; border-color: #FDE68A; }
        .badge-blue { background: #EFF6FF; color: var(--primary); border-color: #BFDBFE; }
        .badge-green { background: #F0FDF4; color: #15803D; border-color: #BBF7D0; }
        
        .alert { padding: 1rem 1.25rem; border-radius: 12px; margin-bottom: 2rem; font-weight: 500; display: flex; align-items: center; gap: 12px; box-shadow: var(--shadow); }
        .alert-success { background: #F0FDF4; color: #15803D; border: 1px solid #BBF7D0; }
        .alert-error { background: #FEF2F2; color: #B91C1C; border: 1px solid #FECACA; }
        
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: var(--dark); font-size: 0.95rem; }
        .form-control { width: 100%; padding: 12px 15px; border: 2px solid #94A3B8; border-radius: 8px; font-size: 1rem; font-family: var(--font-body); transition: all 0.2s; background: #FFFFFF; color: var(--dark); box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .form-control:focus { outline: none; border-color: var(--dark); background: var(--white); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s; border: none; font-size: 0.95rem; text-decoration: none; font-family: var(--font-heading); }
        .btn-primary { background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%); color: var(--white); box-shadow: 0 4px 6px rgba(0, 77, 204, 0.2); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 12px rgba(0, 77, 204, 0.3); }
        .btn-success { background: linear-gradient(135deg, #10B981 0%, #059669 100%); color: var(--white); box-shadow: 0 4px 6px rgba(16, 185, 129, 0.2); }
        .btn-success:hover { transform: translateY(-2px); box-shadow: 0 8px 12px rgba(16, 185, 129, 0.3); }
        .btn-warning { background: linear-gradient(135deg, var(--secondary) 0%, #D97706 100%); color: var(--dark); box-shadow: 0 4px 6px rgba(255, 204, 0, 0.2); }
        .btn-warning:hover { transform: translateY(-2px); box-shadow: 0 8px 12px rgba(255, 204, 0, 0.3); }
        .btn-danger { background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%); color: var(--white); box-shadow: 0 4px 6px rgba(239, 68, 68, 0.2); }
        .btn-danger:hover { transform: translateY(-2px); box-shadow: 0 8px 12px rgba(239, 68, 68, 0.3); }
        .btn-secondary { background: #E2E8F0; color: #475569; }
        .btn-secondary:hover { background: #CBD5E1; color: #1E293B; }
        .btn-action { display: inline-flex; align-items: center; justify-content: center; gap: 6px; background: linear-gradient(135deg, var(--primary) 0%, #003399 100%); color: var(--white); padding: 8px 16px; border-radius: 8px; font-weight: 700; font-family: var(--font-heading); font-size: 0.9rem; border: none; cursor: pointer; transition: all 0.3s; box-shadow: 0 4px 6px rgba(0, 77, 204, 0.2); text-decoration: none; white-space: nowrap; }
        .btn-action:hover { transform: translateY(-2px); box-shadow: 0 8px 12px rgba(0, 77, 204, 0.3); color: var(--secondary); }

        /* Generic Admin & User UI Components Restored */
        
        /* Accordion */
        .accordion-header { cursor: pointer; display: flex; justify-content: space-between; align-items: center; color: var(--primary); margin: 0; padding-bottom: 10px; border-bottom: 1px dashed var(--border); font-family: var(--font-heading); font-size: 1.2rem; }
        .accordion-header:hover { color: var(--secondary); }
        .accordion-content { margin-top: 15px; padding-top: 15px; }
        .icon-arrow { font-size: 1rem; color: var(--gray); transition: transform 0.3s; }
        
        /* Form Layout */
        .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px; }
        .info-box { background: #EFF6FF; border: 1px dashed var(--primary); padding: 15px; border-radius: 8px; margin-bottom: 25px; font-size: 0.95rem; color: #1E3A8A; line-height: 1.5; }
        
        /* Modals */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: var(--white); width: 100%; max-width: 600px; border-radius: 16px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); animation: modalIn 0.3s cubic-bezier(0.16, 1, 0.3, 1); max-height: 90vh; overflow-y: auto; }
        @keyframes modalIn { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .modal-header { padding: 1.5rem 2rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: #F8FAFC; border-radius: 16px 16px 0 0; }
        .modal-header h3 { margin: 0; color: var(--primary); font-size: 1.25rem; font-family: var(--font-heading); }
        .close-btn { background: none; border: none; font-size: 1.5rem; color: var(--gray); cursor: pointer; transition: color 0.2s; }
        .close-btn:hover { color: #EF4444; }
        .modal-footer { padding: 1.5rem 2rem; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 10px; background: #F8FAFC; border-radius: 0 0 16px 16px; margin-top: 1.5rem; }
        
        /* Groups & Cards */
        .group-card { background: var(--white); border-radius: var(--radius); border: 2px solid var(--dark); margin-bottom: 2rem; box-shadow: 0 15px 30px -5px rgba(0,0,0,0.15); overflow: hidden; }
        .group-header { padding: 1.25rem 1.5rem; background: var(--dark); border-bottom: 1px solid var(--dark); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        .group-title { font-weight: 700; color: var(--white); font-family: var(--font-heading); font-size: 1.1rem; display: flex; align-items: center; }
        .header-actions { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2rem; flex-wrap: wrap; gap: 15px; }
        .col-diem { width: 100px; text-align: center; }
        .col-actions { width: 120px; text-align: right; }
        
        /* Badges overrides */
        .badge-active { background: #DCFCE7; color: #15803D; border-color: #BBF7D0; }
        .badge-closed { background: #F1F5F9; color: #64748B; border-color: #CBD5E1; }
        
        /* Page Header & Search */
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 15px; }
        .page-title { color: var(--primary); font-size: 1.5rem; display: flex; align-items: center; gap: 10px; font-family: var(--font-heading); }
        .search-box { position: relative; width: 300px; }
        .search-box i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--gray); }
        .search-box input { width: 100%; padding: 10px 10px 10px 35px; border: 2px solid #94A3B8; border-radius: 8px; outline: none; transition: 0.2s; background: #FFFFFF; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .search-box input:focus { border-color: var(--dark); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        
        /* Tabs */
        .tabs { display: flex; gap: 10px; margin-bottom: 1.5rem; overflow-x: auto; padding-bottom: 5px; border-bottom: 2px solid var(--border); }
        .tab-btn { padding: 10px 20px; border: none; background: transparent; font-weight: 600; color: var(--gray); cursor: pointer; border-bottom: 3px solid transparent; transition: 0.2s; white-space: nowrap; font-family: var(--font-heading); font-size: 1.05rem; }
        .tab-btn:hover { color: var(--primary); }
        .tab-btn.active { color: var(--primary); border-bottom-color: var(--primary); font-weight: 800; }
        
        /* User Permissions Cards */
        .user-list { display: flex; flex-direction: column; gap: 15px; }
        .user-card { background: var(--white); border-radius: var(--radius); border: 2px solid var(--dark); box-shadow: 0 10px 20px -5px rgba(0,0,0,0.1); transition: all 0.3s; overflow: hidden; }
        .user-card:hover { transform: translateY(-2px); box-shadow: 0 15px 30px -5px rgba(0,0,0,0.2); }
        .user-header { display: flex; justify-content: space-between; align-items: center; padding: 1.25rem 1.5rem; cursor: pointer; background: #F8FAFC; transition: background 0.2s; }
        .user-header:hover { background: #F1F5F9; }
        .user-info { display: flex; align-items: center; gap: 15px; }
        .user-avatar { width: 45px; height: 45px; border-radius: 50%; color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.2rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1); font-family: var(--font-heading); }
        .u-name { font-size: 1.1rem; color: var(--dark); font-weight: 700; font-family: var(--font-heading); margin-bottom: 2px; }
        .f-name { font-size: 0.85rem; color: var(--gray); font-weight: 500; }
        .user-meta { display: flex; align-items: center; gap: 15px; }
        .expand-icon { transition: transform 0.3s; color: var(--gray); }
        .user-card.expanded .expand-icon { transform: rotate(180deg); }
        .user-details { max-height: 0; overflow: hidden; transition: max-height 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
        .user-card.expanded .user-details { max-height: 1500px; border-top: 2px solid var(--dark); }
        .details-inner { padding: 1.5rem; background: var(--white); }
        
        /* Permission Grid */
        .perm-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; }
        .perm-group { background: #F8FAFC; border: 2px solid #E2E8F0; border-radius: 12px; padding: 1.25rem; }
        .perm-group h5 { margin-bottom: 15px; display: flex; align-items: center; gap: 10px; font-size: 1.05rem; font-family: var(--font-heading); }
        .perm-group h5 i { width: 28px; height: 28px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; color: white; font-size: 0.9rem; }
        .perm-items { display: flex; flex-direction: column; gap: 12px; }
        .perm-item { display: flex; justify-content: space-between; align-items: center; padding: 10px 12px; background: var(--white); border-radius: 8px; border: 2px solid #E2E8F0; font-size: 0.9rem; font-weight: 600; color: var(--dark); transition: all 0.2s; }
        .perm-item:hover { border-color: var(--primary); }
        
        /* Toggle Switch */
        .toggle-switch { position: relative; display: inline-block; width: 44px; height: 24px; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #CBD5E1; transition: .3s; border-radius: 34px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.1); }
        .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .3s; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.3); }
        input:checked + .slider { background-color: #10B981; }
        input:focus + .slider { box-shadow: 0 0 0 2px rgba(16,185,129,0.3); }
        input:checked + .slider:before { transform: translateX(20px); }
        
        /* Toast Notification */
        #toast { visibility: hidden; min-width: 250px; background-color: var(--dark); color: #fff; text-align: center; border-radius: 12px; padding: 15px 25px; position: fixed; z-index: 1100; right: 30px; bottom: 30px; font-weight: 700; box-shadow: 0 15px 30px -5px rgba(0,0,0,0.3); opacity: 0; transition: opacity 0.3s, transform 0.3s; transform: translateY(20px); display: flex; align-items: center; justify-content: center; gap: 10px; font-family: var(--font-body); border-left: 5px solid #10B981; }
        #toast.show { visibility: visible; opacity: 1; transform: translateY(0); }
        
        /* Assessment Table Specifics */
        .nhom-tieu-chi td { background: linear-gradient(to right, #F8FAFC, #FFFFFF); font-weight: 800; color: var(--dark); font-family: var(--font-heading); font-size: 1.15rem; border-bottom: 2px solid var(--border); border-top: 2px solid var(--border); }
        .tc-content { font-weight: 600; color: var(--dark); line-height: 1.5; font-size: 1.05rem; }
        .input-diem { width: 80px; padding: 10px; border: 2px solid var(--dark); border-radius: 8px; text-align: center; font-weight: 800; font-size: 1.2rem; color: var(--primary); font-family: var(--font-heading); transition: all 0.2s; background: #FFFFFF; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .input-diem:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 4px rgba(0,77,204,0.15); transform: translateY(-2px); }
        .input-diem:disabled { background: #F1F5F9; color: var(--gray); border-color: #CBD5E1; box-shadow: none; }
        .input-minh-chung { width: 100%; padding: 10px 15px; border: 2px solid #94A3B8; border-radius: 8px; font-size: 1rem; font-weight: 500; transition: all 0.2s; background: #FFFFFF; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .input-minh-chung:focus { outline: none; border-color: var(--dark); box-shadow: 0 0 0 4px rgba(30,41,59,0.1); transform: translateY(-2px); }
        .input-minh-chung:disabled { background: #F1F5F9; color: var(--gray); border-color: #CBD5E1; box-shadow: none; }
        .table-footer td { background: var(--dark); color: var(--white); font-weight: 800; font-family: var(--font-heading); font-size: 1.25rem; border-top: 3px solid var(--secondary); text-transform: uppercase; }
        .submit-area { margin-top: 2rem; display: flex; flex-direction: column; align-items: center; gap: 15px; padding-top: 2rem; border-top: 1px dashed var(--border); }
        .btn-submit { background: linear-gradient(135deg, var(--primary) 0%, #003399 100%); color: var(--white); padding: 15px 40px; border-radius: 50px; font-weight: 700; font-size: 1.1rem; border: none; cursor: pointer; transition: all 0.3s; font-family: var(--font-heading); display: inline-flex; align-items: center; gap: 10px; box-shadow: 0 10px 20px rgba(0, 77, 204, 0.2); }
        .btn-submit:hover:not(:disabled) { transform: translateY(-3px); box-shadow: 0 15px 25px rgba(0, 77, 204, 0.3); }
        .btn-submit:disabled { background: var(--gray); box-shadow: none; cursor: not-allowed; opacity: 0.7; }
        .badge-deadline { display: inline-flex; align-items: center; gap: 8px; background: #FEF2F2; color: #DC2626; padding: 6px 15px; border-radius: 50px; font-weight: 700; font-size: 0.9rem; border: 1px solid #FECACA; }
        
        /* Profile Layout */
        .profile-wrapper { display: grid; grid-template-columns: 1fr 2fr; gap: 2rem; align-items: start; }
        .profile-card-left { background: var(--white); border-radius: var(--radius); border: 1px solid var(--border); padding: 3rem 2rem; text-align: center; box-shadow: var(--shadow); position: sticky; top: 100px; }
        .profile-avatar-container { width: 120px; height: 120px; border-radius: 50%; margin: 0 auto 1.5rem auto; background: linear-gradient(135deg, var(--primary), var(--primary-light)); padding: 4px; box-shadow: 0 10px 25px rgba(0, 77, 204, 0.2); }
        .profile-avatar { width: 100%; height: 100%; border-radius: 50%; background: var(--white); display: flex; align-items: center; justify-content: center; overflow: hidden; font-size: 2rem; font-weight: 700; color: var(--primary); }
        .profile-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .profile-name { font-size: 1.5rem; color: var(--primary); margin-bottom: 5px; font-family: var(--font-heading); }
        .profile-role { display: inline-block; background: #FFFBEB; color: #B45309; padding: 4px 12px; border-radius: 50px; font-weight: 600; font-size: 0.85rem; border: 1px solid #FDE68A; margin-bottom: 1.5rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .profile-card-right { background: var(--white); border-radius: var(--radius); border: 1px solid var(--border); padding: 2.5rem; box-shadow: var(--shadow); }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2.5rem; }
        .info-item { background: #F8FAFC; padding: 1.25rem; border-radius: 12px; border: 1px solid var(--border); transition: all 0.2s; }
        .info-item:hover { border-color: #CBD5E1; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
        .info-item label { display: block; font-size: 0.85rem; color: var(--gray); margin-bottom: 8px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .info-item .val { font-size: 1.05rem; font-weight: 600; color: var(--dark); font-family: var(--font-heading); }
        
        /* Event Box */
        .event-box { margin-top: 2rem; padding: 1.5rem; background: #FFFBEB; border: 1px solid #FDE68A; border-radius: 12px; display: flex; flex-direction: column; gap: 10px; }
        .btn-event { background: linear-gradient(135deg, var(--secondary) 0%, #D97706 100%); color: var(--white); padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.3s; box-shadow: 0 4px 6px rgba(217, 119, 6, 0.2); font-family: var(--font-heading); font-size: 1.05rem; border: none; cursor: pointer; width: 100%; }
        .btn-event:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 8px 12px rgba(217, 119, 6, 0.3); }
        .btn-event:disabled { background: var(--gray); box-shadow: none; cursor: not-allowed; opacity: 0.8; }
        .btn-event.expired { background: #EF4444; }
        .event-meta { font-size: 0.85rem; color: #B45309; font-weight: 500; display: flex; align-items: center; gap: 6px; }
        
        /* Empty States & Common utilities */
        .empty-state { text-align: center; padding: 4rem 2rem; color: var(--gray); background: var(--white); border-radius: var(--radius); border: 1px solid var(--border); box-shadow: var(--shadow); }
        .empty-state i { font-size: 4rem; color: #E2E8F0; margin-bottom: 1.5rem; }
        .empty-state p { font-size: 1.1rem; }
        .text-center { text-align: center; }
        .mt-3 { margin-top: 1rem; }
        .mt-4 { margin-top: 1.5rem; }
        .mb-3 { margin-bottom: 1rem; }
        .mb-4 { margin-bottom: 1.5rem; }
        .d-flex { display: flex; }
        .justify-content-between { justify-content: space-between; }
        .align-items-center { align-items: center; }
        .gap-2 { gap: 0.5rem; }
        .gap-3 { gap: 1rem; }
        .fw-bold { font-weight: 700; }
        .text-primary { color: var(--primary); }
        .text-danger { color: #EF4444; }
        
        /* Notifications & Messages */
        .notification-card { background: var(--white); border-radius: var(--radius); border: 1px solid var(--border); padding: 2rem; transition: var(--transition); position: relative; overflow: hidden; box-shadow: var(--shadow); margin-bottom: 1.5rem; }
        .notification-card::before { content: ''; position: absolute; left: 0; top: 0; height: 100%; width: 4px; background: var(--primary); border-radius: var(--radius) 0 0 var(--radius); }
        .noti-header { display: flex; justify-content: space-between; margin-bottom: 1rem; align-items: flex-start; gap: 1rem; flex-wrap: wrap; }
        .noti-title { font-size: 1.4rem; font-weight: 700; color: var(--primary); font-family: var(--font-heading); margin-bottom: 8px; line-height: 1.3; }
        .noti-meta { font-size: 0.9rem; color: var(--gray); display: flex; gap: 15px; flex-wrap: wrap; }
        .noti-body { margin-top: 1rem; color: var(--dark); line-height: 1.6; font-size: 1rem; white-space: pre-line; }
        .noti-actions { margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px dashed var(--border); display: flex; gap: 15px; justify-content: flex-end; }
        
        .chat-box { background: var(--white); border-radius: var(--radius); border: 1px solid var(--border); overflow: hidden; display: flex; flex-direction: column; box-shadow: var(--shadow); }
        .chat-header { padding: 1.25rem 1.5rem; background: linear-gradient(to right, #F8FAFC, #FFFFFF); border-bottom: 1px solid var(--border); font-weight: 700; color: var(--primary); font-family: var(--font-heading); font-size: 1.1rem; display: flex; align-items: center; gap: 10px; }
        .chat-messages { padding: 1.5rem; display: flex; flex-direction: column; gap: 1.5rem; max-height: 500px; overflow-y: auto; background: #F8FAFC; }
        .msg-item { display: flex; flex-direction: column; max-width: 80%; }
        .msg-item.sv { align-self: flex-end; }
        .msg-item.sv .msg-bubble { background: var(--primary); color: var(--white); border-radius: 16px 16px 0 16px; box-shadow: 0 4px 10px rgba(0, 77, 204, 0.15); }
        .msg-item.admin { align-self: flex-start; }
        .msg-item.admin .msg-bubble { background: var(--white); color: var(--dark); border-radius: 16px 16px 16px 0; border: 1px solid var(--border); box-shadow: var(--shadow); }
        .msg-meta { font-size: 0.8rem; color: var(--gray); margin-bottom: 6px; display: flex; gap: 10px; }
        .msg-item.sv .msg-meta { justify-content: flex-end; }
        .msg-bubble { padding: 12px 18px; line-height: 1.5; font-size: 0.95rem; }
        .chat-input { padding: 1.5rem; background: var(--white); border-top: 1px solid var(--border); display: flex; gap: 15px; }
        .chat-input textarea { flex: 1; border: 2px solid #E2E8F0; border-radius: 12px; padding: 12px 15px; font-family: var(--font-body); resize: none; outline: none; transition: 0.2s; background: #F8FAFC; }
        .chat-input textarea:focus { border-color: var(--primary); background: var(--white); }

        .close-sidebar { display: none; font-size: 1.5rem; color: var(--white); cursor: pointer; transition: 0.2s; }
        .close-sidebar:hover { color: var(--secondary); transform: rotate(90deg); }

        @media (max-width: 992px) {
            .sidebar { position: fixed; left: -280px; transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1); z-index: 10000; height: 100dvh; }
            .sidebar.show { left: 0; box-shadow: 10px 0 25px rgba(0,0,0,0.2); }
            .menu-toggle { display: block; }
            .close-sidebar { display: block; }
            .sidebar-header { justify-content: space-between; }
            .sidebar-overlay { display: block; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 9999; opacity: 0; visibility: hidden; transition: all 0.3s; }
            .sidebar-overlay.show { opacity: 1; visibility: visible; }
            
            .profile-wrapper { grid-template-columns: 1fr; }
            .profile-card-left { position: static; }
        }
        
        @media (max-width: 768px) {
            .top-navbar { padding: 1rem; }
            .page-header-title { font-size: 1.2rem; }
            .nav-date { display: none; }
            
            .main-content { padding: 1rem; }
            .card-header, .card-body { padding: 1.25rem; }
            .group-header { padding: 1rem; flex-direction: column; align-items: flex-start; }
            
            .btn { width: 100%; justify-content: center; margin-bottom: 8px; }
            .header-actions { flex-direction: column; align-items: stretch; }
            .search-box { width: 100%; }
            
            .info-grid { grid-template-columns: 1fr; gap: 1rem; }
            
            .noti-header { flex-direction: column; align-items: flex-start; }
            .noti-meta { gap: 10px; }
            .notification-card { padding: 1.25rem; }
            .noti-actions { flex-direction: column; }
            
            .modal-content { max-height: 95vh; border-radius: 12px; }
            .modal-header, .modal-footer { padding: 1rem; }
            
            .table-responsive { border-radius: 8px; border: 1px solid var(--border); }
            th, td { padding: 1rem; font-size: 0.9rem; }
            .col-diem { width: auto; }
            
            .tabs { padding-bottom: 0; }
            .tab-btn { padding: 10px 15px; font-size: 0.95rem; }
        }
    </style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div style="display: flex; align-items: center; gap: 12px;">
            <img src="logo.png" alt="ITC Logo"> ITC ĐRL
        </div>
        <i class="fas fa-times close-sidebar" id="closeSidebar"></i>
    </div>
    
    <div class="sidebar-user">
        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($ho_ten); ?>&background=FFCC00&color=004DCC" alt="Avatar">
        <div class="sidebar-user-info">
            <div class="sidebar-user-name"><?php echo htmlspecialchars($ho_ten); ?></div>
            <div class="sidebar-user-role"><?php echo htmlspecialchars($role); ?></div>
        </div>
    </div>
    
    <div class="sidebar-menu">
        <a href="dashboard.php" class="menu-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i> <span>Trang Chủ</span>
        </a>
        <a href="profile.php" class="menu-item <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
            <i class="fas fa-user-circle"></i> <span>Hồ Sơ Cá Nhân</span>
        </a>
        
        <div style="padding: 1rem 1.5rem 0.5rem; font-size: 0.75rem; color: rgba(255,255,255,0.5); text-transform: uppercase; font-weight: 700; letter-spacing: 1px;">Tính Năng</div>
        
        <?php foreach($my_features as $f): ?>
        <a href="<?php echo $f['url']; ?>" class="menu-item <?php echo $current_page == $f['url'] ? 'active' : ''; ?>">
            <i class="fas <?php echo $f['icon']; ?>"></i> <span><?php echo $f['title']; ?></span>
            <?php if ($f['url'] == 'notifications.php' && $unread_count > 0): ?>
                <span class="sidebar-badge"><?php echo $unread_count; ?></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>
    
    <div class="sidebar-footer">
        <a href="logout.php" class="btn-logout">
            <i class="fas fa-sign-out-alt"></i> Đăng xuất
        </a>
    </div>
</aside>

<div class="main-wrapper">
    <div class="top-navbar">
        <div style="display: flex; align-items: center; gap: 15px;">
            <i class="fas fa-bars menu-toggle" id="menuToggle"></i>
            <div class="page-header-title"><?php echo htmlspecialchars($page_title); ?></div>
        </div>
        <div>
            <div class="nav-date" style="font-weight: 600; color: var(--gray);"><i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y'); ?></div>
        </div>
    </div>
    
    <div class="main-content">
