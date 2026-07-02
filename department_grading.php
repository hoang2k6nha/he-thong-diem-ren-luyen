<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'khoa' && !has_permission('grade_department'))) redirect('index.php');

$sv_id = isset($_GET['sv_id']) ? (int)$_GET['sv_id'] : 0;
$dot_id = isset($_GET['dot_id']) ? (int)$_GET['dot_id'] : 0;
if (!$sv_id || !$dot_id) redirect('department_classes.php');

$khoa_id = isset($_SESSION['khoa_id']) ? (int)$_SESSION['khoa_id'] : 0;

$user_id = (int)$_SESSION['user_id'];
$res_user = $conn->query("SELECT permissions FROM tai_khoan WHERE id = $user_id");
$user_perms = [];
if ($res_user && $row_u = $res_user->fetch_assoc()) {
    $user_perms = $row_u['permissions'] ? explode(',', $row_u['permissions']) : [];
}
$can_edit = in_array('edit_grades', $user_perms);

// Lấy thông tin sinh viên và kiểm tra xem sinh viên có thuộc khoa không
$sql_sv = "SELECT t.*, l.ten_lop FROM tai_khoan t JOIN lop_hoc l ON t.lop_id = l.id WHERE t.id = $sv_id AND " . ($_SESSION["role"] !== "khoa" ? "1=1" : "l.khoa_id = $khoa_id") . "";
$res_sv = $conn->query($sql_sv);
if ($res_sv->num_rows == 0) redirect('department_classes.php');
$sv_info = $res_sv->fetch_assoc();

// Lấy phiếu đánh giá
$sql_phieu = "SELECT * FROM phieu_danh_gia WHERE sinh_vien_id = $sv_id AND dot_id = $dot_id";
$res_phieu = $conn->query($sql_phieu);
$phieu = $res_phieu->fetch_assoc();

if (!$phieu || in_array($phieu['trang_thai'], ['chua_nop', 'cho_cvht_duyet'])) {
    die("Sinh viên chưa được CVHT duyệt điểm.");
}

$is_approved = ($phieu['trang_thai'] == 'da_duyet');

// Hàm tính xếp loại
function tinh_xep_loai($diem) {
    if ($diem >= 90) return 'Xuất sắc';
    if ($diem >= 80) return 'Tốt';
    if ($diem >= 65) return 'Khá';
    if ($diem >= 50) return 'Trung bình';
    return 'Yếu';
}

// Xử lý Mở khóa để sửa lại
if (isset($_GET['action']) && $_GET['action'] == 'unlock' && $is_approved && $can_edit) {
    $conn->query("UPDATE phieu_danh_gia SET trang_thai = 'cho_khoa_duyet' WHERE id = {$phieu['id']}");
    redirect("department_grading.php?sv_id=$sv_id&dot_id=$dot_id");
}

// Xử lý Duyệt phiếu
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$is_approved) {
    $tong_diem_khoa = 0;
    if (isset($_POST['diem_khoa'])) {
        foreach ($_POST['diem_khoa'] as $tieu_chi_id => $diem) {
            $diem = (int)$diem;
            $tong_diem_khoa += $diem;
            $conn->query("UPDATE chi_tiet_diem SET diem_khoa = $diem WHERE phieu_id = {$phieu['id']} AND tieu_chi_id = $tieu_chi_id");
        }
    }
    $xep_loai = tinh_xep_loai($tong_diem_khoa);
    $conn->query("UPDATE phieu_danh_gia SET tong_diem_khoa = $tong_diem_khoa, xep_loai = '$xep_loai', trang_thai = 'da_duyet' WHERE id = {$phieu['id']}");
    redirect("department_review.php?lop_id={$sv_info['lop_id']}");
}

// Lấy chi tiết điểm
$nhom_tieu_chi = [];
$res_nhom = $conn->query("SELECT * FROM nhom_tieu_chi ORDER BY thu_tu ASC");
while ($row = $res_nhom->fetch_assoc()) {
    $nhom_id = $row['id'];
    $res_tc = $conn->query("SELECT tc.*, ct.diem_sv, ct.diem_cvht, ct.diem_khoa, ct.minh_chung, ct.diem_tru, ct.ghi_chu_tru 
                            FROM tieu_chi tc 
                            LEFT JOIN chi_tiet_diem ct ON tc.id = ct.tieu_chi_id AND ct.phieu_id = {$phieu['id']}
                            WHERE tc.nhom_id = $nhom_id");
    $tieu_chis = [];
    while ($tc = $res_tc->fetch_assoc()) {
        $tc['diem_sv'] = $tc['diem_sv'] !== null ? $tc['diem_sv'] : '-';
        $tc['diem_cvht'] = $tc['diem_cvht'] !== null ? $tc['diem_cvht'] : '-';
        $tc['diem_khoa'] = $tc['diem_khoa'] !== null ? $tc['diem_khoa'] : ($tc['diem_cvht'] !== '-' ? $tc['diem_cvht'] : 0);
        $tc['diem_tru'] = isset($tc['diem_tru']) ? (int)$tc['diem_tru'] : 0;
        $tc['ghi_chu_tru'] = isset($tc['ghi_chu_tru']) ? $tc['ghi_chu_tru'] : '';
        $tieu_chis[] = $tc;
    }
    $row['tieu_chi_list'] = $tieu_chis;
    $nhom_tieu_chi[] = $row;
}

$page_title = 'Chấm điểm Sinh viên - Khoa';
require_once 'layout_header.php';
?>

<div class="card" style="background: #EFF6FF; border-color: #BFDBFE;">
            <h3 style="color: var(--primary-blue); margin-bottom: 10px;">Sinh viên: <?php echo htmlspecialchars($sv_info['ho_ten']); ?> (<?php echo htmlspecialchars($sv_info['username']); ?>)</h3>
            <p style="color: #475569;">Lớp: <strong><?php echo htmlspecialchars($sv_info['ten_lop']); ?></strong> | Điểm SV Tự Chấm: <strong><?php echo $phieu['tong_diem_sv']; ?> điểm</strong> | CVHT Chấm: <strong style="color: #B45309;"><?php echo $phieu['tong_diem_cvht']; ?> điểm</strong></p>
        </div>

        <div class="card">
            <form method="POST" action="">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th width="5%">STT</th>
                                <th width="40%">Nội dung đánh giá</th>
                                <th width="8%">Tối đa</th>
                                <th width="8%">Điểm Trừ</th>
                                <th width="8%">SV</th>
                                <th width="8%">CVHT</th>
                                <th width="11%">Khoa Chấm</th>
                                <th width="20%">Minh chứng</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($nhom_tieu_chi as $nhom): ?>
                                <tr class="nhom-tieu-chi">
                                    <td colspan="2"><?php echo htmlspecialchars($nhom['ten_nhom']); ?></td>
                                    <td style="text-align: center;"><?php echo $nhom['diem_toi_da']; ?></td>
                                    <td colspan="5"></td>
                                </tr>
                                <?php foreach($nhom['tieu_chi_list'] as $index => $tc): ?>
                                    <tr>
                                        <td style="text-align: center;"><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($tc['noi_dung']); ?></td>
                                        <td style="text-align: center; font-weight: bold;"><?php echo $tc['diem_toi_da']; ?></td>
                                        <td style="text-align: center;">
                                            <?php if ($tc['diem_tru'] > 0): ?>
                                                <div style="position: relative; display: inline-block; width: 100%;">
                                                    <button type="button" onclick="const p = this.nextElementSibling; document.querySelectorAll('.penalty-popover').forEach(el => { if(el !== p) el.style.display = 'none'; }); p.style.display = p.style.display === 'none' ? 'block' : 'none';" style="background: none; border: none; color: #DC2626; border-bottom: 1px dotted #DC2626; cursor: pointer; padding: 0; font-family: inherit; font-size: inherit; font-weight: bold; display: inline-flex; align-items: center; gap: 4px; justify-content: center; outline: none;">
                                                        -<?php echo $tc['diem_tru']; ?> <i class="fas fa-info-circle" style="font-size: 0.9em;"></i>
                                                    </button>
                                                    <div class="penalty-popover" style="display: none; position: absolute; right: 50%; transform: translateX(50%); top: 100%; margin-top: 10px; background: #1E293B; color: white; padding: 12px; border-radius: 8px; font-size: 0.85rem; width: max-content; max-width: 220px; z-index: 100; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.5); text-align: left; font-weight: normal; line-height: 1.5;">
                                                        <div style="font-weight: bold; margin-bottom: 8px; color: #FCA5A5; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 5px; display: flex; justify-content: space-between; align-items: center;">
                                                            <span><i class="fas fa-exclamation-circle"></i> Chi tiết bị trừ</span>
                                                            <i class="fas fa-times" onclick="this.closest('.penalty-popover').style.display='none';" style="cursor: pointer; padding: 2px;"></i>
                                                        </div>
                                                        <?php echo htmlspecialchars($tc['ghi_chu_tru']); ?>
                                                        <div style="position: absolute; left: 50%; transform: translateX(-50%); top: -6px; width: 0; height: 0; border-left: 6px solid transparent; border-right: 6px solid transparent; border-bottom: 6px solid #1E293B;"></div>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <span style="color: #94A3B8;">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: center; color: #64748B; font-weight: bold;"><?php echo $tc['diem_sv']; ?></td>
                                        <td style="text-align: center; color: #B45309; font-weight: bold;"><?php echo $tc['diem_cvht']; ?></td>
                                        <td style="text-align: center;">
                                            <input type="number" name="diem_khoa[<?php echo $tc['id']; ?>]" class="diem-input" 
                                                   value="<?php echo $tc['diem_khoa']; ?>" min="0" max="<?php echo max(0, $tc['diem_toi_da'] - $tc['diem_tru']); ?>"
                                                   <?php echo $is_approved ? 'disabled' : ''; ?> required>
                                        </td>
                                        <td>
                                            <?php if($tc['minh_chung']): ?>
                                                <a href="<?php echo htmlspecialchars($tc['minh_chung']); ?>" target="_blank" style="color: #2563EB; font-size: 0.9rem;"><i class="fas fa-link"></i> Xem File</a>
                                            <?php else: ?>
                                                <span style="color: #94A3B8; font-size: 0.85rem;">Không có</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div style="margin-top: 2rem; text-align: center;">
                    <?php if($is_approved): ?>
                        <div style="display: flex; justify-content: center; align-items: center; gap: 15px;">
                            <div class="badge" style="background: #D1FAE5; color: #065F46; padding: 15px 30px; font-size: 1rem;">
                                <i class="fas fa-check-circle"></i> Đã Duyệt Lần Cuối (<?php echo $phieu['tong_diem_khoa']; ?> điểm - Xếp loại: <?php echo $phieu['xep_loai']; ?>)
                            </div>
                            <?php if($can_edit): ?>
                                <a href="department_grading.php?sv_id=<?php echo $sv_id; ?>&dot_id=<?php echo $dot_id; ?>&action=unlock" class="btn-primary" style="background: #EF4444; text-decoration: none;" onclick="return confirm('Bạn có chắc muốn mở khóa để sửa lại điểm không?');">
                                    <i class="fas fa-edit"></i> Sửa Lại
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <button type="submit" class="btn-primary" onclick="return confirm('Bạn có chắc chắn muốn CHỐT ĐIỂM CUỐI CÙNG cho sinh viên này không?');">
                            <i class="fas fa-save"></i> Chốt Điểm Lần Cuối
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>

<?php require_once 'layout_footer.php'; ?>
