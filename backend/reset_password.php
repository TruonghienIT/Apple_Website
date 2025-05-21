<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');
require_once 'db.php';

$data = json_decode(file_get_contents("php://input"), true);
$email = trim($data['email'] ?? '');
$code = trim($data['code'] ?? '');
$newPassword = trim($data['newPassword'] ?? '');

if (empty($email) || empty($code) || empty($newPassword)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin.']);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Email không hợp lệ.']);
    exit();
}

// Kiểm tra tài khoản
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$userResult = $stmt->get_result();

if ($userResult->num_rows !== 1) {
    echo json_encode(['success' => false, 'message' => 'Email không tồn tại.']);
    exit();
}

$userId = $userResult->fetch_assoc()['id'];

// Kiểm tra mã xác nhận hợp lệ và chưa hết hạn
$stmt = $conn->prepare("SELECT id FROM forgot_password WHERE user_id = ? AND code = ? AND expiry > NOW() ORDER BY id DESC LIMIT 1");
$stmt->bind_param("is", $userId, $code);
$stmt->execute();
$codeResult = $stmt->get_result();

if ($codeResult->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Mã xác nhận không hợp lệ hoặc đã hết hạn (chỉ có hiệu lực trong 1 phút).']);
    exit();
}

// Cập nhật mật khẩu
$hashed = password_hash($newPassword, PASSWORD_DEFAULT);
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
$stmt->bind_param("si", $hashed, $userId);
$stmt->execute();

echo json_encode(['success' => true, 'message' => 'Mật khẩu đã được đặt lại thành công.']);
