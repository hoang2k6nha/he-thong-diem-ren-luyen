<?php
require_once 'config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    redirect('index.php');
}

$user_id = (int)$_SESSION['user_id'];
$msg = '';
$msg_type = 'success';

// Lấy thông tin người dùng
$sql = "SELECT t.*, l.ten_lop, k.ten_khoa 
        FROM tai_khoan t
        LEFT JOIN lop_hoc l ON t.lop_id = l.id
        LEFT JOIN khoa k ON t.khoa_id = k.id OR l.khoa_id = k.id
        WHERE t.id = $user_id";
$res = $conn->query($sql);
if (!$res || $res->num_rows == 0) {
    die("Không tìm thấy thông tin tài khoản.");
}
$user = $res->fetch_assoc();

// Xử lý đổi mật khẩu
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'change_password') {
    $old_pw = md5($_POST['old_password']);
    $new_pw = $_POST['new_password'];
    $confirm_pw = $_POST['confirm_password'];
    
    if ($old_pw !== $user['password']) {
        $msg = "Mật khẩu cũ không chính xác.";
        $msg_type = "error";
    } elseif ($new_pw !== $confirm_pw) {
        $msg = "Mật khẩu xác nhận không khớp.";
        $msg_type = "error";
    } elseif (strlen($new_pw) < 6) {
        $msg = "Mật khẩu mới phải từ 6 ký tự trở lên.";
        $msg_type = "error";
    } else {
        $new_pw_md5 = md5($new_pw);
        $conn->query("UPDATE tai_khoan SET password = '$new_pw_md5' WHERE id = $user_id");
        $msg = "Đổi mật khẩu thành công!";
        $msg_type = "success";
        $user['password'] = $new_pw_md5; // update local context
    }
}

// Xử lý cập nhật thông tin liên hệ
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_contact') {
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['so_dien_thoai']);
    
    $conn->query("UPDATE tai_khoan SET email = '$email', so_dien_thoai = '$phone' WHERE id = $user_id");
    $msg = "Cập nhật thông tin liên hệ thành công!";
    $msg_type = "success";
    $user['email'] = $email;
    $user['so_dien_thoai'] = $phone;
}

// Xử lý cập nhật thông tin cá nhân bổ sung
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_personal') {
    $ngay_sinh = $conn->real_escape_string($_POST['ngay_sinh']);
    $noi_sinh = $conn->real_escape_string($_POST['noi_sinh']);
    $nganh_dao_tao = $conn->real_escape_string($_POST['nganh_dao_tao']);
    $chuyen_nganh = $conn->real_escape_string($_POST['chuyen_nganh']);
    $loai_dao_tao = $conn->real_escape_string($_POST['loai_dao_tao']);
    $bac_dao_tao = $conn->real_escape_string($_POST['bac_dao_tao']);
    
    $ngay_sinh_sql = !empty($ngay_sinh) ? "'$ngay_sinh'" : "NULL";

    $sql = "UPDATE tai_khoan SET 
            ngay_sinh = $ngay_sinh_sql,
            noi_sinh = '$noi_sinh',
            nganh_dao_tao = '$nganh_dao_tao',
            chuyen_nganh = '$chuyen_nganh',
            loai_dao_tao = '$loai_dao_tao',
            bac_dao_tao = '$bac_dao_tao'
            WHERE id = $user_id";
    $conn->query($sql);
    
    $msg = "Cập nhật hồ sơ cá nhân thành công!";
    $msg_type = "success";
    
    $user['ngay_sinh'] = $ngay_sinh;
    $user['noi_sinh'] = $noi_sinh;
    $user['nganh_dao_tao'] = $nganh_dao_tao;
    $user['chuyen_nganh'] = $chuyen_nganh;
    $user['loai_dao_tao'] = $loai_dao_tao;
    $user['bac_dao_tao'] = $bac_dao_tao;
}

$page_title = 'Hồ Sơ Cá Nhân - Hệ Thống ĐRL';
require_once 'layout_header.php';
?>

<style>
.modern-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 25px;
    margin-bottom: 30px;
}
.modern-info-item {
    display: flex;
    align-items: center;
    gap: 15px;
}
.modern-info-icon {
    width: 48px;
    height: 48px;
    background: #F1F5F9;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #004DCC;
    font-size: 1.25rem;
    flex-shrink: 0;
}
.modern-info-text label {
    display: block;
    font-size: 0.85rem;
    color: #64748B;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 4px;
}
.modern-info-text .val {
    font-size: 1.1rem;
    color: #1E293B;
    font-weight: 500;
}
@media (max-width: 768px) {
    .modern-info-grid { grid-template-columns: 1fr; }
}
</style><?php if($msg): ?>
        <div class="alert alert-<?php echo $msg_type; ?>"><i class="fas fa-<?php echo $msg_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i> <?php echo $msg; ?></div>
    <?php endif; ?>

    <div class="profile-wrapper">
        <!-- Sidebar Profile Card -->
        <div class="profile-card-left">
            <div class="profile-avatar-container">
                <div class="profile-avatar">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['ho_ten']); ?>&background=004DCC&color=fff&size=120" alt="Avatar">
                </div>
            </div>
            <h2 class="profile-name"><?php echo htmlspecialchars($user['ho_ten']); ?></h2>
            <div class="profile-role"><?php echo strtoupper($user['vai_tro']); ?></div>
            
            <div style="font-size: 0.9rem; color: var(--gray); text-align: left; background: #F8FAFC; padding: 15px; border-radius: 12px; border: 1px dashed #CBD5E1;">
                <p style="margin-bottom: 8px;"><i class="fas fa-id-badge" style="width: 20px; color: var(--primary);"></i> <strong><?php echo htmlspecialchars($user['username']); ?></strong></p>
                <?php if(!empty($user['email'])): ?>
                <p style="margin-bottom: 8px;"><i class="fas fa-envelope" style="width: 20px; color: var(--primary);"></i> <?php echo htmlspecialchars($user['email']); ?></p>
                <?php endif; ?>
                <?php if(!empty($user['so_dien_thoai'])): ?>
                <p><i class="fas fa-phone-alt" style="width: 20px; color: var(--primary);"></i> <?php echo htmlspecialchars($user['so_dien_thoai']); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Main Content Card -->
        <div class="profile-card-right">
            
            <h3 class="section-title"><i class="fas fa-address-card" style="color: var(--secondary);"></i> Thông tin hồ sơ</h3>
            <div class="modern-info-grid">
                <div class="modern-info-item">
                    <div class="modern-info-icon"><i class="fas fa-calendar-alt"></i></div>
                    <div class="modern-info-text">
                        <label>Ngày sinh</label>
                        <div class="val"><?php echo !empty($user['ngay_sinh']) ? date('d/m/Y', strtotime($user['ngay_sinh'])) : '<span style="color: var(--gray); font-weight: 400;">Đang cập nhật</span>'; ?></div>
                    </div>
                </div>
                <div class="modern-info-item">
                    <div class="modern-info-icon"><i class="fas fa-map-marker-alt"></i></div>
                    <div class="modern-info-text">
                        <label>Nơi sinh</label>
                        <div class="val"><?php echo !empty($user['noi_sinh']) ? htmlspecialchars($user['noi_sinh']) : '<span style="color: var(--gray); font-weight: 400;">Đang cập nhật</span>'; ?></div>
                    </div>
                </div>
                <div class="modern-info-item">
                    <div class="modern-info-icon"><i class="fas fa-graduation-cap"></i></div>
                    <div class="modern-info-text">
                        <label>Lớp học</label>
                        <div class="val"><?php echo $user['ten_lop'] ? htmlspecialchars($user['ten_lop']) : '<span style="color: var(--gray); font-weight: 400;">Chưa phân lớp</span>'; ?></div>
                    </div>
                </div>
                <div class="modern-info-item">
                    <div class="modern-info-icon"><i class="fas fa-laptop-code"></i></div>
                    <div class="modern-info-text">
                        <label>Ngành đào tạo</label>
                        <div class="val"><?php echo !empty($user['nganh_dao_tao']) ? htmlspecialchars($user['nganh_dao_tao']) : '<span style="color: var(--gray); font-weight: 400;">Đang cập nhật</span>'; ?></div>
                    </div>
                </div>
                <div class="modern-info-item">
                    <div class="modern-info-icon"><i class="fas fa-certificate"></i></div>
                    <div class="modern-info-text">
                        <label>Chuyên ngành</label>
                        <div class="val"><?php echo !empty($user['chuyen_nganh']) ? htmlspecialchars($user['chuyen_nganh']) : '<span style="color: var(--gray); font-weight: 400;">Đang cập nhật</span>'; ?></div>
                    </div>
                </div>
                <div class="modern-info-item">
                    <div class="modern-info-icon"><i class="fas fa-layer-group"></i></div>
                    <div class="modern-info-text">
                        <label>Bậc đào tạo</label>
                        <div class="val"><?php echo !empty($user['bac_dao_tao']) ? htmlspecialchars($user['bac_dao_tao']) : '<span style="color: var(--gray); font-weight: 400;">Cao đẳng</span>'; ?></div>
                    </div>
                </div>
                <div class="modern-info-item">
                    <div class="modern-info-icon"><i class="fas fa-building"></i></div>
                    <div class="modern-info-text">
                        <label>Loại đào tạo</label>
                        <div class="val"><?php echo !empty($user['loai_dao_tao']) ? htmlspecialchars($user['loai_dao_tao']) : '<span style="color: var(--gray); font-weight: 400;">Chính quy</span>'; ?></div>
                    </div>
                </div>
                <div class="modern-info-item">
                    <div class="modern-info-icon"><i class="fas fa-university"></i></div>
                    <div class="modern-info-text">
                        <label>Khoa Quản Lý</label>
                        <div class="val"><?php echo $user['ten_khoa'] ? htmlspecialchars($user['ten_khoa']) : '<span style="color: var(--gray); font-weight: 400;">Chưa cập nhật</span>'; ?></div>
                    </div>
                </div>
            </div>

            <h3 class="section-title" style="margin-top: 3rem;"><i class="fas fa-user-edit" style="color: #8B5CF6;"></i> Cập nhật hồ sơ</h3>
            <form method="POST">
                <input type="hidden" name="action" value="update_personal">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Ngày sinh</label>
                        <input type="date" name="ngay_sinh" class="form-control" value="<?php echo htmlspecialchars($user['ngay_sinh'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Nơi sinh</label>
                        <input type="text" name="noi_sinh" class="form-control" value="<?php echo htmlspecialchars($user['noi_sinh'] ?? ''); ?>" placeholder="VD: TP.HCM">
                    </div>
                    <div class="form-group">
                        <label>Ngành đào tạo</label>
                        <input type="text" name="nganh_dao_tao" class="form-control" value="<?php echo htmlspecialchars($user['nganh_dao_tao'] ?? ''); ?>" placeholder="VD: Công nghệ thông tin">
                    </div>
                    <div class="form-group">
                        <label>Chuyên ngành</label>
                        <input type="text" name="chuyen_nganh" class="form-control" value="<?php echo htmlspecialchars($user['chuyen_nganh'] ?? ''); ?>" placeholder="VD: Lập trình Mobile">
                    </div>
                    <div class="form-group">
                        <label>Loại đào tạo</label>
                        <input type="text" name="loai_dao_tao" class="form-control" value="<?php echo htmlspecialchars($user['loai_dao_tao'] ?? ''); ?>" placeholder="VD: Chính quy">
                    </div>
                    <div class="form-group">
                        <label>Bậc đào tạo</label>
                        <input type="text" name="bac_dao_tao" class="form-control" value="<?php echo htmlspecialchars($user['bac_dao_tao'] ?? ''); ?>" placeholder="VD: Cao đẳng">
                    </div>
                </div>
                <button type="submit" class="btn-submit" style="background: #8B5CF6; color: white;"><i class="fas fa-save"></i> Lưu hồ sơ cá nhân</button>
            </form>

            
            <h3 class="section-title" style="margin-top: 2.5rem;"><i class="fas fa-address-book" style="color: #10B981;"></i> Thông tin liên hệ</h3>
            <form method="POST">
                <input type="hidden" name="action" value="update_contact">
                <div class="form-group">
                    <label>Email (Dùng để nhận mã khôi phục mật khẩu)</label>
                    <div style="position: relative;">
                        <i class="fas fa-envelope" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--gray);"></i>
                        <input type="email" name="email" class="form-control" style="padding-left: 45px;" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" placeholder="Nhập địa chỉ email...">
                    </div>
                </div>
                <div class="form-group">
                    <label>Số điện thoại</label>
                    <div style="position: relative;">
                        <i class="fas fa-phone-alt" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--gray);"></i>
                        <input type="text" name="so_dien_thoai" class="form-control" style="padding-left: 45px;" value="<?php echo htmlspecialchars($user['so_dien_thoai'] ?? ''); ?>" placeholder="Nhập số điện thoại...">
                    </div>
                </div>
                <button type="submit" class="btn-submit btn-success"><i class="fas fa-save"></i> Cập nhật liên hệ</button>
            </form>
            
            <h3 class="section-title" style="margin-top: 3rem;"><i class="fas fa-lock" style="color: #EF4444;"></i> Đổi Mật Khẩu</h3>
            <div style="background: #FEF2F2; border: 1px solid #FECACA; padding: 15px; border-radius: 12px; margin-bottom: 20px; font-size: 0.9rem; color: #B91C1C;">
                <i class="fas fa-shield-alt"></i> Vui lòng sử dụng mật khẩu mạnh bao gồm chữ hoa, chữ thường và số để bảo vệ tài khoản.
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="change_password">
                <div class="form-group">
                    <label>Mật khẩu hiện tại</label>
                    <input type="password" name="old_password" class="form-control" required placeholder="Nhập mật khẩu cũ">
                </div>
                <div class="form-group">
                    <label>Mật khẩu mới</label>
                    <input type="password" name="new_password" class="form-control" required placeholder="Mật khẩu từ 6 ký tự trở lên">
                </div>
                <div class="form-group">
                    <label>Xác nhận mật khẩu mới</label>
                    <input type="password" name="confirm_password" class="form-control" required placeholder="Nhập lại mật khẩu mới">
                </div>
                <button type="submit" class="btn-submit"><i class="fas fa-key"></i> Lưu mật khẩu mới</button>
            </form>
        </div>
    </div>

<?php require_once 'layout_footer.php'; ?>
