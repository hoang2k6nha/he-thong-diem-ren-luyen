<?php
require_once 'config.php';

// Kiểm tra quyền Admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && !has_permission('manage_criteria'))) {
    redirect('index.php');
}

$msg = '';
$msg_type = 'success';

// Xử lý Form Submit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // Thêm/Sửa Nhóm
    if ($action == 'save_group') {
        $id = (int)$_POST['group_id'];
        $ten_nhom = $conn->real_escape_string($_POST['ten_nhom']);
        $diem_toi_da = (int)$_POST['diem_toi_da'];
        
        // Kiểm tra tổng điểm các nhóm không được vượt quá 100
        $res_sum = $conn->query("SELECT SUM(diem_toi_da) as total FROM nhom_tieu_chi WHERE id != $id");
        $total_other = ($res_sum && $r = $res_sum->fetch_assoc()) ? (int)$r['total'] : 0;
        
        if ($total_other + $diem_toi_da > 100) {
            $msg = "Lỗi: Tổng điểm tối đa của tất cả các nhóm hiện tại là $total_other. Nếu thêm $diem_toi_da đ sẽ vượt quá 100 điểm!";
            $msg_type = 'error';
        } else {
            if ($id > 0) {
                // Trước khi sửa, cần kiểm tra xem điểm tối đa mới của nhóm có nhỏ hơn tổng điểm các tiêu chí con hiện có không
                $res_con = $conn->query("SELECT SUM(diem_toi_da) as total_con FROM tieu_chi WHERE nhom_id = $id");
                $total_con = ($res_con && $r_con = $res_con->fetch_assoc()) ? (int)$r_con['total_con'] : 0;
                
                if ($diem_toi_da < $total_con) {
                    $msg = "Lỗi: Nhóm này đang chứa các tiêu chí con có tổng điểm là $total_con đ. Bạn không thể giảm điểm tối đa của nhóm xuống $diem_toi_da đ trừ khi sửa/xóa các tiêu chí con trước.";
                    $msg_type = 'error';
                } else {
                    $sql = "UPDATE nhom_tieu_chi SET ten_nhom = '$ten_nhom', diem_toi_da = $diem_toi_da WHERE id = $id";
                    $conn->query($sql);
                    $msg = "Đã cập nhật Nhóm Tiêu Chí.";
                }
            } else {
                $res = $conn->query("SELECT MAX(thu_tu) as max_tt FROM nhom_tieu_chi");
                $thu_tu = ($res && $r = $res->fetch_assoc()) ? ((int)$r['max_tt'] + 1) : 1;
                
                $sql = "INSERT INTO nhom_tieu_chi (ten_nhom, diem_toi_da, thu_tu) VALUES ('$ten_nhom', $diem_toi_da, $thu_tu)";
                $conn->query($sql);
                $msg = "Đã thêm Nhóm Tiêu Chí mới.";
            }
        }
    }
    
    // Thêm/Sửa Tiêu Chí
    if ($action == 'save_criteria') {
        $id = (int)$_POST['tc_id'];
        $nhom_id = (int)$_POST['nhom_id'];
        $noi_dung = $conn->real_escape_string($_POST['noi_dung']);
        $diem_toi_da = (int)$_POST['diem_toi_da'];
        $diem_mac_dinh = (int)$_POST['diem_mac_dinh'];
        
        // Lấy điểm tối đa của nhóm
        $res_nhom = $conn->query("SELECT diem_toi_da FROM nhom_tieu_chi WHERE id = $nhom_id");
        $max_nhom = ($res_nhom && $r_nhom = $res_nhom->fetch_assoc()) ? (int)$r_nhom['diem_toi_da'] : 0;
        
        // Tính tổng điểm các tiêu chí khác trong nhóm này
        $res_tc = $conn->query("SELECT SUM(diem_toi_da) as total_tc FROM tieu_chi WHERE nhom_id = $nhom_id AND id != $id");
        $total_other_tc = ($res_tc && $r_tc = $res_tc->fetch_assoc()) ? (int)$r_tc['total_tc'] : 0;
        
        if ($total_other_tc + $diem_toi_da > $max_nhom) {
            $msg = "Lỗi: Nhóm này có giới hạn tối đa là $max_nhom đ. Các tiêu chí khác đã chiếm $total_other_tc đ. Bạn chỉ có thể nhập tối đa " . ($max_nhom - $total_other_tc) . " đ cho tiêu chí này.";
            $msg_type = 'error';
        } else {
            if ($id > 0) {
                $sql = "UPDATE tieu_chi SET noi_dung = '$noi_dung', diem_toi_da = $diem_toi_da, diem_mac_dinh = $diem_mac_dinh WHERE id = $id";
                $msg = "Đã cập nhật Tiêu Chí.";
            } else {
                $sql = "INSERT INTO tieu_chi (nhom_id, noi_dung, diem_toi_da, diem_mac_dinh) VALUES ($nhom_id, '$noi_dung', $diem_toi_da, $diem_mac_dinh)";
                $msg = "Đã thêm Tiêu Chí mới vào nhóm.";
            }
            $conn->query($sql);
        }
    }
}

// Xóa GET
if (isset($_GET['delete_group'])) {
    $id = (int)$_GET['delete_group'];
    $conn->query("DELETE FROM nhom_tieu_chi WHERE id = $id");
    redirect('admin_criteria.php');
}
if (isset($_GET['delete_tc'])) {
    $id = (int)$_GET['delete_tc'];
    $conn->query("DELETE FROM tieu_chi WHERE id = $id");
    redirect('admin_criteria.php');
}

// Load Dữ liệu
$groups = [];
$res = $conn->query("SELECT * FROM nhom_tieu_chi ORDER BY thu_tu ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $g_id = $row['id'];
        $tc_res = $conn->query("SELECT * FROM tieu_chi WHERE nhom_id = $g_id");
        $tieu_chis = [];
        if ($tc_res) {
            while ($tc = $tc_res->fetch_assoc()) {
                $tieu_chis[] = $tc;
            }
        }
        $row['tieu_chis'] = $tieu_chis;
        $groups[] = $row;
    }
}

$page_title = 'Cấu Hình Bộ Tiêu Chí - Admin';
require_once 'layout_header.php';
?>

<?php if($msg): ?>
        <div class="alert"><i class="fas fa-check-circle"></i> <?php echo $msg; ?></div>
    <?php endif; ?>

    <div class="header-actions">
        <div>
            <h2 style="color: var(--primary-blue);">Danh sách Nội dung rèn luyện</h2>
            <p style="color: #64748B; font-size: 0.9rem; margin-top: 5px;">Thêm, sửa, xóa các nhóm và các tiêu chí chấm điểm.</p>
        </div>
        <button class="btn btn-primary" onclick="openGroupModal(0, '', 0)"><i class="fas fa-plus"></i> Thêm Nhóm Mới</button>
    </div>

    <?php foreach($groups as $g): ?>
        <div class="group-card">
            <div class="group-header">
                <div class="group-title">
                    <?php echo htmlspecialchars($g['ten_nhom']); ?> 
                    <span style="background: var(--primary-blue); color: white; padding: 2px 8px; border-radius: 20px; font-size: 0.8rem; margin-left: 10px;">Max: <?php echo $g['diem_toi_da']; ?>đ</span>
                </div>
                <div>
                    <button class="btn btn-success" style="padding: 5px 10px; font-size: 0.8rem;" onclick="openTcModal(0, <?php echo $g['id']; ?>, '', 0, 0)"><i class="fas fa-plus"></i> Thêm nội dung</button>
                    <button class="btn btn-warning" style="padding: 5px 10px; font-size: 0.8rem;" onclick="openGroupModal(<?php echo $g['id']; ?>, '<?php echo htmlspecialchars(addslashes($g['ten_nhom'])); ?>', <?php echo $g['diem_toi_da']; ?>)"><i class="fas fa-edit"></i> Sửa nhóm</button>
                    <a href="?delete_group=<?php echo $g['id']; ?>" class="btn btn-danger" style="padding: 5px 10px; font-size: 0.8rem;" onclick="return confirm('Xóa nhóm này sẽ xóa toàn bộ nội dung bên trong. Chắc chắn xóa?');"><i class="fas fa-trash"></i> Xóa nhóm</a>
                </div>
            </div>
            
            <?php if(!empty($g['tieu_chis'])): ?>
            <table>
                <thead>
                    <tr>
                        <th>Nội dung chi tiết</th>
                        <th class="col-diem">Đ. Tối Đa</th>
                        <th class="col-diem">Đ. Mặc Định</th>
                        <th class="col-actions">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($g['tieu_chis'] as $tc): ?>
                    <tr>
                        <td><?php echo nl2br(htmlspecialchars($tc['noi_dung'])); ?></td>
                        <td class="col-diem font-weight-bold"><?php echo $tc['diem_toi_da']; ?></td>
                        <td class="col-diem"><?php echo $tc['diem_mac_dinh']; ?></td>
                        <td class="col-actions">
                            <button class="btn btn-warning" style="padding: 4px 8px; font-size: 0.75rem;" 
                                onclick="openTcModal(<?php echo $tc['id']; ?>, <?php echo $tc['nhom_id']; ?>, `<?php echo htmlspecialchars(addslashes($tc['noi_dung'])); ?>`, <?php echo $tc['diem_toi_da']; ?>, <?php echo $tc['diem_mac_dinh']; ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="?delete_tc=<?php echo $tc['id']; ?>" class="btn btn-danger" style="padding: 4px 8px; font-size: 0.75rem;" onclick="return confirm('Bạn có chắc muốn xóa nội dung này?');">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <div style="padding: 20px; text-align: center; color: #64748B;">Chưa có nội dung rèn luyện nào trong nhóm này.</div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

</div>

<!-- Modal Nhóm -->
<div class="modal" id="modalGroup">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalGroupTitle">Thêm Nhóm Mới</h3>
            <button class="close-btn" onclick="closeModal('modalGroup')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="save_group">
            <input type="hidden" name="group_id" id="inp_group_id" value="0">
            
            <div class="form-group">
                <label>Tên Nhóm Tiêu Chí <span style="color: red;">*</span></label>
                <input type="text" name="ten_nhom" id="inp_ten_nhom" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Điểm Tối Đa Khung Nhóm <span style="color: red;">*</span></label>
                <input type="number" name="diem_toi_da" id="inp_group_diem" class="form-control" required>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalGroup')">Hủy</button>
                <button type="submit" class="btn btn-primary">Lưu Thay Đổi</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Tiêu Chí -->
<div class="modal" id="modalTc">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTcTitle">Thêm Nội Dung Rèn Luyện</h3>
            <button class="close-btn" onclick="closeModal('modalTc')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="save_criteria">
            <input type="hidden" name="tc_id" id="inp_tc_id" value="0">
            <input type="hidden" name="nhom_id" id="inp_tc_nhom_id" value="0">
            
            <div class="form-group">
                <label>Nội dung tiêu chí <span style="color: red;">*</span></label>
                <textarea name="noi_dung" id="inp_tc_noi_dung" class="form-control" rows="4" required></textarea>
            </div>
            <div style="display: flex; gap: 15px;">
                <div class="form-group" style="flex: 1;">
                    <label>Điểm Tối Đa <span style="color: red;">*</span></label>
                    <input type="number" name="diem_toi_da" id="inp_tc_diem_max" class="form-control" required>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Điểm Mặc Định (Nếu có) <span style="color: red;">*</span></label>
                    <input type="number" name="diem_mac_dinh" id="inp_tc_diem_def" class="form-control" value="0" required>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalTc')">Hủy</button>
                <button type="submit" class="btn btn-primary">Lưu Thay Đổi</button>
            </div>
        </form>
    </div>
</div>

<script>
    function closeModal(id) {
        document.getElementById(id).classList.remove('active');
    }
    
    function openGroupModal(id, ten, diem) {
        document.getElementById('modalGroupTitle').innerText = id ? "Sửa Nhóm" : "Thêm Nhóm Mới";
        document.getElementById('inp_group_id').value = id;
        document.getElementById('inp_ten_nhom').value = ten;
        document.getElementById('inp_group_diem').value = diem;
        document.getElementById('modalGroup').classList.add('active');
    }
    
    function openTcModal(id, nhom_id, noi_dung, diem_max, diem_def) {
        document.getElementById('modalTcTitle').innerText = id ? "Sửa Nội Dung Rèn Luyện" : "Thêm Nội Dung Rèn Luyện";
        document.getElementById('inp_tc_id').value = id;
        document.getElementById('inp_tc_nhom_id').value = nhom_id;
        document.getElementById('inp_tc_noi_dung').value = noi_dung;
        document.getElementById('inp_tc_diem_max').value = diem_max;
        document.getElementById('inp_tc_diem_def').value = diem_def;
        document.getElementById('modalTc').classList.add('active');
    }
</script>

<?php require_once 'layout_footer.php'; ?>
