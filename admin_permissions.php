<?php
require_once 'config.php';

// Kiểm tra quyền Admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && !has_permission('manage_permissions'))) {
    redirect('index.php');
}

// Xử lý Phân quyền (AJAX)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_permission_ajax') {
    $uid = (int)$_POST['user_id'];
    $perm = $conn->real_escape_string($_POST['permission']);
    $is_checked = $_POST['is_checked'] === 'true';

    if ($uid === -1) {
        // Cấp/hủy quyền cho TẤT CẢ sinh viên
        $res = $conn->query("SELECT id, permissions FROM tai_khoan WHERE vai_tro = 'sinh_vien'");
        while ($row = $res->fetch_assoc()) {
            $sv_id = $row['id'];
            $perms = $row['permissions'] ? explode(',', $row['permissions']) : [];
            if ($is_checked) {
                if (!in_array($perm, $perms)) $perms[] = $perm;
            } else {
                $perms = array_filter($perms, fn($p) => $p !== $perm);
            }
            $new_perms = $conn->real_escape_string(implode(',', $perms));
            $conn->query("UPDATE tai_khoan SET permissions = '$new_perms' WHERE id = $sv_id");
        }
        echo json_encode(['success' => true]);
        exit();
    } else {
        $res = $conn->query("SELECT permissions FROM tai_khoan WHERE id = $uid");
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $perms = $row['permissions'] ? explode(',', $row['permissions']) : [];
            if ($is_checked) {
                if (!in_array($perm, $perms)) $perms[] = $perm;
            } else {
                $perms = array_filter($perms, fn($p) => $p !== $perm);
            }
            $new_perms = $conn->real_escape_string(implode(',', $perms));
            $conn->query("UPDATE tai_khoan SET permissions = '$new_perms' WHERE id = $uid");
            echo json_encode(['success' => true]);
            exit();
        }
    }
}

// Lấy danh sách users (ngoại trừ sinh viên, sinh viên sẽ gộp chung)
$users = [];

// Thêm thẻ Đại diện cho toàn bộ Sinh Viên lên đầu
$res_sv = $conn->query("SELECT COUNT(id) as total FROM tai_khoan WHERE vai_tro = 'sinh_vien'");
$total_sv = 0;
if ($res_sv && $res_sv->num_rows > 0) {
    $total_sv = $res_sv->fetch_assoc()['total'];
}

// Lấy danh sách quyền hiện tại của sinh viên để hiển thị đúng trạng thái (vì quản lý chung nên lấy của 1 SV bất kỳ làm đại diện)
$res_sv_perms = $conn->query("SELECT permissions FROM tai_khoan WHERE vai_tro = 'sinh_vien' LIMIT 1");
$bulk_sv_perms = '';
if ($res_sv_perms && $res_sv_perms->num_rows > 0) {
    $bulk_sv_perms = $res_sv_perms->fetch_assoc()['permissions'];
}

$users[] = [
    'id' => -1,
    'username' => 'ALL_STUDENTS',
    'ho_ten' => 'Áp dụng chung cho tất cả Sinh Viên',
    'vai_tro' => 'sinh_vien',
    'permissions' => $bulk_sv_perms
];

$res_users = $conn->query("SELECT id, username, ho_ten, vai_tro, permissions FROM tai_khoan WHERE vai_tro != 'sinh_vien' ORDER BY vai_tro, id DESC");
while ($u = $res_users->fetch_assoc()) {
    $users[] = $u;
}

// Định nghĩa các nhóm tính năng
$feature_groups = [
    'admin' => [
        'title' => 'Quản Lý Hệ Thống',
        'icon' => 'fa-cogs',
        'color' => '#B91C1C',
        'bg' => '#FEE2E2',
        'features' => [
            'manage_cycles' => 'Đợt Đánh Giá',
            'manage_criteria' => 'Cấu hình Tiêu Chí',
            'manage_accounts' => 'Tài Khoản',
            'manage_permissions' => 'Phân Quyền',
            'manage_assignments' => 'Phân Công CVHT'
        ]
    ],
    'khoa' => [
        'title' => 'Khoa / CTSV',
        'icon' => 'fa-building',
        'color' => '#B45309',
        'bg' => '#FEF9C3',
        'features' => [
            'grade_department' => 'Duyệt Điểm Khoa',
            'view_reports_department' => 'Báo Cáo Thống Kê',
            'department_complaints' => 'Giải quyết Khiếu Nại',
            'notifications' => 'Phát Thông Báo',
            'import_grades' => 'Import Điểm Học Tập'
        ]
    ],
    'cvht' => [
        'title' => 'Cố Vấn Học Tập',
        'icon' => 'fa-chalkboard-teacher',
        'color' => '#1D4ED8',
        'bg' => '#DBEAFE',
        'features' => [
            'advisor_manage_class' => 'Quản Lý Lớp',
            'grade_advisor' => 'Duyệt Điểm Lớp',
            'view_reports_advisor' => 'Báo Cáo Lớp',
            'advisor_complaints' => 'Giải quyết KN (Lớp)'
        ]
    ],
    'sinh_vien' => [
        'title' => 'Sinh Viên',
        'icon' => 'fa-user-graduate',
        'color' => '#0F766E',
        'bg' => '#CCFBF1',
        'features' => [
            'student_history' => 'Lịch Sử Điểm',
            'student_complaints' => 'Hỏi Đáp / Khiếu Nại'
        ]
    ]
];

$page_title = 'Phân Quyền Tính Năng - Admin';
require_once 'layout_header.php';
?>

<div class="page-header">
            <h2 class="page-title"><i class="fas fa-check-square" style="color: var(--primary-yellow);"></i> Cấp quyền sử dụng tính năng</h2>
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" onkeyup="searchUsers()" placeholder="Tìm kiếm tài khoản, họ tên...">
            </div>
        </div>
        
        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('all')">Tất cả</button>
            <button class="tab-btn" onclick="switchTab('cvht')">Cố Vấn Học Tập</button>
            <button class="tab-btn" onclick="switchTab('khoa')">Khoa / CTSV</button>
            <button class="tab-btn" onclick="switchTab('admin')">Admin</button>
            <button class="tab-btn" onclick="switchTab('sinh_vien')">Sinh Viên</button>
        </div>
        
        <div class="user-list" id="userList">
            <?php if(empty($users)): ?>
                <div style="text-align: center; padding: 3rem; color: #64748B; background: white; border-radius: 12px;">Không có tài khoản nào trong hệ thống.</div>
            <?php endif; ?>

            <?php foreach($users as $u): 
                $user_perms = $u['permissions'] ? explode(',', $u['permissions']) : [];
                $initial = strtoupper(mb_substr($u['ho_ten'], 0, 1, "UTF-8"));
                
                // Set avatar gradient based on role
                $grad = "linear-gradient(135deg, var(--primary-blue), #3B82F6)";
                if($u['vai_tro'] == 'admin') $grad = "linear-gradient(135deg, #DC2626, #EF4444)";
                if($u['vai_tro'] == 'khoa') $grad = "linear-gradient(135deg, #D97706, #F59E0B)";
                if($u['vai_tro'] == 'cvht') $grad = "linear-gradient(135deg, #2563EB, #60A5FA)";
                if($u['vai_tro'] == 'sinh_vien') $grad = "linear-gradient(135deg, #0F766E, #14B8A6)";
            ?>
            <div class="user-card role-<?php echo $u['vai_tro']; ?>" data-name="<?php echo strtolower($u['username'] . ' ' . $u['ho_ten']); ?>">
                <div class="user-header" onclick="toggleDetails(this)">
                    <div class="user-info">
                        <div class="user-avatar" style="background: <?php echo $grad; ?>;"><?php echo $initial; ?></div>
                        <div>
                            <h4 class="u-name"><?php echo htmlspecialchars($u['username']); ?></h4>
                            <p class="f-name"><?php echo htmlspecialchars($u['ho_ten']); ?></p>
                        </div>
                    </div>
                    <div class="user-meta">
                        <span class="role-badge badge-<?php echo $u['vai_tro']; ?>">
                            <?php 
                                if($u['vai_tro'] == 'admin') echo 'ADMIN';
                                elseif($u['vai_tro'] == 'khoa') echo 'KHOA / CTSV';
                                elseif($u['vai_tro'] == 'cvht') echo 'CVHT';
                                else echo 'SINH VIÊN';
                            ?>
                        </span>
                        <i class="fas fa-chevron-down expand-icon"></i>
                    </div>
                </div>
                
                <div class="user-details">
                    <div class="details-inner">
                        <div class="perm-grid">
                            <?php foreach($feature_groups as $group_id => $group): 
                                // KHÔNG hiển thị các tính năng mặc định của chính vai trò người dùng này (vì họ đã có sẵn)
                                if ($group_id === $u['vai_tro']) continue;
                            ?>
                            <div class="perm-group">
                                <h5 style="color: <?php echo $group['color']; ?>;">
                                    <i class="fas <?php echo $group['icon']; ?>" style="background: <?php echo $group['color']; ?>;"></i> 
                                    <?php echo $group['title']; ?>
                                </h5>
                                <div class="perm-items">
                                    <?php foreach($group['features'] as $f_key => $f_label): ?>
                                    <div class="perm-item">
                                        <span><?php echo $f_label; ?></span>
                                        <label class="toggle-switch">
                                            <input type="checkbox" class="perm-checkbox" data-uid="<?php echo $u['id']; ?>" data-perm="<?php echo $f_key; ?>" <?php echo in_array($f_key, $user_perms) ? 'checked' : ''; ?>>
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast"><i class="fas fa-check-circle"></i> Đã lưu quyền thành công!</div>

<script>
    function toggleDetails(headerElement) {
        const card = headerElement.closest('.user-card');
        card.classList.toggle('expanded');
    }

    function switchTab(role) {
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        event.currentTarget.classList.add('active');
        
        filterUsers();
    }

    function searchUsers() {
        filterUsers();
    }
    
    function filterUsers() {
        const input = document.getElementById('searchInput').value.toLowerCase();
        let activeRole = 'all';
        
        document.querySelectorAll('.tab-btn').forEach(btn => {
            if(btn.classList.contains('active')) {
                if(btn.innerText.includes('Cố Vấn')) activeRole = 'cvht';
                else if(btn.innerText.includes('Khoa')) activeRole = 'khoa';
                else if(btn.innerText.includes('Sinh Viên')) activeRole = 'sinh_vien';
                else if(btn.innerText.includes('Admin')) activeRole = 'admin';
            }
        });

        document.querySelectorAll('.user-card').forEach(card => {
            const matchesRole = (activeRole === 'all' || card.classList.contains('role-' + activeRole));
            const matchesSearch = card.getAttribute('data-name').includes(input);
            
            if (matchesRole && matchesSearch) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }

    // Handle AJAX Checkbox
    document.querySelectorAll('.perm-checkbox').forEach(cb => {
        cb.addEventListener('change', function() {
            const uid = this.getAttribute('data-uid');
            const perm = this.getAttribute('data-perm');
            const is_checked = this.checked;
            
            const formData = new FormData();
            formData.append('action', 'update_permission_ajax');
            formData.append('user_id', uid);
            formData.append('permission', perm);
            formData.append('is_checked', is_checked);
            
            fetch('admin_permissions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    showToast();
                }
            })
            .catch(err => {
                console.error('Error updating permission', err);
                alert("Có lỗi xảy ra khi lưu quyền!");
                this.checked = !is_checked; // revert
            });
        });
    });

    let toastTimeout;
    function showToast() {
        const toast = document.getElementById('toast');
        toast.classList.add('show');
        
        clearTimeout(toastTimeout);
        toastTimeout = setTimeout(() => {
            toast.classList.remove('show');
        }, 2000);
    }
    </script>

<?php require_once 'layout_footer.php'; ?>
