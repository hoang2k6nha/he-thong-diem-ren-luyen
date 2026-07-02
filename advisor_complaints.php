<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'cvht' && !has_permission('advisor_complaints'))) redirect('index.php');

$uid = $_SESSION['user_id'];
$msg = '';
$msg_type = 'success';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'reply') {
    $complaint_id = (int)$_POST['complaint_id'];
    $tra_loi = $conn->real_escape_string($_POST['tra_loi']);
    
    // Check if complaint belongs to this CVHT
    $check_sql = "SELECT k.id FROM khieu_nai k JOIN tai_khoan t ON k.sinh_vien_id = t.id JOIN lop_hoc l ON t.lop_id = l.id WHERE k.id = $complaint_id AND " . ($_SESSION["role"] !== "cvht" ? "1=1" : "l.cvht_id = $uid") . "";
    $check = $conn->query($check_sql);
    if ($check->num_rows > 0) {
        $sql = "UPDATE khieu_nai SET tra_loi = '$tra_loi', trang_thai = 'da_phan_hoi', ngay_tra_loi = NOW() WHERE id = $complaint_id";
        if ($conn->query($sql)) {
            $msg = "Đã gửi phản hồi thành công!";
        } else {
            $msg = "Có lỗi xảy ra khi lưu phản hồi."; $msg_type = 'error';
        }
    } else {
        $msg = "Bạn không có quyền phản hồi khiếu nại này."; $msg_type = 'error';
    }
}

// Lấy danh sách khiếu nại
$complaints = [];
$sql = "SELECT k.*, t.username, t.ho_ten, l.ten_lop 
        FROM khieu_nai k 
        JOIN tai_khoan t ON k.sinh_vien_id = t.id 
        JOIN lop_hoc l ON t.lop_id = l.id 
        WHERE k.nguoi_nhan_role = 'cvht' AND " . ($_SESSION["role"] !== "cvht" ? "1=1" : "l.cvht_id = $uid") . " 
        ORDER BY k.trang_thai ASC, k.id DESC"; // Pending first
$res = $conn->query($sql);
if ($res) {
    while($row = $res->fetch_assoc()) {
        $complaints[] = $row;
    }
}

$page_title = 'Quản Lý Khiếu Nại - CVHT';
require_once 'layout_header.php';
?>

<h2 style="color: var(--primary-blue); margin-bottom: 20px;"><i class="fas fa-envelope-open-text"></i> Danh sách Hỏi Đáp / Khiếu Nại</h2>
    
    <?php if($msg): ?>
        <div class="alert alert-<?php echo $msg_type; ?>"><?php echo $msg; ?></div>
    <?php endif; ?>
    
    <?php if(empty($complaints)): ?>
        <div class="complaint-card" style="padding: 40px; text-align: center; color: #64748B;">
            <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.5;"></i>
            <p>Hiện không có khiếu nại hay câu hỏi nào từ sinh viên.</p>
        </div>
    <?php else: ?>
        <?php foreach($complaints as $c): ?>
            <div class="complaint-card">
                <div class="c-header">
                    <div>
                        <div class="c-title"><?php echo htmlspecialchars($c['tieu_de']); ?></div>
                        <div class="c-meta">
                            Sinh viên: <strong><?php echo htmlspecialchars($c['ho_ten']); ?> (<?php echo $c['username']; ?>)</strong> - Lớp: <?php echo $c['ten_lop']; ?> <br>
                            Ngày gửi: <?php echo date('d/m/Y H:i', strtotime($c['ngay_tao'])); ?>
                        </div>
                    </div>
                    <div>
                        <?php if($c['trang_thai'] == 'cho_phan_hoi'): ?>
                            <span class="badge badge-pending">Chờ phản hồi</span>
                        <?php else: ?>
                            <span class="badge badge-answered">Đã phản hồi</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="c-body">
                    <div style="font-weight: 600; margin-bottom: 5px;">Nội dung của sinh viên:</div>
                    <div class="c-content"><?php echo htmlspecialchars($c['noi_dung']); ?></div>
                    
                    <?php if($c['trang_thai'] == 'cho_phan_hoi'): ?>
                        <form method="POST">
                            <input type="hidden" name="action" value="reply">
                            <input type="hidden" name="complaint_id" value="<?php echo $c['id']; ?>">
                            <div style="font-weight: 600; margin-bottom: 5px;">Trả lời sinh viên <span style="color: red;">*</span>:</div>
                            <textarea name="tra_loi" class="form-control" rows="4" placeholder="Nhập câu trả lời hoặc giải quyết khiếu nại..." required></textarea>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-reply"></i> Gửi Phản Hồi</button>
                        </form>
                    <?php else: ?>
                        <div class="c-answer">
                            <div style="font-weight: 600; color: #065F46; margin-bottom: 5px;"><i class="fas fa-check-circle"></i> Đã trả lời (<?php echo date('d/m/Y H:i', strtotime($c['ngay_tra_loi'])); ?>):</div>
                            <div style="white-space: pre-line;"><?php echo htmlspecialchars($c['tra_loi']); ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

<?php require_once 'layout_footer.php'; ?>
