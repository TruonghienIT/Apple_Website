<?php
header('Access-Control-Allow-Origin: *');  // Cho phép frontend truy cập
header('Content-Type: application/json; charset=UTF-8');
include 'db.php';

$sql = "SELECT id, name, subcategory, description, price, discount_price, category, image, stock, priority, status FROM products ORDER BY priority DESC, id DESC";
$result = $conn->query($sql);

$products = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

echo json_encode(['success' => true, 'products' => $products]);

$conn->close();
