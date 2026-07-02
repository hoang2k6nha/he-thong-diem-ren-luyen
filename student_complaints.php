<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'sinh_vien' && !has_permission('student_complaints'))) redirect('index.php');

$sv_id = $_SESSION['user_id'];
$ho_ten = $_SESSION['ho_ten'];
$msg = '';
$msg_type = 'success';

// Xử lý gửi khiếu nại/câu hỏi mới
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_complaint') {
    $nguoi_nhan_role = $_POST['nguoi_nhan_role'] == 'khoa' ? 'khoa' : 'cvht';
    $tieu_de = $conn->real_escape_string($_POST['tieu_de']);
    $noi_dung = $conn->real_escape_string($_POST['noi_dung']);
    
    $sql = "INSERT INTO khieu_nai (sinh_vien_id, nguoi_nhan_role, tieu_de, noi_dung, trang_thai) VALUES ($sv_id, '$nguoi_nhan_role', '$tieu_de', '$noi_dung', 'cho_phan_hoi')";
    if ($conn->query($sql)) {
        $msg = "Đã gửi câu hỏi/khiếu nại thành công! Vui lòng chờ phản hồi.";
    } else {
        $msg = "Có lỗi xảy ra. Vui lòng thử lại.";
        $msg_type = 'error';
    }
}

// Lấy danh sách khiếu nại của sinh viên
$complaints = [];
$res = $conn->query("SELECT * FROM khieu_nai WHERE sinh_vien_id = $sv_id ORDER BY id DESC");
if ($res) {
    while($row = $res->fetch_assoc()) {
        $complaints[] = $row;
    }
}

$page_title = 'Hỏi Đáp & Khiếu Nại - Sinh Viên';
require_once 'layout_header.php';
?>

<?php if($msg): ?>
        <div class="alert alert-<?php echo $msg_type; ?>"><i class="fas fa-<?php echo $msg_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i> <?php echo $msg; ?></div>
    <?php endif; ?>

    <div class="card">
        <h2 class="card-title"><i class="fas fa-paper-plane" style="color: var(--secondary);"></i> Gửi Câu Hỏi / Khiếu Nại Mới</h2>
        <form method="POST" action="">
            <input type="hidden" name="action" value="add_complaint">
            
            <div class="form-group">
                <label>Gửi đến <span style="color: #EF4444;">*</span></label>
                <select name="nguoi_nhan_role" class="form-control" required>
                    <option value="cvht">Cố vấn học tập (CVHT)</option>
                    <option value="khoa">Ban Chủ nhiệm Khoa / CTSV</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Tiêu đề <span style="color: #EF4444;">*</span></label>
                <input type="text" name="tieu_de" class="form-control" placeholder="Nhập tiêu đề ngắn gọn..." required>
            </div>
            
            <div class="form-group">
                <label>Nội dung chi tiết <span style="color: #EF4444;">*</span></label>
                <textarea name="noi_dung" class="form-control" rows="5" placeholder="Mô tả chi tiết câu hỏi hoặc vấn đề khiếu nại điểm rèn luyện của bạn..." required></textarea>
            </div>
            
            <button type="submit" class="btn-submit"><i class="fas fa-paper-plane"></i> Gửi đi</button>
        </form>
    </div>

    <div class="card">
        <h2 class="card-title" style="margin-bottom: 2rem;"><i class="fas fa-history" style="color: var(--secondary);"></i> Lịch Sử Hỏi Đáp / Khiếu Nại</h2>
        
        <?php if(empty($complaints)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>Bạn chưa có câu hỏi hay khiếu nại nào.</p>
            </div>
        <?php else: ?>
            <?php foreach($complaints as $c): ?>
                <div class="complaint-item">
                    <div class="complaint-header">
                        <div>
                            <div class="complaint-title-text"><?php echo htmlspecialchars($c['tieu_de']); ?></div>
                            <div class="complaint-meta">
                                <span><i class="fas fa-user-tie" style="color: #94A3B8;"></i> <?php echo $c['nguoi_nhan_role'] == 'khoa' ? 'Khoa / CTSV' : 'Cố vấn học tập'; ?></span>
                                <span>&bull;</span>
                                <span><i class="fas fa-clock" style="color: #94A3B8;"></i> <?php echo date('d/m/Y H:i', strtotime($c['ngay_tao'])); ?></span>
                            </div>
                        </div>
                        <div>
                            <?php if($c['trang_thai'] == 'cho_phan_hoi'): ?>
                                <span class="badge badge-pending"><i class="fas fa-hourglass-half"></i> Chờ phản hồi</span>
                            <?php else: ?>
                                <span class="badge badge-answered"><i class="fas fa-check"></i> Đã phản hồi</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="complaint-body question-box">
                        <div style="white-space: pre-line;"><?php echo htmlspecialchars($c['noi_dung']); ?></div>
                    </div>
                    <?php if($c['trang_thai'] == 'da_phan_hoi'): ?>
                    <div class="answer-box">
                        <div class="answer-title"><i class="fas fa-reply"></i> Phản hồi từ <?php echo $c['nguoi_nhan_role'] == 'khoa' ? 'Khoa/CTSV' : 'CVHT'; ?> (<?php echo date('d/m/Y H:i', strtotime($c['ngay_tra_loi'])); ?>):</div>
                        <div class="answer-content" style="white-space: pre-line;"><?php echo htmlspecialchars($c['tra_loi']); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

<?php require_once 'layout_footer.php'; ?>
