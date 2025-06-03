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
$stock = $data['stock'] ?? '';

// Kiểm tra dữ liệu hợp lệ
if (!$id || !$name || !is_numeric($price)) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    exit;
}

if (!is_numeric($stock) || intval($stock) < 0) {
    echo json_encode(['success' => false, 'message' => 'Giá trị hàng tồn kho không hợp lệ']);
    exit;
}
$stock = intval($stock);

// Cập nhật sản phẩm (có discount_price)
$sql = "UPDATE products SET name = ?, price = ?, discount_price = ?, description = ?, stock = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Lỗi chuẩn bị câu lệnh SQL']);
    exit;
}

$stmt->bind_param("sddssi", $name, $price, $discount_price, $description, $stock, $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi cập nhật dữ liệu']);
}

$stmt->close();
$conn->close();
?>