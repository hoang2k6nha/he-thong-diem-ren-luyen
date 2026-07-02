<?php
require_once 'config.php';

// Kiểm tra quyền Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    redirect('index.php');
}

$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$msg = '';
$msg_type = 'success';

// --- XỬ LÝ CÁC HÀNH ĐỘNG POST ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    
    // Xử lý Phân quyền (AJAX)
    if ($_POST['action'] == 'update_permission_ajax') {
        $uid = (int)$_POST['user_id'];
        $perm = $conn->real_escape_string($_POST['permission']);
        $is_checked = $_POST['is_checked'] === 'true';

        $res = $conn->query("SELECT permissions FROM tai_khoan WHERE id = $uid");
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $perms = $row['permissions'] ? explode(',', $row['permissions']) : [];
            if ($is_checked) {
                if (!in_array($perm, $perms)) $perms[] = $perm;
            } else {
                $perms = array_filter($perms, fn($p) => $p !== $perm);
            }
            $new_perms = $conn->real_escape_string(implode(',', $perms));
            $conn->query("UPDATE tai_khoan SET permissions = '$new_perms' WHERE id = $uid");
            echo json_encode(['success' => true]);
            exit();
        }
    }

    // 1. Xử lý Thêm Đợt Đánh Giá
    if ($_POST['action'] == 'add_cycle') {
        $ten_dot = $conn->real_escape_string($_POST['ten_dot']);
        $hoc_ky = (int)$_POST['hoc_ky'];
        $nam_hoc = $conn->real_escape_string($_POST['nam_hoc']);
        
        // Đóng tất cả các đợt cũ
        $conn->query("UPDATE dot_danh_gia SET trang_thai = 'da_dong'");
        
        // Mở đợt mới
        $sql = "INSERT INTO dot_danh_gia (ten_dot, hoc_ky, nam_hoc, trang_thai) VALUES ('$ten_dot', $hoc_ky, '$nam_hoc', 'dang_mo')";
        if ($conn->query($sql)) {
            $msg = "Đã mở đợt đánh giá mới thành công!";
        } else {
            $msg = "Lỗi khi mở đợt đánh giá."; $msg_type = 'error';
        }
    }
    
    // 2. Xử lý Thêm Tài Khoản
    if ($_POST['action'] == 'add_account') {
        $username = $conn->real_escape_string($_POST['username']);
        $ho_ten = $conn->real_escape_string($_POST['ho_ten']);
        $vai_tro = $conn->real_escape_string($_POST['vai_tro']);
        $password = md5('123456'); // Mật khẩu mặc định
        
        // Kiểm tra tồn tại
        $check = $conn->query("SELECT id FROM tai_khoan WHERE username = '$username'");
        if ($check->num_rows > 0) {
            $msg = "Tài khoản (Tên đăng nhập) đã tồn tại!"; $msg_type = 'error';
        } else {
            $sql = "INSERT INTO tai_khoan (username, password, ho_ten, vai_tro, trang_thai) VALUES ('$username', '$password', '$ho_ten', '$vai_tro', 1)";
            if ($conn->query($sql)) {
                $msg = "Đã tạo tài khoản $username thành công (Mật khẩu: 123456).";
            } else {
                $msg = "Lỗi khi tạo tài khoản."; $msg_type = 'error';
            }
        }
    }
}

// --- LẤY DỮ LIỆU ĐỂ HIỂN THỊ ---
$total_sv = $conn->query("SELECT COUNT(id) AS total FROM tai_khoan WHERE vai_tro = 'sinh_vien'")->fetch_assoc()['total'];
$total_cvht = $conn->query("SELECT COUNT(id) AS total FROM tai_khoan WHERE vai_tro = 'cvht'")->fetch_assoc()['total'];
$total_khoa = $conn->query("SELECT COUNT(id) AS total FROM tai_khoan WHERE vai_tro = 'khoa'")->fetch_assoc()['total'];
$current_dot = $conn->query("SELECT * FROM dot_danh_gia WHERE trang_thai = 'dang_mo' ORDER BY id DESC LIMIT 1")->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ITC DRS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary-blue: #004DCC; --primary-yellow: #FFCC00; --bg: #F1F5F9; --border: #E2E8F0; --text: #1E293B; --sidebar: #0F172A; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { display: flex; min-height: 100vh; background: var(--bg); color: var(--text); }
        
        /* Sidebar */
        .sidebar { width: 260px; background: var(--sidebar); color: white; display: flex; flex-direction: column; transition: all 0.3s; }
        .sidebar-header { padding: 20px; display: flex; align-items: center; gap: 10px; font-size: 1.2rem; font-weight: 700; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header img { height: 35px; background: white; padding: 2px; border-radius: 50%; }
        .sidebar-menu { list-style: none; padding: 20px 0; flex: 1; }
        .sidebar-menu li a { display: flex; align-items: center; gap: 15px; padding: 12px 25px; color: #94A3B8; text-decoration: none; font-weight: 500; transition: all 0.2s; border-left: 4px solid transparent; }
        .sidebar-menu li a:hover, .sidebar-menu li a.active { color: white; background: rgba(255,255,255,0.05); border-left-color: var(--primary-yellow); }
        .sidebar-menu li a i { width: 20px; font-size: 1.1rem; }
        
        /* Main Content */
        .main-wrapper { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
        .topbar { height: 70px; background: white; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; padding: 0 30px; box-shadow: 0 2px 5px rgba(0,0,0,0.02); }
        .topbar-user { display: flex; align-items: center; gap: 15px; font-weight: 600; }
        .btn-logout { background: #FEF2F2; color: #DC2626; padding: 8px 15px; border-radius: 6px; text-decoration: none; font-size: 0.9rem; font-weight: 500; }
        .btn-logout:hover { background: #FEE2E2; }
        
        .content { padding: 30px; overflow-y: auto; flex: 1; }
        .page-title { font-size: 1.5rem; color: var(--primary-blue); margin-bottom: 25px; font-weight: 700; }
        
        /* Dashboard Stats */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 12px; border: 1px solid var(--border); box-shadow: 0 4px 6px rgba(0,0,0,0.02); display: flex; align-items: center; gap: 20px; }
        .stat-icon { width: 60px; height: 60px; border-radius: 12px; background: #EFF6FF; color: var(--primary-blue); display: flex; justify-content: center; align-items: center; font-size: 1.8rem; }
        .stat-icon.yellow { background: #FEF9C3; color: #B45309; }
        .stat-icon.green { background: #DCFCE7; color: #15803D; }
        .stat-info h4 { font-size: 0.9rem; color: #64748B; font-weight: 500; margin-bottom: 5px; }
        .stat-info h2 { font-size: 1.8rem; color: var(--text); }
        
        /* Cards */
        .card { background: white; border-radius: 12px; border: 1px solid var(--border); box-shadow: 0 4px 6px rgba(0,0,0,0.02); margin-bottom: 30px; overflow: hidden; }
        .card-header { padding: 20px 25px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: #F8FAFC; }
        .card-header h3 { font-size: 1.1rem; color: var(--primary-blue); }
        .card-body { padding: 25px; }
        
        /* Form */
        .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 8px; font-size: 0.9rem; font-weight: 600; color: #475569; }
        .form-control { width: 100%; padding: 10px 15px; border: 1px solid #CBD5E1; border-radius: 6px; font-size: 0.95rem; }
        .form-control:focus { outline: none; border-color: var(--primary-blue); }
        .btn { padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer; border: none; font-size: 0.95rem; transition: all 0.2s; }
        .btn-primary { background: var(--primary-blue); color: white; }
        .btn-primary:hover { background: var(--secondary-blue); }
        
        /* Alert */
        .alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; font-weight: 500; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: #F0FDF4; color: #15803D; border: 1px solid #BBF7D0; }
        .alert-error { background: #FEF2F2; color: #B91C1C; border: 1px solid #FECACA; }
        
        /* Tabs & Search */
        .tabs { display: flex; border-bottom: 2px solid var(--border); margin-bottom: 20px; }
        .tab-btn { padding: 12px 25px; cursor: pointer; font-weight: 600; color: #64748B; border-bottom: 2px solid transparent; margin-bottom: -2px; }
        .tab-btn.active { color: var(--primary-blue); border-bottom-color: var(--primary-blue); }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        .search-box { position: relative; margin-bottom: 20px; width: 100%; max-width: 400px; }
        .search-box i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #94A3B8; }
        .search-box input { width: 100%; padding: 12px 15px 12px 40px; border: 1px solid #CBD5E1; border-radius: 20px; background: #F8FAFC; }
        
        /* Table */
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid var(--border); }
        th { background: #F8FAFC; color: #475569; font-weight: 600; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; }
        td { font-size: 0.95rem; }
        .badge { display: inline-block; white-space: nowrap; text-align: center; padding: 5px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .badge-active { background: #DCFCE7; color: #15803D; }
        
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="logo.png" alt="Logo"> ITC Admin
        </div>
        <ul class="sidebar-menu">
            <li><a href="?page=dashboard" class="<?php echo $page == 'dashboard' ? 'active' : ''; ?>"><i class="fas fa-home"></i> Bảng điều khiển</a></li>
            <li><a href="?page=cycles" class="<?php echo $page == 'cycles' ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i> Quản lý Đợt Đánh Giá</a></li>
            <li><a href="?page=criteria" class="<?php echo $page == 'criteria' ? 'active' : ''; ?>"><i class="fas fa-tasks"></i> Cấu hình Tiêu Chí</a></li>
            <li><a href="?page=accounts" class="<?php echo $page == 'accounts' ? 'active' : ''; ?>"><i class="fas fa-users-cog"></i> Quản lý Tài Khoản</a></li>
            <li><a href="?page=permissions" class="<?php echo $page == 'permissions' ? 'active' : ''; ?>"><i class="fas fa-user-lock"></i> Phân Quyền</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-wrapper">
        <div class="topbar">
            <div><i class="fas fa-bars" style="font-size: 1.2rem; color: #64748B; cursor: pointer;"></i></div>
            <div class="topbar-user">
                <i class="fas fa-user-shield" style="color: var(--primary-blue);"></i> <?php echo htmlspecialchars($_SESSION['ho_ten']); ?>
                <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
            </div>
        </div>
        
        <div class="content">
            <?php if($msg): ?>
                <div class="alert alert-<?php echo $msg_type; ?>">
                    <i class="fas <?php echo $msg_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <?php if ($page == 'dashboard'): ?>
                <h1 class="page-title">Bảng Điều Khiển Quản Trị</h1>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-user-graduate"></i></div>
                        <div class="stat-info">
                            <h4>TỔNG SINH VIÊN</h4>
                            <h2><?php echo $total_sv; ?></h2>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon yellow"><i class="fas fa-chalkboard-teacher"></i></div>
                        <div class="stat-info">
                            <h4>CỐ VẤN HỌC TẬP</h4>
                            <h2><?php echo $total_cvht; ?></h2>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green"><i class="fas fa-building"></i></div>
                        <div class="stat-info">
                            <h4>KHOA / CTSV</h4>
                            <h2><?php echo $total_khoa; ?></h2>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-bell"></i> Trạng thái Đợt đánh giá hiện tại</h3>
                    </div>
                    <div class="card-body">
                        <?php if($current_dot): ?>
                            <div style="background: #F0FDF4; border: 1px solid #BBF7D0; padding: 20px; border-radius: 8px;">
                                <h2 style="color: #15803D; margin-bottom: 10px;"><i class="fas fa-door-open"></i> Đang mở: <?php echo htmlspecialchars($current_dot['ten_dot']); ?></h2>
                                <p style="color: #065F46;">Học kỳ: <strong><?php echo $current_dot['hoc_ky']; ?></strong> - Năm học: <strong><?php echo htmlspecialchars($current_dot['nam_hoc']); ?></strong></p>
                            </div>
                        <?php else: ?>
                            <div style="background: #FEF2F2; border: 1px solid #FECACA; padding: 20px; border-radius: 8px;">
                                <h2 style="color: #B91C1C; margin-bottom: 10px;"><i class="fas fa-door-closed"></i> Hiện không có đợt đánh giá nào được mở!</h2>
                                <p style="color: #991B1B;">Sinh viên không thể nộp phiếu. Hãy vào mục <a href="?page=cycles" style="font-weight: bold; color: var(--primary-blue);">Quản lý Đợt Đánh Giá</a> để mở đợt mới.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            <?php elseif ($page == 'cycles'): ?>
                <h1 class="page-title">Quản lý Đợt Đánh Giá</h1>
                
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-plus-circle"></i> Mở đợt đánh giá mới (Theo mẫu Excel)</h3>
                    </div>
                    <div class="card-body">
                        <div style="background: #EFF6FF; border: 1px dashed var(--primary-blue); padding: 15px; border-radius: 8px; margin-bottom: 25px; font-size: 0.9rem; color: #1E3A8A;">
                            <i class="fas fa-info-circle"></i> <strong>Lưu ý:</strong> Khi mở một đợt đánh giá mới, hệ thống sẽ tự động sao chép các <strong>Tiêu chí đánh giá</strong> dựa theo Mẫu Excel quy chuẩn của trường (VD: Mẫu ĐGRLHK1_NH26_27.xls) để áp dụng cho sinh viên. Đợt cũ sẽ tự động đóng lại.
                        </div>
                        
                        <form method="POST" action="?page=cycles">
                            <input type="hidden" name="action" value="add_cycle">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Tên đợt đánh giá</label>
                                    <input type="text" name="ten_dot" class="form-control" placeholder="VD: Đánh giá ĐRL HK1 2026-2027" required>
                                </div>
                                <div class="form-group">
                                    <label>Học kỳ</label>
                                    <select name="hoc_ky" class="form-control" required>
                                        <option value="1">Học kỳ 1</option>
                                        <option value="2">Học kỳ 2</option>
                                        <option value="3">Học kỳ hè</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Năm học</label>
                                    <input type="text" name="nam_hoc" class="form-control" placeholder="VD: 2026-2027" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-play"></i> Mở Đợt Đánh Giá Ngay</button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-history"></i> Lịch sử các đợt đánh giá</h3>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tên Đợt</th>
                                    <th>Học Kỳ</th>
                                    <th>Năm Học</th>
                                    <th>Trạng Thái</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $cycles = $conn->query("SELECT * FROM dot_danh_gia ORDER BY id DESC");
                                while($c = $cycles->fetch_assoc()):
                                ?>
                                <tr>
                                    <td><?php echo $c['id']; ?></td>
                                    <td style="font-weight: 600;"><?php echo htmlspecialchars($c['ten_dot']); ?></td>
                                    <td>HK <?php echo $c['hoc_ky']; ?></td>
                                    <td><?php echo htmlspecialchars($c['nam_hoc']); ?></td>
                                    <td>
                                        <?php if($c['trang_thai'] == 'dang_mo'): ?>
                                            <span class="badge" style="background: #DCFCE7; color: #15803D;">Đang mở</span>
                                        <?php else: ?>
                                            <span class="badge" style="background: #F1F5F9; color: #64748B;">Đã đóng</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="#" style="color: var(--primary-blue); margin-right: 10px;"><i class="fas fa-edit"></i></a>
                                        <a href="#" style="color: #EF4444;"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php elseif ($page == 'accounts'): ?>
                <h1 class="page-title">Quản lý Tài Khoản & Phân Quyền</h1>
                
                <!-- Khu vực phân chia Tab -->
                <div class="tabs">
                    <div class="tab-btn active" onclick="switchTab('sv')"><i class="fas fa-user-graduate"></i> Sinh Viên</div>
                    <div class="tab-btn" onclick="switchTab('cvht')"><i class="fas fa-chalkboard-teacher"></i> Cố Vấn Học Tập</div>
                    <div class="tab-btn" onclick="switchTab('khoa')"><i class="fas fa-building"></i> Khoa / CTSV</div>
                    <div class="tab-btn" onclick="switchTab('admin')"><i class="fas fa-user-shield"></i> Admin</div>
                </div>

                <div class="card">
                    <div class="card-header" style="background: white; border-bottom: none; padding-bottom: 0;">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="searchInput" onkeyup="searchTable()" placeholder="Nhập tên đăng nhập hoặc họ tên để tìm kiếm...">
                        </div>
                        <button class="btn btn-primary" onclick="document.getElementById('addAccountForm').style.display='block';"><i class="fas fa-plus"></i> Tạo tài khoản mới</button>
                    </div>
                    
                    <!-- Form thêm tài khoản ẩn -->
                    <div id="addAccountForm" style="display: none; padding: 0 25px 25px; border-bottom: 1px solid var(--border); margin-bottom: 20px;">
                        <h4 style="margin-bottom: 15px; color: var(--primary-blue);"><i class="fas fa-user-plus"></i> Thông tin tài khoản mới</h4>
                        <form method="POST" action="?page=accounts">
                            <input type="hidden" name="action" value="add_account">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Loại tài khoản (Vai trò)</label>
                                    <select name="vai_tro" class="form-control" required>
                                        <option value="sinh_vien">Sinh viên</option>
                                        <option value="cvht">Cố vấn học tập</option>
                                        <option value="khoa">Khoa / CTSV</option>
                                        <option value="admin">Quản trị viên (Admin)</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Tên đăng nhập (Username/Mã SV)</label>
                                    <input type="text" name="username" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Họ và Tên</label>
                                    <input type="text" name="ho_ten" class="form-control" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Xác nhận tạo</button>
                            <button type="button" class="btn" style="background: #F1F5F9;" onclick="document.getElementById('addAccountForm').style.display='none';">Hủy</button>
                            <p style="margin-top: 10px; font-size: 0.85rem; color: #64748B;">* Mật khẩu mặc định sẽ là <strong>123456</strong>.</p>
                        </form>
                    </div>
                    
                    <div class="card-body" style="padding: 0;">
                        <table id="accountsTable">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Họ và Tên</th>
                                    <th>Vai trò</th>
                                    <th>Trạng Thái</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $acc_query = $conn->query("SELECT * FROM tai_khoan ORDER BY vai_tro, id DESC");
                                while($a = $acc_query->fetch_assoc()):
                                ?>
                                <tr class="account-row role-<?php echo $a['vai_tro']; ?>">
                                    <td style="font-weight: 600;"><?php echo htmlspecialchars($a['username']); ?></td>
                                    <td class="searchable-name"><?php echo htmlspecialchars($a['ho_ten']); ?></td>
                                    <td>
                                        <?php 
                                            if($a['vai_tro'] == 'sinh_vien') echo 'Sinh Viên';
                                            elseif($a['vai_tro'] == 'cvht') echo 'CVHT';
                                            elseif($a['vai_tro'] == 'khoa') echo 'Khoa/CTSV';
                                            else echo 'Admin';
                                        ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-active">Hoạt động</span>
                                    </td>
                                    <td>
                                        <a href="#" style="color: #F59E0B; margin-right: 10px;" title="Reset Password"><i class="fas fa-key"></i></a>
                                        <a href="#" style="color: #EF4444;" title="Khóa TK"><i class="fas fa-ban"></i></a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <script>
                    // Logic lọc danh sách theo Tab
                    function switchTab(role) {
                        // Đổi màu tab
                        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
                        event.currentTarget.classList.add('active');
                        
                        // Ẩn/Hiện dòng
                        document.querySelectorAll('.account-row').forEach(row => {
                            if (row.classList.contains('role-' + role)) {
                                row.style.display = '';
                            } else {
                                row.style.display = 'none';
                            }
                        });
                        
                        // Reset search
                        document.getElementById('searchInput').value = '';
                    }
                    
                    // Kích hoạt tab Sinh viên mặc định
                    document.addEventListener('DOMContentLoaded', () => {
                        switchTab('sinh_vien');
                    });

                    // Logic tìm kiếm realtime
                    function searchTable() {
                        let input = document.getElementById('searchInput').value.toLowerCase();
                        let rows = document.querySelectorAll('.account-row');
                        
                        // Khôi phục tất cả row của tab đang active để search
                        let activeRole = '';
                        document.querySelectorAll('.tab-btn').forEach(btn => {
                            if(btn.classList.contains('active')) {
                                if(btn.innerText.includes('Sinh Viên')) activeRole = 'sinh_vien';
                                else if(btn.innerText.includes('Cố Vấn')) activeRole = 'cvht';
                                else if(btn.innerText.includes('Khoa')) activeRole = 'khoa';
                                else activeRole = 'admin';
                            }
                        });

                        rows.forEach(row => {
                            if (row.classList.contains('role-' + activeRole)) {
                                let username = row.cells[0].innerText.toLowerCase();
                                let fullname = row.cells[1].innerText.toLowerCase();
                                if (username.includes(input) || fullname.includes(input)) {
                                    row.style.display = '';
                                } else {
                                    row.style.display = 'none';
                                }
                            }
                        });
                    }
                </script>

            <?php elseif ($page == 'criteria'): ?>
                <h1 class="page-title">Cấu Hình Bộ Tiêu Chí (Dựa theo Mẫu ĐGRLHK1_NH26_27.xls)</h1>
                <div class="card">
                    <div class="card-body" style="text-align: center; padding: 40px;">
                        <i class="fas fa-file-excel" style="font-size: 4rem; color: #107C41; margin-bottom: 20px;"></i>
                        <h2 style="color: var(--primary-blue); margin-bottom: 10px;">Chức năng Import/Export Excel</h2>
                        <p style="color: #64748B; max-width: 600px; margin: 0 auto;">
                            Tính năng đồng bộ dữ liệu Tiêu chí đánh giá trực tiếp từ file Excel (.xls, .xlsx) đang được phát triển. Tạm thời, hệ thống sử dụng bộ tiêu chí mặc định đã được thiết lập trong cơ sở dữ liệu.
                        </p>
                    </div>
                </div>
            <?php elseif ($page == 'permissions'): ?>
                <h1 class="page-title">Bảng Phân Quyền Các Tính Năng</h1>
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-check-square"></i> Chọn tính năng cho từng người dùng (Tự động lưu)</h3>
                    </div>
                    <div class="card-body" style="padding: 0; overflow-x: auto;">
                        <table style="min-width: 800px;">
                            <thead>
                                <tr>
                                    <th>Người dùng</th>
                                    <th>Vai trò</th>
                                    <th style="text-align:center;">QL Đợt đánh giá</th>
                                    <th style="text-align:center;">Cấu hình Tiêu chí</th>
                                    <th style="text-align:center;">QL Tài khoản</th>
                                    <th style="text-align:center;">Chấm điểm CVHT</th>
                                    <th style="text-align:center;">Chấm điểm Khoa</th>
                                    <th style="text-align:center;">Xem Báo cáo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $users = $conn->query("SELECT id, username, ho_ten, vai_tro, permissions FROM tai_khoan WHERE vai_tro != 'sinh_vien' ORDER BY vai_tro, id DESC");
                                $features = [
                                    'manage_cycles' => 'QL Đợt',
                                    'manage_criteria' => 'QL Tiêu chí',
                                    'manage_accounts' => 'QL TK',
                                    'grade_advisor' => 'Chấm CVHT',
                                    'grade_department' => 'Chấm Khoa',
                                    'view_reports' => 'Báo cáo'
                                ];
                                while($u = $users->fetch_assoc()):
                                    $user_perms = $u['permissions'] ? explode(',', $u['permissions']) : [];
                                ?>
                                <tr>
                                    <td>
                                        <strong style="color: var(--primary-blue);"><?php echo htmlspecialchars($u['username']); ?></strong><br>
                                        <small style="color: #64748B;"><?php echo htmlspecialchars($u['ho_ten']); ?></small>
                                    </td>
                                    <td>
                                        <?php 
                                            if($u['vai_tro'] == 'admin') echo '<span class="badge" style="background:#FEE2E2;color:#B91C1C;">Admin</span>';
                                            elseif($u['vai_tro'] == 'khoa') echo '<span class="badge" style="background:#FEF9C3;color:#B45309;">Khoa/CTSV</span>';
                                            elseif($u['vai_tro'] == 'cvht') echo '<span class="badge" style="background:#DBEAFE;color:#1D4ED8;">CVHT</span>';
                                        ?>
                                    </td>
                                    <?php foreach($features as $f_key => $f_label): ?>
                                    <td style="text-align:center;">
                                        <input type="checkbox" 
                                               class="perm-checkbox" 
                                               data-uid="<?php echo $u['id']; ?>" 
                                               data-perm="<?php echo $f_key; ?>"
                                               <?php echo in_array($f_key, $user_perms) ? 'checked' : ''; ?>
                                               style="width: 18px; height: 18px; cursor: pointer;">
                                    </td>
                                    <?php endforeach; ?>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <script>
                document.querySelectorAll('.perm-checkbox').forEach(cb => {
                    cb.addEventListener('change', function() {
                        const uid = this.getAttribute('data-uid');
                        const perm = this.getAttribute('data-perm');
                        const is_checked = this.checked;
                        
                        const formData = new FormData();
                        formData.append('action', 'update_permission_ajax');
                        formData.append('user_id', uid);
                        formData.append('permission', perm);
                        formData.append('is_checked', is_checked);
                        
                        fetch('?page=permissions', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if(data.success) {
                                // Tự động lưu thành công
                            }
                        })
                        .catch(err => console.error('Error updating permission', err));
                    });
                });
                </script>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
