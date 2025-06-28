<?php
session_start();
require_once("../config/config.php");
require_once("functions.php");

// Hoặc nếu không dùng Composer, bạn cần include trực tiếp:
// require_once '../../path/to/PHPMailer/src/Exception.php';
// require_once '../../path/to/PHPMailer/src/PHPMailer.php';
// require_once '../../path/to/PHPMailer/src/SMTP.php';

// Kiểm tra xem người dùng đã thanh toán thành công chưa
if(!isset($_SESSION['payment_success'])) {
    header("Location: index.php");
    exit();
}

// Danh sách gói dịch vụ
$packages = [
    'monthly' => ['name' => 'Gói 1 tháng', 'price' => 79000, 'duration' => 30],
    'sixmonths' => ['name' => 'Gói 6 tháng', 'price' => 450000, 'duration' => 180],
    'yearly' => ['name' => 'Gói 1 năm', 'price' => 790000, 'duration' => 365]
];

// Lấy thông tin gói
$selectedPackage = $_SESSION['user_info']['package'];
$packageInfo = $packages[$selectedPackage] ?? null;
if (!$packageInfo) {
    die("Lỗi: Gói dịch vụ không hợp lệ!");
}

// Tạo hoặc lấy transaction_id
$transactionId = $_SESSION['transaction_id'] ?? ('PF' . time() . rand(1000, 9999));


// Gửi email xác nhận (chỉ gửi 1 lần)
if (!isset($_SESSION['email_sent'])) {
    $emailSent = sendPaymentConfirmationEmail(
        $_SESSION['user_info'], 
        $packageInfo, 
        $transactionId, 
        $_SESSION['payment_method']
    );
    
    if ($emailSent) {
        $_SESSION['email_sent'] = true;
        echo "Email xác nhận đã được gửi!";
    } else {
        echo "Lỗi: Không thể gửi email!";
    }
}


// Biến kiểm tra trạng thái đăng ký
$registrationSuccess = false;

try {
    $conn->begin_transaction(); // Bắt đầu transaction

    // Kiểm tra người dùng đã tồn tại chưa
    $email = $_SESSION['user_info']['email'];
    $fullname = $_SESSION['user_info']['fullname'];
    $stmt_check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $stmt_check->bind_result($userId);
    $stmt_check->fetch();
    $stmt_check->close();

    if (!$userId) { // Nếu user chưa tồn tại
        $username = $email;
        $password = password_hash("123", PASSWORD_DEFAULT); // Mật khẩu mặc định
        $role = 'user';

        // Thêm vào users
        $stmt_insert = $conn->prepare("INSERT INTO users (username, email, password, fullname, role, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt_insert->bind_param("sssss", $username, $email, $password, $fullname, $role);
        $stmt_insert->execute();
        $userId = $stmt_insert->insert_id;
        $stmt_insert->close();
    }

    // Thêm vào bảng subscriptions
    $paymentDate = date('Y-m-d H:i:s');
    $expiryDate = date('Y-m-d H:i:s', strtotime('+' . $packageInfo['duration'] . ' days'));
    $paymentMethod = $_SESSION['payment_method'];
    $status = 'active';

    $stmt_subscription = $conn->prepare("INSERT INTO subscriptions (user_id, package_name, transaction_id, amount, payment_method, payment_date, expiry_date, status) 
                                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt_subscription->bind_param("issdssss", $userId, $packageInfo['name'], $transactionId, $packageInfo['price'], $paymentMethod, $paymentDate, $expiryDate, $status);
    $stmt_subscription->execute();
    $stmt_subscription->close();

    $conn->commit(); // Xác nhận giao dịch
    $registrationSuccess = true;
    $_SESSION['user_registered'] = true;

} catch (Exception $e) {
    $conn->rollback(); // Hoàn tác nếu có lỗi
    die("Lỗi hệ thống: " . $e->getMessage());
}


// Xử lý nút "Xem ngay"
if(isset($_POST['watch_now'])) {
    // Chuyển hướng đến trang chính
    header("Location: login.php");
    exit();
}

// Đóng kết nối database an toàn
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán thành công - Phimflix</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #000;
            color: #fff;
            font-family: 'Arial', sans-serif;
        }
        .logo {
            color: #e50914;
            font-size: 2.5rem;
            font-weight: bold;
        }
        .confirmation-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background-color: rgba(0, 0, 0, 0.75);
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
        }
        .success-icon {
            font-size: 80px;
            color: #2ecc71;
        }
        .transaction-details {
            background-color: #222;
            padding: 20px;
            border-radius: 5px;
            margin: 25px 0;
        }
        .btn-primary {
            background-color: #e50914;
            border-color: #e50914;
        }
        .btn-primary:hover, .btn-primary:focus {
            background-color: #b2070f;
            border-color: #b2070f;
        }
        .email-notification {
            background-color: #2ecc71;
            color: #fff;
            padding: 10px 15px;
            border-radius: 5px;
            margin-top: 15px;
            text-align: center;
        }
        .email-error {
            background-color: #e74c3c;
            color: #fff;
        }
        .account-info {
            background-color: #333;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            border-left: 4px solid #e50914;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="text-center mb-4">
            <div class="logo">PHIMFLIX</div>
        </div>
        
        <div class="confirmation-container">
            <div class="text-center">
                <div class="success-icon mb-3">✓</div>
                <h2 class="mb-3">Thanh toán thành công!</h2>
                <p>Cảm ơn bạn đã đăng ký dịch vụ Phimflix. Tài khoản của bạn đã được kích hoạt.</p>
                
                <?php if(isset($_SESSION['email_sent'])): ?>
                    <div class="email-notification <?php echo $_SESSION['email_sent'] ? '' : 'email-error'; ?>">
                        <?php if($_SESSION['email_sent']): ?>
                            <i class="bi bi-envelope-check"></i> Hóa đơn thanh toán đã được gửi đến email của bạn.
                        <?php else: ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php if($registrationSuccess): ?>
                <div class="account-info mt-3">
                    <h5>Thông tin tài khoản</h5>
                    <p><strong>Tên đăng nhập:</strong> <?php echo $_SESSION['user_info']['email']; ?></p>
                    <p><strong>Mật khẩu:</strong> 123</p>
                    <p class="text-warning mb-0"><small>Vui lòng đổi mật khẩu sau khi đăng nhập để bảo mật tài khoản.</small></p>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="transaction-details">
                <h4 class="mb-3">Chi tiết giao dịch</h4>
                <div class="row mb-2">
                    <div class="col-6">Mã giao dịch:</div>
                    <div class="col-6 text-end"><?php echo $transactionId; ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-6">Họ và tên:</div>
                    <div class="col-6 text-end"><?php echo $_SESSION['user_info']['fullname']; ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-6">Email:</div>
                    <div class="col-6 text-end"><?php echo $_SESSION['user_info']['email']; ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-6">Gói dịch vụ:</div>
                    <div class="col-6 text-end"><?php echo $packageInfo['name']; ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-6">Thời hạn:</div>
                    <div class="col-6 text-end"><?php echo $packageInfo['duration']; ?> ngày</div>
                </div>
                <div class="row mb-2">
                    <div class="col-6">Phương thức:</div>
                    <div class="col-6 text-end"><?php echo $_SESSION['payment_method'] == 'momo' ? 'Ví MoMo' : 'Ví ZaloPay'; ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-6">Ngày thanh toán:</div>
                    <div class="col-6 text-end"><?php echo date('d/m/Y H:i:s'); ?></div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-6"><strong>Tổng tiền:</strong></div>
                    <div class="col-6 text-end"><strong><?php echo number_format($packageInfo['price']); ?> VNĐ</strong></div>
                </div>
            </div>
            
            <form method="post" action="">
                <div class="d-grid">
                    <button type="submit" name="watch_now" class="btn btn-primary btn-lg">Xem ngay</button>
                </div>
            </form>
            
            
        </div>
    </div>
</body>
</html>