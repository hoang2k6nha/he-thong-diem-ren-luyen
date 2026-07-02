<?php
require_once "vendor/autoload.php";
use Shuchkin\SimpleXLS;
$xls = SimpleXLS::parse("Mẫu ĐGRLHK1_NH26_27.xls");
if ($xls) {
    print_r(array_slice($xls->rows(), 0, 10));
} else {
    echo "Error parsing XLS";
}
