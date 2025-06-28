<?php
// Kết nối CSDL
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "phim";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Lỗi kết nối CSDL: " . $conn->connect_error);
}

// Thiết lập UTF-8 để tránh lỗi font tiếng Việt
$conn->set_charset("utf8");
?>