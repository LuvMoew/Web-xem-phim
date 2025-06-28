<?php
// functions.php

// Kiểm tra quyền admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'admin';
}

// Kiểm tra đăng nhập
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Chuyển hướng nếu không phải admin
function redirectIfNotAdmin() {
    if(!isLoggedIn() || !isAdmin()) {
        header("Location: index.php");
        exit();
    }
}

// Hàm lấy thông tin phim từ ID
function getSeriesInfo($conn, $series_id) {
    $query = "SELECT * FROM series WHERE id = '$series_id'";
    $result = mysqli_query($conn, $query);
    if(mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return null;
}

// Hàm lấy số lượng video của một phim
function getEpisodeCount($conn, $series_id) {
    $query = "SELECT COUNT(*) as total FROM videos WHERE series_id = '$series_id'";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    return $row['total'];
}

// Hàm tạo URL thân thiện
function createSlug($string) {
    $string = trim($string);
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return $string;
}

// Hàm cắt chuỗi và thêm dấu "..." ở cuối
function truncateString($string, $length = 150) {
    if (strlen($string) > $length) {
        return substr($string, 0, $length) . '...';
    }
    return $string;
}

// Hàm kiểm tra và tạo thư mục nếu chưa tồn tại
function createDirectoryIfNotExists($path) {
    if (!file_exists($path)) {
        mkdir($path, 0777, true);
    }
}
?>