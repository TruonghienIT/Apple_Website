<?php
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Lỗi không xác định.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy dữ liệu
    $fullname = $_POST['fullname'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $email    = $_POST['email'] ?? '';
    $gender   = $_POST['gender'] ?? '';
    $phone    = $_POST['phone'] ?? '';
    $address  = $_POST['address'] ?? '';

    if (empty($fullname) || empty($username) || empty($password) || empty($email) || empty($phone)) {
        $response['message'] = 'Vui lòng điền đầy đủ các trường bắt buộc.';
        echo json_encode($response);
        exit;
    }

    // Xử lý upload ảnh
    $avatar_url = null;
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = $_FILES['avatar']['type'];

        if (!in_array($fileType, $allowedTypes)) {
            $response['message'] = 'Chỉ chấp nhận ảnh JPG, PNG, GIF hoặc WebP.';
            echo json_encode($response);
            exit;
        }

        $tmp_name = $_FILES['avatar']['tmp_name'];
        $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('img_') . '.' . $ext;

        $upload_dir = '../uploads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $path = $upload_dir . $filename;

        if (move_uploaded_file($tmp_name, $path)) {
            // Lưu đường dẫn tương đối để sau dễ dùng hiển thị ảnh
            $avatar_url = 'uploads/' . $filename;
        } else {
            $response['message'] = 'Không thể lưu ảnh vào thư mục máy chủ.';
            echo json_encode($response);
            exit;
        }
    }

    // Gán biến image trùng với cột trong database
    $image = $avatar_url;

    // Kết nối DB
    $conn = new mysqli('localhost', 'root', '', 'appleweb');
    if ($conn->connect_error) {
        $response['message'] = 'Lỗi kết nối cơ sở dữ liệu: ' . $conn->connect_error;
        echo json_encode($response);
        exit;
    }

    // Mã hóa mật khẩu
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Chuẩn bị câu SQL, dùng prepared statement tránh SQL injection
    $stmt = $conn->prepare("INSERT INTO users (fullname, username, password, gender, email, address, phone, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        $response['message'] = 'Lỗi chuẩn bị câu truy vấn: ' . $conn->error;
        echo json_encode($response);
        exit;
    }

    $stmt->bind_param('ssssssss', $fullname, $username, $passwordHash, $gender, $email, $address, $phone, $image);

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Đăng ký thành công!';
    } else {
        $response['message'] = 'Lỗi khi lưu dữ liệu: ' . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}

echo json_encode($response);
