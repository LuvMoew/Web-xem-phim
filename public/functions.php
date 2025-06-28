<?php
/**
 * Các hàm xử lý gửi email và thanh toán
 */

// Import PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Đường dẫn đến thư viện PHPMailer (đảm bảo đường dẫn đúng)
require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

/**
 * Gửi email xác nhận thanh toán
 *
 * @param array $userInfo Thông tin người dùng
 * @param array $packageInfo Thông tin gói dịch vụ
 * @param string $transactionId Mã giao dịch
 * @param string $paymentMethod Phương thức thanh toán
 * @return bool Kết quả gửi email (true nếu thành công, false nếu thất bại)
 */
function sendPaymentConfirmationEmail($userInfo, $packageInfo, $transactionId, $paymentMethod) {
    // Khởi tạo đối tượng PHPMailer
    $mail = new PHPMailer(true);

    try {
        // Cấu hình SMTP Server
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Thay đổi nếu dùng server SMTP khác
        $mail->SMTPAuth = true;
        $mail->Username = 'your-email@gmail.com'; // Thay bằng email của bạn
        $mail->Password = 'your-app-password'; // Thay bằng App Password (không dùng mật khẩu thật)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';

        // Người gửi & người nhận
        $mail->setFrom('your-email@gmail.com', 'PHIMFLIX');
        $mail->addAddress($userInfo['email'], $userInfo['fullname']);

        // Nội dung email
        $mail->isHTML(true);
        $mail->Subject = 'Xác nhận thanh toán thành công - PHIMFLIX';

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
                    <h2>Xác nhận thanh toán thành công</h2>
                    <p>Xin chào ' . htmlspecialchars($userInfo['fullname']) . ',</p>
                    <p>Cảm ơn bạn đã đăng ký dịch vụ Phimflix. Dưới đây là thông tin chi tiết về giao dịch của bạn:</p>
                    
                    <div class="transaction">
                        <h3>Chi tiết giao dịch</h3>
                        <p><span>Mã giao dịch:</span> <span>' . htmlspecialchars($transactionId) . '</span></p>
                        <p><span>Họ và tên:</span> <span>' . htmlspecialchars($userInfo['fullname']) . '</span></p>
                        <p><span>Email:</span> <span>' . htmlspecialchars($userInfo['email']) . '</span></p>
                        <p><span>Gói dịch vụ:</span> <span>' . htmlspecialchars($packageInfo['name']) . '</span></p>
                        <p><span>Phương thức:</span> <span>' . ($paymentMethod == 'momo' ? 'Ví MoMo' : 'Ví ZaloPay') . '</span></p>
                        <p><span>Ngày thanh toán:</span> <span>' . date('d/m/Y H:i:s') . '</span></p>
                        <p><strong>Tổng tiền:</strong> <strong>' . number_format($packageInfo['price']) . ' VNĐ</strong></p>
                    </div>
                    
                    <p>Tài khoản của bạn đã được kích hoạt. Bạn có thể bắt đầu xem ngay bây giờ.</p>
                    <p style="text-align: center; margin-top: 20px;">
                        <a href="../main.php" class="btn">Xem ngay</a>
                    </p>
                </div>
                <div class="footer">
                    <p>© ' . date('Y') . ' PHIMFLIX. Tất cả các quyền được bảo lưu.</p>
                    <p>Đây là email tự động, vui lòng không trả lời email này.</p>
                    <p>Nếu bạn có bất kỳ câu hỏi nào, vui lòng liên hệ <a href="mailto:support@yourwebsite.com">support@yourwebsite.com</a></p>
                </div>
            </div>
        </body>
        </html>
        ';

        $mail->Body = $emailContent;
        $mail->AltBody = "Xác nhận thanh toán thành công - PHIMFLIX
Xin chào {$userInfo['fullname']},
Cảm ơn bạn đã đăng ký dịch vụ Phimflix. Dưới đây là thông tin giao dịch của bạn:

Mã giao dịch: {$transactionId}
Họ và tên: {$userInfo['fullname']}
Email: {$userInfo['email']}
Gói dịch vụ: {$packageInfo['name']}
Phương thức: " . ($paymentMethod == 'momo' ? 'Ví MoMo' : 'Ví ZaloPay') . "
Ngày thanh toán: " . date('d/m/Y H:i:s') . "
Tổng tiền: " . number_format($packageInfo['price']) . " VNĐ

Tài khoản của bạn đã được kích hoạt. Bạn có thể bắt đầu xem ngay bây giờ.";

        // Gửi email
        $mail->send();
        return true;
    } catch (Exception $e) {
        // Ghi log lỗi nếu cần
        error_log("Không thể gửi email: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Gửi email thông báo yêu cầu thanh toán tới admin
 *
 * @param string $adminEmail Email của admin
 * @param array $userInfo Thông tin người dùng
 * @param array $packageInfo Thông tin gói dịch vụ
 * @param string $transactionId Mã giao dịch
 * @param string $paymentMethod Phương thức thanh toán
 * @return bool Kết quả gửi email (true nếu thành công, false nếu thất bại)
 */
function sendPaymentRequestToAdmin($adminEmail, $userInfo, $packageInfo, $transactionId, $paymentMethod) {
    // Khởi tạo đối tượng PHPMailer
    $mail = new PHPMailer(true);

    try {
        // Cấu hình SMTP Server
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'your-email@gmail.com'; // Thay bằng email của bạn
        $mail->Password = 'your-app-password'; // Thay bằng App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';

        // Người gửi & người nhận
        $mail->setFrom('your-email@gmail.com', 'PHIMFLIX System');
        $mail->addAddress($adminEmail);

        // Tạo token bảo mật cho link phê duyệt
        $token = hash('sha256', $transactionId . 'phimflix_secret_key');
        $approvalUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/admin_payment_approval.php?transaction_id=' . $transactionId . '&token=' . $token;

        // Nội dung email
        $mail->isHTML(true);
        $mail->Subject = 'Yêu cầu xác nhận thanh toán mới - PHIMFLIX';

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
                .transaction { margin: 20px 0; background-color: white; padding: 15
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
                    <h2>Xác nhận thanh toán thành công</h2>
                    <p>Xin chào ' . htmlspecialchars($userInfo['fullname']) . ',</p>
                    <p>Cảm ơn bạn đã đăng ký dịch vụ Phimflix. Dưới đây là thông tin chi tiết về giao dịch của bạn:</p>
                    
                    <div class="transaction">
                        <h3>Chi tiết giao dịch</h3>
                        <p><span>Mã giao dịch:</span> <span>' . htmlspecialchars($transactionId) . '</span></p>
                        <p><span>Họ và tên:</span> <span>' . htmlspecialchars($userInfo['fullname']) . '</span></p>
                        <p><span>Email:</span> <span>' . htmlspecialchars($userInfo['email']) . '</span></p>
                        <p><span>Gói dịch vụ:</span> <span>' . htmlspecialchars($packageInfo['name']) . '</span></p>
                        <p><span>Phương thức:</span> <span>' . ($paymentMethod == 'momo' ? 'Ví MoMo' : 'Ví ZaloPay') . '</span></p>
                        <p><span>Ngày thanh toán:</span> <span>' . date('d/m/Y H:i:s') . '</span></p>
                        <p><strong>Tổng tiền:</strong> <strong>' . number_format($packageInfo['price']) . ' VNĐ</strong></p>
                    </div>
                    
                    <p>Tài khoản của bạn đã được kích hoạt. Bạn có thể bắt đầu xem ngay bây giờ.</p>
                    <p style="text-align: center; margin-top: 20px;">
                        <a href="../main.php" class="btn">Xem ngay</a>
                    </p>
                </div>
                <div class="footer">
                    <p>© ' . date('Y') . ' PHIMFLIX. Tất cả các quyền được bảo lưu.</p>
                    <p>Đây là email tự động, vui lòng không trả lời email này.</p>
                    <p>Nếu bạn có bất kỳ câu hỏi nào, vui lòng liên hệ <a href="mailto:support@yourwebsite.com">support@yourwebsite.com</a></p>
                </div>
            </div>
        </body>
        </html>
        ';

        $mail->Body = $emailContent;
        $mail->AltBody = "Xác nhận thanh toán thành công - PHIMFLIX
Xin chào {$userInfo['fullname']},
Cảm ơn bạn đã đăng ký dịch vụ Phimflix. Dưới đây là thông tin giao dịch của bạn:

Mã giao dịch: {$transactionId}
Họ và tên: {$userInfo['fullname']}
Email: {$userInfo['email']}
Gói dịch vụ: {$packageInfo['name']}
Phương thức: " . ($paymentMethod == 'momo' ? 'Ví MoMo' : 'Ví ZaloPay') . "
Ngày thanh toán: " . date('d/m/Y H:i:s') . "
Tổng tiền: " . number_format($packageInfo['price']) . " VNĐ

Tài khoản của bạn đã được kích hoạt. Bạn có thể bắt đầu xem ngay bây giờ.";

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
