<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'sinh_vien') {
    redirect('index.php');
}

$user_id = $_SESSION['user_id'];
$ho_ten = $_SESSION['ho_ten'];

// Lấy đợt đánh giá đang mở
$sql_dot = "SELECT * FROM dot_danh_gia WHERE trang_thai = 'dang_mo' ORDER BY id DESC LIMIT 1";
$res_dot = $conn->query($sql_dot);
$dot = $res_dot->fetch_assoc();

$error_msg = '';
$success_msg = '';
$phieu = null;
$is_submitted = false;
$nhom_tieu_chi = [];
$dot_id = 0;

if (!$dot) {
    $error_msg = "Hiện tại không có đợt đánh giá điểm rèn luyện nào đang mở.";
} else {
    $dot_id = $dot['id'];
    $is_expired = false;
    if (!empty($dot['ngay_ket_thuc']) && $dot['ngay_ket_thuc'] != '0000-00-00') {
        if (strtotime(date('Y-m-d')) > strtotime($dot['ngay_ket_thuc'])) {
            $is_expired = true;
        }
    }
    
    // Kiểm tra xem sinh viên đã nộp phiếu chưa
    $sql_phieu = "SELECT * FROM phieu_danh_gia WHERE sinh_vien_id = $user_id AND dot_id = $dot_id";
    $res_phieu = $conn->query($sql_phieu);
    if ($res_phieu && $res_phieu->num_rows > 0) {
        $phieu = $res_phieu->fetch_assoc();
        $is_submitted = ($phieu['trang_thai'] != 'chua_nop');
    }
    
    // Xử lý submit form
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$is_submitted && !$is_expired) {
        $tong_diem_sv = 0;
        
        if (!$phieu) {
            $student_lop_id = (int)$_SESSION['lop_id'];
            // Fetch assigned CVHT
            $sql_assign = "SELECT cvht_id FROM phan_cong_cvht WHERE dot_id = $dot_id AND lop_id = $student_lop_id";
            $res_assign = $conn->query($sql_assign);
            if ($res_assign && $res_assign->num_rows > 0) {
                $student_cvht_id = $res_assign->fetch_assoc()['cvht_id'];
            } else {
                $res_l = $conn->query("SELECT cvht_id FROM lop_hoc WHERE id = $student_lop_id");
                $student_cvht_id = $res_l->fetch_assoc()['cvht_id'] ?? 'NULL';
            }
            
            $sql_insert = "INSERT INTO phieu_danh_gia (sinh_vien_id, dot_id, trang_thai, ngay_nop, lop_id, cvht_id) 
                           VALUES ($user_id, $dot_id, 'cho_cvht_duyet', NOW(), $student_lop_id, $student_cvht_id)";
            $conn->query($sql_insert);
            $phieu_id = $conn->insert_id;
        } else {
            $phieu_id = $phieu['id'];
            $conn->query("UPDATE phieu_danh_gia SET trang_thai = 'cho_cvht_duyet', ngay_nop = NOW() WHERE id = $phieu_id");
        }
        
        if (isset($_POST['diem'])) {
            foreach ($_POST['diem'] as $tieu_chi_id => $diem) {
                $diem = (int)$diem;
                // Validation (tránh nhập quá điểm tối đa, sẽ làm thêm logic check sau)
                $minh_chung = isset($_POST['minh_chung'][$tieu_chi_id]) ? $conn->real_escape_string($_POST['minh_chung'][$tieu_chi_id]) : '';
                
                $tong_diem_sv += $diem;
                
                $check_ct = $conn->query("SELECT id FROM chi_tiet_diem WHERE phieu_id = $phieu_id AND tieu_chi_id = $tieu_chi_id");
                if ($check_ct && $check_ct->num_rows > 0) {
                    $conn->query("UPDATE chi_tiet_diem SET diem_sv = $diem, minh_chung = '$minh_chung' WHERE phieu_id = $phieu_id AND tieu_chi_id = $tieu_chi_id");
                } else {
                    $conn->query("INSERT INTO chi_tiet_diem (phieu_id, tieu_chi_id, diem_sv, minh_chung) VALUES ($phieu_id, $tieu_chi_id, $diem, '$minh_chung')");
                }
            }
        }
        
        $conn->query("UPDATE phieu_danh_gia SET tong_diem_sv = $tong_diem_sv WHERE id = $phieu_id");
        redirect('student_assessment.php?success=1');
    }
    
    // Lấy danh sách nhóm và tiêu chí
    $res_nhom = $conn->query("SELECT * FROM nhom_tieu_chi ORDER BY thu_tu ASC");
    if ($res_nhom) {
        while ($row = $res_nhom->fetch_assoc()) {
            $nhom_id = $row['id'];
            $res_tc = $conn->query("SELECT * FROM tieu_chi WHERE nhom_id = $nhom_id");
            $tieu_chis = [];
            if ($res_tc) {
                while ($tc = $res_tc->fetch_assoc()) {
                    $tc['diem_sv'] = $tc['diem_mac_dinh'];
                    $tc['minh_chung'] = '';
                    $tc['diem_tru'] = 0;
                    $tc['ghi_chu_tru'] = '';
                    
                    if ($phieu) {
                        $p_id = $phieu['id'];
                        $tc_id = $tc['id'];
                        $r_diem = $conn->query("SELECT diem_sv, diem_cvht, diem_khoa, minh_chung, diem_tru, ghi_chu_tru FROM chi_tiet_diem WHERE phieu_id = $p_id AND tieu_chi_id = $tc_id");
                        if ($r_diem && $r_diem->num_rows > 0) {
                            $ct = $r_diem->fetch_assoc();
                            $tc['diem_sv'] = $ct['diem_sv'];
                            $tc['diem_cvht'] = $ct['diem_cvht'];
                            $tc['diem_khoa'] = $ct['diem_khoa'];
                            $tc['minh_chung'] = $ct['minh_chung'];
                            $tc['diem_tru'] = (int)$ct['diem_tru'];
                            $tc['ghi_chu_tru'] = $ct['ghi_chu_tru'];
                        }
                    }
                    
                    // Ràng buộc diem_sv mặc định nếu có điểm trừ (đối với lần đầu chưa đánh giá)
                    if (!$is_submitted && $tc['diem_tru'] > 0) {
                        $tc['diem_sv'] = max(0, $tc['diem_sv'] - $tc['diem_tru']);
                    }
                    
                    $tieu_chis[] = $tc;
                }
            }
            $row['tieu_chi_list'] = $tieu_chis;
            $nhom_tieu_chi[] = $row;
        }
    }
}

if (isset($_GET['success'])) {
    $success_msg = "Nộp phiếu đánh giá điểm rèn luyện thành công! Vui lòng chờ CVHT duyệt.";
    $is_submitted = true;
}

$page_title = 'Tự Đánh Giá ĐRL - ITC';
require_once 'layout_header.php';
?>

<?php if($error_msg): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i> <?php echo $error_msg; ?></div>
    <?php elseif($dot): ?>
        
        <?php if($success_msg): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success_msg; ?></div>
        <?php endif; ?>
        
        <?php if($is_submitted): 
            $status_text = ['cho_cvht_duyet'=>'Chờ CVHT duyệt', 'cho_khoa_duyet'=>'Chờ Khoa duyệt', 'da_duyet'=>'Đã hoàn tất'];
            $txt = isset($status_text[$phieu['trang_thai']]) ? $status_text[$phieu['trang_thai']] : $phieu['trang_thai'];
        ?>
            <div class="alert alert-warning">
                <div style="font-size: 1.5rem;"><i class="fas fa-info-circle"></i></div>
                <div>
                    <div>Phiếu đánh giá của bạn đã nộp. Trạng thái hiện tại: <strong><?php echo $txt; ?></strong>.</div>
                    <?php if($phieu['trang_thai'] == 'cho_khoa_duyet' || $phieu['trang_thai'] == 'da_duyet'): ?>
                        <div style="margin-top: 5px;">Điểm do CVHT chấm: <strong style="color: #B45309;"><?php echo $phieu['tong_diem_cvht']; ?></strong></div>
                    <?php endif; ?>
                    <?php if($phieu['trang_thai'] == 'da_duyet'): ?>
                        <div style="margin-top: 5px;">Điểm do Khoa duyệt (Cuối cùng): <strong style="color: #15803D;"><?php echo $phieu['tong_diem_khoa']; ?></strong> &bull; Xếp loại: <strong style="background: var(--secondary); color: var(--primary); padding: 2px 8px; border-radius: 4px;"><?php echo $phieu['xep_loai']; ?></strong></div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2><?php echo htmlspecialchars($dot['ten_dot']); ?></h2>
                <p><i class="fas fa-graduation-cap" style="color: var(--primary);"></i> Học kỳ <?php echo $dot['hoc_ky']; ?> - Năm học <?php echo $dot['nam_hoc']; ?></p>
                <?php if (!empty($dot['ngay_ket_thuc']) && $dot['ngay_ket_thuc'] != '0000-00-00'): ?>
                    <div class="badge-deadline"><i class="fas fa-clock"></i> Hạn chót: <?php echo date('d/m/Y', strtotime($dot['ngay_ket_thuc'])); ?></div>
                <?php endif; ?>
                <?php if ($is_expired && !$is_submitted): ?>
                    <div class="alert alert-error" style="margin-top: 1.5rem; margin-bottom: 0;"><i class="fas fa-times-circle"></i> Đã hết hạn nộp phiếu đánh giá điểm rèn luyện đợt này.</div>
                <?php endif; ?>
            </div>
            
            <form method="POST" action="">
                <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th width="5%" style="text-align: center;">STT</th>
                            <th width="40%">Nội dung đánh giá</th>
                            <th width="10%" style="text-align: center;">Điểm Tối Đa</th>
                            <th width="8%" style="text-align: center;">Điểm Trừ</th>
                            <th width="10%" style="text-align: center;">SV Tự Chấm</th>
                            <?php if($phieu && in_array($phieu['trang_thai'], ['cho_khoa_duyet', 'da_duyet'])): ?><th width="10%" style="text-align: center;">CVHT Chấm</th><?php endif; ?>
                            <?php if($phieu && $phieu['trang_thai'] == 'da_duyet'): ?><th width="10%" style="text-align: center;">Khoa Chấm</th><?php endif; ?>
                            <th width="25%">Minh chứng (Link URL)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_max = 0;
                        $total_sv = 0;
                        foreach($nhom_tieu_chi as $nhom): 
                            $total_max += $nhom['diem_toi_da'];
                        ?>
                            <tr class="nhom-tieu-chi">
                                <td colspan="2"><?php echo htmlspecialchars($nhom['ten_nhom']); ?></td>
                                <td style="text-align: center;"><?php echo $nhom['diem_toi_da']; ?></td>
                                <td></td>
                                <td></td>
                                <?php if($phieu && in_array($phieu['trang_thai'], ['cho_khoa_duyet', 'da_duyet'])): ?><td></td><?php endif; ?>
                                <?php if($phieu && $phieu['trang_thai'] == 'da_duyet'): ?><td></td><?php endif; ?>
                                <td></td>
                            </tr>
                            
                            <?php foreach($nhom['tieu_chi_list'] as $index => $tc): 
                                $total_sv += $tc['diem_sv'];
                            ?>
                                <tr>
                                    <td style="text-align: center; color: var(--gray); font-weight: 600;"><?php echo $index + 1; ?></td>
                                    <td class="tc-content"><?php echo htmlspecialchars($tc['noi_dung']); ?></td>
                                    <td style="text-align: center; color: var(--primary); font-weight: 700; font-family: var(--font-heading); font-size: 1.1rem;"><?php echo $tc['diem_toi_da']; ?></td>
                                    <td style="text-align: center; font-weight: 700; font-family: var(--font-heading); font-size: 1.1rem;">
                                        <?php if ($tc['diem_tru'] > 0): ?>
                                            <div style="position: relative; display: inline-block; width: 100%;">
                                                <button type="button" onclick="const p = this.nextElementSibling; document.querySelectorAll('.penalty-popover').forEach(el => { if(el !== p) el.style.display = 'none'; }); p.style.display = p.style.display === 'none' ? 'block' : 'none';" style="background: none; border: none; color: #DC2626; border-bottom: 1px dotted #DC2626; cursor: pointer; padding: 0; font-family: inherit; font-size: inherit; font-weight: bold; display: inline-flex; align-items: center; gap: 4px; justify-content: center; outline: none;">
                                                    -<?php echo $tc['diem_tru']; ?> <i class="fas fa-info-circle" style="font-size: 0.9em;"></i>
                                                </button>
                                                <div class="penalty-popover" style="display: none; position: absolute; right: 50%; transform: translateX(50%); top: 100%; margin-top: 10px; background: #1E293B; color: white; padding: 12px; border-radius: 8px; font-size: 0.85rem; width: max-content; max-width: 220px; z-index: 100; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.5); text-align: left; font-weight: normal; line-height: 1.5;">
                                                    <div style="font-weight: bold; margin-bottom: 8px; color: #FCA5A5; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 5px; display: flex; justify-content: space-between; align-items: center;">
                                                        <span><i class="fas fa-exclamation-circle"></i> Chi tiết bị trừ</span>
                                                        <i class="fas fa-times" onclick="this.closest('.penalty-popover').style.display='none';" style="cursor: pointer; padding: 2px;"></i>
                                                    </div>
                                                    <?php echo htmlspecialchars($tc['ghi_chu_tru']); ?>
                                                    <div style="position: absolute; left: 50%; transform: translateX(-50%); top: -6px; width: 0; height: 0; border-left: 6px solid transparent; border-right: 6px solid transparent; border-bottom: 6px solid #1E293B;"></div>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <span style="color: var(--gray);">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <input type="number" 
                                               name="diem[<?php echo $tc['id']; ?>]" 
                                               class="input-diem" 
                                               min="0" max="<?php echo max(0, $tc['diem_toi_da'] - $tc['diem_tru']); ?>" 
                                               value="<?php echo $tc['diem_sv']; ?>"
                                               <?php echo ($is_submitted || ($is_expired && !$is_submitted)) ? 'readonly disabled' : ''; ?>>
                                    </td>
                                    <?php if($phieu && in_array($phieu['trang_thai'], ['cho_khoa_duyet', 'da_duyet'])): ?>
                                        <td style="text-align: center; color: #B45309; font-weight: 700; font-family: var(--font-heading); font-size: 1.1rem; background: #FFFBEB;"><?php echo isset($tc['diem_cvht']) ? $tc['diem_cvht'] : '-'; ?></td>
                                    <?php endif; ?>
                                    <?php if($phieu && $phieu['trang_thai'] == 'da_duyet'): ?>
                                        <td style="text-align: center; color: #065F46; font-weight: 700; font-family: var(--font-heading); font-size: 1.1rem; background: #F0FDF4;"><?php echo isset($tc['diem_khoa']) ? $tc['diem_khoa'] : '-'; ?></td>
                                    <?php endif; ?>
                                    <td>
                                        <input type="url" 
                                               name="minh_chung[<?php echo $tc['id']; ?>]" 
                                               class="input-minh-chung" 
                                               placeholder="https://..." 
                                               value="<?php echo htmlspecialchars($tc['minh_chung']); ?>"
                                               <?php echo ($is_submitted || ($is_expired && !$is_submitted)) ? 'readonly disabled' : ''; ?>>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-footer">
                            <td colspan="2" style="text-align: right;">TỔNG CỘNG ĐIỂM:</td>
                            <td style="text-align: center; color: var(--secondary);"><?php echo $total_max; ?></td>
                            <td></td>
                            <td style="text-align: center; color: #34D399;" id="tong-diem-sv"><?php echo $total_sv; ?></td>
                            <?php if($phieu && in_array($phieu['trang_thai'], ['cho_khoa_duyet', 'da_duyet'])): ?><td style="text-align: center; color: var(--secondary);"><?php echo $phieu['tong_diem_cvht']; ?></td><?php endif; ?>
                            <?php if($phieu && $phieu['trang_thai'] == 'da_duyet'): ?><td style="text-align: center; color: var(--secondary);"><?php echo $phieu['tong_diem_khoa']; ?></td><?php endif; ?>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
                </div>
                
                <div class="submit-area">
                    <button type="submit" class="btn-submit" <?php echo ($is_submitted || $is_expired) ? 'disabled' : ''; ?>>
                        <i class="fas fa-paper-plane"></i> Gửi Phiếu Đánh Giá
                    </button>
                    <p style="color: var(--gray); font-size: 0.95rem;"><i class="fas fa-shield-alt"></i> Lưu ý: Sau khi nộp, bạn không thể tự chỉnh sửa điểm tự chấm.</p>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<script>
    // Tính tổng điểm realtime
    const inputs = document.querySelectorAll('.input-diem');
    const tongDiemEl = document.getElementById('tong-diem-sv');
    
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            let total = 0;
            inputs.forEach(inp => {
                let val = parseInt(inp.value) || 0;
                let max = parseInt(inp.getAttribute('max')) || 0;
                if(val > max) {
                    val = max;
                    inp.value = max;
                }
                total += val;
            });
            tongDiemEl.textContent = total;
        });
    });
</script>

<?php require_once 'layout_footer.php'; ?>
