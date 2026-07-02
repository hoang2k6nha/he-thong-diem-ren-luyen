<?php
require 'config.php';
$conn->query("UPDATE tai_khoan SET permissions = '' WHERE vai_tro = 'sinh_vien'");
echo "Cleared permissions for all sinh_vien";
