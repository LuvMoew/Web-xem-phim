<?php
include '../config/config.php';
include '../config/functions.php';
// Nhận dữ liệu từ request
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : "";
$genre = isset($_GET['genre']) ? $conn->real_escape_string($_GET['genre']) : "";
$sort = isset($_GET['sort']) ? $conn->real_escape_string($_GET['sort']) : "views";

// Truy vấn dữ liệu dựa trên bộ lọc
$query = "SELECT * FROM movies WHERE title LIKE '%$search%'";

if (!empty($genre)) {
    $query .= " AND genre = '$genre'";
}

$query .= " ORDER BY $sort DESC";

$result = $conn->query($query);
$movies = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $movies[] = $row;
    }
}

// Trả về dữ liệu dạng JSON
echo json_encode($movies);

$conn->close();
?>
