<?php
$conn = new mysqli('localhost', 'root', '', 'itc_diemrenluyen');

$conn->query("CREATE TABLE IF NOT EXISTS phan_cong_cvht (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dot_id INT NOT NULL,
    lop_id INT NOT NULL,
    cvht_id INT NOT NULL,
    UNIQUE KEY (dot_id, lop_id)
)");

$conn->query("ALTER TABLE phieu_danh_gia ADD COLUMN lop_id INT NULL AFTER dot_id");
$conn->query("ALTER TABLE phieu_danh_gia ADD COLUMN cvht_id INT NULL AFTER lop_id");

// Seed current data
$conn->query("INSERT IGNORE INTO phan_cong_cvht (dot_id, lop_id, cvht_id)
SELECT (SELECT id FROM dot_danh_gia ORDER BY id DESC LIMIT 1), id, cvht_id 
FROM lop_hoc 
WHERE cvht_id IS NOT NULL AND (SELECT id FROM dot_danh_gia ORDER BY id DESC LIMIT 1) IS NOT NULL");

// Also update existing phieu_danh_gia with current lop_id and cvht_id
$conn->query("UPDATE phieu_danh_gia p
JOIN tai_khoan t ON p.sinh_vien_id = t.id
JOIN lop_hoc l ON t.lop_id = l.id
SET p.lop_id = t.lop_id, p.cvht_id = l.cvht_id");

echo "DB updated successfully.\n";
