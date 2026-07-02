<?php
require_once 'config.php';

// Kiểm tra quyền Admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && !has_permission('manage_cycles'))) {
    redirect('index.php');
}

$msg = '';
$msg_type = 'success';

// Xử lý mở đợt đánh giá
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_cycle') {
    $ten_dot = $conn->real_escape_string($_POST['ten_dot']);
    $hoc_ky = (int)$_POST['hoc_ky'];
    $nam_hoc = $conn->real_escape_string($_POST['nam_hoc']);
    $ngay_bat_dau = $conn->real_escape_string($_POST['ngay_bat_dau']);
    $ngay_ket_thuc = $conn->real_escape_string($_POST['ngay_ket_thuc']);
    
    // Đóng tất cả đợt cũ
    $conn->query("UPDATE dot_danh_gia SET trang_thai = 'da_dong'");
    
    // Tạo đợt mới
    $sql = "INSERT INTO dot_danh_gia (ten_dot, hoc_ky, nam_hoc, ngay_bat_dau, ngay_ket_thuc, trang_thai) VALUES ('$ten_dot', $hoc_ky, '$nam_hoc', '$ngay_bat_dau', '$ngay_ket_thuc', 'dang_mo')";
    if ($conn->query($sql)) {
        $msg = "Đã mở đợt đánh giá mới thành công! Hệ thống đã tải Bộ tiêu chí mặc định.";
    } else {
        $msg = "Lỗi khi mở đợt đánh giá."; $msg_type = 'error';
    }
}

$page_title = 'Đợt Đánh Giá - Admin';
require_once 'layout_header.php';
?>

<?php if($msg): ?>
            <div class="alert alert-<?php echo $msg_type; ?>"><?php echo $msg; ?></div>
        <?php endif; ?>

        <div class="card">
            <h3 class="accordion-header" onclick="toggleAccordion('add')">
                <span><i class="fas fa-plus-circle"></i> Mở đợt đánh giá mới</span>
                <i class="fas fa-chevron-down icon-arrow" id="arrowAdd"></i>
            </h3>
            
            <div class="accordion-content" id="contentAdd" style="display: none;">
                <div class="info-box">
                    <i class="fas fa-info-circle"></i> <strong>Lưu ý:</strong> Mở đợt đánh giá mới sẽ tự động đóng các đợt cũ. Bộ tiêu chí đánh giá sẽ được tự động đồng bộ theo cấu trúc của file <strong>Mẫu ĐGRLHK1_NH26_27.xls</strong>.
                </div>
                
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add_cycle">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Tên đợt đánh giá <span style="color: red;">*</span></label>
                            <input type="text" name="ten_dot" class="form-control" placeholder="VD: Đánh giá ĐRL HK1 2026-2027" required>
                        </div>
                        <div class="form-group">
                            <label>Học kỳ <span style="color: red;">*</span></label>
                            <select name="hoc_ky" class="form-control" required>
                                <option value="1">Học kỳ 1</option>
                                <option value="2">Học kỳ 2</option>
                                <option value="3">Học kỳ hè</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Năm học <span style="color: red;">*</span></label>
                            <input type="text" name="nam_hoc" class="form-control" placeholder="VD: 2026-2027" required>
                        </div>
                        <div class="form-group">
                            <label>Ngày bắt đầu <span style="color: red;">*</span></label>
                            <input type="date" name="ngay_bat_dau" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Ngày kết thúc (Hạn chót) <span style="color: red;">*</span></label>
                            <input type="date" name="ngay_ket_thuc" class="form-control" required>
                        </div>
                    </div>
                    <div style="margin-top: 15px;">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-play"></i> Mở Đợt Đánh Giá</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <h3 class="accordion-header" onclick="toggleAccordion('list')">
                <span><i class="fas fa-list"></i> Danh sách Đợt Đánh Giá</span>
                <i class="fas fa-chevron-up icon-arrow" id="arrowList"></i>
            </h3>
            
            <div class="accordion-content" id="contentList" style="display: block;">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tên Đợt</th>
                                <th>Học Kỳ</th>
                                <th>Năm Học</th>
                                <th>Thời Gian</th>
                                <th>Trạng Thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $cycles = $conn->query("SELECT * FROM dot_danh_gia ORDER BY id DESC");
                            while($c = $cycles->fetch_assoc()):
                            ?>
                            <tr>
                                <td><?php echo $c['id']; ?></td>
                                <td style="font-weight: 600;"><?php echo htmlspecialchars($c['ten_dot']); ?></td>
                                <td>HK <?php echo $c['hoc_ky']; ?></td>
                                <td><?php echo htmlspecialchars($c['nam_hoc']); ?></td>
                                <td style="font-size: 0.85rem; color: #475569;">
                                    <?php 
                                        if ($c['ngay_bat_dau'] && $c['ngay_ket_thuc'] && $c['ngay_bat_dau'] != '0000-00-00') {
                                            echo date('d/m/Y', strtotime($c['ngay_bat_dau'])) . ' - ' . date('d/m/Y', strtotime($c['ngay_ket_thuc']));
                                        } else {
                                            echo 'Không xác định';
                                        }
                                    ?>
                                </td>
                                <td>
                                    <?php if($c['trang_thai'] == 'dang_mo'): ?>
                                        <span class="badge badge-active">Đang mở</span>
                                    <?php else: ?>
                                        <span class="badge badge-closed">Đã đóng</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<script>
    function toggleAccordion(type) {
        const contentAdd = document.getElementById('contentAdd');
        const contentList = document.getElementById('contentList');
        const arrowAdd = document.getElementById('arrowAdd');
        const arrowList = document.getElementById('arrowList');
        
        if (type === 'add') {
            if (contentAdd.style.display === 'none') {
                contentAdd.style.display = 'block';
                arrowAdd.className = 'fas fa-chevron-up icon-arrow';
                contentList.style.display = 'none';
                arrowList.className = 'fas fa-chevron-down icon-arrow';
            } else {
                contentAdd.style.display = 'none';
                arrowAdd.className = 'fas fa-chevron-down icon-arrow';
            }
        } else {
            if (contentList.style.display === 'none') {
                contentList.style.display = 'block';
                arrowList.className = 'fas fa-chevron-up icon-arrow';
                contentAdd.style.display = 'none';
                arrowAdd.className = 'fas fa-chevron-down icon-arrow';
            } else {
                contentList.style.display = 'none';
                arrowList.className = 'fas fa-chevron-down icon-arrow';
            }
        }
    }
    </script>

<?php require_once 'layout_footer.php'; ?>
