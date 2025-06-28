<?php
include __DIR__ . '/../../config/config.php';session_start();

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "error: Not authorized";
    exit;
}

// Check if comment_id is provided
if (!isset($_POST['comment_id']) || !is_numeric($_POST['comment_id'])) {
    echo "error: Invalid comment ID";
    exit;
}

$comment_id = intval($_POST['comment_id']);

// Delete the comment
$query = "DELETE FROM comments WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $comment_id);

if ($stmt->execute()) {
    echo "success";
} else {
    echo "error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>