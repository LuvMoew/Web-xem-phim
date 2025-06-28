<?php
include __DIR__ . '/../../config/config.php';
include __DIR__ . '/../../config/functions.php';


session_start();

// Kiểm tra quyền admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Không có quyền truy cập");
}

// Kiểm tra nếu có ID phim
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Bắt đầu transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Xóa các bình luận của phim
        mysqli_query($conn, "DELETE FROM comments WHERE movie_id = $id");
        
        // Xóa các tập phim
        mysqli_query($conn, "DELETE FROM episodes WHERE movie_id = $id");
        
        // Xóa phim
        mysqli_query($conn, "DELETE FROM movies WHERE id = $id");
        
        // Commit transaction
        mysqli_commit($conn);
        
        echo "success";
    } catch (Exception $e) {
        // Rollback nếu có lỗi
        mysqli_rollback($conn);
        echo "Lỗi xóa phim: " . $e->getMessage();
    }
} else {
    echo "ID phim không hợp lệ";
}
?>