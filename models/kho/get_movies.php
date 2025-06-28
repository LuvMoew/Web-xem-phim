<?php
session_start();
include __DIR__ . '/../../config/config.php';

$user_id = $_SESSION['user_id'];
$type = isset($_GET['type']) ? $_GET['type'] : 'all';

// Truy vấn phụ thuộc vào loại tab
switch ($type) {
    case 'movies':
        $sql = "SELECT movies.id, movies.title, movies.poster, movies.release_year, movies.rating 
                FROM movies 
                INNER JOIN bookmarks ON movies.id = bookmarks.movie_id 
                WHERE bookmarks.user_id = ? AND movies.type = 'movie'";
        break;
    case 'series':
        $sql = "SELECT movies.id, movies.title, movies.poster, movies.release_year, movies.rating 
                FROM movies 
                INNER JOIN bookmarks ON movies.id = bookmarks.movie_id 
                WHERE bookmarks.user_id = ? AND movies.type = 'series'";
        break;
    case 'recent':
        $sql = "SELECT movies.id, movies.title, movies.poster, movies.release_year, movies.rating,
                watch_history.watched_at, watch_history.progress
                FROM movies 
                INNER JOIN watch_history ON movies.id = watch_history.movie_id 
                WHERE watch_history.user_id = ? 
                ORDER BY watch_history.watched_at DESC";
        break;
    default:
        // Trả về trống, vì tab 'all' đã được nạp sẵn
        exit;
}

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Tạo HTML cho danh sách phim
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Hiển thị mỗi phim
        include 'movie_card_template.php';  // Template riêng cho mỗi loại thẻ phim
    }
} else {
    echo '<div class="empty-message text-center">Không có phim nào.</div>';
}
?>