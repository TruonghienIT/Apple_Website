<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=UTF-8');
require_once 'db.php'; // Kết nối CSDL

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "success" => false,
        "message" => "Bạn cần đăng nhập để thêm vào giỏ hàng."
    ]);
    exit;
}

$user_id = (int) $_SESSION['user_id'];

// Nhận dữ liệu JSON từ fetch()
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['product_id'], $data['quantity'])) {
    echo json_encode([
        "success" => false,
        "message" => "Thiếu thông tin sản phẩm hoặc số lượng."
    ]);
    exit;
}

$product_id = (int) $data['product_id'];
$quantity = (int) $data['quantity'];

// ✅ Kiểm tra sản phẩm có tồn tại trong bảng products không
$product_check = $conn->prepare("SELECT id FROM products WHERE id = ?");
$product_check->bind_param("i", $product_id);
$product_check->execute();
$product_result = $product_check->get_result();

if ($product_result->num_rows === 0) {
    echo json_encode([
        "success" => false,
        "message" => "Sản phẩm không tồn tại trong hệ thống."
    ]);
    exit;
}

// ✅ Kiểm tra sản phẩm đã có trong giỏ chưa
$sql = "SELECT id, quantity FROM cart_items WHERE user_id = ? AND product_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Đã tồn tại, cập nhật số lượng
    $new_quantity = $row['quantity'] + $quantity;
    $update = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
    $update->bind_param("ii", $new_quantity, $row['id']);
    $update->execute();

    echo json_encode([
        "success" => true,
        "message" => "Số lượng sản phẩm đã được cập nhật trong giỏ hàng."
    ]);
} else {
    // Chưa có, thêm mới
    $insert = $conn->prepare("INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?)");
    $insert->bind_param("iii", $user_id, $product_id, $quantity);
    $insert->execute();

    if ($insert->affected_rows > 0) {
        echo json_encode([
            "success" => true,
            "message" => "Sản phẩm đã được thêm vào giỏ hàng."
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Không thể thêm sản phẩm vào giỏ hàng."
        ]);
    }
}

$conn->close();
?>
