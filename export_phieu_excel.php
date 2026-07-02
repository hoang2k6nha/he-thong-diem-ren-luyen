<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    die("Vui lòng đăng nhập.");
}

$sv_id = isset($_GET['sv_id']) ? (int)$_GET['sv_id'] : 0;
$dot_id = isset($_GET['dot_id']) ? (int)$_GET['dot_id'] : 0;

if (!$sv_id || !$dot_id) {
    die("Dữ liệu không hợp lệ.");
}

// Lấy thông tin sinh viên
$sql_sv = "SELECT t.*, l.ten_lop 
           FROM tai_khoan t 
           LEFT JOIN lop_hoc l ON t.lop_id = l.id 
           WHERE t.id = $sv_id AND t.vai_tro = 'sinh_vien'";
$res_sv = $conn->query($sql_sv);
if (!$res_sv || $res_sv->num_rows == 0) die("Không tìm thấy sinh viên.");
$sv = $res_sv->fetch_assoc();

// Lấy thông tin đợt
$res_dot = $conn->query("SELECT * FROM dot_danh_gia WHERE id = $dot_id");
$dot = $res_dot->fetch_assoc();
if (!$dot) die("Không tìm thấy đợt đánh giá.");

// Lấy phiếu đánh giá
$sql_phieu = "SELECT * FROM phieu_danh_gia WHERE sinh_vien_id = $sv_id AND dot_id = $dot_id";
$res_phieu = $conn->query($sql_phieu);
$phieu = $res_phieu->fetch_assoc();

// Cấu hình Header để xuất Excel
$filename = "Phieu_Danh_Gia_" . $sv['username'] . ".xls";
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>';
echo '<body>';

// Bắt đầu vẽ bảng giống Phiếu Đánh Giá
echo '<table border="1" cellpadding="5" cellspacing="0" style="width: 100%; border-collapse: collapse; font-family: Times New Roman; font-size: 13px;">';
echo '<tr>';
echo '<th colspan="5" style="text-align: center; border:none; font-size: 16px;">BỘ GIÁO DỤC VÀ ĐÀO TẠO<br>TRƯỜNG CAO ĐẲNG CÔNG NGHỆ THÔNG TIN TP.HCM</th>';
echo '</tr>';
echo '<tr>';
echo '<th colspan="5" style="text-align: center; border:none; font-size: 18px; padding-top: 20px;">PHIẾU ĐÁNH GIÁ KẾT QUẢ RÈN LUYỆN SINH VIÊN</th>';
echo '</tr>';
echo '<tr>';
echo '<th colspan="5" style="text-align: center; border:none; padding-bottom: 20px;">Học kỳ: ' . $dot['hoc_ky'] . ' - Năm học: ' . $dot['nam_hoc'] . '</th>';
echo '</tr>';

echo '<tr>';
echo '<td colspan="5" style="border:none;">Họ và tên sinh viên: <b>' . $sv['ho_ten'] . '</b> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; MSSV: <b>' . $sv['username'] . '</b> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Lớp: <b>' . $sv['ten_lop'] . '</b></td>';
echo '</tr>';
echo '<tr><td colspan="5" style="border:none; height: 10px;"></td></tr>';

// Lấy tiêu chí và vẽ bảng
echo '<tr style="background-color: #f2f2f2;">';
echo '<th width="5%">STT</th>';
echo '<th width="50%">Nội dung đánh giá</th>';
echo '<th width="15%">Điểm tối đa</th>';
echo '<th width="15%">SV tự chấm</th>';
echo '<th width="15%">CVHT chấm</th>';
echo '</tr>';

$res_nhom = $conn->query("SELECT * FROM nhom_tieu_chi ORDER BY thu_tu ASC");
$stt_nhom = 1;
while ($nhom = $res_nhom->fetch_assoc()) {
    echo '<tr style="font-weight: bold; background-color: #f9f9f9;">';
    echo '<td style="text-align: center;">' . $stt_nhom . '</td>';
    echo '<td>' . htmlspecialchars($nhom['ten_nhom']) . '</td>';
    echo '<td style="text-align: center;">' . $nhom['diem_toi_da'] . '</td>';
    echo '<td></td><td></td>';
    echo '</tr>';
    
    $res_tc = $conn->query("SELECT tc.*, ct.diem_sv, ct.diem_cvht 
                            FROM tieu_chi tc 
                            LEFT JOIN chi_tiet_phieu ct ON tc.id = ct.tieu_chi_id AND ct.phieu_id = " . ($phieu ? $phieu['id'] : 0) . "
                            WHERE tc.nhom_id = " . $nhom['id']);
    $stt_tc = 1;
    while ($tc = $res_tc->fetch_assoc()) {
        echo '<tr>';
        echo '<td style="text-align: center;">1.' . $stt_tc . '</td>';
        echo '<td>' . htmlspecialchars($tc['noi_dung']) . '</td>';
        echo '<td style="text-align: center;">' . $tc['diem_toi_da'] . '</td>';
        echo '<td style="text-align: center;">' . ($phieu ? $tc['diem_sv'] : '') . '</td>';
        echo '<td style="text-align: center;">' . ($phieu ? $tc['diem_cvht'] : '') . '</td>';
        echo '</tr>';
        $stt_tc++;
    }
    $stt_nhom++;
}

// Chữ ký và tổng hợp như trong ảnh
echo '<tr>';
echo '<td colspan="5" style="border:none; height: 30px;"></td>';
echo '</tr>';

// Nhận xét
echo '<tr>';
echo '<td colspan="2" style="font-weight: bold; border-right: none; border-bottom: 1px dotted #000;">Nhận xét của Phòng CTSV:</td>';
echo '<td colspan="3" style="border-left: none; border-bottom: 1px dotted #000;"></td>';
echo '</tr>';

// Điểm tổng hợp
echo '<tr>';
echo '<td colspan="2" style="border-right: none; border-bottom: 1px dotted #000; border-top: none;">Điểm tổng hợp</td>';
echo '<td colspan="3" style="font-weight: bold; text-align: center; border-left: none; border-bottom: 1px dotted #000; border-top: none;">' . ($phieu ? $phieu['tong_diem_khoa'] : '') . '</td>';
echo '</tr>';

// Xếp loại
echo '<tr>';
echo '<td colspan="2" style="border-right: none; border-bottom: 1px dotted #000; border-top: none;">Xếp loại</td>';
echo '<td colspan="3" style="font-weight: bold; text-align: center; border-left: none; border-bottom: 1px dotted #000; border-top: none;">' . ($phieu ? $phieu['xep_loai'] : '') . '</td>';
echo '</tr>';

echo '<tr><td colspan="5" style="border:none; height: 20px;"></td></tr>';

// Hàng chữ ký
echo '<tr>';
echo '<td colspan="2" style="text-align: center; font-weight: bold; border:none; vertical-align: top;">CỐ VẤN HỌC TẬP<br><span style="font-weight: normal; font-style: italic;">(ký và ghi rõ họ tên)</span></td>';
echo '<td style="text-align: center; font-weight: bold; border:none; vertical-align: top;">BAN CÁN SỰ LỚP<br><span style="font-weight: normal; font-style: italic;">(ký và ghi rõ họ tên)</span></td>';
echo '<td colspan="2" style="text-align: center; font-weight: bold; border:none; vertical-align: top;"><span style="font-weight: normal; font-style: italic;">ITC, ngày .... tháng .... năm '.date('Y').'</span><br>SINH VIÊN TỰ ĐÁNH GIÁ<br><span style="font-weight: normal; font-style: italic;">(ký và ghi rõ họ tên)</span></td>';
echo '</tr>';

// Khoảng trống ký tên
echo '<tr><td colspan="5" style="border:none; height: 80px;"></td></tr>';

// Tên sinh viên
echo '<tr>';
echo '<td colspan="2" style="border:none;"></td>';
echo '<td style="border:none;"></td>';
echo '<td colspan="2" style="text-align: center; font-weight: bold; border:none;">' . $sv['ho_ten'] . '</td>';
echo '</tr>';

// Phòng CTSV
echo '<tr><td colspan="5" style="border:none; height: 30px;"></td></tr>';
echo '<tr>';
echo '<td colspan="5" style="text-align: center; font-weight: bold; border:none; font-size: 16px;">PHÒNG CÔNG TÁC SINH VIÊN</td>';
echo '</tr>';

echo '</table>';
echo '</body></html>';
?>
