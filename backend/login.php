<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=UTF-8');
require 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu gửi lên không hợp lệ.']);
    exit();
}

$username = trim($data['username'] ?? '');
$password = $data['password'] ?? '';

if ($username === '' || $password === '') {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.']);
    exit();
}

$stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Lỗi truy vấn: ' . $conn->error]);
    exit();
}

$stmt->bind_param("s", $username);

if ($stmt->execute()) {
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];

            echo json_encode([
                'success' => true,
                'message' => '🎉 Đăng nhập thành công!',
                'username' => $user['username']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Mật khẩu không đúng.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Tên đăng nhập không tồn tại.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi truy vấn: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
exit();