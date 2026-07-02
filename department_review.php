<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'khoa' && !has_permission('grade_department'))) {
    redirect('index.php');
}

$lop_id = isset($_GET['lop_id']) ? (int)$_GET['lop_id'] : 0;
if (!$lop_id) redirect('department_classes.php');

$khoa_id = isset($_SESSION['khoa_id']) ? (int)$_SESSION['khoa_id'] : 0;

// Kiểm tra lớp này có thuộc Khoa không
$check = $conn->query("SELECT * FROM lop_hoc WHERE id = $lop_id AND " . ($_SESSION["role"] !== "khoa" ? "1=1" : "khoa_id = $khoa_id") . "");
if ($check->num_rows == 0) redirect('department_classes.php');

$lop = $check->fetch_assoc();

// Lấy danh sách đợt đánh giá đang mở hoặc vừa đóng
$res_dot = $conn->query("SELECT * FROM dot_danh_gia ORDER BY id DESC LIMIT 1");
$dot = $res_dot->fetch_assoc();
$dot_id = $dot ? $dot['id'] : 0;

// Lấy danh sách sinh viên trong lớp và trạng thái nộp phiếu
$sql_sv = "SELECT t.id, t.username, t.ho_ten, p.trang_thai, p.tong_diem_sv, p.tong_diem_cvht, p.tong_diem_khoa 
           FROM tai_khoan t 
           LEFT JOIN phieu_danh_gia p ON t.id = p.sinh_vien_id AND p.dot_id = $dot_id
           WHERE t.lop_id = $lop_id AND t.vai_tro = 'sinh_vien' AND t.trang_thai = 1";
$res_sv = $conn->query($sql_sv);
$students = [];
if ($res_sv) {
    while($row = $res_sv->fetch_assoc()){
        if(!$row['trang_thai']) $row['trang_thai'] = 'chua_nop';
        $students[] = $row;
    }
}

$page_title = 'Duyệt Điểm - KHOA ITC';
require_once 'layout_header.php';
?>

<div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; margin-bottom: 15px;">
                <h2 style="color: var(--primary-blue); margin-bottom: 0;">Danh Sách Sinh Viên</h2>
            </div>
            
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchStudent" onkeyup="filterStudent()" placeholder="Tìm kiếm theo mã sinh viên, họ tên...">
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th width="5%">STT</th>
                            <th width="15%">Mã SV</th>
                            <th width="30%">Họ Tên</th>
                            <th width="12%">Điểm SV</th>
                            <th width="12%">Điểm CVHT</th>
                            <th width="15%">Trạng Thái</th>
                            <th width="11%">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($students as $index => $sv): ?>
                        <tr class="student-row">
                            <td><?php echo $index + 1; ?></td>
                            <td style="font-weight: 600;" class="s-msv"><?php echo htmlspecialchars($sv['username']); ?></td>
                            <td class="s-name"><?php echo htmlspecialchars($sv['ho_ten']); ?></td>
                            <td style="font-weight: bold; color: #64748B;"><?php echo $sv['tong_diem_sv'] !== null ? $sv['tong_diem_sv'] : '-'; ?></td>
                            <td style="font-weight: bold; color: #B45309;"><?php echo $sv['tong_diem_cvht'] !== null ? $sv['tong_diem_cvht'] : '-'; ?></td>
                            <td>
                                <?php if($sv['trang_thai'] == 'chua_nop'): ?>
                                    <span class="badge badge-chua-nop">Chưa nộp</span>
                                <?php elseif($sv['trang_thai'] == 'cho_cvht_duyet'): ?>
                                    <span class="badge badge-cho-cvht">Chờ CVHT duyệt</span>
                                <?php elseif($sv['trang_thai'] == 'cho_khoa_duyet'): ?>
                                    <span class="badge badge-cho-khoa">Chờ Khoa duyệt</span>
                                <?php else: ?>
                                    <span class="badge badge-da-duyet">Đã hoàn tất</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($sv['trang_thai'] == 'cho_khoa_duyet'): ?>
                                    <a href="department_grading.php?sv_id=<?php echo $sv['id']; ?>&dot_id=<?php echo $dot_id; ?>" class="btn-action">Duyệt</a>
                                <?php elseif($sv['trang_thai'] == 'da_duyet'): ?>
                                    <a href="department_grading.php?sv_id=<?php echo $sv['id']; ?>&dot_id=<?php echo $dot_id; ?>" class="btn-view">Xem</a>
                                <?php else: ?>
                                    <span style="color: #94A3B8; font-size: 0.85rem;">Chưa thể duyệt</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<script>
    function filterStudent() {
        let input = document.getElementById('searchStudent').value.toLowerCase();
        let rows = document.querySelectorAll('.student-row');
        
        rows.forEach(row => {
            let msv = row.querySelector('.s-msv').innerText.toLowerCase();
            let name = row.querySelector('.s-name').innerText.toLowerCase();
            if (msv.includes(input) || name.includes(input)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
    </script>

<?php require_once 'layout_footer.php'; ?>
