<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    redirect('index.php');
}

$role = $_SESSION['role'];
$ho_ten = $_SESSION['ho_ten'];
$user_id = (int)$_SESSION['user_id'];

// Cập nhật thời gian xem thông báo
$conn->query("UPDATE tai_khoan SET ngay_xem_thong_bao = NOW() WHERE id = $user_id");

if ($role == 'sinh_vien') {
    // Sinh viên chỉ thấy thông báo dành cho tất cả
    $sql = "SELECT tb.*, tk.ho_ten as nguoi_tao, tk.vai_tro as vai_tro_tao,
                   (SELECT COUNT(*) FROM dang_ky_thong_bao WHERE thong_bao_id = tb.id AND sinh_vien_id = $user_id) as is_registered
            FROM thong_bao tb 
            JOIN tai_khoan tk ON tb.nguoi_tao_id = tk.id 
            WHERE tb.doi_tuong = 'tat_ca' 
            ORDER BY tb.ngay_tao DESC";
} else {
    // CVHT và Khoa thấy tất cả thông báo
    $sql = "SELECT tb.*, tk.ho_ten as nguoi_tao, tk.vai_tro as vai_tro_tao,
                   (SELECT COUNT(*) FROM dang_ky_thong_bao WHERE thong_bao_id = tb.id) as total_registered
            FROM thong_bao tb 
            JOIN tai_khoan tk ON tb.nguoi_tao_id = tk.id 
            ORDER BY tb.ngay_tao DESC";
}

$res = $conn->query($sql);
$notifications = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $notifications[] = $row;
    }
}

$page_title = 'Thông Báo - Hệ Thống ĐRL';
require_once 'layout_header.php';
?>

    <div class="page-header">
        <div class="page-title"><i class="fas fa-bullhorn" style="color: var(--secondary);"></i> Thông Báo Của Bạn</div>
        <?php if ($role == 'cvht' || $role == 'khoa' || $role == 'admin'): ?>
            <a href="manage_notifications.php" class="btn btn-primary"><i class="fas fa-plus"></i> Tạo Thông Báo Mới</a>
        <?php endif; ?>
    </div>

    <div class="notification-list">
        <?php if (empty($notifications)): ?>
            <div class="empty-state">
                <i class="fas fa-bell-slash"></i>
                <p>Hiện chưa có thông báo nào mới dành cho bạn.</p>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $n): ?>
                <div class="notification-card">
                    <div class="noti-header">
                        <div>
                            <div class="noti-title"><?php echo htmlspecialchars($n['tieu_de']); ?></div>
                            <div class="noti-meta">
                                <span><i class="fas fa-user-circle"></i> Đăng bởi: <strong><?php echo htmlspecialchars($n['nguoi_tao']); ?></strong></span>
                                <span class="badge badge-gray"><?php echo strtoupper($n['vai_tro_tao']); ?></span>
                                <span>&bull;</span>
                                <span><i class="fas fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($n['ngay_tao'])); ?></span>
                            </div>
                        </div>
                        <?php if ($role == 'khoa' || $role == 'cvht' || $role == 'admin'): ?>
                            <div>
                                <?php if ($n['doi_tuong'] == 'tat_ca'): ?>
                                    <span class="badge badge-green"><i class="fas fa-users"></i> Sinh viên & CVHT</span>
                                <?php else: ?>
                                    <span class="badge badge-yellow"><i class="fas fa-lock"></i> Chỉ CVHT & Khoa</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="noti-body">
                        <?php echo nl2br(htmlspecialchars($n['noi_dung'])); ?>
                        
                        <?php if (!empty($n['hinh_anh'])): ?>
                            <div style="margin-top: 1.5rem; text-align: center;">
                                <img src="<?php echo htmlspecialchars($n['hinh_anh']); ?>" alt="Ảnh đính kèm" style="max-width: 100%; max-height: 500px; border-radius: 12px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);">
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($n['duong_dan'])): ?>
                            <div style="margin-top: 1.5rem;">
                                <a href="<?php echo htmlspecialchars($n['duong_dan']); ?>" target="_blank" class="btn btn-secondary" style="display: inline-flex; background: #EFF6FF; color: var(--primary); border: 1px solid #BFDBFE;">
                                    <i class="fas fa-link"></i> 
                                    <?php echo !empty($n['ten_duong_dan']) ? htmlspecialchars($n['ten_duong_dan']) : 'Truy cập liên kết đính kèm'; ?>
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($n['la_su_kien']) && $n['la_su_kien'] == 1): ?>
                            <div class="event-box">
                                <?php 
                                    $now = time();
                                    $is_started = empty($n['thoi_gian_bat_dau']) || $now >= strtotime($n['thoi_gian_bat_dau']);
                                    $is_ended = !empty($n['thoi_gian_ket_thuc']) && $now > strtotime($n['thoi_gian_ket_thuc']);
                                ?>
                                <?php if ($role == 'sinh_vien'): ?>
                                    <?php if ($n['is_registered'] > 0): ?>
                                        <button disabled class="btn-event" style="background: #10B981;"><i class="fas fa-check-circle"></i> Đã đăng ký thành công</button>
                                    <?php elseif (!$is_started): ?>
                                        <button disabled class="btn-event"><i class="fas fa-hourglass-start"></i> Chưa đến thời gian đăng ký</button>
                                        <div class="event-meta"><i class="fas fa-clock"></i> Mở lúc: <?php echo date('d/m/Y H:i', strtotime($n['thoi_gian_bat_dau'])); ?></div>
                                    <?php elseif ($is_ended): ?>
                                        <button disabled class="btn-event expired"><i class="fas fa-times-circle"></i> Đã hết hạn đăng ký sự kiện</button>
                                    <?php else: ?>
                                        <a href="event_register.php?id=<?php echo $n['id']; ?>" class="btn-event"><i class="fas fa-ticket-alt"></i> Đăng ký tham gia ngay</a>
                                        <?php if (!empty($n['thoi_gian_ket_thuc'])): ?>
                                            <div class="event-meta"><i class="fas fa-exclamation-circle"></i> Hạn chót: <?php echo date('d/m/Y H:i', strtotime($n['thoi_gian_ket_thuc'])); ?></div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <a href="event_registrations.php?id=<?php echo $n['id']; ?>" class="btn-event"><i class="fas fa-clipboard-list"></i> Xem danh sách (<?php echo $n['total_registered'] ?? 0; ?> sinh viên)</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($role == 'khoa' || $role == 'cvht' || $role == 'admin'): ?>
                        <?php if ($_SESSION['user_id'] == $n['nguoi_tao_id'] || $role == 'khoa' || $role == 'admin'): ?>
                        <div class="noti-actions">
                            <a href="manage_notifications.php?edit=<?php echo $n['id']; ?>" class="btn btn-warning"><i class="fas fa-edit"></i> Sửa</a>
                            <a href="manage_notifications.php?delete=<?php echo $n['id']; ?>" class="btn btn-danger" onclick="return confirm('Bạn có chắc muốn xóa thông báo này?');"><i class="fas fa-trash-alt"></i> Xóa</a>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

<?php require_once 'layout_footer.php'; ?>
