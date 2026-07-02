<?php
require 'config.php';
$res = $conn->query("SHOW COLUMNS FROM tai_khoan LIKE 'vai_tro'");
print_r($res->fetch_assoc());
