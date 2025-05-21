<?php
// CORS headers, cần nếu frontend gọi backend khác domain/port
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Trả về 200 ngay với preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

header("Content-Type: application/json; charset=UTF-8");
include 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

$id = intval($data['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(["success" => false, "message" => "ID sản phẩm không hợp lệ."]);
    exit;
}

$stmt = $conn->prepare("DELETE FROM products WHERE id=?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Xóa sản phẩm thành công."]);
} else {
    echo json_encode(["success" => false, "message" => "Lỗi xóa: " . $stmt->error]);
}

$stmt->close();
$conn->close();
