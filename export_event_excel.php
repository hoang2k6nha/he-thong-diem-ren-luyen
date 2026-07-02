<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['cvht', 'khoa', 'admin'])) {
    die("Bạn không có quyền truy cập.");
}

$thong_bao_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Lấy thông tin sự kiện
$sql_event = "SELECT * FROM thong_bao WHERE id = $thong_bao_id AND la_su_kien = 1";
$res_event = $conn->query($sql_event);
if (!$res_event || $res_event->num_rows == 0) {
    die("Không tìm thấy sự kiện.");
}
$event = $res_event->fetch_assoc();

// Lấy danh sách đăng ký
$cond = "";
if ($role == 'cvht') {
    $cond = " AND l.cvht_id = $user_id";
}

$sql_reg = "SELECT d.*, t.username, t.ho_ten, l.ten_lop 
            FROM dang_ky_thong_bao d 
            JOIN tai_khoan t ON d.sinh_vien_id = t.id 
            JOIN lop_hoc l ON t.lop_id = l.id 
            WHERE d.thong_bao_id = $thong_bao_id $cond 
            ORDER BY d.ngay_dang_ky DESC";
$res_reg = $conn->query($sql_reg);
$registrations = [];
if ($res_reg) {
    while ($row = $res_reg->fetch_assoc()) {
        $registrations[] = $row;
    }
}

// Cấu hình Header để xuất Excel
$filename = "Danh_Sach_Dang_Ky_Su_Kien_" . date('Ymd') . ".xls";
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>';
echo '<body>';

echo '<table border="1" cellpadding="5" cellspacing="0" style="width: 100%; border-collapse: collapse; font-family: Times New Roman; font-size: 13px;">';
$cols = $event['cho_phep_dat_cau_hoi'] ? 6 : 5;

echo '<tr>';
echo '<th colspan="'.$cols.'" style="text-align: center; border:none; font-size: 18px; padding-top: 20px;">DANH SÁCH SINH VIÊN ĐĂNG KÝ THAM GIA SỰ KIÊN</th>';
echo '</tr>';
echo '<tr>';
echo '<th colspan="'.$cols.'" style="text-align: center; border:none; padding-bottom: 20px;">Sự kiện: ' . htmlspecialchars($event['tieu_de']) . '</th>';
echo '</tr>';

// Header bảng
echo '<tr style="background-color: #f2f2f2; text-align: center; font-weight: bold;">';
echo '<th width="5%">STT</th>';
echo '<th width="15%">MSSV</th>';
echo '<th width="20%">Họ Tên</th>';
echo '<th width="15%">Lớp</th>';
echo '<th width="15%">Ngày Đăng Ký</th>';
if($event['cho_phep_dat_cau_hoi']) {
    echo '<th width="30%">Câu Hỏi Gửi BTC</th>';
}
echo '</tr>';

// Dữ liệu sinh viên
if (empty($registrations)) {
    echo '<tr><td colspan="'.$cols.'" style="text-align: center;">Chưa có sinh viên nào đăng ký.</td></tr>';
} else {
    foreach($registrations as $index => $r) {
        echo '<tr>';
        echo '<td style="text-align: center;">' . ($index + 1) . '</td>';
        echo '<td style="text-align: center;">' . htmlspecialchars($r['username']) . '</td>';
        echo '<td>' . htmlspecialchars($r['ho_ten']) . '</td>';
        echo '<td style="text-align: center;">' . htmlspecialchars($r['ten_lop']) . '</td>';
        echo '<td style="text-align: center;">' . date('d/m/Y H:i', strtotime($r['ngay_dang_ky'])) . '</td>';
        if($event['cho_phep_dat_cau_hoi']) {
            echo '<td>' . htmlspecialchars($r['cau_hoi']) . '</td>';
        }
        echo '</tr>';
    }
}

// Chữ ký và tổng hợp như trong ảnh báo cáo
echo '<tr><td colspan="'.$cols.'" style="border:none; height: 30px;"></td></tr>';

// Hàng chữ ký
$col_part = floor($cols / 2);
echo '<tr>';
echo '<td colspan="'.$col_part.'" style="text-align: center; font-weight: bold; border:none; vertical-align: top;">NGƯỜI LẬP DANH SÁCH<br><span style="font-weight: normal; font-style: italic;">(ký và ghi rõ họ tên)</span></td>';
echo '<td colspan="'.($cols - $col_part).'" style="text-align: center; font-weight: bold; border:none; vertical-align: top;"><span style="font-weight: normal; font-style: italic;">ITC, ngày .... tháng .... năm '.date('Y').'</span><br>BAN TỔ CHỨC SỰ KIỆN<br><span style="font-weight: normal; font-style: italic;">(ký và ghi rõ họ tên)</span></td>';
echo '</tr>';

// Khoảng trống ký tên
echo '<tr><td colspan="'.$cols.'" style="border:none; height: 80px;"></td></tr>';

echo '</table>';
echo '</body></html>';
?>
