<?php
header('Content-Type: application/json');

// Kết nối CSDL
$conn = new mysqli('localhost', 'root', '', 'appleweb');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối DB']);
    exit;
}

// Lấy dữ liệu POST dạng JSON
$data = json_decode(file_get_contents("php://input"), true);

$id = $data['productId'] ?? null;
$name = $data['name'] ?? '';
$price = $data['price'] ?? 0;
$discount_price = $data['discount_price'] ?? 0;
$description = $data['description'] ?? '';

// Kiểm tra dữ liệu hợp lệ
if (!$id || !$name || !is_numeric($price)) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    exit;
}

// Cập nhật sản phẩm (có discount_price)
$sql = "UPDATE products SET name = ?, price = ?, discount_price = ?, description = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Lỗi chuẩn bị câu lệnh SQL']);
    exit;
}

$stmt->bind_param("sddsi", $name, $price, $discount_price, $description, $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi cập nhật dữ liệu']);
}

$stmt->close();
$conn->close();
?>