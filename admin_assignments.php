<?php
require_once 'config.php';

// Kiểm tra quyền Admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && !has_permission('manage_assignments'))) {
    redirect('index.php');
}

// Lấy danh sách đợt đánh giá
$res_dots = $conn->query("SELECT * FROM dot_danh_gia ORDER BY id DESC");
$dots = [];
while ($row = $res_dots->fetch_assoc()) {
    $dots[] = $row;
}

$current_dot_id = isset($_GET['dot_id']) ? (int)$_GET['dot_id'] : ($dots[0]['id'] ?? 0);

// Xử lý Phân công CVHT (Cập nhật 1 lớp)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'assign') {
    $lop_id = (int)$_POST['lop_id'];
    $cvht_id = (int)$_POST['cvht_id'];
    $dot_id = (int)$_POST['dot_id'];
    
    // Nếu chọn "Trống", xoá phân công
    if ($cvht_id == 0) {
        $conn->query("DELETE FROM phan_cong_cvht WHERE dot_id = $dot_id AND lop_id = $lop_id");
    } else {
        $check = $conn->query("SELECT id FROM phan_cong_cvht WHERE dot_id = $dot_id AND lop_id = $lop_id");
        if ($check && $check->num_rows > 0) {
            $conn->query("UPDATE phan_cong_cvht SET cvht_id = $cvht_id WHERE dot_id = $dot_id AND lop_id = $lop_id");
        } else {
            $conn->query("INSERT INTO phan_cong_cvht (dot_id, lop_id, cvht_id) VALUES ($dot_id, $lop_id, $cvht_id)");
        }
    }
    
    // Auto-update lop_hoc.cvht_id as a fallback (optional, for backwards compatibility if needed)
    if ($cvht_id > 0 && $dot_id == ($dots[0]['id'] ?? 0)) {
        $conn->query("UPDATE lop_hoc SET cvht_id = $cvht_id WHERE id = $lop_id");
    }
    
    echo json_encode(['success' => true]);
    exit();
}

// Lấy danh sách toàn bộ Lớp
$res_lop = $conn->query("SELECT l.id, l.ma_lop, l.ten_lop, k.ten_khoa 
                         FROM lop_hoc l 
                         LEFT JOIN khoa k ON l.khoa_id = k.id 
                         ORDER BY k.id, l.ten_lop");
$classes = [];
while ($row = $res_lop->fetch_assoc()) {
    $classes[] = $row;
}

// Lấy danh sách toàn bộ CVHT
$res_cvht = $conn->query("SELECT id, ho_ten, username FROM tai_khoan WHERE vai_tro = 'cvht' ORDER BY ho_ten");
$cvhts = [];
while ($row = $res_cvht->fetch_assoc()) {
    $cvhts[] = $row;
}

// Lấy danh sách phân công hiện tại cho đợt được chọn
$assignments = [];
if ($current_dot_id) {
    $res_assign = $conn->query("SELECT lop_id, cvht_id FROM phan_cong_cvht WHERE dot_id = $current_dot_id");
    while ($row = $res_assign->fetch_assoc()) {
        $assignments[$row['lop_id']] = $row['cvht_id'];
    }
}

$page_title = 'Phân Công CVHT - Admin';
require_once 'layout_header.php';
?>

<div class="alert alert-info">
            <i class="fas fa-info-circle"></i> 
            Phân công CVHT được lưu theo từng Đợt Đánh Giá (Học Kỳ). Sinh viên sẽ làm phiếu dưới sự quản lý của CVHT được phân công trong đợt đó. Lịch sử các đợt trước sẽ không bị thay đổi.
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-list"></i> Danh sách Lớp Học & CVHT</h3>
                <form id="dotForm" method="GET" style="display: flex; gap: 10px; align-items: center;">
                    <label for="dot_id" style="font-weight: 600; font-size: 0.95rem;">Chọn Đợt / Học Kỳ:</label>
                    <select name="dot_id" id="dot_id" class="form-control" onchange="document.getElementById('dotForm').submit()">
                        <?php foreach($dots as $d): ?>
                            <option value="<?php echo $d['id']; ?>" <?php echo $d['id'] == $current_dot_id ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($d['ten_dot']); ?> (HK<?php echo $d['hoc_ky']; ?> - <?php echo $d['nam_hoc']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            
            <div class="action-bar">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" onkeyup="searchTable()" placeholder="Tìm kiếm lớp học...">
                </div>
                <div style="font-size: 0.9rem; color: #64748B;">
                    <i class="fas fa-check"></i> Hệ thống tự động lưu khi chọn CVHT
                </div>
            </div>
            
            <div class="table-responsive">
                <table id="assignTable">
                    <thead>
                        <tr>
                            <th width="5%">STT</th>
                            <th width="20%">Khoa</th>
                            <th width="15%">Mã Lớp</th>
                            <th width="25%">Tên Lớp</th>
                            <th width="15%" style="text-align: center;">Trạng Thái</th>
                            <th width="20%">Phân Công CVHT</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($classes as $index => $lop): 
                            $assigned_cvht_id = isset($assignments[$lop['id']]) ? $assignments[$lop['id']] : 0;
                        ?>
                        <tr class="lop-row">
                            <td style="text-align:center;"><?php echo $index + 1; ?></td>
                            <td><?php echo htmlspecialchars($lop['ten_khoa']); ?></td>
                            <td><strong style="color: var(--primary-blue);" class="l-ma"><?php echo htmlspecialchars($lop['ma_lop']); ?></strong></td>
                            <td class="l-ten"><?php echo htmlspecialchars($lop['ten_lop']); ?></td>
                            <td style="text-align: center;">
                                <?php if($assigned_cvht_id > 0): ?>
                                    <span class="status-badge status-assigned" id="status_<?php echo $lop['id']; ?>">Đã phân công</span>
                                <?php else: ?>
                                    <span class="status-badge status-unassigned" id="status_<?php echo $lop['id']; ?>">Chưa phân công</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <select class="cvht-select" data-lop-id="<?php echo $lop['id']; ?>">
                                    <option value="0">--- Trống (Chưa gán) ---</option>
                                    <?php foreach($cvhts as $c): ?>
                                        <option value="<?php echo $c['id']; ?>" <?php echo $c['id'] == $assigned_cvht_id ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($c['ho_ten']); ?> (<?php echo htmlspecialchars($c['username']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<script>
    function searchTable() {
        let input = document.getElementById('searchInput').value.toLowerCase();
        let rows = document.querySelectorAll('.lop-row');
        
        rows.forEach(row => {
            let ma = row.querySelector('.l-ma').innerText.toLowerCase();
            let ten = row.querySelector('.l-ten').innerText.toLowerCase();
            if (ma.includes(input) || ten.includes(input)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    document.querySelectorAll('.cvht-select').forEach(sel => {
        sel.addEventListener('change', function() {
            const lop_id = this.getAttribute('data-lop-id');
            const cvht_id = this.value;
            const dot_id = document.getElementById('dot_id').value;
            
            const formData = new FormData();
            formData.append('action', 'assign');
            formData.append('lop_id', lop_id);
            formData.append('cvht_id', cvht_id);
            formData.append('dot_id', dot_id);
            
            fetch('admin_assignments.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    let tr = this.closest('tr');
                    tr.style.backgroundColor = '#f0fdf4';
                    setTimeout(() => tr.style.backgroundColor = '', 500);
                    
                    let statusBadge = document.getElementById('status_' + lop_id);
                    if (cvht_id > 0) {
                        statusBadge.className = 'status-badge status-assigned';
                        statusBadge.innerText = 'Đã phân công';
                    } else {
                        statusBadge.className = 'status-badge status-unassigned';
                        statusBadge.innerText = 'Chưa phân công';
                    }
                }
            })
            .catch(err => console.error('Error updating assignment', err));
        });
    });
    </script>

<?php require_once 'layout_footer.php'; ?>
