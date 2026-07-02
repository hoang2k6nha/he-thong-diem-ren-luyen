<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'cvht' && !has_permission('advisor_manage_class'))) {
    redirect('index.php');
}

$cvht_id = $_SESSION['user_id'];
$sql = "SELECT l.*, k.ten_khoa, (SELECT COUNT(id) FROM tai_khoan WHERE lop_id = l.id AND vai_tro = 'sinh_vien') as si_so 
        FROM lop_hoc l 
        LEFT JOIN khoa k ON l.khoa_id = k.id 
        WHERE " . ($_SESSION["role"] !== "cvht" ? "1=1" : "l.cvht_id = $cvht_id") . "";
$result = $conn->query($sql);
$classes = [];
if ($result) {
    while($row = $result->fetch_assoc()) {
        $classes[] = $row;
    }
}

$page_title = 'Quản Lý Lớp - CVHT';
require_once 'layout_header.php';
?>

<div class="card">
        <h2 style="color: var(--primary-blue); margin-bottom: 15px;">Danh sách Lớp</h2>
        <p style="color: #64748B;">Vui lòng chọn lớp để xem danh sách sinh viên trực thuộc.</p>
        <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th width="15%">Mã Lớp</th>
                    <th width="30%">Tên Lớp</th>
                    <th width="25%">Khoa</th>
                    <th width="15%" style="text-align: center;">Sĩ Số</th>
                    <th width="15%" style="text-align: center;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($classes) > 0): ?>
                    <?php foreach($classes as $lop): ?>
                    <tr>
                        <td style="font-weight: 600;"><?php echo htmlspecialchars($lop['ma_lop']); ?></td>
                        <td><?php echo htmlspecialchars($lop['ten_lop']); ?></td>
                        <td><?php echo htmlspecialchars($lop['ten_khoa']); ?></td>
                        <td style="text-align: center;">
                            <span style="background: #EFF6FF; color: var(--primary-blue); padding: 5px 10px; border-radius: 20px; font-weight: 600;"><?php echo $lop['si_so']; ?> SV</span>
                        </td>
                        <td style="text-align: center;">
                            <a href="advisor_student_list.php?lop_id=<?php echo $lop['id']; ?>" class="btn-action"><i class="fas fa-list"></i> Xem DS</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align: center; padding: 20px; color: #64748B;">Bạn chưa được phân công chủ nhiệm lớp nào.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>

<?php require_once 'layout_footer.php'; ?>
