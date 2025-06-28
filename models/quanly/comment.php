<?php
include __DIR__ . '/../../config/config.php';
include __DIR__ . '/../../config/functions.php';
session_start();

global $conn;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $comment_id = intval($_POST['comment_id'] ?? 0);
    $movie_id = intval($_POST['movie_id'] ?? 0);
    $user_id = intval($_SESSION['user_id'] ?? 0);
    $is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

    if ($action === 'add') {
        $comment = trim($_POST['comment'] ?? '');
        $rating = intval($_POST['rating'] ?? 0);
        $user = mysqli_real_escape_string($conn, $_SESSION['username'] ?? 'Khách');

        if ($movie_id <= 0 || empty($comment) || $rating < 1 || $rating > 5) {
            echo json_encode(['status' => 'error', 'message' => 'Dữ liệu không hợp lệ']);
            exit;
        }

        $comment = mysqli_real_escape_string($conn, $comment);
        $query = "INSERT INTO comments (movie_id, user, user_id, comment, rating, created_at) 
                  VALUES ($movie_id, '$user', $user_id, '$comment', $rating, NOW())";

        if (mysqli_query($conn, $query)) {
            updateMovieRating($conn, $movie_id);
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
        }
    } elseif ($action === 'edit' && $comment_id > 0) {
        $comment = trim($_POST['comment'] ?? '');
        if (empty($comment)) {
            echo json_encode(['status' => 'error', 'message' => 'Bình luận không được để trống']);
            exit;
        }

        $check_query = "SELECT user_id FROM comments WHERE id = $comment_id";
        $result = mysqli_query($conn, $check_query);
        if (!$result || !($row = mysqli_fetch_assoc($result))) {
            echo json_encode(['status' => 'error', 'message' => 'Bình luận không tồn tại']);
            exit;
        }

        if (intval($row['user_id']) !== $user_id && !$is_admin) {
            echo json_encode(['status' => 'error', 'message' => 'Bạn không có quyền chỉnh sửa']);
            exit;
        }

        $comment = mysqli_real_escape_string($conn, $comment);
        $update_query = "UPDATE comments SET comment = '$comment', created_at = NOW() WHERE id = $comment_id";


        echo json_encode(mysqli_query($conn, $update_query) ? ['status' => 'success'] : ['status' => 'error', 'message' => mysqli_error($conn)]);
    } elseif ($action === 'delete' && $comment_id > 0) {
        $check_query = "SELECT user_id, movie_id FROM comments WHERE id = $comment_id";
        $result = mysqli_query($conn, $check_query);
        if (!$result || !($row = mysqli_fetch_assoc($result))) {
            echo json_encode(['status' => 'error', 'message' => 'Bình luận không tồn tại']);
            exit;
        }

        if (intval($row['user_id']) !== $user_id && !$is_admin) {
            echo json_encode(['status' => 'error', 'message' => 'Bạn không có quyền xóa bình luận này']);
            exit;
        }
        

        $movie_id = $row['movie_id'];
        $delete_query = "DELETE FROM comments WHERE id = $comment_id";

        if (mysqli_query($conn, $delete_query)) {
            updateMovieRating($conn, $movie_id);
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Hành động không hợp lệ']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Phương thức không hợp lệ']);
}

function updateMovieRating($conn, $movie_id) {
    $rating_query = "SELECT AVG(rating) as avg_rating, COUNT(id) as votes FROM comments WHERE movie_id = $movie_id AND rating > 0";
    $rating_result = mysqli_query($conn, $rating_query);
    
    if ($rating_result && ($rating_data = mysqli_fetch_assoc($rating_result))) {
        $avg_rating = round($rating_data['avg_rating'], 1);
        $votes = $rating_data['votes'];
        mysqli_query($conn, "UPDATE movies SET rating = $avg_rating, votes = $votes WHERE id = $movie_id");
    }
}
?>