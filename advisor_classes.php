<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'cvht' && !has_permission('grade_advisor'))) {
    redirect('index.php');
}

$cvht_id = $_SESSION['user_id'];
$ho_ten = $_SESSION['ho_ten'];

// Lấy đợt đánh giá hiện tại
$res_dot = $conn->query("SELECT * FROM dot_danh_gia ORDER BY id DESC LIMIT 1");
$current_dot = $res_dot->fetch_assoc();
$dot_id = $current_dot ? $current_dot['id'] : 0;

// Lấy danh sách lớp chủ nhiệm theo phân công
$sql = "SELECT l.id, l.ma_lop, l.ten_lop, k.ten_khoa, 
        (SELECT COUNT(id) FROM tai_khoan WHERE lop_id = l.id AND vai_tro = 'sinh_vien' AND trang_thai = 1) as si_so 
        FROM phan_cong_cvht pc
        JOIN lop_hoc l ON pc.lop_id = l.id
        LEFT JOIN khoa k ON l.khoa_id = k.id 
        WHERE " . ($_SESSION["role"] !== "cvht" ? "1=1" : "pc.cvht_id = $cvht_id") . " AND pc.dot_id = $dot_id";
$res_lop = $conn->query($sql);
$classes = [];
if ($res_lop) {
    while($row = $res_lop->fetch_assoc()){
        $classes[] = $row;
    }
}

$page_title = 'Quản Lý Lớp - CVHT ITC';
require_once 'layout_header.php';
?>

<div class="card">
        <h2 style="color: var(--primary-blue); margin-bottom: 15px;">Lớp Học Thuộc Quản Lý</h2>
        <p style="color: #64748B;">Bạn có thể chọn một lớp để xem danh sách sinh viên và duyệt điểm rèn luyện.</p>
        
        <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th width="5%">STT</th>
                    <th width="15%">Mã Lớp</th>
                    <th width="35%">Tên Lớp</th>
                    <th width="20%">Khoa</th>
                    <th width="10%">Sĩ Số</th>
                    <th width="15%" style="text-align: center;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($classes)): ?>
                    <tr><td colspan="6" style="text-align: center;">Chưa có lớp nào được phân công cho bạn.</td></tr>
                <?php else: ?>
                    <?php foreach($classes as $index => $lop): ?>
                    <tr>
                        <td style="text-align: center;"><?php echo $index + 1; ?></td>
                        <td style="font-weight: 600; color: var(--primary-blue);"><?php echo htmlspecialchars($lop['ma_lop']); ?></td>
                        <td><?php echo htmlspecialchars($lop['ten_lop']); ?></td>
                        <td><?php echo htmlspecialchars($lop['ten_khoa']); ?></td>
                        <td style="text-align: center;"><?php echo $lop['si_so']; ?> SV</td>
                        <td style="text-align: center;">
                            <a href="advisor_review.php?lop_id=<?php echo $lop['id']; ?>" class="btn-action"><i class="fas fa-eye"></i> Xem & Duyệt</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>

<?php require_once 'layout_footer.php'; ?>
