<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "appleweb";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Lỗi kết nối: " . $conn->connect_error);
}

echo "Kết nối thành công!";
$conn->close();