<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Bạn chưa đăng nhập"]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Kiểm tra kết nối DB
if (!$conn) {
    echo json_encode(["success" => false, "message" => "Lỗi kết nối cơ sở dữ liệu"]);
    exit;
}

$base_url = "http://localhost/Web%20Apple/backend/";

$sql = "SELECT p.id, p.name, p.image, p.discount_price, c.quantity
        FROM cart_items c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Lỗi chuẩn bị truy vấn"]);
    exit;
}

$stmt->bind_param("i", $user_id);

if (!$stmt->execute()) {
    echo json_encode(["success" => false, "message" => "Lỗi thực thi truy vấn"]);
    exit;
}

$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    if (empty($row['image'])) {
        $row['image'] = $base_url . "uploads/default-product.jpg";  // ảnh mặc định nếu không có
    } else {
        // Nếu trong db đã lưu dạng 'uploads/filename.png' thì thêm base_url + ảnh
        $row['image'] = $base_url . $row['image'];
    }
    $items[] = $row;
}

// Lấy số loại sản phẩm khác nhau
$sql_count = "SELECT COUNT(*) AS count_types FROM cart_items WHERE user_id = ?";
$stmt_count = $conn->prepare($sql_count);
$stmt_count->bind_param("i", $user_id);
$stmt_count->execute();
$result_count = $stmt_count->get_result();
$count_row = $result_count->fetch_assoc();
$count_types = (int) $count_row['count_types'];

// Trả kết quả
echo json_encode([
    "success" => true,
    "items" => $items,
    "count_types" => $count_types
]);
?>
