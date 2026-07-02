<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'cvht' && !has_permission('view_reports_advisor'))) redirect('index.php');

$cvht_id = $_SESSION['user_id'];

// Lấy đợt đánh giá hiện tại hoặc mới nhất
$res_dot = $conn->query("SELECT * FROM dot_danh_gia ORDER BY id DESC LIMIT 1");
$dot = $res_dot->fetch_assoc();
$dot_id = $dot ? $dot['id'] : 0;

// Lấy danh sách lớp do CVHT chủ nhiệm
$res_lop = $conn->query("SELECT * FROM lop_hoc WHERE " . ($_SESSION["role"] !== "cvht" ? "1=1" : "cvht_id = $cvht_id") . "");
$classes = [];
while ($row = $res_lop->fetch_assoc()) {
    $classes[] = $row;
}

$lop_id = isset($_GET['lop_id']) ? (int)$_GET['lop_id'] : ($classes[0]['id'] ?? 0);

$students = [];
if ($lop_id && $dot_id) {
    // Chỉ lấy những sinh viên ĐÃ CÓ ĐIỂM HOÀN TẤT (da_duyet) hoặc xem toàn bộ cũng được, nhưng tốt nhất là có xếp loại
    $sql_sv = "SELECT t.id, t.username, t.ho_ten, p.tong_diem_sv, p.tong_diem_cvht, p.tong_diem_khoa, p.xep_loai, p.trang_thai 
               FROM tai_khoan t 
               LEFT JOIN phieu_danh_gia p ON t.id = p.sinh_vien_id AND p.dot_id = $dot_id
               WHERE t.lop_id = $lop_id AND t.vai_tro = 'sinh_vien' 
               ORDER BY t.username ASC";
    $res_sv = $conn->query($sql_sv);
    while($row = $res_sv->fetch_assoc()){
        $students[] = $row;
    }
}

$page_title = 'Báo Cáo Lớp - CVHT';
require_once 'layout_header.php';
?>

<div class="card">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                <h2 style="color: var(--primary-blue); margin: 0;">Bảng Điểm Tổng Hợp - <?php echo $dot ? htmlspecialchars($dot['ten_dot']) : 'Không có'; ?></h2>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <a href="export_report_excel.php?lop_id=<?php echo $lop_id; ?>&dot_id=<?php echo $dot_id; ?>" class="btn-action" style="background: #2563EB;"><i class="fas fa-file-excel"></i> Xuất Excel</a>
                    <button onclick="window.print()" class="btn-action"><i class="fas fa-print"></i> In Báo Cáo</button>
                </div>
            </div>
            
            <form class="filter-form" method="GET">
                <label style="font-weight: 600; color: var(--primary-blue);">Chọn Lớp:</label>
                <select name="lop_id" onchange="this.form.submit()">
                    <?php foreach($classes as $c): ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo $c['id'] == $lop_id ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['ten_lop']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
            
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th width="5%">STT</th>
                            <th width="15%">MSSV</th>
                            <th width="30%">Họ Tên</th>
                            <th width="10%">Điểm SV</th>
                            <th width="10%">Điểm CVHT</th>
                            <th width="10%">Điểm Khoa (Cuối)</th>
                            <th width="10%">Xếp Loại</th>
                            <th width="10%">Tình Trạng</th>
                            <th width="10%">Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($students)): ?>
                            <tr><td colspan="8" style="text-align: center;">Không có dữ liệu sinh viên trong lớp này.</td></tr>
                        <?php else: ?>
                            <?php foreach($students as $index => $sv): ?>
                            <tr>
                                <td style="text-align: center;"><?php echo $index + 1; ?></td>
                                <td style="font-weight: 600; text-align: center;"><?php echo htmlspecialchars($sv['username']); ?></td>
                                <td><?php echo htmlspecialchars($sv['ho_ten']); ?></td>
                                <td style="text-align: center; color: #64748B; font-weight: bold;"><?php echo $sv['tong_diem_sv'] ?? '-'; ?></td>
                                <td style="text-align: center; color: #B45309; font-weight: bold;"><?php echo $sv['tong_diem_cvht'] ?? '-'; ?></td>
                                <td style="text-align: center; color: #065F46; font-weight: bold;"><?php echo $sv['tong_diem_khoa'] ?? '-'; ?></td>
                                <td style="text-align: center; font-weight: bold; color: var(--primary-blue);"><?php echo $sv['xep_loai'] ? $sv['xep_loai'] : '-'; ?></td>
                                <td style="text-align: center;">
                                    <?php 
                                        if(!$sv['trang_thai'] || $sv['trang_thai'] == 'chua_nop') echo 'Chưa nộp';
                                        elseif($sv['trang_thai'] == 'cho_cvht_duyet') echo 'Chờ CVHT';
                                        elseif($sv['trang_thai'] == 'cho_khoa_duyet') echo 'Chờ Khoa';
                                        else echo 'Hoàn tất';
                                    ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php if($sv['trang_thai'] && $sv['trang_thai'] != 'chua_nop'): ?>
                                        <a href="export_phieu_excel.php?sv_id=<?php echo $sv['id'] ?? (isset($students[$index]['id']) ? $students[$index]['id'] : 0); ?>&dot_id=<?php echo $dot_id; ?>" class="btn-primary" style="text-decoration: none; font-size: 0.85rem; padding: 6px 10px; background: #10B981;"><i class="fas fa-file-excel"></i> Xuất File</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div style="margin-top: 30px; display: flex; justify-content: space-between;">
                <div style="text-align: center;">
                    <p style="font-weight: 600;">Cố Vấn Học Tập</p>
                    <p style="margin-top: 50px;"><?php echo htmlspecialchars($_SESSION['ho_ten']); ?></p>
                </div>
                <div style="text-align: center;">
                    <p style="font-weight: 600;">Xác nhận của Khoa/CTSV</p>
                    <p style="margin-top: 50px;">(Ký và ghi rõ họ tên)</p>
                </div>
            </div>
        </div>

<?php require_once 'layout_footer.php'; ?>
