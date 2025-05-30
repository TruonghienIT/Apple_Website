<?php
include 'db.php';
session_start();

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Người dùng chưa đăng nhập"]);
    exit;
}

$user_id = intval($_SESSION['user_id']);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "message" => "Phương thức không hợp lệ"]);
    exit;
}

// Lấy dữ liệu JSON từ request body
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "Dữ liệu đầu vào không hợp lệ"]);
    exit;
}

$payment_method = isset($data['payment_method']) ? trim($data['payment_method']) : '';
$note = isset($data['note']) ? trim($data['note']) : '';
$product_ids = isset($data['product_ids']) && is_array($data['product_ids']) ? $data['product_ids'] : [];

if ($payment_method === '' || empty($product_ids)) {
    echo json_encode(["success" => false, "message" => "Thiếu dữ liệu thanh toán hoặc sản phẩm"]);
    exit;
}

$product_ids = array_map('intval', $product_ids);
$product_ids_str = implode(',', $product_ids);

// Lấy sản phẩm trong giỏ hàng
$sql = "SELECT ci.product_id, ci.quantity, p.price 
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        WHERE ci.user_id = $user_id AND ci.product_id IN ($product_ids_str)";
$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) === 0) {
    echo json_encode(["success" => false, "message" => "Không tìm thấy sản phẩm trong giỏ hàng"]);
    exit;
}

$total_amount = 0;
$cart_items = [];

while ($row = mysqli_fetch_assoc($result)) {
    $total_amount += $row['price'] * $row['quantity'];
    $cart_items[] = [
        'product_id' => $row['product_id'],
        'quantity' => $row['quantity']
    ];
}

$order_date = date("Y-m-d H:i:s");
$status = "pending";

// Tạo đơn hàng
$sql_insert_order = "INSERT INTO orders (user_id, order_date, total_amount, status, payment_method, note)
                     VALUES (?, ?, ?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $sql_insert_order);
if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Lỗi truy vấn tạo đơn hàng"]);
    exit;
}
mysqli_stmt_bind_param($stmt, "isdsss", $user_id, $order_date, $total_amount, $status, $payment_method, $note);
if (!mysqli_stmt_execute($stmt)) {
    echo json_encode(["success" => false, "message" => "Không thể tạo đơn hàng"]);
    exit;
}
$order_id = mysqli_insert_id($conn);
mysqli_stmt_close($stmt);

// Thêm vào order_items và xóa khỏi giỏ
foreach ($cart_items as $item) {
    $product_id = $item['product_id'];
    $quantity = $item['quantity'];

    // Thêm vào order_items
    $sql_insert_item = "INSERT INTO order_items (order_id, product_id, quantity) VALUES (?, ?, ?)";
    $stmt_item = mysqli_prepare($conn, $sql_insert_item);
    if ($stmt_item) {
        mysqli_stmt_bind_param($stmt_item, "iii", $order_id, $product_id, $quantity);
        mysqli_stmt_execute($stmt_item);
        mysqli_stmt_close($stmt_item);
    }

    // Xóa khỏi cart_items
    $sql_delete_cart = "DELETE FROM cart_items WHERE user_id = ? AND product_id = ?";
    $stmt_delete = mysqli_prepare($conn, $sql_delete_cart);
    if ($stmt_delete) {
        mysqli_stmt_bind_param($stmt_delete, "ii", $user_id, $product_id);
        mysqli_stmt_execute($stmt_delete);
        mysqli_stmt_close($stmt_delete);
    }
}

// ✅ Cập nhật số lần mua
$sql_update_purchased = "UPDATE users SET purchased = purchased + 1 WHERE id = ?";
$stmt_update = mysqli_prepare($conn, $sql_update_purchased);
if ($stmt_update) {
    mysqli_stmt_bind_param($stmt_update, "i", $user_id);
    mysqli_stmt_execute($stmt_update);
    mysqli_stmt_close($stmt_update);
}

// ✅ Truy vấn lại số lần mua để trả về
$sql_get_purchased = "SELECT purchased FROM users WHERE id = ?";
$stmt_get = mysqli_prepare($conn, $sql_get_purchased);
$purchased = 0;
if ($stmt_get) {
    mysqli_stmt_bind_param($stmt_get, "i", $user_id);
    mysqli_stmt_execute($stmt_get);
    mysqli_stmt_bind_result($stmt_get, $purchased);
    mysqli_stmt_fetch($stmt_get);
    mysqli_stmt_close($stmt_get);
}

echo json_encode([
    "success" => true,
    "message" => "Thanh toán thành công",
    "purchased" => $purchased
]);
?>
