<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

$user_id = $_SESSION['user_id'];

$conn = new mysqli("localhost", "root", "", "appleweb");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Kết nối database thất bại: ' . $conn->connect_error]);
    exit;
}

$sql = "SELECT id, fullname, username, gender, email, phone, address, image FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Lỗi truy vấn']);
    exit;
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Người dùng không tồn tại']);
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();
$conn->close();

// Tạo đường dẫn ảnh đầy đủ
if (!empty($user['image'])) {
    $user['image'] = "http://localhost/Web%20Apple/" . $user['image'];
} else {
    $user['image'] = null;
}

echo json_encode([
    'success' => true,
    'user' => $user
]);
