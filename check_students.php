<?php
require 'config.php';
require_once 'vendor/autoload.php';
use Shuchkin\SimpleXLS;
$xls = SimpleXLS::parse('TongHopDiemDanhMon_20260615_20260618.xls');
$found = 0;
$missing = 0;
if($xls) {
    foreach($xls->rows() as $i => $row) {
        if ($i < 2) continue; // skip header
        $mssv = isset($row[2]) ? trim($row[2]) : '';
        if($mssv && is_numeric($mssv)) {
            $q = $conn->query("SELECT id FROM tai_khoan WHERE username='$mssv'");
            if($q && $q->num_rows > 0) {
                $found++;
            } else {
                $missing++;
            }
        }
    }
}
echo "Found: $found, Missing: $missing\n";
