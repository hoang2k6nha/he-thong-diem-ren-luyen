<?php
require_once "vendor/autoload.php";
use Shuchkin\SimpleXLS;
$xls = SimpleXLS::parse("TongHopDiemDanhMon_20260615_20260618.xls");
if ($xls) {
    print_r(array_slice($xls->rows(), 8, 3));
} else {
    echo "Error parsing XLS";
}
