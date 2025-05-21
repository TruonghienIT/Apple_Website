<?php
// Cho phép truy cập từ frontend (CORS)
header('Access-Control-Allow-Origin: *');  
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Xử lý preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

header('Content-Type: application/json; charset=UTF-8');
include 'db.php';

// Lấy dữ liệu từ form
$name = trim($_POST['name'] ?? '');
$subcategory = trim($_POST['subcategory'] ?? '');
$description = trim($_POST['description'] ?? '');
$price = floatval($_POST['price'] ?? 0);
$discount_price = floatval($_POST['discount_price'] ?? 0);
$category = trim($_POST['category'] ?? 'Macbook'); // cố định Macbook
$stock = intval($_POST['stock'] ?? 0);
$priority = intval($_POST['priority'] ?? 0);
$status = isset($_POST['status']) ? 1 : 0; // checkbox

// Kiểm tra dữ liệu bắt buộc
if (empty($name) || $price <= 0 || empty($subcategory)) {
    echo json_encode(['success' => false, 'message' => 'Tên, loại và giá sản phẩm phải hợp lệ.']);
    exit;
}

// Xử lý upload ảnh
$imagePath = '';
if (isset($_FILES['productImage']) && $_FILES['productImage']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . '/uploads/';  // Đường dẫn tuyệt đối trên server
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $fileTmp = $_FILES['productImage']['tmp_name'];
    $fileName = time() . '_' . basename($_FILES['productImage']['name']);
    $targetFile = $uploadDir . $fileName;

    if (move_uploaded_file($fileTmp, $targetFile)) {
        $imagePath = 'uploads/' . $fileName;  // Đường dẫn lưu vào database (tương đối so với backend)
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi tải ảnh lên.']);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Vui lòng chọn ảnh sản phẩm.']);
    exit;
}

// Chuẩn bị câu SQL
$stmt = $conn->prepare("INSERT INTO products (name, subcategory, description, price, discount_price, category, image, stock, priority, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Lỗi chuẩn bị câu lệnh: ' . $conn->error]);
    exit;
}

// Bind tham số
$stmt->bind_param("sssddssiii", $name, $subcategory, $description, $price, $discount_price, $category, $imagePath, $stock, $priority, $status);

// Thực thi và trả về kết quả
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Thêm sản phẩm thành công!', 'product_id' => $stmt->insert_id]);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi thêm sản phẩm: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
