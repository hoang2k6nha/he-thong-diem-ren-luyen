<?php
// index.php - Trang chủ Landing Page (Premium Redesign)
require_once 'config.php';

// Nếu người dùng đã đăng nhập thì tự động chuyển thẳng vào dashboard
if (isset($_SESSION['user_id'])) {
    redirect('dashboard.php');
}

$error = '';
$show_modal = false;

// Xử lý đăng nhập ngay trên trang chủ
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login_submit'])) {
    $username = $conn->real_escape_string($_POST['username']);
    $password = md5($_POST['password']); 

    $sql = "SELECT * FROM tai_khoan WHERE username = '$username'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if ($user['password'] === $password) {
            if ($user['trang_thai'] == 1) {
                // Đăng nhập thành công
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['vai_tro'];
                $_SESSION['ho_ten'] = $user['ho_ten'];
                $_SESSION['khoa_id'] = $user['khoa_id'];
                $_SESSION['lop_id'] = $user['lop_id'];
                
                redirect('dashboard.php');
            } else {
                $error = 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ phòng Công tác sinh viên!';
                $show_modal = true;
            }
        } else {
            $error = 'Tài khoản hoặc mật khẩu không chính xác!';
            $show_modal = true;
        }
    } else {
        $error = 'Tài khoản hoặc mật khẩu không chính xác!';
        $show_modal = true;
    }
}

$support_msg = '';
$support_type = '';
$show_support_modal = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'submit_support') {
    $ho_ten = $conn->real_escape_string($_POST['ho_ten']);
    $mssv = $conn->real_escape_string($_POST['mssv']);
    $nguoi_nhan = 'khoa'; 
    $tieu_de = $conn->real_escape_string($_POST['tieu_de']);
    $noi_dung = $conn->real_escape_string($_POST['noi_dung']);
    
    $sql = "INSERT INTO khieu_nai (sinh_vien_id, ho_ten_khach, mssv_khach, nguoi_nhan_role, tieu_de, noi_dung, trang_thai, ngay_tao) 
            VALUES (NULL, '$ho_ten', '$mssv', '$nguoi_nhan', '$tieu_de', '$noi_dung', 'cho_phan_hoi', NOW())";
            
    if ($conn->query($sql)) {
        $support_msg = "Yêu cầu hỗ trợ của bạn đã được gửi thành công! Khoa/CTSV sẽ phản hồi qua thông tin liên hệ của bạn.";
        $support_type = "success";
    } else {
        $support_msg = "Đã xảy ra lỗi, vui lòng thử lại sau.";
        $support_type = "error";
    }
    $show_support_modal = true;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ thống Điểm Rèn luyện - ITC</title>
    <link rel="icon" type="image/png" href="logo.png">
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
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.02);
            --radius-md: 12px;
            --radius-lg: 24px;
            --radius-full: 9999px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        html { scroll-behavior: smooth; }

        body {
            font-family: var(--font-body);
            background-color: var(--bg);
            color: var(--dark);
            line-height: 1.6;
            overflow-x: hidden;
        }

        h1, h2, h3, h4, h5, h6 { font-family: var(--font-heading); font-weight: 700; line-height: 1.2; }

        /* Blob Backgrounds */
        .blob {
            position: absolute;
            filter: blur(80px);
            z-index: -1;
            opacity: 0.5;
            animation: moveBlob 15s infinite alternate ease-in-out;
        }
        .blob-1 { top: -10%; left: -5%; width: 50vw; height: 50vw; background: rgba(0, 77, 204, 0.15); border-radius: 40% 60% 70% 30% / 40% 50% 60% 50%; }
        .blob-2 { bottom: 10%; right: -5%; width: 40vw; height: 40vw; background: rgba(255, 204, 0, 0.15); border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%; animation-delay: -5s; }

        @keyframes moveBlob {
            0% { transform: translate(0, 0) rotate(0deg); }
            100% { transform: translate(50px, 50px) rotate(20deg); }
        }

        /* Navbar */
        .navbar {
            position: fixed;
            top: 0; left: 0; right: 0;
            padding: 1.25rem 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.3);
            z-index: 1000;
            transition: var(--transition);
        }
        .navbar.scrolled { padding: 1rem 5%; box-shadow: var(--shadow-sm); background: rgba(255, 255, 255, 0.95); }
        
        .navbar-brand {
            display: flex; align-items: center; gap: 12px;
            font-family: var(--font-heading); font-size: 1.5rem; font-weight: 800;
            color: var(--primary); text-decoration: none; letter-spacing: -0.5px;
        }
        .navbar-brand img { height: 40px; }
        
        .nav-links { display: flex; align-items: center; gap: 2rem; }
        .nav-links a {
            text-decoration: none; color: var(--dark); font-weight: 500;
            font-size: 1rem; transition: var(--transition); position: relative;
        }
        .nav-links a:not(.btn-login)::after {
            content: ''; position: absolute; bottom: -4px; left: 0;
            width: 0; height: 2px; background: var(--primary); transition: var(--transition);
        }
        .nav-links a:not(.btn-login):hover::after { width: 100%; }
        .nav-links a:hover { color: var(--primary); }

        .btn-login {
            background: var(--dark); color: var(--white) !important;
            padding: 0.75rem 1.5rem; border-radius: var(--radius-full);
            font-weight: 600; display: inline-flex; align-items: center; gap: 8px;
            transition: var(--transition);
            box-shadow: 0 4px 14px 0 rgba(15, 23, 42, 0.2);
        }
        .btn-login:hover {
            background: var(--secondary); color: var(--primary) !important; transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 204, 0, 0.3);
        }

        /* Hero Section */
        .hero {
            position: relative; min-height: 100vh;
            display: flex; align-items: center; justify-content: space-between;
            padding: 8rem 5% 4rem; overflow: hidden;
            background: url('bg_itc.jpg') top center/cover no-repeat;
        }
        .hero::before {
            content: ''; position: absolute; inset: 0;
            background: linear-gradient(135deg, rgba(248, 250, 252, 0.4) 0%, rgba(255, 255, 255, 0.6) 100%);
            backdrop-filter: blur(3px);
            z-index: 1;
        }
        
        .hero-content { flex: 1; max-width: 600px; position: relative; z-index: 2; }
        
        .badge {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(0, 77, 204, 0.1); color: var(--primary);
            padding: 0.5rem 1rem; border-radius: var(--radius-full);
            font-size: 0.875rem; font-weight: 600; font-family: var(--font-heading);
            margin-bottom: 1.5rem;
            border: 1px solid rgba(0, 77, 204, 0.2);
        }
        .badge i { color: var(--secondary); }

        .hero h1 {
            font-size: 4rem; color: var(--dark);
            margin-bottom: 1.5rem; letter-spacing: -1.5px;
            background: linear-gradient(135deg, var(--dark) 0%, var(--primary) 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .hero p { font-size: 1.25rem; color: var(--gray); margin-bottom: 2.5rem; max-width: 500px; }

        .hero-btns { display: flex; gap: 1rem; }
        .btn-primary {
            background: var(--primary); color: var(--white);
            padding: 1rem 2rem; border-radius: var(--radius-full);
            font-family: var(--font-heading); font-weight: 600; font-size: 1.1rem;
            text-decoration: none; display: inline-flex; align-items: center; gap: 10px;
            transition: var(--transition); box-shadow: 0 8px 20px rgba(0, 77, 204, 0.25);
            cursor: pointer; border: none;
        }
        .btn-primary:hover { background: var(--secondary); color: var(--primary); transform: translateY(-3px); box-shadow: 0 12px 25px rgba(255, 204, 0, 0.35); }
        
        .btn-secondary {
            background: var(--white); color: var(--dark);
            padding: 1rem 2rem; border-radius: var(--radius-full);
            font-family: var(--font-heading); font-weight: 600; font-size: 1.1rem;
            text-decoration: none; display: inline-flex; align-items: center; gap: 10px;
            transition: var(--transition); border: 1px solid #E2E8F0;
            box-shadow: var(--shadow-sm); cursor: pointer;
        }
        .btn-secondary:hover { background: var(--gray-light); transform: translateY(-3px); }

        .hero-visual {
            flex: 1; display: flex; justify-content: center; position: relative; z-index: 2;
            perspective: 1000px;
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: var(--radius-lg);
            padding: 2rem;
            box-shadow: var(--shadow-lg);
            width: 100%; max-width: 500px;
            transform: rotateY(-15deg) rotateX(5deg);
            transition: var(--transition);
            transform-style: preserve-3d;
        }
        .hero-visual:hover .glass-card { transform: rotateY(-5deg) rotateX(2deg) translateY(-10px); }

        .glass-stat {
            display: flex; align-items: center; gap: 1rem;
            background: var(--white); padding: 1rem; border-radius: var(--radius-md);
            margin-bottom: 1rem; box-shadow: var(--shadow-sm);
            transform: translateZ(30px);
        }
        .stat-icon {
            width: 48px; height: 48px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
        }
        .stat-1 { background: rgba(0, 77, 204, 0.1); color: var(--primary); }
        .stat-2 { background: rgba(255, 204, 0, 0.15); color: #D97706; }
        .stat-info h4 { font-size: 1rem; color: var(--dark); margin-bottom: 2px; }
        .stat-info p { font-size: 0.875rem; color: var(--gray); margin: 0; }

        /* Features Section */
        .features { padding: 6rem 5%; background: var(--white); position: relative; }
        .section-header { text-align: center; margin-bottom: 4rem; }
        .section-header h2 { font-size: 2.5rem; color: var(--dark); margin-bottom: 1rem; }
        .section-header p { font-size: 1.125rem; color: var(--gray); max-width: 600px; margin: 0 auto; }

        .features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; }
        .feature-item {
            background: var(--bg); border: 1px solid rgba(0,0,0,0.03);
            padding: 2.5rem; border-radius: var(--radius-lg);
            transition: var(--transition);
        }
        .feature-item:hover {
            background: var(--white); transform: translateY(-10px);
            box-shadow: var(--shadow-lg); border-color: transparent;
        }
        .feature-icon-wrapper {
            width: 64px; height: 64px; border-radius: 16px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: var(--white); font-size: 1.75rem;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 1.5rem; box-shadow: 0 10px 20px rgba(0, 77, 204, 0.2);
        }
        .feature-item h3 { font-size: 1.5rem; margin-bottom: 1rem; }
        .feature-item p { color: var(--gray); }

        /* Modal Overlay & Login */
        .modal-overlay {
            position: fixed; inset: 0;
            background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(8px);
            z-index: 2000; display: flex; justify-content: center; align-items: center;
            opacity: 0; visibility: hidden; transition: var(--transition);
        }
        .modal-overlay.active { opacity: 1; visibility: visible; }

        .login-wrapper {
            display: flex; width: 100%; max-width: 900px;
            background: var(--white); border-radius: var(--radius-lg);
            overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            transform: scale(0.95) translateY(20px); opacity: 0;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .modal-overlay.active .login-wrapper { transform: scale(1) translateY(0); opacity: 1; }

        .login-visual {
            flex: 1; background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            padding: 3rem; color: var(--white); display: flex; flex-direction: column; justify-content: space-between;
            position: relative; overflow: hidden;
        }
        .login-visual::after {
            content: ''; position: absolute; bottom: -50px; right: -50px;
            width: 200px; height: 200px; background: rgba(255,255,255,0.1); border-radius: 50%;
        }
        .login-visual h2 { font-size: 2.5rem; margin-bottom: 1rem; position: relative; z-index: 1; }
        .login-visual p { font-size: 1.1rem; opacity: 0.9; position: relative; z-index: 1; }
        
        .login-form-container { flex: 1; padding: 4rem 3rem; position: relative; background: var(--white); }
        .close-modal {
            position: absolute; top: 1.5rem; right: 1.5rem;
            width: 36px; height: 36px; background: var(--gray-light); border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: var(--gray); cursor: pointer; transition: var(--transition);
        }
        .close-modal:hover { background: #fee2e2; color: #ef4444; transform: rotate(90deg); }

        .form-header { margin-bottom: 2.5rem; }
        .form-header h3 { font-size: 1.75rem; color: var(--dark); margin-bottom: 0.5rem; }
        .form-header p { color: var(--gray); }

        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--dark); font-size: 0.9rem; }
        .input-group { position: relative; }
        .input-group i { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--gray); }
        .form-control {
            width: 100%; padding: 0.875rem 1rem 0.875rem 2.75rem;
            border: 2px solid #E2E8F0; border-radius: var(--radius-md);
            font-family: var(--font-body); font-size: 1rem; color: var(--dark);
            transition: var(--transition); background: #F8FAFC;
        }
        .form-control:focus { outline: none; border-color: var(--primary); background: var(--white); box-shadow: 0 0 0 4px rgba(0, 77, 204, 0.1); }
        
        .btn-submit {
            width: 100%; padding: 1rem; background: var(--primary); color: var(--white);
            border: none; border-radius: var(--radius-md); font-family: var(--font-heading);
            font-weight: 600; font-size: 1.1rem; cursor: pointer; transition: var(--transition);
            margin-top: 1rem;
        }
        .btn-submit:hover { background: var(--secondary); color: var(--primary); transform: translateY(-2px); box-shadow: 0 4px 12px rgba(255, 204, 0, 0.25); }

        .forgot-link { display: block; text-align: right; color: var(--primary); font-weight: 500; text-decoration: none; font-size: 0.9rem; margin-top: 0.5rem; transition: var(--transition); }
        .forgot-link:hover { color: var(--secondary); text-decoration: underline; }

        .error-msg {
            background: #FEF2F2; color: #EF4444; padding: 1rem; border-radius: var(--radius-md);
            margin-bottom: 1.5rem; font-size: 0.9rem; font-weight: 500; display: flex; align-items: center; gap: 8px; border: 1px solid #FECACA;
        }

        /* Footer */
        footer { background: var(--primary); color: var(--white); padding: 5rem 5% 2rem; border-top: 4px solid var(--secondary); }
        .footer-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 4rem; margin-bottom: 3rem; }
        .footer-brand h3 { font-size: 1.5rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 12px; }
        .footer-brand img { height: 40px; background: white; padding: 4px; border-radius: 8px; }
        .footer-desc { color: #94A3B8; margin-bottom: 2rem; }
        
        .footer-links-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem; }
        .footer-col h4 { font-size: 1.1rem; margin-bottom: 1.5rem; color: var(--white); }
        .footer-col ul { list-style: none; }
        .footer-col ul li { margin-bottom: 0.75rem; }
        .footer-col ul li a { color: #94A3B8; text-decoration: none; transition: var(--transition); font-size: 0.95rem; }
        .footer-col ul li a:hover { color: var(--secondary); padding-left: 5px; }

        .footer-bottom { padding-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); text-align: center; color: #94A3B8; font-size: 0.9rem; }

        @media (max-width: 992px) {
            .hero { flex-direction: column; text-align: center; padding-top: 8rem; }
            .hero h1 { font-size: 3rem; }
            .hero-content { margin-bottom: 4rem; margin-right: 0; }
            .hero-btns { justify-content: center; }
            .badge { margin: 0 auto 1.5rem; }
            .hero-visual { width: 100%; perspective: none; }
            .glass-card { transform: none; }
            .hero-visual:hover .glass-card { transform: translateY(-10px); }
            
            .login-wrapper { flex-direction: column; max-width: 500px; max-height: 90vh; overflow-y: auto; }
            .login-visual { padding: 2rem; display: none; }
            .login-form-container { padding: 3rem 2rem; }
            
            .footer-grid { grid-template-columns: 1fr; gap: 2rem; }
            .footer-links-grid { grid-template-columns: 1fr 1fr; }
        }
        
        @media (max-width: 768px) {
            .nav-links a:not(.btn-login) { display: none; }
            .hero h1 { font-size: 2.5rem; }
            .footer-links-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    <!-- Navbar -->
    <nav class="navbar" id="navbar">
        <a href="index.php" class="navbar-brand">
            <img src="logo.png" alt="ITC Logo">
            ITC DRL
        </a>
        <div class="nav-links">
            <a href="tra_cuu.php" style="color: var(--primary); font-weight: 600;"><i class="fas fa-search"></i> Tra cứu điểm</a>
            <a href="#" class="btn-open-support">Hỗ trợ</a>
            <button class="btn-login btn-open-login" style="border:none; cursor:pointer; font-family:var(--font-heading);">
                <i class="fas fa-user-circle"></i> Đăng nhập
            </button>
        </div>
    </nav>

    <!-- Scroll to Top Button -->
    <div id="scrollToTop" class="scroll-to-top">
        <i class="fas fa-arrow-up"></i>
    </div>
    <style>
        .scroll-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            background: var(--secondary);
            color: var(--dark);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            cursor: pointer;
            box-shadow: 0 10px 15px -3px rgba(255, 204, 0, 0.4);
            z-index: 999;
            opacity: 0;
            visibility: hidden;
            transform: translateY(20px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .scroll-to-top.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        .scroll-to-top:hover {
            background: var(--primary);
            color: var(--white);
            transform: translateY(-5px);
            box-shadow: 0 15px 20px -3px rgba(0, 77, 204, 0.4);
        }
    </style>

    <!-- Hero -->
    <section class="hero">
        <div class="hero-content">
            <div class="badge"><i class="fas fa-sparkles"></i> Nền tảng đánh giá hiện đại 2026</div>
            <h1>Hệ thống Quản lý Điểm Rèn Luyện ITC</h1>
            <p>Trải nghiệm mượt mà, minh bạch và toàn diện. Số hóa quy trình đánh giá kết quả rèn luyện cho sinh viên Trường Cao đẳng Công nghệ Thông tin TP.HCM.</p>
            <div class="hero-btns">
                <button class="btn-primary btn-open-login"><i class="fas fa-rocket"></i> Bắt đầu ngay</button>
                <a href="#features" class="btn-secondary">Tìm hiểu thêm</a>
            </div>
        </div>
        
        <div class="hero-visual">
            <div class="glass-card">
                <div style="margin-bottom: 2rem;">
                    <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem;">Quy trình Đánh giá</h3>
                    <div style="height: 4px; width: 40px; background: var(--primary); border-radius: 2px;"></div>
                </div>
                
                <div class="glass-stat">
                    <div class="stat-icon stat-1"><i class="fas fa-user-graduate"></i></div>
                    <div class="stat-info">
                        <h4>Sinh Viên Tự Đánh Giá</h4>
                        <p>Khai báo minh chứng trực tuyến</p>
                    </div>
                    <i class="fas fa-check-circle" style="margin-left: auto; color: #10B981; font-size: 1.25rem;"></i>
                </div>
                
                <div class="glass-stat">
                    <div class="stat-icon stat-2"><i class="fas fa-chalkboard-teacher"></i></div>
                    <div class="stat-info">
                        <h4>Cố Vấn Học Tập Duyệt</h4>
                        <p>Kiểm tra & xác nhận điểm</p>
                    </div>
                    <i class="fas fa-clock" style="margin-left: auto; color: var(--secondary); font-size: 1.25rem;"></i>
                </div>
                
                <div class="glass-stat" style="opacity: 0.7;">
                    <div class="stat-icon" style="background: rgba(100,116,139,0.1); color: var(--gray);"><i class="fas fa-building"></i></div>
                    <div class="stat-info">
                        <h4>Khoa Xét Phê Duyệt</h4>
                        <p>Tổng hợp & công bố</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section id="features" class="features">
        <div class="section-header">
            <h2>Giải pháp Quản lý Toàn diện</h2>
            <p>Thiết kế tinh tế dành riêng cho hệ sinh thái ITC, đáp ứng nhu cầu của mọi đối tượng từ Sinh viên đến Ban quản trị.</p>
        </div>
        
        <div class="features-grid">
            <div class="feature-item">
                <div class="feature-icon-wrapper"><i class="fas fa-user-graduate"></i></div>
                <h3>Dành cho Sinh Viên</h3>
                <p>Giao diện trực quan giúp bạn dễ dàng tự chấm điểm, đính kèm minh chứng và theo dõi tiến độ xét duyệt một cách minh bạch.</p>
            </div>
            
            <div class="feature-item">
                <div class="feature-icon-wrapper"><i class="fas fa-chalkboard-teacher"></i></div>
                <h3>Cố Vấn Học Tập</h3>
                <p>Công cụ quản lý lớp học tối ưu, duyệt điểm hàng loạt và tự động tính toán, tiết kiệm tối đa thời gian xử lý hồ sơ.</p>
            </div>
            
            <div class="feature-item">
                <div class="feature-icon-wrapper"><i class="fas fa-chart-pie"></i></div>
                <h3>Ban Quản Trị & Khoa</h3>
                <p>Hệ thống báo cáo đa chiều, theo dõi thống kê toàn trường realtime, dễ dàng xuất dữ liệu và quản lý đợt đánh giá.</p>
            </div>
        </div>
    </section>

    <!-- Login Modal -->
    <div class="modal-overlay <?php echo $show_modal ? 'active' : ''; ?>" id="loginModal">
        <div class="login-wrapper">
            <div class="login-visual">
                <div>
                    <img src="logo.png" alt="Logo" style="height: 60px; background: white; padding: 5px; border-radius: 12px; margin-bottom: 2rem;">
                    <h2>Chào mừng trở lại!</h2>
                    <p>Hệ thống quản lý điểm rèn luyện ITC. Đăng nhập để truy cập không gian làm việc của bạn.</p>
                </div>
                <div style="font-size: 0.9rem; opacity: 0.7;">
                    &copy; 2026 ITC College
                </div>
            </div>
            
            <div class="login-form-container">
                <div class="close-modal" id="closeModal"><i class="fas fa-times"></i></div>
                
                <div class="form-header">
                    <h3>Đăng Nhập</h3>
                    <p>Nhập thông tin tài khoản của bạn</p>
                </div>
                
                <?php if($error): ?>
                    <div class="error-msg">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="index.php">
                    <input type="hidden" name="login_submit" value="1">
                    
                    <div class="form-group">
                        <label>Tên đăng nhập / Mã SV</label>
                        <div class="input-group">
                            <i class="fas fa-user"></i>
                            <input type="text" name="username" class="form-control" placeholder="Nhập mã sinh viên hoặc tài khoản" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Mật khẩu</label>
                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                        </div>
                        <a href="forgot_password.php" class="forgot-link">Quên mật khẩu?</a>
                    </div>
                    
                    <button type="submit" class="btn-submit">
                        Đăng nhập vào hệ thống <i class="fas fa-arrow-right" style="margin-left: 8px;"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Support Modal -->
    <div class="modal-overlay <?php echo $show_support_modal ? 'active' : ''; ?>" id="supportModal">
        <div class="login-wrapper">
            <div class="login-visual" style="background: linear-gradient(135deg, var(--secondary) 0%, #D97706 100%);">
                <div>
                    <i class="fas fa-headset" style="font-size: 3rem; margin-bottom: 1.5rem; color: white;"></i>
                    <h2>Cổng Hỗ Trợ</h2>
                    <p>Gửi yêu cầu, khiếu nại hoặc thắc mắc về điểm rèn luyện. Ban quản trị sẽ phản hồi sớm nhất.</p>
                </div>
                <div style="font-size: 0.9rem; opacity: 0.7; color: white;">
                    &copy; 2026 ITC College
                </div>
            </div>
            
            <div class="login-form-container" style="overflow-y: auto; max-height: 90vh;">
                <div class="close-modal" id="closeSupportModal"><i class="fas fa-times"></i></div>
                
                <div class="form-header">
                    <h3>Gửi Yêu Cầu Hỗ Trợ</h3>
                    <p>Vui lòng điền đầy đủ thông tin</p>
                </div>
                
                <?php if($support_msg): ?>
                    <div class="error-msg" style="background: <?php echo $support_type == 'success' ? '#F0FDF4' : '#FEF2F2'; ?>; color: <?php echo $support_type == 'success' ? '#15803D' : '#EF4444'; ?>; border-color: <?php echo $support_type == 'success' ? '#BBF7D0' : '#FECACA'; ?>;">
                        <i class="fas <?php echo $support_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                        <span><?php echo $support_msg; ?></span>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="index.php">
                    <input type="hidden" name="action" value="submit_support">
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label>Họ và Tên <span style="color:red">*</span></label>
                            <input type="text" name="ho_ten" class="form-control" required placeholder="Nhập họ tên" style="padding-left: 1rem;">
                        </div>
                        <div class="form-group">
                            <label>Mã Sinh Viên <span style="color:red">*</span></label>
                            <input type="text" name="mssv" class="form-control" required placeholder="Nhập mã SV" style="padding-left: 1rem;">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Tiêu đề cần hỗ trợ <span style="color:red">*</span></label>
                        <input type="text" name="tieu_de" class="form-control" required placeholder="Ví dụ: Xin cấp lại mật khẩu" style="padding-left: 1rem;">
                    </div>
                    
                    <div class="form-group">
                        <label>Nội dung chi tiết <span style="color:red">*</span></label>
                        <textarea name="noi_dung" class="form-control" required placeholder="Mô tả chi tiết vấn đề bạn đang gặp phải..." style="padding-left: 1rem; min-height: 100px; resize: vertical;"></textarea>
                    </div>
                    
                    <button type="submit" class="btn-submit" style="background: var(--dark); color: var(--white);">
                        <i class="fas fa-paper-plane"></i> Gửi Yêu Cầu
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="footer-grid">
            <div class="footer-brand-col">
                <div class="footer-brand">
                    <h3><img src="logo.png" alt="ITC"> ITC College</h3>
                </div>
                <p class="footer-desc">Trường Cao đẳng Công nghệ Thông tin TP.HCM. Nơi ươm mầm tài năng công nghệ và quản trị tương lai.</p>
                <div style="display: flex; gap: 1rem;">
                    <a href="#" style="color: white; font-size: 1.5rem;"><i class="fab fa-facebook"></i></a>
                    <a href="#" style="color: white; font-size: 1.5rem;"><i class="fab fa-youtube"></i></a>
                    <a href="#" style="color: white; font-size: 1.5rem;"><i class="fab fa-tiktok"></i></a>
                </div>
            </div>
            
            <div class="footer-links-grid">
                <div class="footer-col">
                    <h4>Liên hệ</h4>
                    <ul>
                        <li><a href="#"><i class="fas fa-map-marker-alt"></i> 12 Trịnh Đình Thảo, Tân Phú</a></li>
                        <li><a href="#"><i class="fas fa-phone"></i> (028) 397 349 83</a></li>
                        <li><a href="#"><i class="fas fa-envelope"></i> info@itc.edu.vn</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Hỗ trợ</h4>
                    <ul>
                        <li><a href="guest_rules.php">Quy chế ĐRL</a></li>
                        <li><a href="#" class="btn-open-support">Câu hỏi thường gặp</a></li>
                        <li><a href="#">Hướng dẫn sử dụng</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Liên kết nhanh</h4>
                    <ul>
                        <li><a href="#">Trang chủ ITC</a></li>
                        <li><a href="#">Phòng Đào tạo</a></li>
                        <li><a href="#">Phòng CTSV</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            &copy; <?php echo date('Y'); ?> Hệ thống Điểm Rèn Luyện. Phát triển bởi Ban CNTT ITC.
        </div>
    </footer>

    <script>
        // Navbar & Scroll to Top Effect
        window.addEventListener('scroll', () => {
            const navbar = document.getElementById('navbar');
            const scrollTopBtn = document.getElementById('scrollToTop');
            
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
            
            if (window.scrollY > 300) {
                scrollTopBtn.classList.add('show');
            } else {
                scrollTopBtn.classList.remove('show');
            }
        });

        // Scroll to top click
        document.getElementById('scrollToTop').addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // Modal Logic
        const modal = document.getElementById('loginModal');
        const openBtns = document.querySelectorAll('.btn-open-login');
        const closeBtn = document.getElementById('closeModal');

        openBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                modal.classList.add('active');
            });
        });

        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                modal.classList.remove('active');
            });
        }

        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.remove('active');
            }
        });

        // Support Modal Logic
        const supportModal = document.getElementById('supportModal');
        const openSupportBtns = document.querySelectorAll('.btn-open-support');
        const closeSupportBtn = document.getElementById('closeSupportModal');

        openSupportBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                supportModal.classList.add('active');
            });
        });

        if (closeSupportBtn) {
            closeSupportBtn.addEventListener('click', () => {
                supportModal.classList.remove('active');
            });
        }

        supportModal.addEventListener('click', (e) => {
            if (e.target === supportModal) {
                supportModal.classList.remove('active');
            }
        });
    </script>
</body>
</html>
