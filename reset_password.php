<?php
require_once 'config.php';

$error = '';
$success = '';

// Lấy username từ session (nếu có từ bước quên mật khẩu)
$default_username = isset($_SESSION['reset_username']) ? $_SESSION['reset_username'] : '';
$msg = isset($_SESSION['reset_msg']) ? $_SESSION['reset_msg'] : '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_submit'])) {
    $username = $conn->real_escape_string($_POST['username']);
    $token = $conn->real_escape_string($_POST['token']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $error = 'Mật khẩu xác nhận không khớp!';
    } else if (strlen($new_password) < 6) {
        $error = 'Mật khẩu mới phải có ít nhất 6 ký tự!';
    } else {
        // Kiểm tra mã xác nhận có hợp lệ và chưa hết hạn
        $sql = "SELECT id FROM tai_khoan WHERE username = '$username' AND reset_token = '$token' AND reset_token_expiry > NOW() AND trang_thai = 1";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Đổi mật khẩu và xóa token
            $hashed_password = md5($new_password);
            $update_sql = "UPDATE tai_khoan SET password = '$hashed_password', reset_token = NULL, reset_token_expiry = NULL WHERE id = " . $user['id'];
            
            if ($conn->query($update_sql)) {
                // Xóa session
                unset($_SESSION['reset_username']);
                unset($_SESSION['reset_msg']);
                
                // Set flash message cho trang login (hoặc có thể dùng URL param)
                echo "<script>alert('Đổi mật khẩu thành công! Vui lòng đăng nhập lại.'); window.location.href='index.php';</script>";
                exit;
            } else {
                $error = 'Đã xảy ra lỗi khi cập nhật mật khẩu. Vui lòng thử lại sau!';
            }
        } else {
            $error = 'Mã xác nhận không hợp lệ hoặc đã hết hạn!';
        }
    }
}

$page_title = 'Khôi phục mật khẩu - ITC';
require_once 'layout_header.php';
?>

<div class="header">
            <img src="logo.png" alt="ITC Logo">
            <h2>Tạo mật khẩu mới</h2>
            <p>Nhập mã xác nhận (6 chữ số) đã được gửi vào email của bạn và thiết lập mật khẩu mới.</p>
        </div>

        <?php if($msg && !$error): ?>
            <div class="info-msg"><i class="fas fa-envelope-open-text"></i> <?php echo $msg; ?></div>
        <?php endif; ?>

        <?php if($error): ?>
            <div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="reset_submit" value="1">
            <div class="form-group">
                <label>Mã sinh viên / Tên đăng nhập</label>
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($default_username); ?>" required <?php echo $default_username ? 'readonly' : ''; ?>>
                </div>
            </div>
            
            <div class="form-group">
                <label>Mã xác nhận (6 số)</label>
                <div class="input-group">
                    <i class="fas fa-key"></i>
                    <input type="text" name="token" class="form-control" placeholder="Nhập mã gồm 6 số..." maxlength="6" required autocomplete="off">
                </div>
            </div>

            <div class="form-group">
                <label>Mật khẩu mới</label>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="new_password" class="form-control" placeholder="Tối thiểu 6 ký tự..." required minlength="6">
                </div>
            </div>

            <div class="form-group">
                <label>Xác nhận mật khẩu mới</label>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="confirm_password" class="form-control" placeholder="Nhập lại mật khẩu mới..." required minlength="6">
                </div>
            </div>
            
            <button type="submit" class="btn-submit">Đổi mật khẩu <i class="fas fa-check-circle" style="margin-left: 5px;"></i></button>
            <a href="index.php" class="back-link"><i class="fas fa-arrow-left"></i> Quay lại đăng nhập</a>
        </form>

<?php require_once 'layout_footer.php'; ?>
