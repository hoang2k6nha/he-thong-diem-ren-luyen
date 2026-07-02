<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'khoa' && !has_permission('grade_department'))) {
    redirect('index.php');
}

$khoa_id = isset($_SESSION['khoa_id']) ? (int)$_SESSION['khoa_id'] : 0;
$ho_ten = $_SESSION['ho_ten'];

// Lấy đợt đánh giá hiện tại
$res_dot = $conn->query("SELECT id FROM dot_danh_gia ORDER BY id DESC LIMIT 1");
$current_dot = $res_dot->fetch_assoc();
$dot_id = $current_dot ? $current_dot['id'] : 0;

// Lấy danh sách lớp thuộc Khoa
$sql = "SELECT l.id, l.ma_lop, l.ten_lop, 
        (SELECT ho_ten FROM tai_khoan WHERE id = (SELECT cvht_id FROM phan_cong_cvht WHERE lop_id = l.id AND dot_id = $dot_id LIMIT 1)) as ten_cvht,
        (SELECT COUNT(id) FROM tai_khoan WHERE lop_id = l.id AND vai_tro = 'sinh_vien' AND trang_thai = 1) as si_so 
        FROM lop_hoc l 
        WHERE " . ($_SESSION["role"] !== "khoa" ? "1=1" : "l.khoa_id = $khoa_id") . "";
$res_lop = $conn->query($sql);
$classes = [];
if ($res_lop) {
    while($row = $res_lop->fetch_assoc()){
        $classes[] = $row;
    }
}

$page_title = 'Danh Sách Lớp - KHOA ITC';
require_once 'layout_header.php';
?>

<div class="card">
        <h2 style="color: var(--primary-blue); margin-bottom: 15px;">Danh Sách Lớp</h2>
        <p style="color: #64748B;">Chọn một lớp để tiến hành duyệt điểm rèn luyện lần cuối cho sinh viên.</p>
        
        <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th width="5%">STT</th>
                    <th width="15%">Mã Lớp</th>
                    <th width="35%">Tên Lớp</th>
                    <th width="20%">CVHT</th>
                    <th width="10%">Sĩ Số</th>
                    <th width="15%" style="text-align: center;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($classes)): ?>
                    <tr><td colspan="6" style="text-align: center;">Chưa có lớp nào trong Khoa.</td></tr>
                <?php else: ?>
                    <?php foreach($classes as $index => $lop): ?>
                    <tr>
                        <td style="text-align: center;"><?php echo $index + 1; ?></td>
                        <td style="font-weight: 600; color: var(--primary-blue);"><?php echo htmlspecialchars($lop['ma_lop']); ?></td>
                        <td><?php echo htmlspecialchars($lop['ten_lop']); ?></td>
                        <td><?php echo htmlspecialchars($lop['ten_cvht'] ? $lop['ten_cvht'] : 'Chưa phân công'); ?></td>
                        <td style="text-align: center;"><?php echo $lop['si_so']; ?> SV</td>
                        <td style="text-align: center;">
                            <a href="department_review.php?lop_id=<?php echo $lop['id']; ?>" class="btn-action"><i class="fas fa-eye"></i> Duyệt Lớp</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>

<?php require_once 'layout_footer.php'; ?>
