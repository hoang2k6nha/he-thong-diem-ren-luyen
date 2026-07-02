<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'khoa' && !has_permission('view_reports_department'))) redirect('index.php');

$khoa_id = isset($_SESSION['khoa_id']) ? (int)$_SESSION['khoa_id'] : 0;

$user_id = (int)$_SESSION['user_id'];
$res_user = $conn->query("SELECT permissions FROM tai_khoan WHERE id = $user_id");
$user_perms = [];
if ($res_user && $row_u = $res_user->fetch_assoc()) {
    $user_perms = $row_u['permissions'] ? explode(',', $row_u['permissions']) : [];
}
$can_edit = in_array('edit_grades', $user_perms);

// Lấy đợt đánh giá hiện tại hoặc mới nhất
$res_dot = $conn->query("SELECT * FROM dot_danh_gia ORDER BY id DESC LIMIT 1");
$dot = $res_dot->fetch_assoc();
$dot_id = $dot ? $dot['id'] : 0;

// Lấy danh sách lớp thuộc Khoa
$res_lop = $conn->query("SELECT * FROM lop_hoc WHERE " . ($_SESSION["role"] !== "khoa" ? "1=1" : "khoa_id = $khoa_id") . "");
$classes = [];
while ($row = $res_lop->fetch_assoc()) {
    $classes[] = $row;
}

$lop_id = isset($_GET['lop_id']) ? (int)$_GET['lop_id'] : 0;

$students = [];
if ($dot_id) {
    $sql_sv = "SELECT t.id, t.username, t.ho_ten, l.ten_lop, p.tong_diem_khoa, p.xep_loai, p.trang_thai 
               FROM tai_khoan t 
               JOIN lop_hoc l ON t.lop_id = l.id
               LEFT JOIN phieu_danh_gia p ON t.id = p.sinh_vien_id AND p.dot_id = $dot_id
               WHERE " . ($_SESSION["role"] !== "khoa" ? "1=1" : "l.khoa_id = $khoa_id") . " AND t.vai_tro = 'sinh_vien'";
    if ($lop_id) {
        $sql_sv .= " AND t.lop_id = $lop_id";
    }
    $sql_sv .= " ORDER BY l.ten_lop ASC, t.username ASC";
    
    $res_sv = $conn->query($sql_sv);
    while($row = $res_sv->fetch_assoc()){
        $students[] = $row;
    }
}

// Tính thống kê xếp loại (chỉ cho sinh viên đã được duyệt hoàn tất)
$stats = ['Xuất sắc' => 0, 'Tốt' => 0, 'Khá' => 0, 'Trung bình' => 0, 'Yếu' => 0];
$total_approved = 0;
foreach($students as $sv) {
    if ($sv['trang_thai'] == 'da_duyet' && $sv['xep_loai']) {
        if(isset($stats[$sv['xep_loai']])) {
            $stats[$sv['xep_loai']]++;
        }
        $total_approved++;
    }
}

$page_title = 'Báo Cáo Điểm Khoa';
require_once 'layout_header.php';
?>

<div class="card">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                <h2 style="color: var(--primary-blue); margin: 0;">Thống Kê Xếp Loại ĐRL Khoa - <?php echo $dot ? htmlspecialchars($dot['ten_dot']) : 'Không có'; ?></h2>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <a href="export_report_excel.php?lop_id=<?php echo $lop_id; ?>&dot_id=<?php echo $dot_id; ?>" class="btn-action" style="background: #2563EB;"><i class="fas fa-file-excel"></i> Xuất Excel</a>
                    <button onclick="window.print()" class="btn-action"><i class="fas fa-print"></i> In Báo Cáo</button>
                </div>
            </div>
            
            <p style="margin-bottom: 10px;">Tổng số sinh viên đã hoàn tất điểm: <strong><?php echo $total_approved; ?></strong> sinh viên</p>
            <div class="stats-grid">
                <?php foreach($stats as $loai => $count): ?>
                <div class="stat-box">
                    <h3><?php echo $loai; ?></h3>
                    <p><?php echo $count; ?></p>
                </div>
                <?php endforeach; ?>
            </div>
            
            <h2 style="color: var(--primary-blue); margin-bottom: 10px; margin-top: 2rem;">Bảng Điểm Chi Tiết</h2>
            <form class="filter-form" method="GET">
                <label style="font-weight: 600; color: var(--primary-blue);">Lọc theo Lớp:</label>
                <select name="lop_id" onchange="this.form.submit()">
                    <option value="0">--- Tất cả các lớp trong Khoa ---</option>
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
                            <th width="25%">Họ Tên</th>
                            <th width="15%">Lớp</th>
                            <th width="10%">Điểm Cuối</th>
                            <th width="12%">Xếp Loại</th>
                            <th width="13%">Tình Trạng</th>
                            <th width="10%" class="hide-on-print">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($students)): ?>
                            <tr><td colspan="8" style="text-align: center;">Không có dữ liệu sinh viên.</td></tr>
                        <?php else: ?>
                            <?php foreach($students as $index => $sv): ?>
                            <tr>
                                <td style="text-align: center;"><?php echo $index + 1; ?></td>
                                <td style="font-weight: 600; text-align: center;"><?php echo htmlspecialchars($sv['username']); ?></td>
                                <td><?php echo htmlspecialchars($sv['ho_ten']); ?></td>
                                <td style="text-align: center;"><?php echo htmlspecialchars($sv['ten_lop']); ?></td>
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
                                <td style="text-align: center;" class="hide-on-print">
                                    <div style="display: flex; gap: 8px; justify-content: center; align-items: center;">
                                        <?php if(($sv['trang_thai'] == 'da_duyet' || $sv['trang_thai'] == 'cho_khoa_duyet') && $can_edit): ?>
                                            <a href="department_grading.php?sv_id=<?php echo $sv['id']; ?>&dot_id=<?php echo $dot_id; ?>" style="color: #2563EB; font-size: 0.9rem; text-decoration: none; font-weight: 600;"><i class="fas fa-edit"></i> Sửa Lại</a>
                                        <?php endif; ?>
                                        
                                        <?php if($sv['trang_thai'] && $sv['trang_thai'] != 'chua_nop'): ?>
                                            <a href="export_phieu_excel.php?sv_id=<?php echo $sv['id']; ?>&dot_id=<?php echo $dot_id; ?>" class="btn-primary" style="text-decoration: none; font-size: 0.85rem; padding: 4px 8px; background: #10B981;"><i class="fas fa-file-excel"></i> Xuất File</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div style="margin-top: 30px; display: flex; justify-content: flex-end;">
                <div style="text-align: center; margin-right: 50px;">
                    <p style="font-weight: 600;">Trưởng Khoa / Đại diện CTSV</p>
                    <p style="margin-top: 50px;">(Ký và ghi rõ họ tên)</p>
                </div>
            </div>
        </div>

<?php require_once 'layout_footer.php'; ?>
