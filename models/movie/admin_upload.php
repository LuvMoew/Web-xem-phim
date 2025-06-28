<?php
session_start();
include __DIR__ . '/../../config/config.php';
include __DIR__ . '/../../config/functions.php';

// Kiểm tra nếu không phải admin thì không cho truy cập
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    die("Bạn không có quyền truy cập!");
}

$uploadSuccess = false;
$errorMessage = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $genre = mysqli_real_escape_string($conn, $_POST['genre']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $release_year = $_POST['release_year'];
    $type = mysqli_real_escape_string($conn, $_POST['type']); // Thêm trường type
    
    // Xử lý upload ảnh
    $uploadOk = true;
    $poster = "";
    
    if(isset($_FILES['poster']) && $_FILES['poster']['error'] == 0) {
        $targetDir = "uploads/";
        // Tạo thư mục uploads nếu chưa tồn tại
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        $poster = $targetDir . basename($_FILES['poster']['name']);
        $imageFileType = strtolower(pathinfo($poster, PATHINFO_EXTENSION));
        
        // Kiểm tra loại file
        $allowedTypes = array('jpg', 'jpeg', 'png', 'gif');
        if(!in_array($imageFileType, $allowedTypes)) {
            $errorMessage = "Chỉ chấp nhận file JPG, JPEG, PNG & GIF.";
            $uploadOk = false;
        }
        
        // Kiểm tra kích thước file (giới hạn 5MB)
        if($_FILES["poster"]["size"] > 5000000) {
            $errorMessage = "File quá lớn, vui lòng chọn file nhỏ hơn 5MB.";
            $uploadOk = false;
        }
        
        // Upload file
        if($uploadOk) {
            if(move_uploaded_file($_FILES['poster']['tmp_name'], $poster)) {
                // Thêm vào database
                $result = mysqli_query($conn, "INSERT INTO movies (title, genre, description, poster, release_year, views, likes, type) 
                                VALUES ('$title', '$genre', '$description', '$poster', '$release_year', 0, 0, '$type')");
                
                if($result) {
                    $uploadSuccess = true;
                } else {
                    $errorMessage = "Lỗi khi thêm vào cơ sở dữ liệu: " . mysqli_error($conn);
                }
            } else {
                $errorMessage = "Có lỗi xảy ra khi tải lên file.";
            }
        }
    } else {
        $errorMessage = "Vui lòng chọn file poster.";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Phim Mới</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .upload-container {
            max-width: 800px;
            margin: 30px auto;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 30px;
        }
        .upload-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
        }
        .upload-header h2 {
            color: #333;
            font-weight: 600;
        }
        .form-label {
            font-weight: 500;
            color: #495057;
        }
        .form-control:focus, .form-select:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
        }
        .required::after {
            content: " *";
            color: red;
        }
        .poster-preview {
            width: 100%;
            max-height: 300px;
            object-fit: contain;
            border-radius: 5px;
            border: 1px dashed #ddd;
            margin-top: 10px;
            display: none;
        }
        .btn-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
            padding: 10px 20px;
        }
        .btn-secondary {
            padding: 10px 20px;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .upload-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        .custom-file-upload {
            border: 1px solid #ccc;
            display: inline-block;
            padding: 8px 12px;
            cursor: pointer;
            border-radius: 4px;
            background-color: #f8f9fa;
            width: 100%;
            text-align: center;
        }
        .file-info {
            margin-top: 10px;
            font-size: 0.9rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="upload-container">
            <div class="upload-header">
                <h2><i class="fas fa-film me-2"></i>Đăng Phim Mới</h2>
                <p class="text-muted">Điền đầy đủ thông tin để đăng phim mới lên hệ thống</p>
            </div>
            
            <?php if($uploadSuccess): ?>
            <div class="success-message">
                <i class="fas fa-check-circle me-2"></i>Phim đã được đăng thành công!
            </div>
            <?php endif; ?>
            
            <?php if(!empty($errorMessage)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $errorMessage; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" id="uploadForm">
                <div class="row mb-3">
                    <div class="col-md-8">
                        <label for="title" class="form-label required">Tiêu đề phim</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="col-md-4">
                        <label for="release_year" class="form-label required">Năm phát hành</label>
                        <input type="number" class="form-control" id="release_year" name="release_year" min="1900" max="<?php echo date("Y"); ?>" value="<?php echo date("Y"); ?>" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="genre" class="form-label required">Thể loại</label>
                    <input type="text" class="form-control" id="genre" name="genre" placeholder="Nhập thể loại (VD: Hành động, Viễn tưởng, Tình cảm...)" required>
                    <small class="text-muted">Nếu có nhiều thể loại, hãy phân cách bằng dấu phẩy</small>
                </div>
                
                <!-- Thêm dropdown chọn loại phim -->
                <div class="mb-3">
                    <label for="type" class="form-label required">Loại phim</label>
                    <select class="form-select" id="type" name="type" required>
                        <option value="movie">Phim Rạp</option>
                        <option value="series">Phim Bộ</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label required">Mô tả</label>
                    <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="poster" class="form-label required">Ảnh Poster</label>
                    <div class="custom-file">
                        <label for="poster" class="custom-file-upload">
                            <i class="fas fa-cloud-upload-alt me-2"></i>Chọn file ảnh
                        </label>
                        <input type="file" class="form-control d-none" id="poster" name="poster" accept="image/*" required onchange="previewImage(this)">
                        <div class="file-info" id="fileInfo">Chưa có file nào được chọn</div>
                    </div>
                    <img id="posterPreview" src="#" alt="Xem trước poster" class="poster-preview">
                    <small class="text-muted d-block mt-1">Hỗ trợ định dạng: JPG, JPEG, PNG, GIF. Kích thước tối đa: 5MB</small>
                </div>
                
                <div class="upload-actions">
                    <a href="/webFilm/pages/index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Quay về trang chủ
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload me-2"></i>Đăng phim
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- ------------------js----------------------- -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function previewImage(input) {
            var fileInfo = document.getElementById('fileInfo');
            var preview = document.getElementById('posterPreview');
            
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                var fileName = input.files[0].name;
                var fileSize = Math.round(input.files[0].size / 1024); // Convert to KB
                
                fileInfo.textContent = fileName + ' (' + fileSize + ' KB)';
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                
                reader.readAsDataURL(input.files[0]);
            } else {
                fileInfo.textContent = 'Chưa có file nào được chọn';
                preview.style.display = 'none';
            }
        }
    </script>
</body>
</html>