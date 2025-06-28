<?php
session_start();
require_once("../config/config.php"); // Đảm bảo đường dẫn đến file config đúng

// Import PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Đường dẫn đến thư viện PHPMailer
require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

// Danh sách gói dịch vụ
$packages = [
    'monthly' => ['name' => 'Gói 1 tháng', 'price' => 79000, 'duration' => 30],
    'sixmonths' => ['name' => 'Gói 6 tháng', 'price' => 450000, 'duration' => 180],
    'yearly' => ['name' => 'Gói 1 năm', 'price' => 790000, 'duration' => 365]
];

// Khởi tạo biến thông báo
$message = '';
$messageType = '';

function generateUniqueTransactionId() {
    // Kết nối CSDL
    global $conn;
    
    // Tạo mã transaction_id mới cho đến khi tìm được mã chưa tồn tại
    do {
        // Tạo mã với tiền tố PF + timestamp + 4 số ngẫu nhiên + microtime để tăng tính duy nhất
        $timestamp = time();
        $random = rand(1000, 9999);
        $microtime = sprintf("%06d", (microtime(true) - floor(microtime(true))) * 1000000);
        $transactionId = 'PF' . $timestamp . $random . $microtime;
        
        // Kiểm tra xem mã đã tồn tại chưa
        $sql = "SELECT COUNT(*) as count FROM subscriptions WHERE transaction_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $transactionId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $exists = $row['count'] > 0;
        
    } while ($exists);
    
    return $transactionId;
}

// Xử lý form khi người dùng gửi đăng ký
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    // Lấy dữ liệu từ form
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $package = $_POST['package'];
    $payment_method = $_POST['payment_method'];
    
    // Kiểm tra các trường dữ liệu
    $errors = [];
    
    if (empty($fullname)) {
        $errors[] = "Họ và tên không được để trống";
    }
    
    if (empty($email)) {
        $errors[] = "Email không được để trống";
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email không hợp lệ";
    }
    
    if (empty($phone)) {
        $errors[] = "Số điện thoại không được để trống";
    } else if (!preg_match("/^[0-9]{10,11}$/", $phone)) {
        $errors[] = "Số điện thoại không hợp lệ";
    }
    
    if (!array_key_exists($package, $packages)) {
        $errors[] = "Gói dịch vụ không hợp lệ";
    }
    
    if ($payment_method != 'momo' && $payment_method != 'zalopay') {
        $errors[] = "Phương thức thanh toán không hợp lệ";
    }
    
    // Nếu không có lỗi, tiến hành xử lý đăng ký
    if (empty($errors)) {
        // Tạo mã giao dịch
        $microtime = sprintf("%06d", (microtime(true) - floor(microtime(true))) * 1000000);
$transactionId = 'PF' . time() . rand(1000, 9999) . $microtime . uniqid(); 
        // Lưu thông tin người dùng vào session
        $_SESSION['user_info'] = [
            'fullname' => $fullname,
            'email' => $email,
            'phone' => $phone,
            'package' => $package
        ];
        
        $_SESSION['transaction_id'] = $transactionId;
        $_SESSION['payment_method'] = $payment_method;
        
        // Giả định thanh toán thành công (trong thực tế sẽ tích hợp cổng thanh toán)
        $_SESSION['payment_success'] = true;
        
        // Gửi email xác nhận
        $emailSent = sendConfirmationEmail($fullname, $email, $packages[$package], $transactionId, $payment_method);
        
        if ($emailSent) {
            $_SESSION['email_sent'] = true;
            // Chuyển hướng đến trang xác nhận
            header("Location: confirmation.php");
            exit();
        } else {
            $message = "Đăng ký thành công nhưng không thể gửi email xác nhận. Vui lòng kiểm tra lại sau.";
            $messageType = "warning";
        }
    } else {
        // Hiển thị lỗi
        $message = implode("<br>", $errors);
        $messageType = "danger";
    }
}

/**
 * Gửi email xác nhận đăng ký
 */
function sendConfirmationEmail($fullname, $email, $packageInfo, $transactionId, $paymentMethod) {
    // Khởi tạo đối tượng PHPMailer
    $mail = new PHPMailer(true);

    try {
        // Cấu hình SMTP Server
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'phamthihongyen17072003@gmail.com'; // Thay bằng email Gmail của bạn
        $mail->Password = 'nxif unzu jklc fsdh'; // Dán App Password vừa tạo vào đây
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8'; // Quan trọng cho tiếng Việt

        // Người gửi & người nhận
        $mail->setFrom('your-email@gmail.com', 'PHIMFLIX');
        $mail->addAddress($email, $fullname);

        // Nội dung email
        $mail->isHTML(true);
        $mail->Subject = 'Xác nhận đăng ký dịch vụ - PHIMFLIX';

        // Nội dung HTML email
        $emailContent = '
        <!DOCTYPE html>
        <html lang="vi">
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #e50914; color: white; padding: 20px; text-align: center; }
                .logo { font-size: 24px; font-weight: bold; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .transaction { margin: 20px 0; background-color: white; padding: 15px; border-radius: 5px; border: 1px solid #ddd; }
                .transaction p { margin: 8px 0; display: flex; justify-content: space-between; }
                .footer { text-align: center; font-size: 12px; color: #777; margin-top: 20px; }
                .btn { display: inline-block; padding: 10px 20px; background-color: #e50914; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <div class="logo">PHIMFLIX</div>
                </div>
                <div class="content">
                    <h2>Xác nhận đăng ký thành công</h2>
                    <p>Xin chào ' . htmlspecialchars($fullname) . ',</p>
                    <p>Cảm ơn bạn đã đăng ký dịch vụ Phimflix. Dưới đây là thông tin chi tiết về đăng ký của bạn:</p>
                    
                    <div class="transaction">
                        <h3>Chi tiết đăng ký</h3>
                        <p><span>Mã giao dịch:</span> <span>' . htmlspecialchars($transactionId) . '</span></p>
                        <p><span>Họ và tên:</span> <span>' . htmlspecialchars($fullname) . '</span></p>
                        <p><span>Email:</span> <span>' . htmlspecialchars($email) . '</span></p>
                        <p><span>Gói dịch vụ:</span> <span>' . htmlspecialchars($packageInfo['name']) . '</span></p>
                        <p><span>Thời hạn:</span> <span>' . htmlspecialchars($packageInfo['duration']) . ' ngày</span></p>
                        <p><span>Phương thức thanh toán:</span> <span>' . ($paymentMethod == 'momo' ? 'Ví MoMo' : 'Ví ZaloPay') . '</span></p>
                        <p><span>Ngày đăng ký:</span> <span>' . date('d/m/Y H:i:s') . '</span></p>
                        <p><strong>Tổng tiền:</strong> <strong>' . number_format($packageInfo['price']) . ' VNĐ</strong></p>
                    </div>
                    
                    <p>Thông tin tài khoản của bạn:</p>
                    <div class="transaction">
                        <p><span>Tên đăng nhập:</span> <span>' . htmlspecialchars($email) . '</span></p>
                        <p><span>Mật khẩu mặc định:</span> <span>123</span></p>
                    </div>
                    
                    <p><strong>Lưu ý:</strong> Vui lòng đổi mật khẩu sau khi đăng nhập để bảo mật tài khoản.</p>
                    
                    <p style="text-align: center; margin-top: 20px;">
                        <a href="http://your-website.com/login.php" class="btn">Đăng nhập ngay</a>
                    </p>
                </div>
                <div class="footer">
                    <p>© ' . date('Y') . ' PHIMFLIX. Tất cả các quyền được bảo lưu.</p>
                    <p>Đây là email tự động, vui lòng không trả lời email này.</p>
                    <p>Nếu bạn có bất kỳ câu hỏi nào, vui lòng liên hệ <a href="mailto:support@your-website.com">support@your-website.com</a></p>
                </div>
            </div>
        </body>
        </html>';

        $mail->Body = $emailContent;
        $mail->AltBody = "Xác nhận đăng ký thành công - PHIMFLIX
Xin chào {$fullname},
Cảm ơn bạn đã đăng ký dịch vụ Phimflix. Dưới đây là thông tin đăng ký của bạn:

Mã giao dịch: {$transactionId}
Họ và tên: {$fullname}
Email: {$email}
Gói dịch vụ: {$packageInfo['name']}
Thời hạn: {$packageInfo['duration']} ngày
Phương thức thanh toán: " . ($paymentMethod == 'momo' ? 'Ví MoMo' : 'Ví ZaloPay') . "
Ngày đăng ký: " . date('d/m/Y H:i:s') . "
Tổng tiền: " . number_format($packageInfo['price']) . " VNĐ

Thông tin tài khoản của bạn:
Tên đăng nhập: {$email}
Mật khẩu mặc định: 123

Lưu ý: Vui lòng đổi mật khẩu sau khi đăng nhập để bảo mật tài khoản.";

        // Gửi email
        $mail->send();
        return true;
    } catch (Exception $e) {
        // Ghi log lỗi nếu cần
        error_log("Không thể gửi email: " . $mail->ErrorInfo);
        return false;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký dịch vụ - PHIMFLIX</title>
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
        .registration-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background-color: rgba(0, 0, 0, 0.75);
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
        }
        .form-control {
            background-color: #333;
            border-color: #666;
            color: #fff;
            padding: 12px;
        }
        .form-control:focus {
            background-color: #444;
            color: #fff;
            border-color: #e50914;
            box-shadow: 0 0 0 0.25rem rgba(229, 9, 20, 0.25);
        }
        .btn-primary {
            background-color: #e50914;
            border-color: #e50914;
            padding: 12px;
            font-weight: bold;
        }
        .btn-primary:hover, .btn-primary:focus {
            background-color: #b2070f;
            border-color: #b2070f;
        }
        .form-label {
            margin-bottom: 0.5rem;
            color: #ddd;
        }
        .package-card {
            background-color: #222;
            border: 1px solid #444;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .package-card.selected {
            border-color: #e50914;
            background-color: rgba(229, 9, 20, 0.1);
        }
        .payment-option {
            background-color: #222;
            border: 1px solid #444;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .payment-option.selected {
            border-color: #e50914;
            background-color: rgba(229, 9, 20, 0.1);
        }
        .payment-logo {
            max-height: 30px;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="text-center mb-4">
            <div class="logo">PHIMFLIX</div>
        </div>
        
        <div class="registration-container">
            <h2 class="mb-4">Đăng ký dịch vụ</h2>
            
            <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <!-- Thông tin cá nhân -->
                <div class="mb-4">
                    <h4 class="mb-3">Thông tin cá nhân</h4>
                    <div class="mb-3">
                        <label for="fullname" class="form-label">Họ và tên</label>
                        <input type="text" class="form-control" id="fullname" name="fullname" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Số điện thoại</label>
                        <input type="tel" class="form-control" id="phone" name="phone" required>
                    </div>
                </div>
                
                <!-- Chọn gói dịch vụ -->
                <div class="mb-4">
                    <h4 class="mb-3">Chọn gói dịch vụ</h4>
                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <div class="package-card" onclick="selectPackage('monthly', this)">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="package" id="package1" value="monthly" required>
                                    <label class="form-check-label" for="package1">
                                        <h5>Gói 1 tháng</h5>
                                        <p class="text-danger mb-0 fw-bold"><?php echo number_format(79000); ?> VNĐ</p>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="package-card" onclick="selectPackage('sixmonths', this)">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="package" id="package2" value="sixmonths">
                                    <label class="form-check-label" for="package2">
                                        <h5>Gói 6 tháng</h5>
                                        <p class="text-danger mb-0 fw-bold"><?php echo number_format(450000); ?> VNĐ</p>
                                        <span class="badge bg-success">Tiết kiệm 5%</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="package-card" onclick="selectPackage('yearly', this)">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="package" id="package3" value="yearly">
                                    <label class="form-check-label" for="package3">
                                        <h5>Gói 1 năm</h5>
                                        <p class="text-danger mb-0 fw-bold"><?php echo number_format(790000); ?> VNĐ</p>
                                        <span class="badge bg-success">Tiết kiệm 17%</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Thêm phần này vào sau phần "Phương thức thanh toán" và trước phần checkbox "Tôi đồng ý..." -->
                <div class="mb-4" id="qr-code-section" style="display: none;">
                    <h4 class="mb-3">Quét mã để thanh toán</h4>
                    <div class="row justify-content-center">
                        <div class="col-md-6 text-center">
                            <div id="momo-qr" style="display: none;">
                                <img src="/webfilm/assets/img/QR.jpg" alt="Mã QR MoMo" class="img-fluid" style="max-width: 200px;">
                                <p class="mt-2">Vui lòng mở ứng dụng MoMo và quét mã QR để thanh toán</p>
                            </div>
                            <div id="zalopay-qr" style="display: none;">
                                <img src="/webfilm/assets/img/QR.jpg" alt="Mã QR ZaloPay" class="img-fluid" style="max-width: 200px;">
                                <p class="mt-2">Vui lòng mở ứng dụng ZaloPay và quét mã QR để thanh toán</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Phương thức thanh toán -->
                <div class="mb-4">
                    <h4 class="mb-3">Phương thức thanh toán</h4>
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <div class="payment-option" onclick="selectPayment('momo', this)">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="payment1" value="momo" required>
                                    <label class="form-check-label d-flex align-items-center" for="payment1">
                                        <img src="https://upload.wikimedia.org/wikipedia/vi/f/fe/MoMo_Logo.png" alt="MoMo" class="payment-logo">
                                        Ví MoMo
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-2">
                            <div class="payment-option" onclick="selectPayment('zalopay', this)">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="payment2" value="zalopay">
                                    <label class="form-check-label d-flex align-items-center" for="payment2">
                                        <img src="https://cdn.haitrieu.com/wp-content/uploads/2022/10/Logo-ZaloPay-Square.png" alt="ZaloPay" class="payment-logo">
                                        Ví ZaloPay
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="termsCheck" required>
                    <label class="form-check-label" for="termsCheck">Tôi đồng ý với <a href="#" class="text-danger">Điều khoản dịch vụ</a> và <a href="#" class="text-danger">Chính sách bảo mật</a> của PHIMFLIX</label>
                </div>
                
                <div class="d-grid">
                    <button type="submit" name="register" class="btn btn-primary btn-lg">Đăng ký ngay</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectPackage(packageId, element) {
            // Xóa selected khỏi tất cả các gói
            document.querySelectorAll('.package-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Thêm selected vào gói được chọn
            element.classList.add('selected');
            
            // Chọn radio button
            document.querySelector('input[name="package"][value="' + packageId + '"]').checked = true;
        }
        
        function selectPayment(paymentId, element) {
            // Xóa selected khỏi tất cả các phương thức
            document.querySelectorAll('.payment-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            // Thêm selected vào phương thức được chọn
            element.classList.add('selected');
            
            // Chọn radio button
            document.querySelector('input[name="payment_method"][value="' + paymentId + '"]').checked = true;
        }

        //--------------------------
        function selectPayment(paymentId, element) {
    // Xóa selected khỏi tất cả các phương thức
    document.querySelectorAll('.payment-option').forEach(option => {
        option.classList.remove('selected');
    });
    
    // Thêm selected vào phương thức được chọn
    element.classList.add('selected');
    
    // Chọn radio button
    document.querySelector('input[name="payment_method"][value="' + paymentId + '"]').checked = true;
    
    // Hiển thị phần mã QR
    document.getElementById('qr-code-section').style.display = 'block';
    
    // Ẩn tất cả các mã QR
    document.getElementById('momo-qr').style.display = 'none';
    document.getElementById('zalopay-qr').style.display = 'none';
    
    // Hiển thị mã QR tương ứng với phương thức thanh toán được chọn
    if (paymentId === 'momo') {
        document.getElementById('momo-qr').style.display = 'block';
    } else if (paymentId === 'zalopay') {
        document.getElementById('zalopay-qr').style.display = 'block';
    }
}
    </script>
</body>
</html>