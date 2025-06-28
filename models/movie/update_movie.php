<?php
include __DIR__ . '/../../config/config.php';
include __DIR__ . '/../../config/functions.php';


session_start();

// Kiểm tra quyền admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Không có quyền truy cập");
}

// Kiểm tra nếu form được gửi
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Lấy dữ liệu từ form
    $id = intval($_POST['id']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $alt_title = mysqli_real_escape_string($conn, $_POST['alt_title']);
    $genre = mysqli_real_escape_string($conn, $_POST['genre']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $release_year = mysqli_real_escape_string($conn, $_POST['release_year']);
    $episodes = mysqli_real_escape_string($conn, $_POST['episodes']);
    $poster = mysqli_real_escape_string($conn, $_POST['poster']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    
    // Cập nhật dữ liệu phim
    $query = "UPDATE movies SET 
                title = '$title', 
                alt_title = '$alt_title', 
                genre = '$genre', 
                status = '$status', 
                release_year = '$release_year', 
                episodes = '$episodes', 
                poster = '$poster', 
                description = '$description' 
              WHERE id = $id";
    
    if (mysqli_query($conn, $query)) {
        echo "success";
    } else {
        echo "Lỗi cập nhật: " . mysqli_error($conn);
    }
} else {
    echo "Phương thức không được hỗ trợ";
}
?>