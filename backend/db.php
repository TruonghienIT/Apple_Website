<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "appleweb";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    // Nếu muốn trả JSON lỗi ngay tại đây
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        "success" => false,
        "message" => "Kết nối thất bại: " . $conn->connect_error
    ]);
    exit;
}

$conn->set_charset("utf8");
?>
