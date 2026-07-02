<?php
require_once 'config.php';

$step = 1;
$msg = '';
$msg_type = 'error';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['step1'])) {
        $username = $conn->real_escape_string($_POST['username']);
        $email = $conn->real_escape_string($_POST['email']);
        
        $res = $conn->query("SELECT id, ho_ten FROM tai_khoan WHERE username = '$username' AND email = '$email'");
        if ($res && $res->num_rows > 0) {
            $user = $res->fetch_assoc();
            
            // Tạo mã 6 số
            $code = rand(100000, 999999);
            $expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            
            $conn->query("UPDATE tai_khoan SET reset_token = '$code', reset_token_expiry = '$expiry' WHERE id = " . $user['id']);
            
            // Gửi mail (Giả lập hoặc dùng hàm mail thực tế tuỳ server)
            $subject = "Mã xác nhận khôi phục mật khẩu - Hệ thống Điểm rèn luyện ITC";
            $message = "Xin chào " . $user['ho_ten'] . ",\n\n";
            $message .= "Mã xác nhận để đặt lại mật khẩu của bạn là: $code\n";
            $message .= "Mã này sẽ hết hạn sau 15 phút.\n\n";
            $message .= "Nếu bạn không yêu cầu, vui lòng bỏ qua email này.\n";
            $headers = "From: no-reply@itc.edu.vn\r\n";
            
            @mail($email, $subject, $message, $headers);
            
            // For testing on localhost: show the code in session so we can display it if mail doesn't work
            $_SESSION['test_reset_code'] = $code;
            $_SESSION['reset_user_id'] = $user['id'];
            
            $step = 2;
            $msg = "Mã xác nhận đã được gửi đến email của bạn! (Dùng mã này để test: $code)";
            $msg_type = "success";
        } else {
            $msg = "Mã sinh viên hoặc email không trùng khớp trong hệ thống!";
        }
    } 
    elseif (isset($_POST['step2'])) {
        $code = $conn->real_escape_string($_POST['code']);
        $new_pass = $_POST['new_pass'];
        $confirm_pass = $_POST['confirm_pass'];
        $user_id = isset($_SESSION['reset_user_id']) ? $_SESSION['reset_user_id'] : 0;
        
        if ($new_pass !== $confirm_pass) {
            $step = 2;
            $msg = "Mật khẩu xác nhận không khớp!";
        } else {
            $check = $conn->query("SELECT id FROM tai_khoan WHERE id = $user_id AND reset_token = '$code' AND reset_token_expiry > NOW()");
            if ($check && $check->num_rows > 0) {
                $hashed_pass = md5($new_pass);
                $conn->query("UPDATE tai_khoan SET password = '$hashed_pass', reset_token = NULL, reset_token_expiry = NULL WHERE id = $user_id");
                
                unset($_SESSION['reset_user_id']);
                unset($_SESSION['test_reset_code']);
                
                $step = 3;
            } else {
                $step = 2;
                $msg = "Mã xác nhận không đúng hoặc đã hết hạn!";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên mật khẩu - ITC</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary-blue: #004DCC; --primary-yellow: #FFCC00; --bg: #F8FAFC; --border: #E2E8F0; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--bg); color: #1E293B; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        
        .auth-card { background: white; width: 100%; max-width: 450px; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); overflow: hidden; }
        .auth-header { background: var(--primary-blue); padding: 30px 20px; text-align: center; color: white; border-bottom: 4px solid var(--primary-yellow); }
        .auth-header img { height: 60px; background: white; padding: 5px; border-radius: 50%; margin-bottom: 15px; }
        .auth-header h2 { font-size: 1.5rem; margin-bottom: 5px; }
        
        .auth-body { padding: 30px; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #334155; }
        .input-group { position: relative; }
        .input-group i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #94A3B8; }
        .form-control { width: 100%; padding: 12px 15px 12px 40px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 1rem; transition: all 0.2s; }
        .form-control:focus { outline: none; border-color: var(--primary-blue); box-shadow: 0 0 0 3px rgba(0, 77, 204, 0.1); }
        
        .btn-submit { background: var(--primary-yellow); color: var(--primary-blue); border: none; width: 100%; padding: 14px; font-weight: 700; font-size: 1rem; border-radius: 8px; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .btn-submit:hover { background: #E5A610; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(253,184,19,0.3); }
        
        .back-link { display: block; text-align: center; margin-top: 20px; color: var(--primary-blue); text-decoration: none; font-weight: 500; }
        .back-link:hover { text-decoration: underline; }
        
        .alert { padding: 12px 15px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; display: flex; align-items: center; gap: 10px; line-height: 1.4; }
        .alert-error { background: #FEF2F2; color: #B91C1C; border: 1px solid #FECACA; }
        .alert-success { background: #F0FDF4; color: #15803D; border: 1px solid #BBF7D0; }
    </style>
</head>
<body>

    <div class="auth-card">
        <div class="auth-header">
            <img src="logo.png" alt="ITC Logo">
            <h2>Quên Mật Khẩu</h2>
            <p>Lấy lại quyền truy cập tài khoản</p>
        </div>
        
        <div class="auth-body">
            <?php if($msg): ?>
                <div class="alert alert-<?php echo $msg_type; ?>">
                    <i class="fas <?php echo $msg_type == 'error' ? 'fa-exclamation-circle' : 'fa-check-circle'; ?>"></i>
                    <div><?php echo $msg; ?></div>
                </div>
            <?php endif; ?>
            
            <?php if($step == 1): ?>
                <form method="POST">
                    <input type="hidden" name="step1" value="1">
                    <p style="color: #64748B; margin-bottom: 20px; font-size: 0.95rem;">Vui lòng nhập Mã Sinh Viên và Email đã đăng ký để nhận mã khôi phục.</p>
                    
                    <div class="form-group">
                        <label>Mã Sinh Viên / Tên đăng nhập</label>
                        <div class="input-group">
                            <i class="fas fa-user"></i>
                            <input type="text" name="username" class="form-control" required placeholder="Nhập mã sinh viên...">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Địa chỉ Email (Gmail)</label>
                        <div class="input-group">
                            <i class="fas fa-envelope"></i>
                            <input type="email" name="email" class="form-control" required placeholder="Nhập địa chỉ email...">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-submit">Nhận Mã Khôi Phục</button>
                    <a href="index.php" class="back-link"><i class="fas fa-arrow-left"></i> Quay lại đăng nhập</a>
                </form>
                
            <?php elseif($step == 2): ?>
                <form method="POST">
                    <input type="hidden" name="step2" value="1">
                    
                    <div class="form-group">
                        <label>Mã Xác Nhận (Gồm 6 số)</label>
                        <div class="input-group">
                            <i class="fas fa-shield-alt"></i>
                            <input type="text" name="code" class="form-control" required placeholder="Nhập mã 6 số từ email..." autocomplete="off">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Mật Khẩu Mới</label>
                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="new_pass" class="form-control" required placeholder="Nhập mật khẩu mới...">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Xác Nhận Mật Khẩu</label>
                        <div class="input-group">
                            <i class="fas fa-check-double"></i>
                            <input type="password" name="confirm_pass" class="form-control" required placeholder="Nhập lại mật khẩu mới...">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-submit">Đổi Mật Khẩu</button>
                    <a href="forgot_password.php" class="back-link">Gửi lại mã khác</a>
                </form>
                
            <?php elseif($step == 3): ?>
                <div style="text-align: center; padding: 20px 0;">
                    <i class="fas fa-check-circle" style="font-size: 4rem; color: #10B981; margin-bottom: 20px;"></i>
                    <h3 style="color: var(--primary-blue); margin-bottom: 10px;">Thành Công!</h3>
                    <p style="color: #64748B; margin-bottom: 25px;">Mật khẩu của bạn đã được thay đổi. Bạn có thể sử dụng mật khẩu mới để đăng nhập.</p>
                    <a href="index.php" class="btn-submit" style="display: inline-block; text-decoration: none;">Đăng Nhập Ngay</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>
