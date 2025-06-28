<?php
session_start();

require_once($_SERVER['DOCUMENT_ROOT'] . '/webFilm/config/config.php');


// Xử lý đăng nhập
$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Kiểm tra lỗi trước khi thực hiện truy vấn
    if (empty($email) || empty($password)) {
        $error = "Vui lòng nhập email và mật khẩu!";
    } else {
        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            die("Lỗi truy vấn: " . $conn->error);
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['username'] = $user['username'];
            
                header("Location: ../pages/index.php");
                exit; // Dừng script để tránh lỗi
            
            
            } else {
                $error = "Sai mật khẩu!";
            }
        } else {
            $error = "Tài khoản không tồn tại!";
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập</title>
    <link rel="stylesheet" href="/webFilm/assets/css/login.css">
    <style>
        body {
            opacity: 0;
            animation: fadeIn 1s ease-in-out forwards;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fade-out {
            opacity: 1;
            transition: opacity 1s ease-in-out;
        }
        .fade-out { opacity: 0; }
    </style>
</head>
<body>
    <div class="login-container">
        <form class="login-form" method="POST">
            <h1>Đăng nhập</h1>
            
            <?php if (!empty($error)): ?>
                <p style="color: red;"><?= $error ?></p>
            <?php endif; ?>

            <div class="form-group">
                <input type="text" name="email" class="form-control" placeholder="Email hoặc số điện thoại" required>
            </div>

            <div class="form-group">
                <input type="password" name="password" class="form-control" placeholder="Mật khẩu" required>
            </div>

            <button type="submit" class="btn-login">Đăng nhập</button>

            <a href="/webFilm/public/index.php" class="signup-link">Quay lại</a>

            <div class="divider">HOẶC</div>
            <button type="button" class="btn-code">Sử dụng mã đăng nhập</button>

            <div class="remember-me">
                <input type="checkbox" id="remember">
                <label for="remember">Ghi nhớ tôi</label>
            </div>

            <a href="#" class="help-text">Bạn quên mật khẩu?</a>

            <div class="signup-text">
                Bạn mới tham gia Netflix? 
                <a href="dangky.php" class="signup-link">Đăng ký ngay</a>
                </div>
        </form>
    </div>

    
</body>
</html>
