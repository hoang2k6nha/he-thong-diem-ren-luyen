<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'sinh_vien' && !has_permission('student_history'))) {
    redirect('index.php');
}

$user_id = $_SESSION['user_id'];
$ho_ten = $_SESSION['ho_ten'];

// Lấy danh sách lịch sử đánh giá của sinh viên này (bao gồm cả các đợt chưa nộp)
$sql = "SELECT d.id as dot_id, d.ten_dot, d.nam_hoc, d.hoc_ky, d.ngay_ket_thuc, d.trang_thai as dot_trang_thai,
               p.id as phieu_id, p.trang_thai as phieu_trang_thai, 
               p.tong_diem_sv, p.tong_diem_cvht, p.tong_diem_khoa, p.xep_loai
        FROM dot_danh_gia d
        LEFT JOIN phieu_danh_gia p ON p.dot_id = d.id AND p.sinh_vien_id = $user_id
        ORDER BY d.id DESC";
$res = $conn->query($sql);
$history = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $history[] = $row;
    }
}

$page_title = 'Lịch Sử Điểm Rèn Luyện - ITC';
require_once 'layout_header.php';
?>

<div class="card">
        <div class="card-header">
            <h2><i class="fas fa-history" style="color: var(--secondary);"></i> Lịch Sử Đánh Giá Điểm Rèn Luyện</h2>
            <?php if (!empty($history)): ?>
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchHistory" onkeyup="filterHistory()" placeholder="Tìm đợt, học kỳ, năm học...">
            </div>
            <?php endif; ?>
        </div>
        
        <?php if (empty($history)): ?>
            <div class="empty-state">
                <i class="fas fa-folder-open"></i>
                <p>Bạn chưa có dữ liệu lịch sử điểm rèn luyện nào.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Đợt Đánh Giá</th>
                            <th style="text-align:center;">Trạng Thái</th>
                            <th style="text-align:center;">Điểm Tự Chấm</th>
                            <th style="text-align:center;">Điểm CVHT</th>
                            <th style="text-align:center;">Điểm Khoa</th>
                            <th style="text-align:center;">Xếp Loại Cuối</th>
                            <th style="text-align:center;">Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $h): ?>
                            <?php
                                $is_expired = false;
                                if (!empty($h['ngay_ket_thuc']) && $h['ngay_ket_thuc'] != '0000-00-00') {
                                    if (strtotime(date('Y-m-d')) > strtotime($h['ngay_ket_thuc'])) {
                                        $is_expired = true;
                                    }
                                }
                                $is_closed = ($h['dot_trang_thai'] == 'da_dong') || $is_expired;
                                
                                $show_missed_message = false;
                                $status_badge = '<span class="badge badge-gray">Chưa nộp</span>';
                                
                                if ((!$h['phieu_id'] || $h['phieu_trang_thai'] == 'chua_nop') && $is_closed) {
                                    $status_badge = '<span class="badge badge-yellow" style="color:#B45309; border-color:#FDE68A;">Bỏ qua, do CVHT duyệt</span>';
                                    $show_missed_message = true;
                                } else {
                                    if ($h['phieu_trang_thai'] == 'cho_cvht_duyet') $status_badge = '<span class="badge badge-yellow">Chờ CVHT duyệt</span>';
                                    elseif ($h['phieu_trang_thai'] == 'cho_khoa_duyet') $status_badge = '<span class="badge badge-blue">Chờ Khoa duyệt</span>';
                                    elseif ($h['phieu_trang_thai'] == 'da_duyet') $status_badge = '<span class="badge badge-green">Đã hoàn tất</span>';
                                }
                            ?>
                            <tr class="history-row" style="<?php echo $show_missed_message ? 'background-color: #FEF2F2;' : ''; ?>">
                                <td>
                                    <div style="font-weight: 700; color: var(--primary); font-family: var(--font-heading); font-size: 1.1rem;"><?php echo htmlspecialchars($h['ten_dot']); ?></div>
                                    <div style="font-size: 0.9rem; color: var(--gray); margin-top: 4px;">Học kỳ <?php echo $h['hoc_ky']; ?> - Năm <?php echo $h['nam_hoc']; ?></div>
                                    <?php if($show_missed_message): ?>
                                        <div style="color: #DC2626; font-size: 0.85rem; margin-top: 8px; font-weight: 600; display: flex; align-items: center; gap: 5px;">
                                            <i class="fas fa-exclamation-circle"></i> Sinh viên không nộp phiếu kì này
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:center;"><?php echo $status_badge; ?></td>
                                <td class="score-box" style="color: var(--gray);"><?php echo isset($h['tong_diem_sv']) ? $h['tong_diem_sv'] : '-'; ?></td>
                                <td class="score-box" style="color: #B45309;"><?php echo isset($h['tong_diem_cvht']) ? $h['tong_diem_cvht'] : '-'; ?></td>
                                <td class="score-box final-score"><?php echo isset($h['tong_diem_khoa']) ? $h['tong_diem_khoa'] : '-'; ?></td>
                                <td style="text-align:center; font-weight: 700; color: var(--primary); font-family: var(--font-heading); font-size: 1.1rem;">
                                    <?php if(!empty($h['xep_loai'])): ?>
                                        <span style="background: var(--secondary); color: var(--primary); padding: 4px 10px; border-radius: 6px;"><?php echo $h['xep_loai']; ?></span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php if($h['phieu_id']): ?>
                                        <a href="export_phieu_excel.php?sv_id=<?php echo $user_id; ?>&dot_id=<?php echo $h['dot_id']; ?>" class="btn-primary"><i class="fas fa-download"></i> Tải Phiếu</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function filterHistory() {
    let input = document.getElementById('searchHistory').value.toLowerCase();
    let rows = document.querySelectorAll('.history-row');
    
    rows.forEach(row => {
        let text = row.cells[0].innerText.toLowerCase();
        if (text.includes(input)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>

<?php require_once 'layout_footer.php'; ?>
