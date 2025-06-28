<?php
session_start();
include __DIR__ . '/../../config/config.php';

if (isset($_POST['movie_id']) && isset($_SESSION['user_id'])) {
    $movie_id = $_POST['movie_id'];
    $user_id = $_SESSION['user_id'];
    
    $sql = "DELETE FROM bookmarks WHERE user_id = ? AND movie_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $movie_id);
    
    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "error";
    }
}
?>