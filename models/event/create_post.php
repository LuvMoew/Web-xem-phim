<?php
session_start();
include __DIR__ . '/../../config/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Kiểm tra nếu form đã được gửi
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Lấy thông tin từ form
    $title = $_POST['title'];
    $content = $_POST['content'];
    $user_id = $_SESSION['user_id'];

    // Kiểm tra thư mục uploads
    $uploadDir = '../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Xử lý hình ảnh
    // Xử lý hình ảnh
$image = basename($_FILES['image']['name']);
$imageTmp = $_FILES['image']['tmp_name'];
$imagePath = $uploadDir . $image;
    
if (move_uploaded_file($imageTmp, $imagePath)) {
    // Thêm dữ liệu vào database
    $sql = "INSERT INTO posts (title, content, image, user_id) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $title, $content, $image, $user_id);

    if ($stmt->execute()) {
        echo "Cập nhật tin tức thành công!";
    } else {
        echo "Lỗi: " . $stmt->error;
    }

    $stmt->close();
} else {
    echo "Lỗi khi tải lên hình ảnh!";
}
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cập nhật tin tức</title>
    <style> 
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #e9ecef;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            flex-direction: column;
        }

        h1 {
            margin-bottom: 20px;
            color: #343a40;
            font-size: 2.5rem;
        }

        form {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            width: 450px;
            text-align: center;
        }

        form div {
            margin-bottom: 20px;
        }

        label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
            display: block;
        }

        input, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            font-size: 1rem;
            background-color: #f8f9fa;
            transition: border-color 0.3s;
        }

        input:focus, textarea:focus {
            border-color: #80bdff;
            outline: none;
        }

        button {
            background-color: #007bff;
            color: white;
            padding: 12px;
            border: none;
            width: 100%;
            font-size: 1.1rem;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #0056b3;
        }

        .back-button {
            margin-top: 20px;
            background-color: #6c757d;
            color: white;
            padding: 10px 20px;
            border: none;
            font-size: 1rem;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .back-button:hover {
            background-color: #5a6268;
        }

    </style>
</head>
<body>

    <h1>Cập nhật tin tức</h1>
    <form action="create_post.php" method="POST" enctype="multipart/form-data">
        <div>
            <label for="title">Tiêu đề:</label>
            <input type="text" id="title" name="title" required>
        </div>
        <div>
            <label for="content">Nội dung:</label>
            <textarea id="content" name="content" rows="4" required></textarea>
        </div>
        <div>
            <label for="image">Chọn hình ảnh:</label>
            <input type="file" id="image" name="image" accept="image/*" required>
        </div>
        <div>
            <button type="submit">Tạo bài viết</button>
        </div>
    </form>
    
    <a href="event.php" class="back-button">Quay về trang chủ</a>

</body>
</html>


<?php $conn->close(); ?>
