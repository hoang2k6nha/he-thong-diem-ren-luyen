<?php
require 'config.php';
$res = $conn->query("SELECT * FROM tai_khoan WHERE vai_tro = 'ctsv' OR username = 'ctsv' OR ho_ten LIKE '%ctsv%'");
if ($res) {
    print_r($res->fetch_all(MYSQLI_ASSOC));
} else {
    echo $conn->error;
}
