<?php
header('Content-Type: application/json');

// Kết nối CSDL
$conn = new mysqli('localhost', 'root', '', 'appleweb');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối DB']);
    exit;
}

// Lấy dữ liệu từ POST
$id = $_POST['productId'] ?? null;
$name = $_POST['name'] ?? '';
$price = $_POST['price'] ?? 0;
$discount_price = $_POST['discount_price'] ?? 0;
$description = $_POST['description'] ?? '';
$subcategory = $_POST['subcategory'] ?? '';
$category = $_POST['category'] ?? '';
$stock = $_POST['stock'] ?? 0;
$priority = $_POST['priority'] ?? 0;
$status = $_POST['status'] ?? 1;

// Kiểm tra dữ liệu hợp lệ
if (!$id || !$name || !is_numeric($price)) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    exit;
}

// Nếu có file ảnh mới
$image_path = null;
if (isset($_FILES['productImage']) && $_FILES['productImage']['error'] === 0) {
    $upload_dir = 'uploads/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
    $filename = time() . '_' . basename($_FILES['productImage']['name']);
    $target_path = $upload_dir . $filename;

    if (move_uploaded_file($_FILES['productImage']['tmp_name'], $target_path)) {
        $image_path = $target_path;
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi upload ảnh']);
        exit;
    }
}

// Câu lệnh SQL cập nhật
$sql = "UPDATE products SET 
    name = ?, 
    price = ?, 
    discount_price = ?, 
    description = ?, 
    subcategory = ?, 
    category = ?, 
    stock = ?, 
    priority = ?, 
    status = ?" . 
    ($image_path ? ", image = ?" : "") . 
    " WHERE id = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Lỗi chuẩn bị câu lệnh SQL']);
    exit;
}

// Gán biến
if ($image_path) {
    $stmt->bind_param(
        "sddsssiiisi",
        $name, $price, $discount_price, $description,
        $subcategory, $category, $stock, $priority, $status, $image_path, $id
    );
} else {
    $stmt->bind_param(
        "sddsssiiii",
        $name, $price, $discount_price, $description,
        $subcategory, $category, $stock, $priority, $status, $id
    );
}

// Thực thi
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi cập nhật dữ liệu']);
}

$stmt->close();
$conn->close();
?>
