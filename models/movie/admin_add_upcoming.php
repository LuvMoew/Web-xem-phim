<?php
session_start();
include __DIR__ . '/../../config/config.php';

// Kiểm tra nếu chưa đăng nhập hoặc không phải admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo "<script>alert('Bạn không có quyền truy cập!'); window.location.href='../pages/index.php';</script>";
    exit;
}

// Khởi tạo biến để lưu thông tin phim khi sửa
$edit_mode = false;
$edit_movie = [
    'id' => '',
    'title' => '',
    'release_date' => '',
    'image' => ''
];

// Xử lý thêm phim sắp ra mắt
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add') {
    $title = $_POST['title'];
    $release_date = $_POST['release_date'];
    
    // Xử lý upload ảnh
    $target_dir = "uploads/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    $target_file = $target_dir . basename($_FILES["image"]["name"]);
    move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);

    // Thêm vào database
    $sql = "INSERT INTO upcoming_movies (title, release_date, image) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $title, $release_date, $target_file);
    if ($stmt->execute()) {
        echo "<script>alert('Thêm phim thành công!'); window.location.href='admin_add_upcoming.php';</script>";
        // Giữ lại 5 phim gần nhất, xóa phim cũ
        $conn->query("DELETE FROM upcoming_movies WHERE id NOT IN (SELECT id FROM (SELECT id FROM upcoming_movies ORDER BY release_date DESC LIMIT 5) AS temp)");
    } else {
        echo "Lỗi: " . $conn->error;
    }
}

// Xử lý xóa phim
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete') {
    $id = $_POST['movie_id'];
    
    // Lấy thông tin ảnh trước khi xóa
    $sql = "SELECT image FROM upcoming_movies WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        // Xóa file ảnh nếu tồn tại
        if (file_exists($row['image'])) {
            unlink($row['image']);
        }
    }
    
    // Xóa phim từ database
    $sql = "DELETE FROM upcoming_movies WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo "<script>alert('Xóa phim thành công!'); window.location.href='admin_add_upcoming.php';</script>";
    } else {
        echo "Lỗi: " . $conn->error;
    }
}

// Xử lý load thông tin phim để sửa
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $edit_mode = true;
    $id = $_GET['id'];
    
    $sql = "SELECT * FROM upcoming_movies WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $edit_movie = $row;
    } else {
        echo "<script>alert('Không tìm thấy phim!'); window.location.href='admin_add_upcoming.php';</script>";
    }
}

// Xử lý cập nhật thông tin phim
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update') {
    $id = $_POST['movie_id'];
    $title = $_POST['title'];
    $release_date = $_POST['release_date'];
    
    // Kiểm tra xem có thay đổi ảnh không
    if ($_FILES["image"]["size"] > 0) {
        // Xử lý upload ảnh mới
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $target_file = $target_dir . basename($_FILES["image"]["name"]);
        move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);
        
        // Lấy ảnh cũ để xóa
        $sql = "SELECT image FROM upcoming_movies WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc() && file_exists($row['image'])) {
            unlink($row['image']);
        }
        
        // Cập nhật với ảnh mới
        $sql = "UPDATE upcoming_movies SET title = ?, release_date = ?, image = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $title, $release_date, $target_file, $id);
    } else {
        // Cập nhật không có ảnh mới
        $sql = "UPDATE upcoming_movies SET title = ?, release_date = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $title, $release_date, $id);
    }
    
    if ($stmt->execute()) {
        echo "<script>alert('Cập nhật phim thành công!'); window.location.href='admin_add_upcoming.php';</script>";
    } else {
        echo "Lỗi: " . $conn->error;
    }
}

// Lấy danh sách phim sắp chiếu, sắp xếp theo ID giảm dần (mới nhất lên đầu)
$sql = "SELECT * FROM upcoming_movies ORDER BY id DESC";
$result = $conn->query($sql);
$upcoming_movies = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $upcoming_movies[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý phim sắp ra mắt</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h2 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
        }
        input[type="text"],
        input[type="date"],
        input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            text-align: center;
            margin-right: 5px;
        }
        .btn-primary {
            background-color: #4CAF50;
            color: white;
        }
        .btn-secondary {
            background-color: #f0f0f0;
            color: #333;
        }
        .btn-danger {
            background-color: #f44336;
            color: white;
        }
        .btn-warning {
            background-color: #ff9800;
            color: white;
        }
        .movie-list {
            margin-top: 50px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .movie-img {
            max-width: 100px;
            max-height: 100px;
        }
        .action-form {
            display: inline;
        }
        .current-image {
            max-width: 200px;
            margin: 10px 0;
            display: block;
        }
        .action-buttons {
            display: flex;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>QUẢN LÝ PHIM SẮP RA MẮT</h2>
        
        <!-- Form thêm/sửa phim -->
        <h3><?php echo $edit_mode ? 'Sửa thông tin phim' : 'Thêm phim mới'; ?></h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="<?php echo $edit_mode ? 'update' : 'add'; ?>">
            <?php if ($edit_mode): ?>
                <input type="hidden" name="movie_id" value="<?php echo $edit_movie['id']; ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label>Tên phim:</label>
                <input type="text" name="title" value="<?php echo $edit_mode ? $edit_movie['title'] : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label>Ngày phát hành:</label>
                <input type="date" name="release_date" value="<?php echo $edit_mode ? $edit_movie['release_date'] : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label>Ảnh phim:</label>
                <?php if ($edit_mode && !empty($edit_movie['image'])): ?>
                    <img src="<?php echo $edit_movie['image']; ?>" alt="Ảnh hiện tại" class="current-image">
                    <p>Chọn ảnh mới nếu muốn thay đổi:</p>
                <?php endif; ?>
                <input type="file" name="image" accept="image/*" <?php echo $edit_mode ? '' : 'required'; ?>>
            </div>
            
            <div class="buttons">
                <a href="/webFilm/pages/index.php" class="btn btn-secondary">Quay lại</a>
                <?php if ($edit_mode): ?>
                    <a href="admin_add_upcoming.php" class="btn btn-secondary">Hủy sửa</a>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary">
                    <?php echo $edit_mode ? 'Cập nhật phim' : 'Thêm phim'; ?>
                </button>
            </div>
        </form>
        
        <!-- Danh sách phim sắp chiếu -->
        <div class="movie-list">
            <h3>Danh sách phim sắp chiếu</h3>
            <?php if (count($upcoming_movies) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tên phim</th>
                            <th>Ngày phát hành</th>
                            <th>Ảnh</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($upcoming_movies as $movie): ?>
                            <tr>
                                <td><?php echo $movie['id']; ?></td>
                                <td><?php echo $movie['title']; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($movie['release_date'])); ?></td>
                                <td>
                                    <?php if (!empty($movie['image'])): ?>
                                        <img src="<?php echo $movie['image']; ?>" alt="<?php echo $movie['title']; ?>" class="movie-img">
                                    <?php else: ?>
                                        Không có ảnh
                                    <?php endif; ?>
                                </td>
                                <td class="action-buttons">
                                    <a href="admin_add_upcoming.php?action=edit&id=<?php echo $movie['id']; ?>" class="btn btn-warning">Sửa</a>
                                    <form method="POST" class="action-form" onsubmit="return confirm('Bạn có chắc chắn muốn xóa phim này?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="movie_id" value="<?php echo $movie['id']; ?>">
                                        <button type="submit" class="btn btn-danger">Xóa</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Chưa có phim sắp chiếu nào.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>