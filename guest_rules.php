<?php
require_once 'config.php';

// Lấy danh sách nhóm tiêu chí
$res_nhom = $conn->query("SELECT * FROM nhom_tieu_chi ORDER BY thu_tu ASC");
$nhom_tieu_chi = [];
while ($n = $res_nhom->fetch_assoc()) {
    $nhom_tieu_chi[$n['id']] = $n;
    $nhom_tieu_chi[$n['id']]['tieu_chi'] = [];
}

// Lấy danh sách tiêu chí con
$res_tc = $conn->query("SELECT * FROM tieu_chi ORDER BY nhom_id, id ASC");
while ($tc = $res_tc->fetch_assoc()) {
    if (isset($nhom_tieu_chi[$tc['nhom_id']])) {
        $nhom_tieu_chi[$tc['nhom_id']]['tieu_chi'][] = $tc;
    }
}

$page_title = 'Quy chế Điểm Rèn luyện - ITC';
require_once 'layout_header.php';
?>

<div class="header-title">
            <h1>Quy chế Đánh giá Điểm Rèn luyện</h1>
            <p>Khung điểm chuẩn áp dụng cho sinh viên trường Cao đẳng Công nghệ Thông tin TP.HCM</p>
        </div>

        <?php foreach ($nhom_tieu_chi as $nhom): ?>
            <div class="card">
                <div class="nhom-header">
                    <div><?php echo htmlspecialchars($nhom['ten_nhom']); ?></div>
                    <div class="nhom-diem">Tối đa: <?php echo $nhom['diem_toi_da']; ?> đ</div>
                </div>
                <ul class="tieu-chi-list">
                    <?php if (empty($nhom['tieu_chi'])): ?>
                        <li class="tieu-chi-item"><div class="tc-noi-dung" style="color: #94A3B8;">Chưa có tiêu chí chi tiết.</div></li>
                    <?php else: ?>
                        <?php foreach ($nhom['tieu_chi'] as $tc): ?>
                            <li class="tieu-chi-item">
                                <div class="tc-noi-dung">
                                    <i class="fas fa-check-circle" style="color: var(--primary-yellow); margin-right: 8px;"></i>
                                    <?php echo htmlspecialchars($tc['noi_dung']); ?>
                                </div>
                                <div class="tc-diem">+<?php echo $tc['diem_toi_da']; ?> đ</div>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        <?php endforeach; ?>
        
        <div style="text-align: center; margin-top: 3rem; color: #64748B;">
            <p>Mọi thắc mắc về điểm rèn luyện, vui lòng liên hệ phòng Công tác sinh viên hoặc sử dụng <a href="guest_support.php" style="color: var(--primary-blue); font-weight: 600;">Cổng Hỗ Trợ</a>.</p>
        </div>

<?php require_once 'layout_footer.php'; ?>
