<?php
require 'config.php';
$conn->query('DROP TABLE IF EXISTS dang_ky_su_kien;');
$conn->query('DROP TABLE IF EXISTS su_kien;');
$conn->query('ALTER TABLE thong_bao ADD COLUMN la_su_kien TINYINT(1) DEFAULT 0, ADD COLUMN cho_phep_dat_cau_hoi TINYINT(1) DEFAULT 0;');
$conn->query('CREATE TABLE IF NOT EXISTS dang_ky_thong_bao (
  id INT AUTO_INCREMENT PRIMARY KEY,
  thong_bao_id INT NOT NULL,
  sinh_vien_id INT NOT NULL,
  cau_hoi TEXT NULL,
  ngay_dang_ky DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');
echo 'OK';
?>
