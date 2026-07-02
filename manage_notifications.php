<?php
require_once 'config.php';

// Chỉ Khoa, CVHT hoặc Admin được phép vào đây
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['cvht', 'khoa', 'admin'])) {
    redirect('notifications.php');
}

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Xử lý Xóa
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    // Khoa có thể xóa mọi bài, CVHT chỉ xóa bài mình tạo
    $cond = ($role == 'khoa') ? "" : " AND nguoi_tao_id = $user_id";
    $conn->query("DELETE FROM thong_bao WHERE id = $del_id $cond");
    redirect('notifications.php');
}

// Xử lý Thêm/Sửa
$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$tieu_de = '';
$noi_dung = '';
$doi_tuong = 'tat_ca';

// Lấy dữ liệu nếu đang Edit
if ($edit_id > 0) {
    $res = $conn->query("SELECT * FROM thong_bao WHERE id = $edit_id");
    if ($res && $res->num_rows > 0) {
        $tb = $res->fetch_assoc();
        // CVHT chỉ được sửa bài của mình
        if ($role != 'khoa' && $tb['nguoi_tao_id'] != $user_id) {
            redirect('notifications.php');
        }
        $tieu_de = $tb['tieu_de'];
        $noi_dung = $tb['noi_dung'];
        $doi_tuong = $tb['doi_tuong'];
        $hinh_anh_cu = $tb['hinh_anh'];
        $duong_dan = $tb['duong_dan'];
        $ten_duong_dan = $tb['ten_duong_dan'];
        $la_su_kien = isset($tb['la_su_kien']) ? $tb['la_su_kien'] : 0;
        $cho_phep_dat_cau_hoi = isset($tb['cho_phep_dat_cau_hoi']) ? $tb['cho_phep_dat_cau_hoi'] : 0;
        $thoi_gian_bat_dau = isset($tb['thoi_gian_bat_dau']) ? date('Y-m-d\TH:i', strtotime($tb['thoi_gian_bat_dau'])) : '';
        $thoi_gian_ket_thuc = isset($tb['thoi_gian_ket_thuc']) ? date('Y-m-d\TH:i', strtotime($tb['thoi_gian_ket_thuc'])) : '';
    }
} else {
    $hinh_anh_cu = '';
    $duong_dan = '';
    $ten_duong_dan = '';
    $la_su_kien = 0;
    $cho_phep_dat_cau_hoi = 0;
    $thoi_gian_bat_dau = '';
    $thoi_gian_ket_thuc = '';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tieu_de = $conn->real_escape_string($_POST['tieu_de']);
    $noi_dung = $conn->real_escape_string($_POST['noi_dung']);
    $duong_dan = $conn->real_escape_string($_POST['duong_dan']);
    $ten_duong_dan = $conn->real_escape_string($_POST['ten_duong_dan']);
    
    $doi_tuong_post = isset($_POST['doi_tuong']) ? $_POST['doi_tuong'] : 'tat_ca';
    if ($role == 'khoa') {
        $doi_tuong = ($doi_tuong_post == 'cvht_khoa') ? 'cvht_khoa' : 'tat_ca';
    } else {
        $doi_tuong = 'tat_ca';
    }

    $la_su_kien = isset($_POST['la_su_kien']) ? 1 : 0;
    $cho_phep_dat_cau_hoi = isset($_POST['cho_phep_dat_cau_hoi']) ? 1 : 0;

    $thoi_gian_bat_dau = !empty($_POST['thoi_gian_bat_dau']) ? "'" . $conn->real_escape_string($_POST['thoi_gian_bat_dau']) . "'" : "NULL";
    $thoi_gian_ket_thuc = !empty($_POST['thoi_gian_ket_thuc']) ? "'" . $conn->real_escape_string($_POST['thoi_gian_ket_thuc']) . "'" : "NULL";

    // Xử lý upload ảnh
    $hinh_anh_moi = $hinh_anh_cu;
    if (isset($_FILES['hinh_anh']) && $_FILES['hinh_anh']['error'] == 0) {
        $ext = pathinfo($_FILES['hinh_anh']['name'], PATHINFO_EXTENSION);
        $new_name = 'noti_' . time() . '.' . $ext;
        if (move_uploaded_file($_FILES['hinh_anh']['tmp_name'], 'uploads/' . $new_name)) {
            $hinh_anh_moi = 'uploads/' . $new_name;
        }
    }

    if (empty($tieu_de) || empty($noi_dung)) {
        $error = 'Vui lòng nhập đầy đủ tiêu đề và nội dung.';
    } else {
        if ($edit_id > 0) {
            $sql = "UPDATE thong_bao SET tieu_de = '$tieu_de', noi_dung = '$noi_dung', doi_tuong = '$doi_tuong', hinh_anh = '$hinh_anh_moi', duong_dan = '$duong_dan', ten_duong_dan = '$ten_duong_dan', la_su_kien = $la_su_kien, cho_phep_dat_cau_hoi = $cho_phep_dat_cau_hoi, thoi_gian_bat_dau = $thoi_gian_bat_dau, thoi_gian_ket_thuc = $thoi_gian_ket_thuc WHERE id = $edit_id";
        } else {
            $sql = "INSERT INTO thong_bao (tieu_de, noi_dung, nguoi_tao_id, doi_tuong, hinh_anh, duong_dan, ten_duong_dan, la_su_kien, cho_phep_dat_cau_hoi, thoi_gian_bat_dau, thoi_gian_ket_thuc) VALUES ('$tieu_de', '$noi_dung', $user_id, '$doi_tuong', '$hinh_anh_moi', '$duong_dan', '$ten_duong_dan', $la_su_kien, $cho_phep_dat_cau_hoi, $thoi_gian_bat_dau, $thoi_gian_ket_thuc)";
        }
        
        if ($conn->query($sql)) {
            redirect('notifications.php');
        } else {
            $error = 'Có lỗi xảy ra: ' . $conn->error;
        }
    }
}

$page_title = ($edit_id ? 'Sửa' : 'Tạo') . ' Thông Báo';
require_once 'layout_header.php';
?>

<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-edit"></i> <?php echo $edit_id ? 'Sửa' : 'Soạn'; ?> Thông Báo</div>
    </div>
    
    <div class="card-body">
        <?php if($error): ?><div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?></div><?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Tiêu đề thông báo</label>
                <input type="text" name="tieu_de" class="form-control" value="<?php echo htmlspecialchars($tieu_de); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Nội dung chi tiết</label>
                <textarea name="noi_dung" class="form-control" rows="8" required><?php echo htmlspecialchars($noi_dung); ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Đính kèm hình ảnh (Không bắt buộc)</label>
                <?php if ($hinh_anh_cu): ?>
                    <div style="margin-bottom: 10px;">
                        <img src="<?php echo htmlspecialchars($hinh_anh_cu); ?>" alt="Ảnh hiện tại" style="max-width: 200px; border-radius: 8px; border: 1px solid #E2E8F0;">
                    </div>
                <?php endif; ?>
                <input type="file" name="hinh_anh" class="form-control" accept="image/*">
            </div>

            <div class="form-group" style="background: #F8FAFC; padding: 15px; border-radius: 8px; border: 1px dashed #CBD5E1; margin-bottom: 25px;">
                <label style="color: var(--primary);"><i class="fas fa-link"></i> Đính kèm liên kết truy cập nhanh (Tùy chọn)</label>
                <select id="quick_link" class="form-control" onchange="applyQuickLink()">
                    <option value="" data-name="">-- Không đính kèm liên kết --</option>
                    <option value="student_assessment.php" data-name="Xét điểm rèn luyện ngay" <?php echo ($duong_dan == 'student_assessment.php') ? 'selected' : ''; ?>>Tới trang Xét Điểm Rèn Luyện</option>
                    <option value="student_history.php" data-name="Xem lịch sử điểm rèn luyện" <?php echo ($duong_dan == 'student_history.php') ? 'selected' : ''; ?>>Tới trang Lịch Sử Điểm Rèn Luyện</option>
                </select>
                <small style="color: #64748B; margin-top: 5px; display: block;">Nếu chọn, thông báo sẽ có một nút bấm dẫn sinh viên tới chức năng tương ứng.</small>
            </div>

            <!-- Hidden inputs to submit data -->
            <input type="hidden" name="ten_duong_dan" value="<?php echo htmlspecialchars($ten_duong_dan); ?>">
            <input type="hidden" name="duong_dan" value="<?php echo htmlspecialchars($duong_dan); ?>">

            <div class="form-group" style="background: #FFFBEB; padding: 15px; border-radius: 8px; border: 1px solid #FDE68A;">
                <label style="color: #B45309; display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" name="la_su_kien" id="la_su_kien" value="1" <?php echo $la_su_kien ? 'checked' : ''; ?> onchange="toggleSuKien()"> 
                    Đây là Sự kiện cần sinh viên tham gia
                </label>
                <div id="sukien_options" style="margin-top: 15px; margin-left: 25px; <?php echo $la_su_kien ? 'display: block;' : 'display: none;'; ?>">
                    <div class="form-row">
                        <div style="flex: 1;">
                            <label style="font-size: 0.9rem; color: #92400E;">Thời gian bắt đầu đăng ký:</label>
                            <input type="datetime-local" name="thoi_gian_bat_dau" class="form-control" value="<?php echo $thoi_gian_bat_dau; ?>">
                        </div>
                        <div style="flex: 1;">
                            <label style="font-size: 0.9rem; color: #92400E;">Thời gian kết thúc đăng ký:</label>
                            <input type="datetime-local" name="thoi_gian_ket_thuc" class="form-control" value="<?php echo $thoi_gian_ket_thuc; ?>">
                        </div>
                    </div>
                    <label style="color: #92400E; display: flex; align-items: center; gap: 8px; font-weight: normal;">
                        <input type="checkbox" name="cho_phep_dat_cau_hoi" value="1" <?php echo $cho_phep_dat_cau_hoi ? 'checked' : ''; ?>> 
                        Cho phép sinh viên đặt câu hỏi khi đăng ký
                    </label>
                </div>
            </div>
            
            <?php if ($role == 'khoa'): ?>
            <div class="form-group">
                <label>Quyền xem (Đối tượng nhận)</label>
                <select name="doi_tuong" class="form-control">
                    <option value="tat_ca" <?php echo ($doi_tuong == 'tat_ca') ? 'selected' : ''; ?>>Tất cả (Sinh viên, CVHT, Khoa)</option>
                    <option value="cvht_khoa" <?php echo ($doi_tuong == 'cvht_khoa') ? 'selected' : ''; ?>>Nội bộ (Chỉ CVHT và Khoa)</option>
                </select>
                <small style="color: #64748B; margin-top: 5px; display: block;">Tính năng phân quyền này chỉ tài khoản Khoa mới được phép thiết lập.</small>
            </div>
            <?php endif; ?>
            
            <div style="margin-top: 2rem; border-top: 1px solid var(--border); padding-top: 1.5rem; display: flex; justify-content: flex-end;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> <?php echo $edit_id ? 'Cập Nhật Thông Báo' : 'Đăng Thông Báo'; ?></button>
            </div>
        </form>
    </div>
</div>

<script>
function applyQuickLink() {
    var select = document.getElementById('quick_link');
    var urlInput = document.querySelector('input[name="duong_dan"]');
    var nameInput = document.querySelector('input[name="ten_duong_dan"]');
    
    if (select.value !== "") {
        // Auto fill url
        urlInput.value = select.value;
        
        // Auto fill name
        var selectedOption = select.options[select.selectedIndex];
        nameInput.value = selectedOption.getAttribute('data-name');
    } else {
        urlInput.value = "";
        nameInput.value = "";
    }
}

function toggleSuKien() {
    var isChecked = document.getElementById('la_su_kien').checked;
    document.getElementById('sukien_options').style.display = isChecked ? 'block' : 'none';
}
</script>

<?php require_once 'layout_footer.php'; ?>
