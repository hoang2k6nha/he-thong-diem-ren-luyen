<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'cvht' && !has_permission('advisor_manage_class'))) redirect('index.php');

$lop_id = isset($_GET['lop_id']) ? (int)$_GET['lop_id'] : 0;
if (!$lop_id) redirect('advisor_manage_class.php');

$cvht_id = $_SESSION['user_id'];
$check = $conn->query("SELECT * FROM lop_hoc WHERE id = $lop_id AND " . ($_SESSION["role"] !== "cvht" ? "1=1" : "cvht_id = $cvht_id") . "");
if ($check->num_rows == 0) redirect('advisor_manage_class.php');
$lop = $check->fetch_assoc();

$sql_sv = "SELECT username, ho_ten, trang_thai FROM tai_khoan WHERE lop_id = $lop_id AND vai_tro = 'sinh_vien' ORDER BY username ASC";
$res_sv = $conn->query($sql_sv);
$students = [];
if ($res_sv) {
    while($row = $res_sv->fetch_assoc()) $students[] = $row;
}

$page_title = 'Danh Sách Sinh Viên - CVHT';
require_once 'layout_header.php';
?>

<div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; margin-bottom: 15px;">
                <h2 style="color: var(--primary-blue); margin-bottom: 0;">Danh Sách Lớp</h2>
            </div>
            
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchStudent" onkeyup="filterStudent()" placeholder="Tìm kiếm theo mã sinh viên, họ tên...">
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th width="10%">STT</th>
                            <th width="30%">Mã SV (Username)</th>
                            <th width="40%">Họ Tên</th>
                            <th width="20%">Trạng Thái TK</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($students as $index => $sv): ?>
                        <tr class="student-row">
                            <td><?php echo $index + 1; ?></td>
                            <td style="font-weight: 600;" class="s-msv"><?php echo htmlspecialchars($sv['username']); ?></td>
                            <td class="s-name"><?php echo htmlspecialchars($sv['ho_ten']); ?></td>
                            <td>
                                <?php if($sv['trang_thai'] == 1): ?>
                                    <span class="badge badge-active">Hoạt động</span>
                                <?php else: ?>
                                    <span class="badge badge-inactive">Bị khóa</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(count($students) == 0): ?>
                            <tr><td colspan="4" style="text-align: center; color: #64748B;">Chưa có sinh viên nào trong lớp này.</td></tr>
                        <?php endif; ?>
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
