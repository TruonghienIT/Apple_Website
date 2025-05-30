<?php
session_start();
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

$user_id = $_SESSION['user_id'];

$fullname = $_POST['fullname'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$address = $_POST['address'] ?? '';

if (empty($fullname) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Họ tên và email là bắt buộc']);
    exit;
}

$avatar_url = null;
if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $fileType = $_FILES['avatar']['type'];

    if (!in_array($fileType, $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'Chỉ chấp nhận ảnh JPG, PNG, GIF hoặc WebP']);
        exit;
    }

    $tmp_name = $_FILES['avatar']['tmp_name'];
    $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
    $filename = uniqid('img_') . '.' . $ext;

    $upload_dir = __DIR__ . '/../uploads/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $path = $upload_dir . $filename;

    if (move_uploaded_file($tmp_name, $path)) {
        $avatar_url = 'uploads/' . $filename;
    } else {
        echo json_encode(['success' => false, 'message' => 'Không thể lưu ảnh lên máy chủ']);
        exit;
    }
}

$conn = new mysqli("localhost", "root", "", "appleweb");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối DB: ' . $conn->connect_error]);
    exit;
}

if ($avatar_url) {
    $sql = "UPDATE users SET fullname=?, email=?, phone=?, address=?, image=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssi", $fullname, $email, $phone, $address, $avatar_url, $user_id);
} else {
    $sql = "UPDATE users SET fullname=?, email=?, phone=?, address=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $fullname, $email, $phone, $address, $user_id);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Cập nhật hồ sơ thành công']);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
