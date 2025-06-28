<?php
// Kết nối CSDL
include __DIR__ . '/../../config/config.php';

session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../pages/main.php");
    exit();
}

// Check if user is admin or regular user
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$currentUserId = $_SESSION['user_id'];

// MOVED HERE: Filter initialization section
// Khởi tạo biến lọc
$startDate = isset($_GET['startDate']) ? $_GET['startDate'] : '';
$endDate = isset($_GET['endDate']) ? $_GET['endDate'] : '';
$paymentMethod = isset($_GET['paymentMethod']) ? $_GET['paymentMethod'] : '';
$packageName = isset($_GET['packageName']) ? $_GET['packageName'] : '';

// Xây dựng điều kiện WHERE cho các truy vấn
$whereConditions = array();

if (!empty($startDate)) {
    $whereConditions[] = "payment_date >= '$startDate 00:00:00'";
}

if (!empty($endDate)) {
    $whereConditions[] = "payment_date <= '$endDate 23:59:59'";
}

if (!empty($paymentMethod)) {
    $whereConditions[] = "payment_method = '$paymentMethod'";
}

if (!empty($packageName)) {
    $whereConditions[] = "package_name = '$packageName'";
}

// Tạo chuỗi WHERE từ mảng điều kiện
$whereClause = '';
if (!empty($whereConditions)) {
    $whereClause = "WHERE " . implode(" AND ", $whereConditions);
}
// END OF MOVED SECTION

// Truy vấn lấy thống kê theo payment_method với điều kiện lọc
$sql_payment_methods = "SELECT payment_method, COUNT(*) as total_transactions, SUM(amount) as total_amount 
                      FROM subscriptions
                      $whereClause
                      GROUP BY payment_method 
                      ORDER BY total_amount DESC";

// Truy vấn lấy thống kê theo tháng/năm với điều kiện lọc
$sql_monthly = "SELECT DATE_FORMAT(payment_date, '%Y-%m') as month_year, 
              COUNT(*) as total_transactions, 
              SUM(amount) as total_amount 
              FROM subscriptions
              $whereClause
              GROUP BY month_year 
              ORDER BY month_year DESC 
              LIMIT 12";

// Truy vấn lấy thống kê theo gói dịch vụ với điều kiện lọc
$sql_packages = "SELECT package_name, COUNT(*) as total_transactions, SUM(amount) as total_amount 
              FROM subscriptions
              $whereClause
              GROUP BY package_name 
              ORDER BY total_amount DESC";

// Lấy dữ liệu thống kê tổng quan với điều kiện lọc
$sql_overview = "SELECT 
              COUNT(*) as total_transactions,
              SUM(amount) as total_revenue,
              COUNT(DISTINCT user_id) as total_users,
              AVG(amount) as average_transaction,
              COUNT(CASE WHEN status = 'active' THEN 1 END) as active_subscriptions
              FROM subscriptions
              $whereClause";

// Truy vấn lấy danh sách 10 giao dịch gần nhất với điều kiện lọc
$sql_recent = "SELECT user_id, package_name, transaction_id, amount, payment_method, 
            payment_date, expiry_date, status
            FROM subscriptions
            $whereClause
            ORDER BY payment_date DESC
            LIMIT 10";

// Thực thi các truy vấn
$result_payment_methods = $conn->query($sql_payment_methods);
$result_monthly = $conn->query($sql_monthly);
$result_packages = $conn->query($sql_packages);
$result_overview = $conn->query($sql_overview);
$result_recent = $conn->query($sql_recent);

// Chuẩn bị dữ liệu cho biểu đồ doanh thu theo tháng
$months = [];
$revenue = [];
while($row = $result_monthly->fetch_assoc()) {
    $months[] = date('m/Y', strtotime($row['month_year'] . '-01'));
    $revenue[] = $row['total_amount'];
}

// Reset con trỏ để có thể đọc lại dữ liệu
$result_monthly->data_seek(0);

// Lấy dữ liệu tổng quan
if ($result_overview) {
    $overview = $result_overview->fetch_assoc();
} else {
    die("Lỗi truy vấn: " . $conn->error);
}

// REMOVED: The filter initialization section was moved to the top of the file
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thống kê hóa đơn thanh toán</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2ecc71;
            --dark-color: #2c3e50;
            --light-color: #ecf0f1;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
        }
        
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .dashboard-header {
            background-color: var(--dark-color);
            color: white;
            padding: 20px 0;
            margin-bottom: 25px;
        }
        
        .stats-card {
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
            height: 100%;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .stats-card .card-header {
            border-radius: 10px 10px 0 0;
            font-weight: 600;
        }
        
        .stats-card .card-body {
            padding: 20px;
        }
        
        .stats-icon {
            background-color: rgba(52, 152, 219, 0.1);
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }
        
        .stats-icon i {
            font-size: 24px;
            color: var(--primary-color);
        }
        
        .stats-info h3 {
            font-size: 24px;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .stats-info p {
            margin: 0;
            color: #7f8c8d;
        }
        
        .table-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .date-filter {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .date-filter .form-control {
            max-width: 200px;
            margin-right: 10px;
        }
        
        .chart-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 25px;
            height: 100%;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-active {
            background-color: rgba(46, 204, 113, 0.15);
            color: #27ae60;
        }
        
        .status-expired {
            background-color: rgba(231, 76, 60, 0.15);
            color: #c0392b;
        }
        
        .status-pending {
            background-color: rgba(243, 156, 18, 0.15);
            color: #d35400;
        }
        
        .payment-method-icon {
            width: 40px;
            height: 40px;
            border-radius: 5px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-size: 18px;
        }
        
        .payment-zalopay {
            background-color: #0068ff;
            color: white;
        }
        
        .payment-momo {
            background-color: #d82d8b;
            color: white;
        }
        
        .payment-bank {
            background-color: #2ecc71;
            color: white;
        }
        
        .payment-paypal {
            background-color: #003087;
            color: white;
        }
        
        .recent-transaction-table th {
            font-weight: 600;
            color: #34495e;
        }
        
        .recent-transaction-table td {
            vertical-align: middle;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(52, 152, 219, 0.05);
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/../../includes/nav.php'; ?>

    <div class="dashboard-header">
        <div class="container" style="margin-top: 60px;">
            <h1><i class="fas fa-chart-line me-2"></i> Thống kê hóa đơn thanh toán</h1>
        </div>
    </div>
    
    <div class="container">
<!-- Bộ lọc ngày tháng -->
<div class="row mb-4">
    <div class="col-12">
        <div class="table-container">
            <h4>Bộ lọc</h4>
            <form method="GET" action="">
                <div class="date-filter">
                    <div class="input-group me-3" style="max-width: 200px;">
                        <span class="input-group-text">Từ</span>
                        <input type="date" class="form-control" id="startDate" name="startDate" value="<?php echo isset($_GET['startDate']) ? $_GET['startDate'] : ''; ?>">
                    </div>
                    <div class="input-group me-3" style="max-width: 200px;">
                        <span class="input-group-text">Đến</span>
                        <input type="date" class="form-control" id="endDate" name="endDate" value="<?php echo isset($_GET['endDate']) ? $_GET['endDate'] : ''; ?>">
                    </div>
                    <select class="form-select me-3" style="max-width: 200px;" name="paymentMethod">
                        <option value="">Tất cả phương thức</option>
                        <option value="zalopay" <?php echo (isset($_GET['paymentMethod']) && $_GET['paymentMethod'] == 'zalopay') ? 'selected' : ''; ?>>ZaloPay</option>
                        <option value="momo" <?php echo (isset($_GET['paymentMethod']) && $_GET['paymentMethod'] == 'momo') ? 'selected' : ''; ?>>MomoPay</option>
                        <option value="bank" <?php echo (isset($_GET['paymentMethod']) && $_GET['paymentMethod'] == 'bank') ? 'selected' : ''; ?>>Ngân hàng</option>
                        <option value="paypal" <?php echo (isset($_GET['paymentMethod']) && $_GET['paymentMethod'] == 'paypal') ? 'selected' : ''; ?>>PayPal</option>
                    </select>
                    <select class="form-select me-3" style="max-width: 200px;" name="packageName">
                        <option value="">Tất cả gói</option>
                        <option value="Gói 1 tháng" <?php echo (isset($_GET['packageName']) && $_GET['packageName'] == 'Gói 1 tháng') ? 'selected' : ''; ?>>Gói 1 tháng</option>
                        <option value="Gói 3 tháng" <?php echo (isset($_GET['packageName']) && $_GET['packageName'] == 'Gói 3 tháng') ? 'selected' : ''; ?>>Gói 3 tháng</option>
                        <option value="Gói 6 tháng" <?php echo (isset($_GET['packageName']) && $_GET['packageName'] == 'Gói 6 tháng') ? 'selected' : ''; ?>>Gói 6 tháng</option>
                        <option value="Gói 12 tháng" <?php echo (isset($_GET['packageName']) && $_GET['packageName'] == 'Gói 12 tháng') ? 'selected' : ''; ?>>Gói 12 tháng</option>
                    </select>
                    <button type="submit" class="btn btn-primary">Lọc dữ liệu</button>
                    <?php if (!empty($_GET['startDate']) || !empty($_GET['endDate']) || !empty($_GET['paymentMethod']) || !empty($_GET['packageName'])): ?>
                        <a href="get_payment_stats.php" class="btn btn-outline-secondary ms-2">Xóa bộ lọc</a>
                    <?php endif; ?>
            </div>
            </form>
        </div>
    </div>
</div>
        
        <!-- Thống kê tổng quan -->
        <div class="row mb-4">
            <div class="col-md-4 mb-4">
                <div class="stats-card card border-0">
                    <div class="card-body d-flex align-items-center">
                        <div class="stats-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="stats-info">
                            <h3><?php echo number_format($overview['total_revenue']); ?> VNĐ</h3>
                            <p>Tổng doanh thu</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="stats-card card border-0">
                    <div class="card-body d-flex align-items-center">
                        <div class="stats-icon">
                            <i class="fas fa-file-invoice"></i>
                        </div>
                        <div class="stats-info">
                            <h3><?php echo number_format($overview['total_transactions']); ?></h3>
                            <p>Tổng số giao dịch</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="stats-card card border-0">
                    <div class="card-body d-flex align-items-center">
                        <div class="stats-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stats-info">
                            <h3><?php echo number_format($overview['total_users']); ?></h3>
                            <p>Tổng số người dùng</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="stats-card card border-0">
                    <div class="card-body d-flex align-items-center">
                        <div class="stats-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stats-info">
                            <h3><?php echo number_format($overview['average_transaction']); ?> VNĐ</h3>
                            <p>Giá trị giao dịch trung bình</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="stats-card card border-0">
                    <div class="card-body d-flex align-items-center">
                        <div class="stats-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stats-info">
                            <h3><?php echo number_format($overview['active_subscriptions']); ?></h3>
                            <p>Gói đang hoạt động</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="stats-card card border-0">
                    <div class="card-body d-flex align-items-center">
                        <div class="stats-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="stats-info">
                            <h3><?php echo number_format($overview['total_transactions'] / 12, 1); ?></h3>
                            <p>Giao dịch trung bình/tháng</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bảng giao dịch gần đây -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="table-container">
                    <h4>Giao dịch gần đây</h4>
                    <div class="table-responsive">
                        <table class="table table-hover recent-transaction-table">
                            <thead>
                                <tr>
                                    <th>ID người dùng</th>
                                    <th>Gói dịch vụ</th>
                                    <th>Mã giao dịch</th>
                                    <th>Số tiền</th>
                                    <th>Phương thức</th>
                                    <th>Ngày thanh toán</th>
                                    <th>Ngày hết hạn</th>
                                    <th>Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                while($row = $result_recent->fetch_assoc()): 
                                    $statusClass = '';
                                    if($row['status'] == 'active') {
                                        $statusClass = 'status-active';
                                    } else if($row['status'] == 'expired') {
                                        $statusClass = 'status-expired';
                                    } else {
                                        $statusClass = 'status-pending';
                                    }
                                    
                                    $paymentIcon = '';
                                    if($row['payment_method'] == 'zalopay') {
                                        $paymentIcon = '<span class="payment-method-icon payment-zalopay"><i class="fas fa-wallet"></i></span>';
                                    } else if($row['payment_method'] == 'momo') {
                                        $paymentIcon = '<span class="payment-method-icon payment-momo"><i class="fas fa-money-bill"></i></span>';
                                    } else if($row['payment_method'] == 'bank') {
                                        $paymentIcon = '<span class="payment-method-icon payment-bank"><i class="fas fa-university"></i></span>';
                                    } else {
                                        $paymentIcon = '<span class="payment-method-icon payment-paypal"><i class="fab fa-paypal"></i></span>';
                                    }
                                ?>
                                <tr>
                                    <td><?php echo $row['user_id']; ?></td>
                                    <td><?php echo $row['package_name']; ?></td>
                                    <td><span class="text-primary"><?php echo $row['transaction_id']; ?></span></td>
                                    <td><strong><?php echo number_format($row['amount']); ?> VNĐ</strong></td>
                                    <td><?php echo $paymentIcon . $row['payment_method']; ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($row['payment_date'])); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($row['expiry_date'])); ?></td>
                                    <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo $row['status']; ?></span></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-center mt-3">
                        <button class="btn btn-outline-primary">Xem tất cả giao dịch</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Biểu đồ thống kê -->
        <!-- <div class="row mb-4">
            <div class="col-lg-8 mb-4">
                <div class="chart-container">
                    <h4>Doanh thu theo tháng</h4>
                    <canvas id="monthlyRevenueChart"></canvas>
                </div>
            </div>
        </div> -->
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fix for chart rendering
        document.addEventListener('DOMContentLoaded', function() {
            const monthlyRevenueCtx = document.getElementById('monthlyRevenueChart').getContext('2d');
            const monthlyRevenueChart = new Chart(monthlyRevenueCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($months); ?>,
                    datasets: [{
                        label: 'Doanh thu (VNĐ)',
                        data: <?php echo json_encode($revenue); ?>,
                        borderColor: '#3498db',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        borderWidth: 3,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#3498db',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value.toLocaleString() + ' VNĐ';
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>