<?php
header("Content-Type: application/json; charset=UTF-8");
if (ob_get_length()) ob_clean();

include 'db.php'; // kết nối $conn

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "Dữ liệu gửi lên không hợp lệ."]);
    exit;
}

$fullname = trim($data['fullname'] ?? '');
$username = trim($data['username'] ?? '');
$password = $data['password'] ?? '';
$confirmPassword = $data['confirmPassword'] ?? '';
$gender = $data['gender'] ?? '';
$email = trim($data['email'] ?? '');
$phone = trim($data['phone'] ?? '');
$address = trim($data['address'] ?? '');

// Validate cơ bản
if (empty($fullname) || empty($username) || empty($password) || empty($confirmPassword) || empty($email) || empty($phone)) {
    echo json_encode(["success" => false, "message" => "Vui lòng điền đầy đủ các trường bắt buộc."]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Email không hợp lệ."]);
    exit;
}

if ($password !== $confirmPassword) {
    echo json_encode(["success" => false, "message" => "Mật khẩu không khớp."]);
    exit;
}

// Kiểm tra username tồn tại
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Tên đăng nhập đã tồn tại."]);
    $stmt->close();
    exit;
}
$stmt->close();

// Kiểm tra email tồn tại
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Email đã được sử dụng."]);
    $stmt->close();
    exit;
}
$stmt->close();

// Mã hóa mật khẩu
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Thêm user mới
$stmt = $conn->prepare("INSERT INTO users (fullname, username, password, gender, email, address, phone) VALUES (?, ?, ?, ?, ?, ?, ?)");
if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Lỗi chuẩn bị truy vấn INSERT."]);
    exit;
}
$stmt->bind_param("sssssss", $fullname, $username, $hashedPassword, $gender, $email, $address, $phone);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "🎉 Đăng ký thành công!"]);
} else {
    echo json_encode(["success" => false, "message" => "Đăng ký thất bại: " . $stmt->error]);
}

$stmt->close();
$conn->close();
exit;
?>
