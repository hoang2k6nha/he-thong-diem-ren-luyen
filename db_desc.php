<?php
$conn = new mysqli('localhost', 'root', '', 'itc_diemrenluyen');
$res = $conn->query("DESCRIBE tai_khoan");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
