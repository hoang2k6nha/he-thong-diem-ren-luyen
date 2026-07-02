<?php
require 'config.php';
$q=$conn->query("SELECT username FROM tai_khoan WHERE vai_tro='sinh_vien' LIMIT 10");
while($row = $q->fetch_assoc()) {
    echo $row['username'] . "\n";
}
