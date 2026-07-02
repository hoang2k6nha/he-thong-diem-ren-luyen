<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'khoa' && !has_permission('import_grades'))) {
    header("Location: index.php");
    exit;
}

require_once 'config.php';
require_once 'vendor/autoload.php';
use Shuchkin\SimpleXLS;
use Shuchkin\SimpleXLSX;

$page_title = 'Import Điểm Học Tập';
$msg = '';
$msg_type = '';

// Lấy danh sách đợt đánh giá đang mở
$dot_list = [];
$res_dot = $conn->query("SELECT id, ten_dot FROM dot_danh_gia WHERE trang_thai = 'dang_mo' ORDER BY id DESC");
while ($row = $res_dot->fetch_assoc()) {
    $dot_list[] = $row;
}

// Lấy danh sách tiêu chí (những tiêu chí có thể cộng điểm)
$tc_list = [];
$res_tc = $conn->query("SELECT t.id, t.noi_dung, t.diem_toi_da, n.ten_nhom FROM tieu_chi t JOIN nhom_tieu_chi n ON t.nhom_id = n.id ORDER BY n.thu_tu, t.id");
while ($row = $res_tc->fetch_assoc()) {
    $tc_list[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_excel'])) {
    $dot_id = (int)$_POST['dot_id'];
    $tieu_chi_id = (int)$_POST['tieu_chi_id'];
    
    // Mức điểm cấu hình
    $dtb_m1 = (float)$_POST['dtb_muc_1'];
    $cong_m1 = (int)$_POST['cong_muc_1'];
    
    $dtb_m2 = (float)$_POST['dtb_muc_2'];
    $cong_m2 = (int)$_POST['cong_muc_2'];
    
    $dtb_m3 = (float)$_POST['dtb_muc_3'];
    $cong_m3 = (int)$_POST['cong_muc_3'];
    
    if ($dot_id > 0 && $tieu_chi_id > 0 && $_FILES['file_excel']['error'] == 0) {
        $file_tmp = $_FILES['file_excel']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['file_excel']['name'], PATHINFO_EXTENSION));
        
        $xls = null;
        if ($ext === 'xls') {
            $xls = SimpleXLS::parse($file_tmp);
        } elseif ($ext === 'xlsx') {
            $xls = SimpleXLSX::parse($file_tmp);
        }
        
        if ($xls) {
            $rows = $xls->rows();
            $count_success = 0;
            $count_student = 0;
            
            $max_score = 0;
            $q_max = $conn->query("SELECT diem_toi_da FROM tieu_chi WHERE id = $tieu_chi_id");
            if ($q_max && $q_max->num_rows > 0) {
                $max_score = (int)$q_max->fetch_assoc()['diem_toi_da'];
            }

            $mssv_col = -1;
            $ngay_sinh_col = -1;
            $start_row = 0;
            
            // Tìm dòng Header
            foreach ($rows as $index => $row) {
                if ($index > 15) break; // Chỉ tìm trong 15 dòng đầu
                foreach ($row as $c => $val) {
                    $val_lower = mb_strtolower(trim($val), 'UTF-8');
                    if ($val_lower === 'mssv' || $val_lower === 'mã số' || $val_lower === 'mã sinh viên') {
                        $mssv_col = $c;
                        $start_row = $index + 1; // Dòng sau header là dòng dữ liệu đầu tiên
                    }
                    if (strpos($val_lower, 'ngày sinh') !== false || strpos($val_lower, 'ngay sinh') !== false) {
                        $ngay_sinh_col = $c;
                    }
                }
                if ($mssv_col !== -1) break;
            }
            
            if ($ngay_sinh_col == -1 && $mssv_col !== -1) {
                $ngay_sinh_col = $mssv_col + 2; // Đoán cột Ngày sinh thường cách MSSV 2 cột
            }

            if ($mssv_col !== -1 && $start_row > 0) {
                $header_row = $rows[$start_row - 1];
                $results_log = []; // Thêm log để hiển thị chi tiết
                
                foreach ($rows as $index => $row) {
                    if ($index < $start_row) continue;
                    
                    $mssv = isset($row[$mssv_col]) ? trim($row[$mssv_col]) : '';
                    if (empty($mssv) || !is_numeric($mssv)) continue;
                    
                    $count_student++;
                    
                    $sum_grades = 0;
                    $count_grades = 0;
                    $last_col = count($row) - 1;
                    
                    // Duyệt các cột điểm từ sau Ngày sinh
                    for ($c = $ngay_sinh_col + 1; $c <= $last_col; $c++) {
                        $h_val = isset($header_row[$c]) ? mb_strtolower(trim($header_row[$c]), 'UTF-8') : '';
                        // Bỏ qua cột Trung bình chung, Ghi chú
                        if (strpos($h_val, 'tbc') !== false || strpos($h_val, 'tk') !== false || strpos($h_val, 'ghi chú') !== false || strpos($h_val, 'tổng') !== false) {
                            continue;
                        }
                        
                        $val = isset($row[$c]) ? trim($row[$c]) : '';
                        if ($val === '') continue;
                        
                        $val = str_replace(',', '.', $val);
                        if (is_numeric($val) && $val >= 0 && $val <= 10) {
                            $sum_grades += (float)$val;
                            $count_grades++;
                        }
                    }
                    
                    $dtb = 0;
                    $diem_cong = 0;
                    
                    if ($count_grades > 0) {
                        $dtb = round($sum_grades / $count_grades, 2);
                        
                        if ($dtb >= $dtb_m1) {
                            $diem_cong = $cong_m1;
                        } elseif ($dtb >= $dtb_m2) {
                            $diem_cong = $cong_m2;
                        } elseif ($dtb >= $dtb_m3) {
                            $diem_cong = $cong_m3;
                        }
                        
                        if ($diem_cong > $max_score) $diem_cong = $max_score;
                        
                        // Luôn lưu vào CSDL để cập nhật thông tin ĐTB, dù điểm cộng là 0
                        if ($diem_cong >= 0) {
                            $q_sv = $conn->query("SELECT id FROM tai_khoan WHERE username = '$mssv' AND vai_tro = 'sinh_vien'");
                            if ($q_sv && $q_sv->num_rows > 0) {
                                $sv_id = (int)$q_sv->fetch_assoc()['id'];
                                
                                $q_phieu = $conn->query("SELECT id FROM phieu_danh_gia WHERE sinh_vien_id = $sv_id AND dot_id = $dot_id");
                                $phieu_id = 0;
                                if ($q_phieu && $q_phieu->num_rows > 0) {
                                    $phieu_id = (int)$q_phieu->fetch_assoc()['id'];
                                } else {
                                    $conn->query("INSERT INTO phieu_danh_gia (sinh_vien_id, dot_id, trang_thai) VALUES ($sv_id, $dot_id, 'chua_nop')");
                                    $phieu_id = $conn->insert_id;
                                }
                                
                                $q_ct = $conn->query("SELECT id FROM chi_tiet_diem WHERE phieu_id = $phieu_id AND tieu_chi_id = $tieu_chi_id");
                                $ghi_chu = "Cộng điểm Học tập: ĐTB=$dtb (File Import Khoa)";
                                
                                if ($q_ct && $q_ct->num_rows > 0) {
                                    $conn->query("UPDATE chi_tiet_diem SET diem_sv = $diem_cong, diem_cvht = $diem_cong, diem_khoa = $diem_cong, diem_tru = 0, ghi_chu_tru = '$ghi_chu' WHERE phieu_id = $phieu_id AND tieu_chi_id = $tieu_chi_id");
                                } else {
                                    $conn->query("INSERT INTO chi_tiet_diem (phieu_id, tieu_chi_id, diem_sv, diem_cvht, diem_khoa, diem_tru, ghi_chu_tru) VALUES ($phieu_id, $tieu_chi_id, $diem_cong, $diem_cong, $diem_cong, 0, '$ghi_chu')");
                                }
                                
                                $res_tong = $conn->query("SELECT SUM(diem_sv) as t_sv, SUM(diem_cvht) as t_cvht, SUM(diem_khoa) as t_khoa FROM chi_tiet_diem WHERE phieu_id = $phieu_id");
                                if ($res_tong && $res_tong->num_rows > 0) {
                                    $t_data = $res_tong->fetch_assoc();
                                    $t_sv = (int)$t_data['t_sv'];
                                    $t_cvht = (int)$t_data['t_cvht'];
                                    $t_khoa = (int)$t_data['t_khoa'];
                                    $conn->query("UPDATE phieu_danh_gia SET tong_diem_sv = $t_sv, tong_diem_cvht = $t_cvht, tong_diem_khoa = $t_khoa WHERE id = $phieu_id");
                                }
                                
                                $count_success++;
                            }
                        }
                    }
                    // Ghi log
                    $results_log[] = [
                        'mssv' => $mssv,
                        'count_grades' => $count_grades,
                        'dtb' => $dtb,
                        'diem_cong' => $diem_cong
                    ];
                }
                $msg = "Đã xử lý xong. Có $count_success / $count_student sinh viên được cộng điểm thành công.";
                $msg_type = 'success';
                
                // Lưu log vào session để hiển thị
                $_SESSION['import_grades_log'] = $results_log;
            } else {
                $msg = "Không tìm thấy cấu trúc bảng (Cột MSSV) trong file. Vui lòng kiểm tra lại.";
                $msg_type = 'error';
            }
        } else {
            $msg = "Lỗi đọc file Excel: " . ($ext === 'xls' ? SimpleXLS::parseError() : SimpleXLSX::parseError());
            $msg_type = 'error';
        }
    } else {
        $msg = "Vui lòng nhập đầy đủ thông tin và chọn file hợp lệ.";
        $msg_type = 'error';
    }
}

require_once 'layout_header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-file-excel"></i> Import Điểm Học Tập</h3>
    </div>
    <div class="card-body">
        <?php if ($msg): ?>
            <div class="alert alert-<?php echo $msg_type; ?>">
                <i class="fas <?php echo $msg_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>

        <div class="info-box">
            <i class="fas fa-info-circle"></i> <strong>Tính năng Import Điểm Học Tập từ Đào Tạo:</strong><br>
            Hệ thống hỗ trợ file Excel (.xls, .xlsx) xuất theo Học kỳ (bao gồm cả trường hợp SV vượt môn/học lại có nhiều cột điểm). 
            Thuật toán sẽ tự động dò tìm dòng tiêu đề, lọc các cột điểm học phần (thang điểm 10) và tính <strong>Điểm Trung Bình (ĐTB) bình thường</strong> (tổng điểm chia số môn). Dựa vào cấu hình mức điểm ĐTB, hệ thống tự động cộng điểm rèn luyện vào Tiêu chí tương ứng.
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
                    <label>Tiêu chí áp dụng cộng điểm <span class="text-danger">*</span></label>
                    <select name="tieu_chi_id" class="form-control" required>
                        <option value="">-- Chọn tiêu chí --</option>
                        <?php foreach($tc_list as $tc): ?>
                            <option value="<?php echo $tc['id']; ?>" <?php echo (strpos($tc['noi_dung'], 'TBC') !== false || strpos($tc['noi_dung'], 'học tập') !== false) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(substr($tc['noi_dung'], 0, 80)) . '... (Max: ' . $tc['diem_toi_da'] . 'đ)'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <h4 style="margin: 1.5rem 0 1rem; color: var(--primary); font-family: var(--font-heading);">Cấu Hình Điểm Cộng Theo ĐTB (Thang 10)</h4>
            <div class="form-row">
                <div class="form-group">
                    <label>Mức 1: ĐTB &ge;</label>
                    <input type="number" step="0.1" name="dtb_muc_1" class="form-control" value="9.0" min="0" max="10" required>
                </div>
                <div class="form-group">
                    <label>Điểm cộng tương ứng (Mức 1)</label>
                    <input type="number" name="cong_muc_1" class="form-control" value="5" min="0" max="20" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Mức 2: ĐTB &ge;</label>
                    <input type="number" step="0.1" name="dtb_muc_2" class="form-control" value="8.0" min="0" max="10" required>
                </div>
                <div class="form-group">
                    <label>Điểm cộng tương ứng (Mức 2)</label>
                    <input type="number" name="cong_muc_2" class="form-control" value="3" min="0" max="20" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Mức 3: ĐTB &ge;</label>
                    <input type="number" step="0.1" name="dtb_muc_3" class="form-control" value="7.0" min="0" max="10" required>
                </div>
                <div class="form-group">
                    <label>Điểm cộng tương ứng (Mức 3)</label>
                    <input type="number" name="cong_muc_3" class="form-control" value="0" min="0" max="20" required>
                </div>
            </div>

            <div class="form-group" style="margin-top: 1.5rem;">
                <label>File Excel Điểm Học Tập từ Đào Tạo (.xls, .xlsx) <span class="text-danger">*</span></label>
                <input type="file" name="file_excel" accept=".xls,.xlsx" class="form-control" required>
                <small style="color:var(--gray)">Hệ thống tự động dò tìm cột MSSV và các cột điểm thành phần để tính ĐTB.</small>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-upload"></i> Xử Lý Tính Điểm & Import
                </button>
            </div>
        </form>

        <?php if (isset($_SESSION['import_grades_log']) && !empty($_SESSION['import_grades_log'])): ?>
            <h4 style="margin: 2.5rem 0 1rem; color: var(--primary); font-family: var(--font-heading);">Chi Tiết Kết Quả Import Gần Nhất</h4>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Mã Sinh Viên</th>
                            <th>Số môn hợp lệ</th>
                            <th>ĐTB (Tính được)</th>
                            <th>Điểm cộng nhận được</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($_SESSION['import_grades_log'] as $idx => $log): ?>
                        <tr>
                            <td><?php echo $idx + 1; ?></td>
                            <td><strong><?php echo htmlspecialchars($log['mssv']); ?></strong></td>
                            <td><?php echo $log['count_grades']; ?> môn</td>
                            <td>
                                <span class="badge <?php echo $log['dtb'] >= 8.0 ? 'badge-active' : 'badge-gray'; ?>">
                                    <?php echo number_format($log['dtb'], 2); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($log['diem_cong'] > 0): ?>
                                    <span style="color: #15803D; font-weight: bold;">+<?php echo $log['diem_cong']; ?> điểm</span>
                                <?php else: ?>
                                    <span style="color: #94A3B8;">0 điểm (Không đạt mức)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php unset($_SESSION['import_grades_log']); ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'layout_footer.php'; ?>
