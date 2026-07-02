<?php
require 'config.php';

$sql1 = "CREATE TABLE IF NOT EXISTS `su_kien` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tieu_de` VARCHAR(255) NOT NULL,
  `noi_dung` TEXT NOT NULL,
  `cho_phep_dat_cau_hoi` TINYINT(1) DEFAULT 0,
  `ngay_tao` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `nguoi_tao_id` INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

$sql2 = "CREATE TABLE IF NOT EXISTS `dang_ky_su_kien` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `su_kien_id` INT NOT NULL,
  `sinh_vien_id` INT NOT NULL,
  `cau_hoi` TEXT NULL,
  `ngay_dang_ky` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

$conn->query($sql1);
$conn->query($sql2);
echo "OK";
?>
