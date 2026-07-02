<?php
require_once 'config.php';
$res = $conn->query("SELECT id, username, vai_tro, permissions FROM tai_khoan");
while ($row = $res->fetch_assoc()) {
    echo $row['id'] . " | " . $row['username'] . " | " . $row['vai_tro'] . " | " . $row['permissions'] . "\n";
}
