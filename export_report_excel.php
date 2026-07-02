<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    die("Vui lòng đăng nhập.");
}

$lop_id = isset($_GET['lop_id']) ? (int)$_GET['lop_id'] : 0;
$dot_id = isset($_GET['dot_id']) ? (int)$_GET['dot_id'] : 0;

if (!$dot_id) {
    die("Không có đợt đánh giá.");
}

// Lấy thông tin đợt
$res_dot = $conn->query("SELECT * FROM dot_danh_gia WHERE id = $dot_id");
$dot = $res_dot->fetch_assoc();

// Lấy danh sách sinh viên
$students = [];
$lop_name = "Tất cả các lớp";

if ($lop_id > 0) {
    $res_lop = $conn->query("SELECT ten_lop FROM lop_hoc WHERE id = $lop_id");
    if ($res_lop && $r = $res_lop->fetch_assoc()) {
        $lop_name = $r['ten_lop'];
    }
    
    $sql_sv = "SELECT t.username, t.ho_ten, p.tong_diem_sv, p.tong_diem_cvht, p.tong_diem_khoa, p.xep_loai, p.trang_thai 
               FROM tai_khoan t 
               LEFT JOIN phieu_danh_gia p ON t.id = p.sinh_vien_id AND p.dot_id = $dot_id
               WHERE t.lop_id = $lop_id AND t.vai_tro = 'sinh_vien' 
               ORDER BY t.username ASC";
} else {
    // Nếu Khoa xuất tất cả
    $khoa_id = isset($_SESSION['khoa_id']) ? $_SESSION['khoa_id'] : 0; // Or we can deduce from role
    $sql_sv = "SELECT t.username, t.ho_ten, l.ten_lop, p.tong_diem_sv, p.tong_diem_cvht, p.tong_diem_khoa, p.xep_loai, p.trang_thai 
               FROM tai_khoan t 
               JOIN lop_hoc l ON t.lop_id = l.id
               LEFT JOIN phieu_danh_gia p ON t.id = p.sinh_vien_id AND p.dot_id = $dot_id
               WHERE t.vai_tro = 'sinh_vien' AND l.khoa_id = " . (isset($_SESSION['user_id']) ? "(SELECT id FROM khoa WHERE admin_id = ".$_SESSION['user_id']." LIMIT 1)" : 0) . "
               ORDER BY l.ten_lop ASC, t.username ASC";
               
    // Sửa lại query cho Khoa nếu session kia ko có
    if($_SESSION['role'] == 'khoa') {
        $sql_sv = "SELECT t.username, t.ho_ten, l.ten_lop, p.tong_diem_sv, p.tong_diem_cvht, p.tong_diem_khoa, p.xep_loai, p.trang_thai 
                   FROM tai_khoan t 
                   JOIN lop_hoc l ON t.lop_id = l.id
                   LEFT JOIN phieu_danh_gia p ON t.id = p.sinh_vien_id AND p.dot_id = $dot_id
                   WHERE t.vai_tro = 'sinh_vien' AND l.khoa_id = (SELECT khoa_id FROM tai_khoan WHERE id = ".$_SESSION['user_id'].")
                   ORDER BY l.ten_lop ASC, t.username ASC";
    }
}

$res_sv = $conn->query($sql_sv);
if($res_sv) {
    while($row = $res_sv->fetch_assoc()){
        $students[] = $row;
    }
}

// Cấu hình Header để xuất Excel
$filename = "Bao_Cao_Diem_Ren_Luyen_" . date('Ymd') . ".xls";
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>';
echo '<body>';

echo '<table border="1" cellpadding="5" cellspacing="0" style="width: 100%; border-collapse: collapse; font-family: Times New Roman; font-size: 13px;">';
echo '<tr>';
echo '<th colspan="'.($lop_id > 0 ? 7 : 8).'" style="text-align: center; border:none; font-size: 16px;">BỘ GIÁO DỤC VÀ ĐÀO TẠO<br>TRƯỜNG CAO ĐẲNG CÔNG NGHỆ THÔNG TIN TP.HCM</th>';
echo '</tr>';
echo '<tr>';
echo '<th colspan="'.($lop_id > 0 ? 7 : 8).'" style="text-align: center; border:none; font-size: 18px; padding-top: 20px;">BÁO CÁO KẾT QUẢ RÈN LUYỆN SINH VIÊN</th>';
echo '</tr>';
echo '<tr>';
echo '<th colspan="'.($lop_id > 0 ? 7 : 8).'" style="text-align: center; border:none; padding-bottom: 20px;">Học kỳ: ' . ($dot['hoc_ky'] ?? '') . ' - Năm học: ' . ($dot['nam_hoc'] ?? '') . '</th>';
echo '</tr>';
echo '<tr>';
echo '<th colspan="'.($lop_id > 0 ? 7 : 8).'" style="text-align: left; border:none; padding-bottom: 10px;">Lớp: ' . htmlspecialchars($lop_name) . '</th>';
echo '</tr>';

// Header bảng
echo '<tr style="background-color: #f2f2f2; text-align: center; font-weight: bold;">';
echo '<th width="5%">STT</th>';
echo '<th width="15%">MSSV</th>';
echo '<th width="25%">Họ Tên</th>';
if ($lop_id == 0) {
    echo '<th width="15%">Lớp</th>';
}
echo '<th width="10%">Điểm SV</th>';
echo '<th width="10%">Điểm CVHT</th>';
echo '<th width="10%">Điểm Cuối</th>';
echo '<th width="10%">Xếp Loại</th>';
echo '</tr>';

// Dữ liệu sinh viên
if (empty($students)) {
    echo '<tr><td colspan="'.($lop_id > 0 ? 7 : 8).'" style="text-align: center;">Không có dữ liệu</td></tr>';
} else {
    foreach($students as $index => $sv) {
        echo '<tr>';
        echo '<td style="text-align: center;">' . ($index + 1) . '</td>';
        echo '<td style="text-align: center;">' . htmlspecialchars($sv['username']) . '</td>';
        echo '<td>' . htmlspecialchars($sv['ho_ten']) . '</td>';
        if ($lop_id == 0) {
            echo '<td style="text-align: center;">' . htmlspecialchars($sv['ten_lop']) . '</td>';
        }
        echo '<td style="text-align: center;">' . ($sv['tong_diem_sv'] ?? '') . '</td>';
        echo '<td style="text-align: center;">' . ($sv['tong_diem_cvht'] ?? '') . '</td>';
        echo '<td style="text-align: center; font-weight: bold;">' . ($sv['tong_diem_khoa'] ?? '') . '</td>';
        echo '<td style="text-align: center; font-weight: bold;">' . ($sv['xep_loai'] ?? '') . '</td>';
        echo '</tr>';
    }
}

$cols = $lop_id > 0 ? 7 : 8;

// Chữ ký và tổng hợp như trong ảnh
echo '<tr><td colspan="'.$cols.'" style="border:none; height: 30px;"></td></tr>';

// Nhận xét
echo '<tr>';
echo '<td colspan="3" style="font-weight: bold; border-right: none; border-bottom: 1px dotted #000;">Nhận xét của Phòng CTSV:</td>';
echo '<td colspan="'.($cols - 3).'" style="border-left: none; border-bottom: 1px dotted #000;"></td>';
echo '</tr>';

// Điểm tổng hợp
echo '<tr>';
echo '<td colspan="3" style="border-right: none; border-bottom: 1px dotted #000; border-top: none;">Điểm tổng hợp</td>';
echo '<td colspan="'.($cols - 3).'" style="font-weight: bold; text-align: left; border-left: none; border-bottom: 1px dotted #000; border-top: none;"></td>';
echo '</tr>';

// Xếp loại
echo '<tr>';
echo '<td colspan="3" style="border-right: none; border-bottom: 1px dotted #000; border-top: none;">Xếp loại</td>';
echo '<td colspan="'.($cols - 3).'" style="font-weight: bold; text-align: left; border-left: none; border-bottom: 1px dotted #000; border-top: none;"></td>';
echo '</tr>';

echo '<tr><td colspan="'.$cols.'" style="border:none; height: 20px;"></td></tr>';

// Hàng chữ ký
$col_part = floor($cols / 3);
echo '<tr>';
echo '<td colspan="'.$col_part.'" style="text-align: center; font-weight: bold; border:none; vertical-align: top;">CỐ VẤN HỌC TẬP<br><span style="font-weight: normal; font-style: italic;">(ký và ghi rõ họ tên)</span></td>';
echo '<td colspan="'.$col_part.'" style="text-align: center; font-weight: bold; border:none; vertical-align: top;">BAN CÁN SỰ LỚP<br><span style="font-weight: normal; font-style: italic;">(ký và ghi rõ họ tên)</span></td>';
echo '<td colspan="'.($cols - $col_part*2).'" style="text-align: center; font-weight: bold; border:none; vertical-align: top;"><span style="font-weight: normal; font-style: italic;">ITC, ngày .... tháng .... năm '.date('Y').'</span><br>SINH VIÊN TỰ ĐÁNH GIÁ<br><span style="font-weight: normal; font-style: italic;">(ký và ghi rõ họ tên)</span></td>';
echo '</tr>';

// Khoảng trống ký tên
echo '<tr><td colspan="'.$cols.'" style="border:none; height: 80px;"></td></tr>';

// Tên sinh viên
echo '<tr>';
echo '<td colspan="'.$col_part.'" style="border:none;"></td>';
echo '<td colspan="'.$col_part.'" style="border:none;"></td>';
echo '<td colspan="'.($cols - $col_part*2).'" style="text-align: center; font-weight: bold; border:none;"></td>';
echo '</tr>';

// Phòng CTSV
echo '<tr><td colspan="'.$cols.'" style="border:none; height: 30px;"></td></tr>';
echo '<tr>';
echo '<td colspan="'.$cols.'" style="text-align: center; font-weight: bold; border:none; font-size: 16px;">PHÒNG CÔNG TÁC SINH VIÊN</td>';
echo '</tr>';

echo '</table>';
echo '</body></html>';
?>
