<?php
session_start();
include __DIR__ . '/../../config/config.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo "Bạn không có quyền truy cập trang này.";
    exit();
}

// Kiểm tra bài viết có tồn tại hay không
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "SELECT * FROM posts WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $post = $result->fetch_assoc();
    } else {
        echo "Bài viết không tồn tại!";
        exit();
    }
} else {
    echo "ID bài viết không hợp lệ!";
    exit();
}

// Cập nhật bài viết
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $image = $_FILES['image']['name'];

    // Nếu có thay đổi ảnh mới
    if ($image) {
        $target_dir = "../uploads/";
        $target_file = $target_dir . basename($image);
        move_uploaded_file($_FILES['image']['tmp_name'], $target_file);
    } else {
        $image = $post['image']; // Giữ nguyên ảnh cũ nếu không có ảnh mới
    }

    if (!empty($title) && !empty($content)) {
        $sql = "UPDATE posts SET title = ?, content = ?, image = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $title, $content, $image, $id);
        
        if ($stmt->execute()) {
            header("Location: post.php?id=$id"); // Quay lại trang bài viết sau khi chỉnh sửa
            exit();
        } else {
            echo "Lỗi: " . $stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chỉnh sửa bài viết</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background-color: #f9f9f9; }
        .container { max-width: 800px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1); }
        h1 { color: #333; }
        input[type="text"], textarea {
            width: 100%; 
            padding: 10px; 
            border: 1px solid #e5e5e5; 
            border-radius: 5px; 
            background-color: #f7f7f7;
            margin-bottom: 20px;
        }
        button {
            padding: 10px 20px;
            background-color: #d61818;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #a91313;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Chỉnh sửa bài viết</h1>

    <form action="edit_post.php?id=<?php echo $id; ?>" method="POST" enctype="multipart/form-data">
        <label for="title">Tiêu đề</label>
        <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($post['title']); ?>" required>

        <label for="content">Nội dung</label>
        <textarea name="content" id="content" required><?php echo htmlspecialchars($post['content']); ?></textarea>

        <label for="image">Ảnh minh họa</label>
        <input type="file" name="image" id="image">
        <p>Ảnh hiện tại: <img src="../uploads/<?php echo htmlspecialchars($post['image']); ?>" alt="Image" style="max-width: 200px;"></p>

        <button type="submit">Cập nhật bài viết</button>
    </form>
</div>

</body>
</html>

<?php $conn->close(); ?>
