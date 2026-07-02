<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'khoa' && !has_permission('import_attendance'))) {
    header("Location: index.php");
    exit;
}

require_once 'config.php';
require_once 'vendor/autoload.php';
use Shuchkin\SimpleXLS;

$page_title = 'Import Điểm Danh (CTSV)';
$msg = '';
$msg_type = '';

// Lấy danh sách đợt đánh giá đang mở
$dot_list = [];
$res_dot = $conn->query("SELECT id, ten_dot FROM dot_danh_gia WHERE trang_thai = 'dang_mo' ORDER BY id DESC");
while ($row = $res_dot->fetch_assoc()) {
    $dot_list[] = $row;
}

// Lấy danh sách tiêu chí để trừ điểm
$tc_list = [];
$res_tc = $conn->query("SELECT t.id, t.noi_dung, t.diem_toi_da, n.ten_nhom FROM tieu_chi t JOIN nhom_tieu_chi n ON t.nhom_id = n.id ORDER BY n.thu_tu, t.id");
while ($row = $res_tc->fetch_assoc()) {
    $tc_list[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_excel'])) {
    $dot_id = (int)$_POST['dot_id'];
    $tieu_chi_id = (int)$_POST['tieu_chi_id'];
    $diem_tru_tre = (int)$_POST['diem_tru_tre'];
    $diem_tru_nhieu = (int)$_POST['diem_tru_nhieu'];
    
    // Check dot_id, tieu_chi_id
    if ($dot_id > 0 && $tieu_chi_id > 0 && $_FILES['file_excel']['error'] == 0) {
        $file_tmp = $_FILES['file_excel']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['file_excel']['name'], PATHINFO_EXTENSION));
        
        if ($ext === 'xls') {
            if ($xls = SimpleXLS::parse($file_tmp)) {
                $rows = $xls->rows();
                $count_success = 0;
                $count_student = 0;
                
                // Lấy điểm tối đa của tiêu chí
                $max_score = 0;
                $q_max = $conn->query("SELECT diem_toi_da FROM tieu_chi WHERE id = $tieu_chi_id");
                if ($q_max && $q_max->num_rows > 0) {
                    $max_score = (int)$q_max->fetch_assoc()['diem_toi_da'];
                }

                $mssv_col = 2; // Default
                $tiet_vang_col = 9; // Default
                
                // Tự động dò tìm cột MSSV và Tổng số tiết vắng trên các dòng đầu
                foreach ($rows as $index => $row) {
                    if ($index > 5) break;
                    foreach ($row as $c => $val) {
                        $val_lower = mb_strtolower(trim($val), 'UTF-8');
                        if ($val_lower === 'mssv' || $val_lower === 'mã sinh viên') {
                            $mssv_col = $c;
                        }
                        if (strpos($val_lower, 'tiết vắng') !== false) {
                            $tiet_vang_col = $c;
                        }
                    }
                }

                foreach ($rows as $index => $row) {
                    if ($index < 2) continue; // Bỏ qua header
                    
                    $mssv = isset($row[$mssv_col]) ? trim($row[$mssv_col]) : '';
                    $tiet_vang_str = isset($row[$tiet_vang_col]) ? $row[$tiet_vang_col] : '0';
                    $tiet_vang = (int)$tiet_vang_str;
                    
                    if (!empty($mssv) && is_numeric($mssv)) {
                        $count_student++;
                        
                        if ($tiet_vang > 0) {
                            // Tính điểm trừ
                            $diem_tru = 0;
                            if ($tiet_vang <= 2) {
                                // Trễ bình thường
                                $diem_tru = $tiet_vang * $diem_tru_tre;
                            } else {
                                // Đi trễ nhiều (vắng nhiều)
                                $diem_tru = $diem_tru_nhieu; 
                                // Hoặc tính luỹ tiến: $diem_tru = $tiet_vang * $diem_tru_tre;
                                // Dùng cách trừ cứng nếu trễ nhiều
                            }
                            
                            $diem_moi = $max_score - $diem_tru;
                            if ($diem_moi < 0) $diem_moi = 0;
                            
                            // Tìm user_id của MSSV này
                            $q_sv = $conn->query("SELECT id FROM tai_khoan WHERE username = '$mssv' AND vai_tro = 'sinh_vien'");
                            if ($q_sv && $q_sv->num_rows > 0) {
                                $sv_id = (int)$q_sv->fetch_assoc()['id'];
                                
                                // Kiểm tra phiếu đánh giá
                                $q_phieu = $conn->query("SELECT id FROM phieu_danh_gia WHERE sinh_vien_id = $sv_id AND dot_id = $dot_id");
                                $phieu_id = 0;
                                if ($q_phieu && $q_phieu->num_rows > 0) {
                                    $phieu_id = (int)$q_phieu->fetch_assoc()['id'];
                                } else {
                                    $conn->query("INSERT INTO phieu_danh_gia (sinh_vien_id, dot_id, trang_thai) VALUES ($sv_id, $dot_id, 'chua_nop')");
                                    $phieu_id = $conn->insert_id;
                                }
                                
                                // Cập nhật chi tiết điểm
                                $q_ct = $conn->query("SELECT id FROM chi_tiet_diem WHERE phieu_id = $phieu_id AND tieu_chi_id = $tieu_chi_id");
                                $ghi_chu = "Bị trừ điểm do vắng/trễ $tiet_vang tiết (File Import CTSV)";
                                if ($q_ct && $q_ct->num_rows > 0) {
                                    $conn->query("UPDATE chi_tiet_diem SET diem_sv = $diem_moi, diem_cvht = $diem_moi, diem_khoa = $diem_moi, diem_tru = $diem_tru, ghi_chu_tru = '$ghi_chu' WHERE phieu_id = $phieu_id AND tieu_chi_id = $tieu_chi_id");
                                } else {
                                    $conn->query("INSERT INTO chi_tiet_diem (phieu_id, tieu_chi_id, diem_sv, diem_cvht, diem_khoa, diem_tru, ghi_chu_tru) VALUES ($phieu_id, $tieu_chi_id, $diem_moi, $diem_moi, $diem_moi, $diem_tru, '$ghi_chu')");
                                }
                                
                                // Cập nhật tổng điểm của phiếu đánh giá
                                $res_tong = $conn->query("SELECT SUM(diem_sv) as t_sv, SUM(diem_cvht) as t_cvht, SUM(diem_khoa) as t_khoa FROM chi_tiet_diem WHERE phieu_id = $phieu_id");
                                if ($res_tong && $res_tong->num_rows > 0) {
                                    $t_data = $res_tong->fetch_assoc();
                                    $t_sv = (int)$t_data['t_sv'];
                                    $t_cvht = (int)$t_data['t_cvht'];
                                    $t_khoa = (int)$t_data['t_khoa'];
                                    
                                    // Xác định xếp loại nếu phiếu đã duyệt
                                    // Tạm thời chưa xếp loại lại nếu chưa duyệt hoàn toàn, logic update tổng điểm là chính
                                    $conn->query("UPDATE phieu_danh_gia SET tong_diem_sv = $t_sv, tong_diem_cvht = $t_cvht, tong_diem_khoa = $t_khoa WHERE id = $phieu_id");
                                }
                                
                                $count_success++;
                            }
                        }
                    }
                }
                $msg = "Đã xử lý xong. Có $count_success / $count_student sinh viên bị trừ điểm vì đi trễ/vắng.";
                $msg_type = 'success';
            } else {
                $msg = "Lỗi đọc file Excel: " . SimpleXLS::parseError();
                $msg_type = 'error';
            }
        } else {
            $msg = "Chỉ hỗ trợ định dạng file .xls (Legacy Excel).";
            $msg_type = 'error';
        }
    } else {
        $msg = "Vui lòng nhập đầy đủ thông tin và chọn file.";
        $msg_type = 'error';
    }
}

require_once 'layout_header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-file-import"></i> Import Điểm Danh (Đi Trễ / Vắng)</h3>
    </div>
    <div class="card-body">
        <?php if ($msg): ?>
            <div class="alert alert-<?php echo $msg_type; ?>">
                <i class="fas <?php echo $msg_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>

        <div class="info-box">
            <i class="fas fa-info-circle"></i> Tính năng này cho phép Upload mẫu điểm danh (.xls) từ phòng CTSV. Hệ thống sẽ tự động tìm các sinh viên có "Tổng số tiết vắng" &gt; 0 để áp dụng mức trừ điểm (đi trễ / vắng) vào một tiêu chí cụ thể (Ví dụ: Tiêu chí Chuyên cần). Điểm trừ sẽ được cập nhật trực tiếp cho điểm SV, điểm CVHT và điểm Khoa.
        </div>

        <form method="POST" enctype="multipart/form-data" class="mt-4">
            <div class="form-row">
                <div class="form-group">
                    <label>Đợt đánh giá <span class="text-danger">*</span></label>
                    <select name="dot_id" class="form-control" required>
                        <option value="">-- Chọn đợt đánh giá --</option>
                        <?php foreach($dot_list as $d): ?>
                            <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['ten_dot']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Tiêu chí áp dụng trừ điểm <span class="text-danger">*</span></label>
                    <select name="tieu_chi_id" class="form-control" required>
                        <option value="">-- Chọn tiêu chí --</option>
                        <?php foreach($tc_list as $tc): ?>
                            <option value="<?php echo $tc['id']; ?>">
                                <?php echo htmlspecialchars(substr($tc['noi_dung'], 0, 80)) . '... (Max: ' . $tc['diem_toi_da'] . 'đ)'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Điểm trừ mỗi tiết vắng/trễ (Nếu &le; 2 tiết)</label>
                    <input type="number" name="diem_tru_tre" class="form-control" value="2" min="1" max="10" required>
                    <small style="color:var(--gray)">Ví dụ: Vắng 1 tiết trừ 2đ, vắng 2 tiết trừ 4đ</small>
                </div>

                <div class="form-group">
                    <label>Điểm trừ khi đi trễ nhiều (Vắng &gt; 2 tiết)</label>
                    <input type="number" name="diem_tru_nhieu" class="form-control" value="10" min="1" max="20" required>
                    <small style="color:var(--gray)">Ví dụ: Trừ 10đ (trừ hết điểm chuyên cần)</small>
                </div>
            </div>

            <div class="form-group">
                <label>File Excel Điểm Danh (.xls) <span class="text-danger">*</span></label>
                <input type="file" name="file_excel" accept=".xls" class="form-control" required>
                <small style="color:var(--gray)">Chỉ chấp nhận file .xls (Excel 97-2003) - là mẫu mặc định của CTSV.</small>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-upload"></i> Xử Lý Import
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'layout_footer.php'; ?>
