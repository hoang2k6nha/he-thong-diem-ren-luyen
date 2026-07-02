<?php
require_once 'config.php';

$mssv = '';
$student_info = null;
$diem_info = [];
$error = '';

if (isset($_GET['mssv'])) {
    $mssv = $conn->real_escape_string(trim($_GET['mssv']));
    
    $sql = "SELECT t.*, l.ten_lop 
            FROM tai_khoan t 
            LEFT JOIN lop_hoc l ON t.lop_id = l.id 
            WHERE t.username = '$mssv' AND t.vai_tro = 'sinh_vien'";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $student_info = $result->fetch_assoc();
        
        $sql_diem = "SELECT p.*, d.ten_dot, d.nam_hoc, d.hoc_ky
                     FROM phieu_danh_gia p
                     JOIN dot_danh_gia d ON p.dot_id = d.id
                     WHERE p.sinh_vien_id = {$student_info['id']}
                     ORDER BY d.id DESC LIMIT 1";
        $res_diem = $conn->query($sql_diem);
        if ($res_diem && $res_diem->num_rows > 0) {
            while ($row = $res_diem->fetch_assoc()) {
                $diem_info[] = $row;
            }
        }
    } else {
        $error = "Không tìm thấy dữ liệu sinh viên với mã số này.";
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tra cứu Điểm rèn luyện - ITC</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #004DCC;
            --primary-light: #3377FF;
            --secondary: #FFCC00;
            --secondary-light: #FFD633;
            --dark: #1E293B;
            --gray: #64748B;
            --gray-light: #F1F5F9;
            --bg: #F8FAFC;
            --white: #FFFFFF;
            --font-heading: 'Outfit', sans-serif;
            --font-body: 'Plus Jakarta Sans', sans-serif;
            --shadow-sm: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.05);
            --radius-md: 12px;
            --radius-lg: 24px;
            --radius-full: 9999px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: var(--font-body); background-color: var(--bg); color: var(--dark); line-height: 1.6; min-height: 100vh; display: flex; flex-direction: column; position: relative; overflow-x: hidden; }
        
        /* Background Blobs */
        .blob { position: absolute; filter: blur(80px); z-index: -1; opacity: 0.5; animation: moveBlob 15s infinite alternate ease-in-out; }
        .blob-1 { top: -10%; left: -5%; width: 50vw; height: 50vw; background: rgba(0, 77, 204, 0.15); border-radius: 40% 60% 70% 30% / 40% 50% 60% 50%; }
        .blob-2 { bottom: -10%; right: -5%; width: 40vw; height: 40vw; background: rgba(255, 204, 0, 0.15); border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%; animation-delay: -5s; }
        @keyframes moveBlob { 0% { transform: translate(0, 0) rotate(0deg); } 100% { transform: translate(50px, 50px) rotate(20deg); } }

        /* Navbar */
        .navbar { padding: 1.25rem 5%; display: flex; justify-content: space-between; align-items: center; background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(16px); border-bottom: 1px solid rgba(255, 255, 255, 0.3); z-index: 1000; box-shadow: var(--shadow-sm); }
        .navbar-brand { display: flex; align-items: center; gap: 12px; font-family: var(--font-heading); font-size: 1.5rem; font-weight: 800; color: var(--primary); text-decoration: none; }
        .navbar-brand img { height: 40px; }
        .btn-back { background: var(--white); color: var(--dark); padding: 0.6rem 1.2rem; border-radius: var(--radius-full); font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; border: 1px solid #E2E8F0; transition: var(--transition); box-shadow: var(--shadow-sm); }
        .btn-back:hover { background: var(--gray-light); transform: translateY(-2px); }

        /* Main Container */
        .main-content { flex: 1; padding: 4rem 5%; display: flex; justify-content: center; }
        .glass-container { width: 100%; max-width: 900px; background: rgba(255, 255, 255, 0.75); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.5); border-radius: var(--radius-lg); padding: 3rem; box-shadow: var(--shadow-lg); }
        
        /* Search Box */
        .search-header { text-align: center; margin-bottom: 3rem; }
        .search-header h1 { font-family: var(--font-heading); font-size: 2.5rem; color: var(--dark); margin-bottom: 0.5rem; }
        .search-header p { color: var(--gray); font-size: 1.1rem; }

        .search-form { display: flex; justify-content: center; gap: 1rem; margin-bottom: 3rem; }
        .search-input-group { position: relative; width: 100%; max-width: 400px; }
        .search-input-group i { position: absolute; left: 1.2rem; top: 50%; transform: translateY(-50%); color: var(--gray); font-size: 1.2rem; }
        .search-input { width: 100%; padding: 1rem 1rem 1rem 3rem; border: 2px solid #E2E8F0; border-radius: var(--radius-full); font-family: var(--font-body); font-size: 1rem; color: var(--dark); background: var(--white); transition: var(--transition); outline: none; }
        .search-input:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(0, 77, 204, 0.1); }
        
        .btn-search { background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%); color: var(--white); border: none; padding: 1rem 2rem; border-radius: var(--radius-full); font-family: var(--font-heading); font-weight: 600; font-size: 1.1rem; cursor: pointer; transition: var(--transition); box-shadow: 0 8px 20px rgba(0, 77, 204, 0.25); display: inline-flex; align-items: center; gap: 8px; }
        .btn-search:hover { transform: translateY(-3px); box-shadow: 0 12px 25px rgba(0, 77, 204, 0.35); }

        /* Error */
        .error-msg { background: #FEF2F2; color: #EF4444; padding: 1rem 1.5rem; border-radius: var(--radius-md); font-weight: 500; display: flex; align-items: center; gap: 10px; border: 1px solid #FECACA; max-width: 500px; margin: 0 auto 2rem; }

        /* Student Profile Card */
        .profile-card { background: var(--white); border-radius: var(--radius-md); padding: 2rem; margin-bottom: 2rem; box-shadow: var(--shadow-sm); border: 1px solid #E2E8F0; }
        .profile-header { display: flex; align-items: center; gap: 1.5rem; margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 1px solid #E2E8F0; }
        .profile-avatar { width: 70px; height: 70px; background: linear-gradient(135deg, var(--secondary) 0%, #D97706 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--white); font-size: 2rem; }
        .profile-title h2 { font-family: var(--font-heading); font-size: 1.8rem; color: var(--dark); margin: 0; }
        .profile-title .mssv-badge { display: inline-block; background: rgba(0, 77, 204, 0.1); color: var(--primary); padding: 0.2rem 0.8rem; border-radius: var(--radius-full); font-weight: 600; font-size: 0.9rem; margin-top: 0.5rem; }

        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; }
        .info-item { display: flex; align-items: flex-start; gap: 12px; }
        .info-icon { width: 36px; height: 36px; background: var(--gray-light); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 1.1rem; flex-shrink: 0; }
        .info-content label { display: block; font-size: 0.85rem; color: var(--gray); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
        .info-content span { display: block; font-size: 1.05rem; color: var(--dark); font-weight: 500; }

        /* Points Table */
        .section-title { font-family: var(--font-heading); font-size: 1.5rem; color: var(--dark); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px; }
        .table-responsive { overflow-x: auto; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); border: 1px solid #E2E8F0; background: var(--white); }
        .modern-table { width: 100%; border-collapse: collapse; }
        .modern-table th { background: #F8FAFC; padding: 1.25rem 1rem; text-align: left; font-size: 0.85rem; font-weight: 700; color: var(--gray); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #E2E8F0; }
        .modern-table td { padding: 1.25rem 1rem; border-bottom: 1px solid #E2E8F0; color: var(--dark); font-weight: 500; }
        .modern-table tr:last-child td { border-bottom: none; }
        .modern-table tbody tr:hover { background: #F8FAFC; }
        
        .score-badge { display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; background: rgba(0, 77, 204, 0.1); color: var(--primary); border-radius: 50%; font-weight: 700; }
        .status-badge { padding: 0.4rem 0.8rem; border-radius: var(--radius-full); font-size: 0.85rem; font-weight: 600; }
        .status-chua-nop { background: #FEF2F2; color: #EF4444; }
        .status-cho-duyet { background: #FEF9C3; color: #CA8A04; }
        .status-da-duyet { background: #F0FDF4; color: #16A34A; }

        .empty-state { padding: 3rem; text-align: center; color: var(--gray); }
        .empty-state i { font-size: 3rem; color: #CBD5E1; margin-bottom: 1rem; }

        /* Footer */
        footer { background: var(--white); padding: 2rem 5%; text-align: center; color: var(--gray); border-top: 1px solid #E2E8F0; }

        @media (max-width: 768px) {
            .glass-container { padding: 2rem 1.5rem; }
            .search-form { flex-direction: column; }
            .search-input-group { max-width: 100%; }
            .info-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    <nav class="navbar">
        <a href="index.php" class="navbar-brand">
            <img src="logo.png" alt="ITC Logo">
            ITC DRL
        </a>
        <a href="index.php" class="btn-back"><i class="fas fa-arrow-left"></i> Về trang chủ</a>
    </nav>

    <div class="main-content">
        <div class="glass-container">
            <div class="search-header">
                <h1>Tra Cứu Điểm Rèn Luyện</h1>
                <p>Nhập mã số sinh viên của bạn để xem kết quả đánh giá</p>
            </div>

            <form method="GET" action="tra_cuu.php" class="search-form">
                <div class="search-input-group">
                    <i class="fas fa-search"></i>
                    <input type="text" name="mssv" class="search-input" placeholder="Nhập Mã số sinh viên..." value="<?php echo htmlspecialchars($mssv); ?>" required>
                </div>
                <button type="submit" class="btn-search">Tra cứu</button>
            </form>

            <?php if ($error): ?>
                <div class="error-msg">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <?php if ($student_info): ?>
                <div class="profile-card">
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div class="profile-title">
                            <h2><?php echo htmlspecialchars($student_info['ho_ten']); ?></h2>
                            <div class="mssv-badge"><i class="fas fa-id-card"></i> MSSV: <?php echo htmlspecialchars($student_info['username']); ?></div>
                        </div>
                    </div>
                    
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-icon"><i class="fas fa-calendar-alt"></i></div>
                            <div class="info-content">
                                <label>Ngày sinh</label>
                                <span><?php echo !empty($student_info['ngay_sinh']) ? date('d/m/Y', strtotime($student_info['ngay_sinh'])) : 'Đang cập nhật'; ?></span>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon"><i class="fas fa-map-marker-alt"></i></div>
                            <div class="info-content">
                                <label>Nơi sinh</label>
                                <span><?php echo !empty($student_info['noi_sinh']) ? htmlspecialchars($student_info['noi_sinh']) : 'Đang cập nhật'; ?></span>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon"><i class="fas fa-graduation-cap"></i></div>
                            <div class="info-content">
                                <label>Lớp học</label>
                                <span><?php echo htmlspecialchars($student_info['ten_lop'] ?? 'Chưa phân lớp'); ?></span>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon"><i class="fas fa-laptop-code"></i></div>
                            <div class="info-content">
                                <label>Ngành đào tạo</label>
                                <span><?php echo !empty($student_info['nganh_dao_tao']) ? htmlspecialchars($student_info['nganh_dao_tao']) : 'Đang cập nhật'; ?></span>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon"><i class="fas fa-certificate"></i></div>
                            <div class="info-content">
                                <label>Chuyên ngành</label>
                                <span><?php echo !empty($student_info['chuyen_nganh']) ? htmlspecialchars($student_info['chuyen_nganh']) : 'Đang cập nhật'; ?></span>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon"><i class="fas fa-layer-group"></i></div>
                            <div class="info-content">
                                <label>Bậc đào tạo</label>
                                <span><?php echo !empty($student_info['bac_dao_tao']) ? htmlspecialchars($student_info['bac_dao_tao']) : 'Cao đẳng'; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <h3 class="section-title"><i class="fas fa-star"></i> Điểm rèn luyện kỳ mới nhất</h3>
                
                <div class="table-responsive">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Năm học / Học kỳ</th>
                                <th>Đợt đánh giá</th>
                                <th style="text-align: center;">Điểm SV</th>
                                <th style="text-align: center;">Điểm Tổng</th>
                                <th>Xếp loại</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($diem_info)): ?>
                                <?php foreach ($diem_info as $diem): ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight: 700; color: var(--primary);"><?php echo htmlspecialchars($diem['nam_hoc']); ?></div>
                                            <div style="font-size: 0.85rem; color: var(--gray);">Học kỳ <?php echo $diem['hoc_ky']; ?></div>
                                        </td>
                                        <td><?php echo htmlspecialchars($diem['ten_dot']); ?></td>
                                        <td align="center"><span class="score-badge" style="background: rgba(100,116,139,0.1); color: var(--gray);"><?php echo $diem['tong_diem_sv']; ?></span></td>
                                        <td align="center"><span class="score-badge"><?php echo $diem['tong_diem_khoa']; ?></span></td>
                                        <td>
                                            <strong style="color: var(--primary);"><?php echo $diem['xep_loai'] ? htmlspecialchars($diem['xep_loai']) : 'Chưa có'; ?></strong>
                                        </td>
                                        <td>
                                            <?php 
                                                $statusClass = '';
                                                $statusText = '';
                                                switch($diem['trang_thai']) {
                                                    case 'chua_nop':
                                                        $statusClass = 'status-chua-nop';
                                                        $statusText = 'Chưa nộp';
                                                        break;
                                                    case 'cho_cvht_duyet':
                                                    case 'cho_khoa_duyet':
                                                        $statusClass = 'status-cho-duyet';
                                                        $statusText = 'Đang xét duyệt';
                                                        break;
                                                    case 'da_duyet':
                                                        $statusClass = 'status-da-duyet';
                                                        $statusText = 'Đã duyệt';
                                                        break;
                                                }
                                            ?>
                                            <span class="status-badge <?php echo $statusClass; ?>">
                                                <?php echo $statusText; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6">
                                        <div class="empty-state">
                                            <i class="fas fa-folder-open"></i>
                                            <p>Chưa có dữ liệu điểm rèn luyện trong hệ thống.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        &copy; <?php echo date('Y'); ?> Hệ thống Quản lý Điểm Rèn Luyện. Phát triển bởi Ban CNTT ITC.
    </footer>
</body>
</html>
