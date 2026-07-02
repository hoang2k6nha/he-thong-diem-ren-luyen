<?php
require 'config.php';
$res = $conn->query('SHOW CREATE TABLE phieu_danh_gia');
print_r($res->fetch_assoc());
