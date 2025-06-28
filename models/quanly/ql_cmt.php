<?php
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

// Thiết lập charset
$conn->set_charset("utf8mb4");

// Initialize variables to prevent undefined variable errors
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$sortOrder = isset($_GET['order']) ? $_GET['order'] : 'DESC';

// Validate sort parameters
$allowedSortFields = ['rating', 'created_at', 'user'];
if (!in_array($sortBy, $allowedSortFields)) {
    $sortBy = 'created_at';
}
$allowedSortOrders = ['ASC', 'DESC'];
if (!in_array($sortOrder, $allowedSortOrders)) {
    $sortOrder = 'DESC';
}

// Xử lý xóa comment nếu được yêu cầu
if (isset($_POST['delete']) && isset($_POST['comment_id'])) {
    $commentId = (int)$_POST['comment_id'];
    $deleteSql = "DELETE FROM comments WHERE id = $commentId";
    if ($conn->query($deleteSql) === TRUE) {
        $deleteMessage = "Đã xóa bình luận thành công!";
    } else {
        $deleteMessage = "Lỗi khi xóa bình luận: " . $conn->error;
    }
}

// Hàm lấy tên phim từ movie_id
function getMovieName($conn, $movieId) {
    $sql = "SELECT title FROM movies WHERE id = $movieId LIMIT 1";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['title'];
    }
    return "Phim #" . $movieId;
}

// Xử lý phân trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$recordsPerPage = 10;
$offset = ($page - 1) * $recordsPerPage;

// Xử lý tìm kiếm và truy vấn đếm
$searchCondition = "";
if (!empty($search)) {
    // Truy vấn với điều kiện tìm kiếm
    $countSql = "SELECT COUNT(*) as total 
                 FROM comments c
                 LEFT JOIN movies m ON c.movie_id = m.id
                 WHERE c.comment LIKE '%$search%' OR c.user LIKE '%$search%' OR m.title LIKE '%$search%'";
    
    $listSql = "SELECT c.id, c.movie_id, c.user, c.comment, c.created_at, c.user_id, c.rating 
                FROM comments c 
                LEFT JOIN movies m ON c.movie_id = m.id
                WHERE c.comment LIKE '%$search%' OR c.user LIKE '%$search%' OR m.title LIKE '%$search%' 
                ORDER BY c.$sortBy $sortOrder LIMIT $offset, $recordsPerPage";
} else {
    // Truy vấn tiêu chuẩn
    $countSql = "SELECT COUNT(*) as total FROM comments";
    
    $listSql = "SELECT id, movie_id, user, comment, created_at, user_id, rating FROM comments 
                ORDER BY $sortBy $sortOrder LIMIT $offset, $recordsPerPage";
}

// Thực hiện truy vấn đếm
$countResult = $conn->query($countSql);
$totalRecords = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $recordsPerPage);

// Thực hiện truy vấn danh sách bình luận
$comments = $conn->query($listSql);

// Truy vấn thống kê đánh giá - BẢO ĐẢM TRUY VẤN NÀY LUÔN ĐƯỢC THỰC HIỆN
// In the statistics query section, modify the statsSql query to include search conditions:
    $statsSql = "SELECT 
    c.movie_id,
    COUNT(*) AS total_comments,
    ROUND(AVG(c.rating), 1) AS average_rating,
    SUM(CASE WHEN c.rating = 5 THEN 1 ELSE 0 END) AS five_star,
    SUM(CASE WHEN c.rating = 4 THEN 1 ELSE 0 END) AS four_star,
    SUM(CASE WHEN c.rating = 3 THEN 1 ELSE 0 END) AS three_star,
    SUM(CASE WHEN c.rating = 2 THEN 1 ELSE 0 END) AS two_star,
    SUM(CASE WHEN c.rating = 1 THEN 1 ELSE 0 END) AS one_star,
    m.views AS total_views
FROM 
    comments c
JOIN
    movies m ON c.movie_id = m.id";

// Add search condition if search is not empty
if (!empty($search)) {
    $statsSql .= " WHERE c.comment LIKE '%$search%' OR c.user LIKE '%$search%' OR m.title LIKE '%$search%'";
}

$statsSql .= " GROUP BY 
    c.movie_id
ORDER BY 
    average_rating DESC";

// Similarly, modify the time stats query
// Truy vấn thống kê theo thời gian (sửa lại)
$timeStatsSql = "SELECT 
    DATE_FORMAT(created_at, '%Y-%m') AS month,
    COUNT(*) AS total_comments,
    ROUND(AVG(rating), 1) AS average_rating
FROM 
    comments";

// Thêm điều kiện tìm kiếm nếu search không trống
if (!empty($search)) {
    $timeStatsSql .= " WHERE comment LIKE '%$search%' OR user LIKE '%$search%' OR 
                       movie_id IN (SELECT id FROM movies WHERE title LIKE '%$search%')";
}

$timeStatsSql .= " GROUP BY 
    DATE_FORMAT(created_at, '%Y-%m')
ORDER BY 
    month DESC";

// Kiểm tra lỗi trong truy vấn
$timeStatsResult = $conn->query($timeStatsSql);
if (!$timeStatsResult) {
    echo "Lỗi truy vấn thống kê theo thời gian: " . $conn->error;
}

// Also modify the user stats query
$userStatsSql = "SELECT 
    user_id,
    user,
    COUNT(*) AS comment_count,
    ROUND(AVG(rating), 1) AS average_rating
FROM 
    comments";

// Add search condition if search is not empty
if (!empty($search)) {
    $userStatsSql .= " WHERE comment LIKE '%$search%' OR user LIKE '%$search%' OR 
                      movie_id IN (SELECT id FROM movies WHERE title LIKE '%$search%')";
}

$userStatsSql .= " GROUP BY 
    user_id
ORDER BY 
    comment_count DESC
LIMIT 5";

$userStatsResult = $conn->query($userStatsSql);
if (!$userStatsResult) {
    die("Lỗi truy vấn thống kê người dùng: " . $conn->error);
}

$statsResult = $conn->query($statsSql);
if (!$statsResult) {
    die("Lỗi truy vấn thống kê: " . $conn->error);
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thống Kê Bình Luận</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>

        :root {
                    --primary-color: #3498db;
                    --secondary-color: #2ecc71;
                    --dark-color: #2c3e50;
                    --light-color: #ecf0f1;
                    --danger-color: #e74c3c;
                    --warning-color: #f39c12;
        }
        .dashboard-header {
            background-color: var(--dark-color);
            color: white;
            padding: 20px 0;
            margin-bottom: 25px;
        }
        .rating {
            color: #ffc107;
            font-size: 1.2em;
        }
        .stats-card {
            transition: all 0.3s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .progress {
            height: 20px;
        }
        .search-form {
            margin-bottom: 20px;
        }
        .view-count {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .view-count i {
            margin-right: 5px;
        }

        #backToTop {
            position: fixed;
            bottom: 20px;
            right: 20px; /* Chuyển nút sang góc phải */
            width: 40px;
            height: 40px;
            background-color: #ff6600;
            color: white;
            border: none;
            border-radius: 50%;
            font-size: 20px;
            cursor: pointer;
            display: none; /* Ẩn mặc định */
            transition: opacity 0.3s, transform 0.3s;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.2);
        }
        
        #backToTop:hover {
            background-color: #cc5500;
            transform: scale(1.1);
        }
        
       
    </style>
</head>
<body>

<?php include __DIR__ . '/../../includes/nav.php'; ?>

<div class="dashboard-header">
        <div class="container" style="margin-top: 60px;">
            <h1><i class="fas fa-chart-line me-2"></i> Quản lý tương tác </h1>
        </div>
    </div>

    <button id="backToTop" title="Lên đầu trang">↑</button>

    <div class="container-fluid py-4">
        <div class="bg">

        </div>

        <?php if(isset($deleteMessage)): ?>
            <div class="alert alert-success"><?php echo $deleteMessage; ?></div>
        <?php endif; ?>

        <!-- Thanh tìm kiếm -->
        <div class="row mb-4">
            <div class="col-md-6 offset-md-3">
                <form class="search-form" action="" method="GET">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" placeholder="Tìm kiếm bình luận..." value="<?php echo htmlspecialchars($search); ?>">
                        <button class="btn btn-primary" type="submit">Tìm kiếm</button>
                        <?php if(!empty($search)): ?>
                            <a href="?" class="btn btn-secondary">Xóa bộ lọc</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabs điều hướng -->
        <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="comments-tab" data-bs-toggle="tab" data-bs-target="#comments" type="button" role="tab" aria-controls="comments" aria-selected="true">Danh Sách Bình Luận</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="stats-tab" data-bs-toggle="tab" data-bs-target="#stats" type="button" role="tab" aria-controls="stats" aria-selected="false">Thống Kê Đánh Giá</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="time-stats-tab" data-bs-toggle="tab" data-bs-target="#time-stats" type="button" role="tab" aria-controls="time-stats" aria-selected="false">Thống Kê Theo Thời Gian</button>
            </li>
        </ul>

        <!-- Nội dung tab -->
        <div class="tab-content" id="myTabContent">

            <?php if(!empty($search)): ?>
                <div class="alert alert-info mb-3">
                    
                </div>
            <?php endif; ?>
            <!-- Tab danh sách bình luận -->
            <div class="tab-pane fade show active" id="comments" role="tabpanel" aria-labelledby="comments-tab">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Phim</th>
                                <th>
                                    <a href="?sort=user&order=<?php echo ($sortBy == 'user' && $sortOrder == 'ASC') ? 'DESC' : 'ASC'; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                        Người dùng
                                        <?php if($sortBy == 'user'): ?>
                                            <i class="bi bi-arrow-<?php echo ($sortOrder == 'ASC') ? 'up' : 'down'; ?>"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>Bình luận</th>
                                <th>
                                    <a href="?sort=rating&order=<?php echo ($sortBy == 'rating' && $sortOrder == 'ASC') ? 'DESC' : 'ASC'; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                        Đánh giá
                                        <?php if($sortBy == 'rating'): ?>
                                            <i class="bi bi-arrow-<?php echo ($sortOrder == 'ASC') ? 'up' : 'down'; ?>"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="?sort=created_at&order=<?php echo ($sortBy == 'created_at' && $sortOrder == 'ASC') ? 'DESC' : 'ASC'; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                        Ngày đăng
                                        <?php if($sortBy == 'created_at'): ?>
                                            <i class="bi bi-arrow-<?php echo ($sortOrder == 'ASC') ? 'up' : 'down'; ?>"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($comments && $comments->num_rows > 0): ?>
                                <?php while($row = $comments->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $row['id']; ?></td>
                                        <td><?php echo htmlspecialchars(getMovieName($conn, $row['movie_id'])); ?></td>
                                        <td><?php echo htmlspecialchars($row['user']); ?></td>
                                        <td><?php echo htmlspecialchars($row['comment']); ?></td>
                                        <td>
                                            <div class="rating">
                                                <?php for($i = 1; $i <= 5; $i++): ?>
                                                    <?php if($i <= $row['rating']): ?>
                                                        ★
                                                    <?php else: ?>
                                                        ☆
                                                    <?php endif; ?>
                                                <?php endfor; ?>
                                            </div>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                                        <td>
                                            <form method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa bình luận này?');">
                                                <input type="hidden" name="comment_id" value="<?php echo $row['id']; ?>">
                                                <button type="submit" name="delete" class="btn btn-danger btn-sm">Xóa</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">Không có bình luận nào</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Phân trang -->
                <?php if($totalPages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo ($page - 1); ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo isset($_GET['sort']) ? '&sort=' . $_GET['sort'] . '&order=' . $_GET['order'] : ''; ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            
                            <?php for($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo isset($_GET['sort']) ? '&sort=' . $_GET['sort'] . '&order=' . $_GET['order'] : ''; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo ($page + 1); ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo isset($_GET['sort']) ? '&sort=' . $_GET['sort'] . '&order=' . $_GET['order'] : ''; ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>

            <?php if(!empty($search)): ?>
                <div class="alert alert-info mb-3">
                    
                </div>
            <?php endif; ?>
            <!-- Tab thống kê đánh giá -->
            <div class="tab-pane fade" id="stats" role="tabpanel" aria-labelledby="stats-tab">
                <div class="row">
                    <div class="col-md-6">
                        <h3 class="mb-4">Thống kê đánh giá theo phim</h3>
                        <?php if($statsResult && $statsResult->num_rows > 0): ?>
                            <?php while($row = $statsResult->fetch_assoc()): ?>
                                <div class="card mb-4 stats-card">

                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><?php echo htmlspecialchars(getMovieName($conn, $row['movie_id'])); ?></h5>
                                    <div>
                                        <span class="badge bg-primary me-2"><?php echo $row['total_comments']; ?> bình luận</span>
                                        <span class="badge bg-secondary"><i class="bi bi-eye"></i> <?php echo isset($row['total_views']) ? number_format($row['total_views']) : 0; ?> lượt xem</span>
                                    </div>
                                </div>

                                    <div class="card-body">
                                        <h6>Đánh giá trung bình: <span class="rating"><?php echo $row['average_rating']; ?> ★</span></h6>
                                        
                                        <!-- Thanh hiển thị phân bố đánh giá -->
                                        <div class="mb-2">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span>5 ★</span>
                                                <span><?php echo $row['five_star']; ?> bình luận</span>
                                            </div>
                                            <div class="progress mb-3">
                                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo ($row['total_comments'] > 0) ? ($row['five_star'] / $row['total_comments'] * 100) : 0; ?>%" 
                                                     aria-valuenow="<?php echo $row['five_star']; ?>" aria-valuemin="0" aria-valuemax="<?php echo $row['total_comments']; ?>">
                                                    <?php echo ($row['total_comments'] > 0) ? round($row['five_star'] / $row['total_comments'] * 100) : 0; ?>%
                                                </div>
                                            </div>
                                            
                                            <div class="d-flex justify-content-between mb-1">
                                                <span>4 ★</span>
                                                <span><?php echo $row['four_star']; ?> bình luận</span>
                                            </div>
                                            <div class="progress mb-3">
                                                <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo ($row['total_comments'] > 0) ? ($row['four_star'] / $row['total_comments'] * 100) : 0; ?>%" 
                                                     aria-valuenow="<?php echo $row['four_star']; ?>" aria-valuemin="0" aria-valuemax="<?php echo $row['total_comments']; ?>">
                                                    <?php echo ($row['total_comments'] > 0) ? round($row['four_star'] / $row['total_comments'] * 100) : 0; ?>%
                                                </div>
                                            </div>
                                            
                                            <div class="d-flex justify-content-between mb-1">
                                                <span>3 ★</span>
                                                <span><?php echo $row['three_star']; ?> bình luận</span>
                                            </div>
                                            <div class="progress mb-3">
                                                <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo ($row['total_comments'] > 0) ? ($row['three_star'] / $row['total_comments'] * 100) : 0; ?>%" 
                                                     aria-valuenow="<?php echo $row['three_star']; ?>" aria-valuemin="0" aria-valuemax="<?php echo $row['total_comments']; ?>">
                                                    <?php echo ($row['total_comments'] > 0) ? round($row['three_star'] / $row['total_comments'] * 100) : 0; ?>%
                                                </div>
                                            </div>
                                            
                                            <div class="d-flex justify-content-between mb-1">
                                                <span>2 ★</span>
                                                <span><?php echo $row['two_star']; ?> bình luận</span>
                                            </div>
                                            <div class="progress mb-3">
                                                <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo ($row['total_comments'] > 0) ? ($row['two_star'] / $row['total_comments'] * 100) : 0; ?>%" 
                                                     aria-valuenow="<?php echo $row['two_star']; ?>" aria-valuemin="0" aria-valuemax="<?php echo $row['total_comments']; ?>">
                                                    <?php echo ($row['total_comments'] > 0) ? round($row['two_star'] / $row['total_comments'] * 100) : 0; ?>%
                                                </div>
                                            </div>
                                            
                                            <div class="d-flex justify-content-between mb-1">
                                                <span>1 ★</span>
                                                <span><?php echo $row['one_star']; ?> bình luận</span>
                                            </div>
                                            <div class="progress mb-3">
                                                <div class="progress-bar bg-dark" role="progressbar" style="width: <?php echo ($row['total_comments'] > 0) ? ($row['one_star'] / $row['total_comments'] * 100) : 0; ?>%" 
                                                     aria-valuenow="<?php echo $row['one_star']; ?>" aria-valuemin="0" aria-valuemax="<?php echo $row['total_comments']; ?>">
                                                    <?php echo ($row['total_comments'] > 0) ? round($row['one_star'] / $row['total_comments'] * 100) : 0; ?>%
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="alert alert-info">Không có dữ liệu thống kê</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-6">
                        <h3 class="mb-4">Người dùng tích cực nhất</h3>
                        <div class="card stats-card">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Người dùng</th>
                                                <th>Số bình luận</th>
                                                <th>Đánh giá TB</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if($userStatsResult && $userStatsResult->num_rows > 0): ?>
                                                <?php while($row = $userStatsResult->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($row['user']); ?></td>
                                                        <td><?php echo $row['comment_count']; ?></td>
                                                        <td>
                                                            <span class="rating">
                                                                <?php echo $row['average_rating']; ?> ★
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="3" class="text-center">Không có dữ liệu</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tab thống kê theo thời gian -->
            <div class="tab-pane fade" id="time-stats" role="tabpanel" aria-labelledby="time-stats-tab">
                <h3 class="mb-4">Thống kê theo tháng</h3>
                <div class="card stats-card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Tháng</th>
                                        <th>Số lượng bình luận</th>
                                        <th>Đánh giá trung bình</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if($timeStatsResult && $timeStatsResult->num_rows > 0): ?>
                                        <?php while($row = $timeStatsResult->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $row['month']; ?></td>
                                                <td><?php echo $row['total_comments']; ?></td>
                                                <td>
                                                    <div class="rating">
                                                        <?php echo $row['average_rating']; ?> ★
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center">Không có dữ liệu</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white text-center py-3 mt-5">
        <div class="container">
            <p class="m-0">Hệ thống quản lý và thống kê bình luận © <?php echo date('Y'); ?></p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Xử lý lưu tab đang mở khi refresh trang
        document.addEventListener('DOMContentLoaded', function() {
            // Lấy tab đang active từ URL hash hoặc dùng tab mặc định
            let activeTab = window.location.hash ? window.location.hash : '#comments';
            
            // Kích hoạt tab
            const triggerEl = document.querySelector('button[data-bs-target="' + activeTab + '"]');
            if (triggerEl) {
                const tabInstance = new bootstrap.Tab(triggerEl);
                tabInstance.show();
            }
            
            // Lưu tab được chọn vào URL hash
            document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(tab => {
                tab.addEventListener('shown.bs.tab', function (event) {
                    window.location.hash = event.target.getAttribute('data-bs-target');
                });
            });
        });

        // ----------------------------------------------------------------
        document.addEventListener("DOMContentLoaded", function() {
    let backToTop = document.getElementById("backToTop");

    // Hiện nút khi cuộn xuống 300px
    window.addEventListener("scroll", function() {
        if (window.scrollY > 300) {
            backToTop.style.display = "block";
        } else {
            backToTop.style.display = "none";
        }
    });

    // Cuộn lên đầu khi bấm vào nút
    backToTop.addEventListener("click", function() {
        window.scrollTo({ top: 0, behavior: "smooth" });
    });
});

    </script>
</body>
</html>
<?php
// Đóng kết nối
$conn->close();
?>