<?php
require_once 'config.php';

// Kiểm tra quyền Admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && !has_permission('manage_accounts'))) {
    redirect('index.php');
}

$msg = '';
$msg_type = 'success';

// Thêm tài khoản
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_account') {
    $username = $conn->real_escape_string($_POST['username']);
    $ho_ten = $conn->real_escape_string($_POST['ho_ten']);
    $vai_tro = $conn->real_escape_string($_POST['vai_tro']);
    $password = md5('123456');
    
    $check = $conn->query("SELECT id FROM tai_khoan WHERE username = '$username'");
    if ($check->num_rows > 0) {
        $msg = "Tên đăng nhập đã tồn tại!"; $msg_type = 'error';
    } else {
        $sql = "INSERT INTO tai_khoan (username, password, ho_ten, vai_tro, trang_thai) VALUES ('$username', '$password', '$ho_ten', '$vai_tro', 'hoat_dong')";
        if ($conn->query($sql)) {
            $msg = "Đã tạo tài khoản $username thành công!";
        } else {
            $msg = "Lỗi khi tạo tài khoản."; $msg_type = 'error';
        }
    }
}

// Khóa / Mở khóa tài khoản (AJAX)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajax_action'])) {
    $id = (int)$_POST['id'];
    if ($_POST['ajax_action'] == 'lock') {
        $conn->query("UPDATE tai_khoan SET trang_thai = 0 WHERE id = $id AND vai_tro != 'admin'");
        echo json_encode(['success' => true, 'new_status' => 0]);
        exit;
    } elseif ($_POST['ajax_action'] == 'unlock') {
        $conn->query("UPDATE tai_khoan SET trang_thai = 1 WHERE id = $id");
        echo json_encode(['success' => true, 'new_status' => 1]);
        exit;
    }
}

$page_title = 'Quản lý Tài Khoản - Admin';
require_once 'layout_header.php';
?>

<?php if($msg): ?>
            <div class="alert alert-<?php echo $msg_type; ?>"><?php echo $msg; ?></div>
        <?php endif; ?>

        <div class="card" id="formCard" style="display: none; background: #EFF6FF; border-color: #BFDBFE;">
            <h3 style="color: var(--primary-blue); margin-bottom: 15px;"><i class="fas fa-user-plus"></i> Tạo tài khoản mới</h3>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_account">
                <div class="form-row">
                    <div class="form-group">
                        <label>Vai trò (Phân quyền)</label>
                        <select name="vai_tro" class="form-control" required>
                            <option value="sinh_vien">Sinh viên</option>
                            <option value="cvht">Cố vấn học tập</option>
                            <option value="khoa">Cộng tác viên (Khoa/CTSV)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tên đăng nhập (Mã SV/Mã GV)</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Họ và Tên</label>
                        <input type="text" name="ho_ten" class="form-control" required>
                    </div>
                </div>
                <div style="margin-top: 15px;">
                    <button type="submit" class="btn btn-primary">Xác nhận tạo</button>
                    <button type="button" class="btn" style="background: #94A3B8;" onclick="document.getElementById('formCard').style.display='none';">Hủy</button>
                    <span style="margin-left: 10px; font-size: 0.85rem; color: #64748B;">* Mật khẩu mặc định: 123456</span>
                </div>
            </form>
        </div>

        <div class="card">
            <div class="tabs">
                <div class="tab-btn active" onclick="switchTab('sinh_vien')">Sinh Viên</div>
                <div class="tab-btn" onclick="switchTab('cvht')">Cố Vấn Học Tập</div>
                <div class="tab-btn" onclick="switchTab('khoa')">Khoa / CTSV</div>
                <div class="tab-btn" onclick="switchTab('admin')">Admin</div>
            </div>

            <div class="action-bar">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" onkeyup="searchTable()" placeholder="Tìm kiếm tài khoản, họ tên...">
                </div>
                <button class="btn btn-primary" onclick="document.getElementById('formCard').style.display='block';"><i class="fas fa-plus"></i> Thêm tài khoản</button>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Tên Đăng Nhập</th>
                            <th>Họ và Tên</th>
                            <th>Vai Trò</th>
                            <th>Trạng Thái</th>
                            <th>Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $acc_query = $conn->query("SELECT * FROM tai_khoan ORDER BY vai_tro, id DESC");
                        while($a = $acc_query->fetch_assoc()):
                        ?>
                        <tr class="account-row role-<?php echo $a['vai_tro']; ?>">
                            <td style="font-weight: 600;"><?php echo htmlspecialchars($a['username']); ?></td>
                            <td><?php echo htmlspecialchars($a['ho_ten']); ?></td>
                            <td>
                                <?php 
                                    if($a['vai_tro'] == 'sinh_vien') echo 'Sinh Viên';
                                    elseif($a['vai_tro'] == 'cvht') echo 'CVHT';
                                    elseif($a['vai_tro'] == 'khoa') echo 'Khoa / CTSV';
                                    else echo 'Admin';
                                ?>
                            </td>
                            <td id="status_<?php echo $a['id']; ?>">
                                <?php if($a['trang_thai'] == 1): ?>
                                    <span class="badge badge-active">Hoạt động</span>
                                <?php else: ?>
                                    <span class="badge" style="background: #FEF2F2; color: #B91C1C;">Đã khóa</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="#" style="color: var(--primary-blue); margin-right: 15px;" title="Reset mật khẩu"><i class="fas fa-key"></i></a>
                                <?php if($a['vai_tro'] != 'admin'): ?>
                                    <?php if($a['trang_thai'] == 1): ?>
                                        <a href="javascript:void(0)" onclick="toggleLock(<?php echo $a['id']; ?>, 'lock', this)" style="color: #EF4444;" title="Khóa tài khoản"><i class="fas fa-lock"></i></a>
                                    <?php else: ?>
                                        <a href="javascript:void(0)" onclick="toggleLock(<?php echo $a['id']; ?>, 'unlock', this)" style="color: #10B981;" title="Mở khóa tài khoản"><i class="fas fa-unlock"></i></a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<script>
        function switchTab(role) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            event.currentTarget.classList.add('active');
            
            document.querySelectorAll('.account-row').forEach(row => {
                row.style.display = row.classList.contains('role-' + role) ? '' : 'none';
            });
            document.getElementById('searchInput').value = '';
        }
        
        function searchTable() {
            let input = document.getElementById('searchInput').value.toLowerCase();
            let activeRole = '';
            document.querySelectorAll('.tab-btn').forEach(btn => {
                if(btn.classList.contains('active')) {
                    if(btn.innerText.includes('Sinh Viên')) activeRole = 'sinh_vien';
                    else if(btn.innerText.includes('Cố Vấn')) activeRole = 'cvht';
                    else if(btn.innerText.includes('Khoa')) activeRole = 'khoa';
                    else activeRole = 'admin';
                }
            });

            document.querySelectorAll('.account-row').forEach(row => {
                if (row.classList.contains('role-' + activeRole)) {
                    let text = row.innerText.toLowerCase();
                    row.style.display = text.includes(input) ? '' : 'none';
                }
            });
        }
        function toggleLock(id, action, btnElement) {
            let confirmMsg = action === 'lock' ? 
                'Bạn có chắc chắn muốn khóa tài khoản này? Người dùng sẽ không thể đăng nhập.' : 
                'Mở khóa cho tài khoản này?';
                
            if(!confirm(confirmMsg)) return;
            
            const formData = new FormData();
            formData.append('ajax_action', action);
            formData.append('id', id);
            
            fetch('admin_accounts.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    let statusTd = document.getElementById('status_' + id);
                    if(data.new_status === 0) {
                        statusTd.innerHTML = '<span class="badge" style="background: #FEF2F2; color: #B91C1C;">Đã khóa</span>';
                        btnElement.setAttribute('onclick', `toggleLock(${id}, 'unlock', this)`);
                        btnElement.setAttribute('title', 'Mở khóa tài khoản');
                        btnElement.style.color = '#10B981';
                        btnElement.innerHTML = '<i class="fas fa-unlock"></i>';
                    } else {
                        statusTd.innerHTML = '<span class="badge badge-active">Hoạt động</span>';
                        btnElement.setAttribute('onclick', `toggleLock(${id}, 'lock', this)`);
                        btnElement.setAttribute('title', 'Khóa tài khoản');
                        btnElement.style.color = '#EF4444';
                        btnElement.innerHTML = '<i class="fas fa-lock"></i>';
                    }
                }
            });
        }
        
        document.addEventListener('DOMContentLoaded', () => switchTab('sinh_vien'));
    </script>

<?php require_once 'layout_footer.php'; ?>
