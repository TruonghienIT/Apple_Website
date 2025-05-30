<?php
session_start();
header('Content-Type: application/json');

// Cấu hình CORS — sửa đúng port frontend bạn đang chạy
header("Access-Control-Allow-Origin: http://localhost:5500");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

// Xử lý preflight request (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  exit(0);
}

if (!isset($_SESSION['user_id'])) {
  echo json_encode(['success' => false, 'message' => 'Bạn chưa đăng nhập']);
  exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$item_ids = $data['item_ids'] ?? [];

if (empty($item_ids)) {
  echo json_encode(['success' => false, 'message' => 'Danh sách sản phẩm không hợp lệ']);
  exit;
}

require 'connect.php';

$user_id = $_SESSION['user_id'];
$placeholders = implode(',', array_fill(0, count($item_ids), '?'));

// Chuyển sản phẩm từ cart sang purchased_items
$stmt = $conn->prepare("INSERT INTO purchased_items (user_id, product_id, purchased_at)
                        SELECT user_id, product_id, NOW() FROM cart_items
                        WHERE user_id = ? AND id IN ($placeholders)");
if (!$stmt || !$stmt->execute(array_merge([$user_id], $item_ids))) {
  echo json_encode(['success' => false, 'message' => 'Lỗi khi thêm vào bảng purchased_items']);
  exit;
}

// Xoá sản phẩm khỏi giỏ hàng
$stmt = $conn->prepare("DELETE FROM cart_items WHERE user_id = ? AND id IN ($placeholders)");
if (!$stmt || !$stmt->execute(array_merge([$user_id], $item_ids))) {
  echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa khỏi giỏ hàng']);
  exit;
}

echo json_encode(['success' => true, 'message' => 'Thanh toán thành công!']);
