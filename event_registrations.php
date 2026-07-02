<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['cvht', 'khoa', 'admin'])) {
    redirect('notifications.php');
}

$thong_bao_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Lấy thông tin sự kiện
$sql_event = "SELECT * FROM thong_bao WHERE id = $thong_bao_id AND la_su_kien = 1";
$res_event = $conn->query($sql_event);
if (!$res_event || $res_event->num_rows == 0) {
    redirect('notifications.php');
}
$event = $res_event->fetch_assoc();

// Lấy danh sách đăng ký
// Nếu là CVHT, chỉ xem được sinh viên lớp mình quản lý
$cond = "";
if ($role == 'cvht') {
    $cond = " AND l.cvht_id = $user_id";
} elseif ($role == 'khoa') {
    $khoa_id = isset($_SESSION['khoa_id']) ? $_SESSION['khoa_id'] : 0;
    // Tùy theo kiến trúc, ở đây tạm thời Khoa thấy hết hoặc lọc theo khoa_id
    // $cond = " AND l.khoa_id = $khoa_id";
}

$sql_reg = "SELECT d.*, t.username, t.ho_ten, l.ten_lop 
            FROM dang_ky_thong_bao d 
            JOIN tai_khoan t ON d.sinh_vien_id = t.id 
            JOIN lop_hoc l ON t.lop_id = l.id 
            WHERE d.thong_bao_id = $thong_bao_id $cond 
            ORDER BY d.ngay_dang_ky DESC";
$res_reg = $conn->query($sql_reg);
$registrations = [];
if ($res_reg) {
    while ($row = $res_reg->fetch_assoc()) {
        $registrations[] = $row;
    }
}

$page_title = 'Danh Sách Đăng Ký Tham Gia Sự Kiện';
require_once 'layout_header.php';
?>

<div class="card">
        <div class="action-buttons">
            <a href="export_event_excel.php?id=<?php echo $thong_bao_id; ?>" class="btn-export" style="background: #2563EB;"><i class="fas fa-file-excel"></i> Xuất Excel</a>
            <a href="#" class="btn-export" onclick="window.print()"><i class="fas fa-print"></i> In Danh Sách</a>
        </div>
        
        <h2><i class="fas fa-users"></i> Danh Sách Đăng Ký</h2>
        <p class="meta-text">Sự kiện: <strong><?php echo htmlspecialchars($event['tieu_de']); ?></strong></p>
        
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th width="5%">STT</th>
                        <th width="15%">MSSV</th>
                        <th width="20%">Họ Tên</th>
                        <th width="15%">Lớp</th>
                        <th width="15%">Ngày Đăng Ký</th>
                        <?php if($event['cho_phep_dat_cau_hoi']): ?>
                            <th width="30%">Câu Hỏi Gửi BTC</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($registrations)): ?>
                        <tr><td colspan="<?php echo $event['cho_phep_dat_cau_hoi'] ? '6' : '5'; ?>" style="text-align: center;">Chưa có sinh viên nào đăng ký.</td></tr>
                    <?php else: ?>
                        <?php foreach ($registrations as $index => $r): ?>
                            <tr>
                                <td style="text-align: center;"><?php echo $index + 1; ?></td>
                                <td style="text-align: center; font-weight: 600;"><?php echo htmlspecialchars($r['username']); ?></td>
                                <td><?php echo htmlspecialchars($r['ho_ten']); ?></td>
                                <td style="text-align: center;"><?php echo htmlspecialchars($r['ten_lop']); ?></td>
                                <td style="text-align: center; font-size: 0.85rem; color: #64748B;"><?php echo date('d/m/Y H:i', strtotime($r['ngay_dang_ky'])); ?></td>
                                <?php if($event['cho_phep_dat_cau_hoi']): ?>
                                    <td>
                                        <?php if (!empty($r['cau_hoi'])): ?>
                                            <i class="fas fa-comment-dots" style="color: #F59E0B; margin-right: 5px;"></i>
                                            <?php echo htmlspecialchars($r['cau_hoi']); ?>
                                        <?php else: ?>
                                            <span style="color: #CBD5E1; font-style: italic;">Không có</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
    </div>

<?php require_once 'layout_footer.php'; ?>
