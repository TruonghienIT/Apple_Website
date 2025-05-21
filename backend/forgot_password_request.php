<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();
header('Content-Type: application/json; charset=UTF-8');

// Load biến môi trường từ file .env
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            putenv(trim($name) . '=' . trim($value));
        }
    }
}

$smtpUsername = getenv('SMTP_USERNAME');
$smtpPassword = getenv('SMTP_PASSWORD');

if (!$smtpUsername || !$smtpPassword) {
    echo json_encode(['success' => false, 'message' => 'Lỗi cấu hình SMTP. Vui lòng kiểm tra lại file .env']);
    exit();
}

// Include PHPMailer
require_once __DIR__ . '/../PHPMailer-6.10.0/src/Exception.php';
require_once __DIR__ . '/../PHPMailer-6.10.0/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer-6.10.0/src/SMTP.php';
require_once 'db.php';

// Đọc và xử lý dữ liệu JSON từ client
$data = json_decode(file_get_contents("php://input"), true);
$email = trim($data['email'] ?? '');

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập email.']);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Định dạng email không hợp lệ.']);
    exit();
}

// Kiểm tra tài khoản tồn tại
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy tài khoản với email này.']);
    exit();
}

$user = $result->fetch_assoc();
$userId = $user['id'];

// Kiểm tra lần gửi mã gần nhất
$stmtCheck = $conn->prepare("SELECT created_at FROM forgot_password WHERE user_id = ? ORDER BY id DESC LIMIT 1");
$stmtCheck->bind_param("i", $userId);
$stmtCheck->execute();
$checkResult = $stmtCheck->get_result();

if ($checkResult->num_rows > 0) {
    $lastRecord = $checkResult->fetch_assoc();
    $lastTime = strtotime($lastRecord['created_at']);
    $now = time();

    $cooldown = 60; // giây nút gửi lại
    $diff = $now - $lastTime;

    if ($diff < $cooldown) {
        echo json_encode([
            'success' => false,
            'message' => 'Bạn vừa gửi yêu cầu, vui lòng thử lại sau 1 phút.',
            'remaining_time' => $cooldown - $diff,
            'server_time' => date('Y-m-d H:i:s', $now),
            'created_at' => $lastRecord['created_at']
        ]);
        exit();
    }
}

// Tạo mã xác nhận và thời gian hết hạn
$code = rand(100000, 999999);
$expiry = date('Y-m-d H:i:s', strtotime('+1 minutes'));
$createdAt = date('Y-m-d H:i:s');

// Lưu mã vào DB
$stmtInsert = $conn->prepare("INSERT INTO forgot_password (user_id, code, expiry, created_at) VALUES (?, ?, ?, ?)");
$stmtInsert->bind_param("isss", $userId, $code, $expiry, $createdAt);
$stmtInsert->execute();

if ($stmtInsert->affected_rows !== 1) {
    echo json_encode([
        'success' => false,
        'message' => 'Không thể lưu mã xác nhận vào CSDL.'
    ]);
    exit();
}

// Gửi email
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtpUsername;
    $mail->Password   = $smtpPassword;
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom($smtpUsername, 'Chủ tịch tập đoàn TH_IT');
    $mail->addAddress($email);
    $mail->isHTML(true);
    $mail->Subject = 'Mã xác nhận đặt lại mật khẩu';
    $mail->Body    = "<p>Mã xác nhận của bạn là: <strong>$code</strong></p><p>Mã sẽ hết hạn sau 1 phút.</p>";

    $mail->send();

    echo json_encode([
        'success' => true,
        'message' => 'Mã xác nhận đã được gửi đến email!'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi gửi email: ' . $mail->ErrorInfo
    ]);
}
