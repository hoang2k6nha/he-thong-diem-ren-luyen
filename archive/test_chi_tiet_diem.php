<?php
require 'config.php';
$res = $conn->query("SHOW CREATE TABLE chi_tiet_diem");
print_r($res->fetch_assoc());
