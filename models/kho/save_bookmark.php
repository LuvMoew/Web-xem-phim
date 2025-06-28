<?php
session_start();
include __DIR__ . '/../../config/config.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $movie_id = $_POST["movie_id"];
    $user_id = $_SESSION['user_id']; // Kiểm tra user_id có đúng không

    if (!$user_id) {
        echo "Lỗi: Chưa đăng nhập!";
        exit;
    }

    // Kiểm tra xem phim đã có trong kho chưa
    $check_sql = "SELECT * FROM bookmarks WHERE user_id = ? AND movie_id = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("ii", $user_id, $movie_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "Phim này đã có trong kho!";
    } else {
        // Chèn dữ liệu vào bảng bookmarks
        $insert_sql = "INSERT INTO bookmarks (user_id, movie_id) VALUES (?, ?)";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("ii", $user_id, $movie_id);

        if ($stmt->execute()) {
            echo "Lưu thành công!";
        } else {
            echo "Lỗi: " . $conn->error;
        }
    }
}
?>
