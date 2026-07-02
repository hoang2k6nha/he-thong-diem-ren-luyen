<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'sinh_vien') {
    redirect('notifications.php');
}

$thong_bao_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Lấy thông tin sự kiện
$sql_event = "SELECT * FROM thong_bao WHERE id = $thong_bao_id AND la_su_kien = 1";
$res_event = $conn->query($sql_event);
if (!$res_event || $res_event->num_rows == 0) {
    redirect('notifications.php');
}
$event = $res_event->fetch_assoc();

// Kiểm tra xem đã đăng ký chưa
$sql_check = "SELECT * FROM dang_ky_thong_bao WHERE thong_bao_id = $thong_bao_id AND sinh_vien_id = $user_id";
$res_check = $conn->query($sql_check);
if ($res_check && $res_check->num_rows > 0) {
    $success = "Bạn đã đăng ký tham gia sự kiện này rồi.";
}

// Kiểm tra thời gian đăng ký
$now = time();
$is_started = empty($event['thoi_gian_bat_dau']) || $now >= strtotime($event['thoi_gian_bat_dau']);
$is_ended = !empty($event['thoi_gian_ket_thuc']) && $now > strtotime($event['thoi_gian_ket_thuc']);

if (!$is_started) {
    $error = "Sự kiện chưa mở đăng ký.";
} elseif ($is_ended) {
    $error = "Sự kiện đã hết hạn đăng ký.";
}

// Lấy thông tin sinh viên để tự điền
$sql_sv = "SELECT t.username, t.ho_ten, l.ten_lop 
           FROM tai_khoan t 
           LEFT JOIN lop_hoc l ON t.lop_id = l.id 
           WHERE t.id = $user_id";
$sv = $conn->query($sql_sv)->fetch_assoc();

// Xử lý Form đăng ký
if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($success) && empty($error)) {
    $cau_hoi = '';
    if ($event['cho_phep_dat_cau_hoi']) {
        $cau_hoi = $conn->real_escape_string($_POST['cau_hoi']);
    }
    
    $sql_insert = "INSERT INTO dang_ky_thong_bao (thong_bao_id, sinh_vien_id, cau_hoi) VALUES ($thong_bao_id, $user_id, '$cau_hoi')";
    if ($conn->query($sql_insert)) {
        $success = "Đăng ký tham gia thành công!";
    } else {
        $error = "Có lỗi xảy ra, vui lòng thử lại sau.";
    }
}

$page_title = 'Đăng Ký Tham Gia Sự Kiện';
require_once 'layout_header.php';
?>

<div class="card">
        <div style="text-align: center; margin-bottom: 2rem;">
            <div style="display: inline-flex; align-items: center; justify-content: center; width: 64px; height: 64px; border-radius: 50%; background: #EFF6FF; color: var(--primary); font-size: 1.75rem; margin-bottom: 1rem;">
                <i class="fas fa-calendar-check"></i>
            </div>
            <h2 class="card-title"><?php echo htmlspecialchars($event['tieu_de']); ?></h2>
        </div>
        
        <?php if($success): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
            <a href="notifications.php" class="btn-submit" style="background: var(--dark); color: var(--white); text-decoration: none; box-shadow: none;">Về Trang Thông Báo</a>
        <?php elseif($error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?></div>
            <a href="notifications.php" class="btn-submit" style="background: var(--dark); color: var(--white); text-decoration: none; box-shadow: none;">Về Trang Thông Báo</a>
        <?php else: ?>
            
            <div class="event-info">
                <i class="fas fa-info-circle"></i>
                <p>Vui lòng kiểm tra lại thông tin cá nhân của bạn bên dưới trước khi xác nhận đăng ký tham gia sự kiện.</p>
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label>Họ và Tên</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($sv['ho_ten']); ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label>Mã Sinh Viên (MSSV)</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($sv['username']); ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label>Lớp Học</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($sv['ten_lop']); ?>" readonly>
                </div>
                
                <?php if ($event['cho_phep_dat_cau_hoi']): ?>
                <div class="form-group" style="margin-top: 2rem;">
                    <label style="color: var(--primary); display: flex; align-items: center; gap: 8px;"><i class="fas fa-comment-dots"></i> Gửi câu hỏi đến Ban Tổ Chức</label>
                    <textarea name="cau_hoi" class="form-control" rows="4" placeholder="Nhập câu hỏi của bạn tại đây (không bắt buộc)..."></textarea>
                </div>
                <?php endif; ?>
                
                <button type="submit" class="btn-submit" style="margin-top: 2rem;"><i class="fas fa-paper-plane"></i> Xác Nhận Đăng Ký</button>
            </form>
            
        <?php endif; ?>
    </div>

<?php require_once 'layout_footer.php'; ?>
