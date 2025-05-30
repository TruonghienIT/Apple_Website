<?php
if (isset($_GET['query'])) {
    $search = $_GET['query'];

    $conn = new mysqli("localhost", "root", "", "appleweb");
    if ($conn->connect_error) {
        die("Lỗi kết nối: " . $conn->connect_error);
    }

    $sql = "SELECT * FROM products WHERE name LIKE ?";
    $stmt = $conn->prepare($sql);
    $search_param = "%" . $search . "%";
    $stmt->bind_param("s", $search_param);
    $stmt->execute();
    $result = $stmt->get_result();

    echo "<h2 class='text-xl font-semibold mb-2'>Kết quả cho: <em class='text-blue-600'>" . htmlspecialchars($search) . "</em></h2>";

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo '<div class="border rounded-lg shadow p-4 bg-white">';
            echo    '<h3 class="text-lg font-bold">' . htmlspecialchars($row['name']) . '</h3>';
            echo    '<p class="text-gray-600">Giá: <strong>' . number_format($row['price'], 0, ',', '.') . '₫</strong></p>';
            echo '</div>';
        }
    } else {
        echo "<p class='text-gray-500'>Không tìm thấy sản phẩm nào phù hợp.</p>";
    }

    $stmt->close();
    $conn->close();
} else {
    echo "<p class='text-red-500'>Không có từ khóa tìm kiếm.</p>";
}
?>
