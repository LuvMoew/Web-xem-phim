<?php
session_start();
include "config.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id']; // Now safe to use

// Truy vấn đầu tiên
$sql = "SELECT movies.id, movies.title, movies.poster 
        FROM movies 
        INNER JOIN bookmarks ON movies.id = bookmarks.movie_id 
        WHERE bookmarks.user_id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Lỗi chuẩn bị truy vấn: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Truy vấn bookmarks
$sql_bookmarks = "SELECT movies.id, movies.title, movies.poster, movies.release_year
                FROM movies 
                INNER JOIN bookmarks ON movies.id = bookmarks.movie_id 
                WHERE bookmarks.user_id = ?";
$stmt_bookmarks = $conn->prepare($sql_bookmarks);
if (!$stmt_bookmarks) {
    die("Lỗi chuẩn bị truy vấn bookmarks: " . $conn->error);
}
$stmt_bookmarks->bind_param("i", $user_id);
$stmt_bookmarks->execute();
$bookmarks_result = $stmt_bookmarks->get_result();

// Truy vấn xem gần đây
// Truy vấn xem gần đây
$sql_recent = "SELECT movies.id, movies.title, movies.poster, movies.release_year,
              watch_history.updated_at AS watched_at, watch_history.episode_number, watch_history.watched_time
              FROM movies 
              INNER JOIN watch_history ON movies.id = watch_history.movie_id 
              WHERE watch_history.user_id = ? 
              ORDER BY watch_history.updated_at DESC 
              LIMIT 6";
$stmt_recent = $conn->prepare($sql_recent);
if (!$stmt_recent) {
    die("Lỗi chuẩn bị truy vấn recent: " . $conn->error);
}
$stmt_recent->bind_param("i", $user_id);
$stmt_recent->execute();
$recent_result = $stmt_recent->get_result();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kho phim của bạn</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #0f0f1a;
            color: #ffffff;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            margin-top: 40px;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 1px solid #2c2c44;
            padding-bottom: 15px;
        }
        
        .page-header h2 {
            font-size: 28px;
            font-weight: 700;
            color: #ffffff;
        }
        
        .filter-options {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .filter-btn {
            background-color: #1f1f2f;
            color: #ffffff;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .filter-btn:hover, .filter-btn.active {
            background-color: #e50914;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 30px 0 15px 0;
        }
        
        .section-title {
            font-size: 22px;
            font-weight: 600;
            color: #ffffff;
        }
        
        .see-all {
            color: #e50914;
            text-decoration: none;
            font-size: 15px;
            transition: opacity 0.3s;
        }
        
        .see-all:hover {
            opacity: 0.8;
        }
        
        .movie-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 25px;
        }
        
        .recent-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 20px;
        }
        
        .movie-card {
            background-color: #1a1a2e;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
        }
        
        .movie-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
        }
        
        .movie-poster {
            position: relative;
            height: 280px;
            overflow: hidden;
        }
        
        .recent-card .movie-poster {
            height: 240px;
        }
        
        .movie-poster img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .movie-card:hover .movie-poster img {
            transform: scale(1.05);
        }
        
        .movie-info {
            padding: 15px;
        }
        
        .recent-card .movie-info {
            padding: 12px;
        }
        
        .movie-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .recent-card .movie-title {
            font-size: 14px;
        }
        
        .movie-meta {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            color: #b3b3b3;
            margin-bottom: 12px;
        }
        
        .recent-card .movie-meta {
            font-size: 12px;
            margin-bottom: 8px;
        }
        
        .movie-rating {
            display: flex;
            align-items: center;
        }
        
        .rating-star {
            color: #ffc107;
            margin-right: 4px;
        }
        
        .movie-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 8px;
        }
        
        .recent-card .movie-actions {
            margin-top: 5px;
        }
        
        .action-btn {
            background-color: #2c2c44;
            color: #ffffff;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 48%;
            text-decoration: none;
        }
        
        .recent-card .action-btn {
            font-size: 12px;
            padding: 6px 12px;
        }
        
        .recent-card .action-btn i {
            font-size: 12px;
        }
        
        .action-btn i {
            margin-right: 5px;
        }
        
        .view-btn {
            background-color: #e50914;
        }
        
        .action-btn:hover {
            background-color: #3c3c64;
        }
        
        .view-btn:hover {
            background-color: #ff0a16;
        }
        
        .hover-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to bottom, rgba(0,0,0,0) 0%, rgba(0,0,0,0.8) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .movie-poster:hover .hover-overlay {
            opacity: 1;
        }
        
        .play-btn {
            width: 50px;
            height: 50px;
            background-color: rgba(229, 9, 20, 0.8);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .recent-card .play-btn {
            width: 40px;
            height: 40px;
        }
        
        .play-btn:hover {
            transform: scale(1.1);
            background-color: rgba(229, 9, 20, 1);
        }
        
        .progress-bar {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            background-color: #e50914;
        }
        
        .badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: rgba(229, 9, 20, 0.9);
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .watched-time {
            color: #b3b3b3;
            font-size: 12px;
            margin-top: 3px;
        }
        
        .empty-library {
            text-align: center;
            padding: 50px 0;
        }
        
        .empty-icon {
            font-size: 60px;
            color: #2c2c44;
            margin-bottom: 20px;
        }
        
        .empty-message {
            font-size: 18px;
            color: #b3b3b3;
            margin-bottom: 20px;
        }
        
        .browse-btn {
            background-color: #e50914;
            color: #ffffff;
            border: none;
            padding: 12px 24px;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .browse-btn:hover {
            background-color: #ff0a16;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        @media (max-width: 768px) {
            .movie-grid, .recent-grid {
                grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
                gap: 15px;
            }
            
            .movie-poster {
                height: 220px;
            }
            
            .recent-card .movie-poster {
                height: 200px;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .filter-options {
                width: 100%;
                overflow-x: auto;
                padding-bottom: 10px;
            }
        }
        
        @media (max-width: 480px) {
            .movie-grid, .recent-grid {
                grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
                gap: 10px;
            }
            
            .movie-poster {
                height: 180px;
            }
            
            .recent-card .movie-poster {
                height: 160px;
            }
            
            .movie-info {
                padding: 10px;
            }
            
            .filter-btn {
                padding: 6px 12px;
                font-size: 13px;
            }
        }
    </style>
</head>
<body>

<?php include '../includes/nav.php'; ?>


    <div class="container">
        <div class="page-header">
            <h2>Kho phim của bạn</h2>
            <div class="filter-options">
                <button class="filter-btn tab-btn active" data-tab="all">Đã lưu</button>

                <button class="filter-btn tab-btn" data-tab="recent">Đã xem gần đây</button>
            </div>
        </div>
        
        <!-- Tab nội dung -->
        <div id="all" class="tab-content active">
        <?php if ($bookmarks_result && $bookmarks_result->num_rows > 0): ?>
                <div class="section-header">
                    <h3 class="section-title">Kho phim của bạn</h3>
                </div>
                <div class="movie-grid">
                    <?php while ($row = $bookmarks_result->fetch_assoc()): ?>
                        <div class="movie-card">
                            <div class="movie-poster">
                                <img src="<?php echo $row['poster']; ?>" alt="<?php echo $row['title']; ?>">
                                <div class="hover-overlay">
                                    <a href="movie_detail.php?id=<?php echo $row['id']; ?>" class="play-btn">
                                        <i class="fas fa-play" style="color: white;"></i>
                                    </a>
                                </div>
                            </div>
                            <div class="movie-info">
                                <h3 class="movie-title"><?php echo $row['title']; ?></h3>
                                <div class="movie-meta">
                                    <span><?php echo isset($row['release_year']) ? $row['release_year'] : '2023'; ?></span>
                                    
                                </div>
                                <div class="movie-actions">
                                  
                                    <button class="action-btn remove-btn" data-id="<?php echo $row['id']; ?>">
                                        <i class="fas fa-trash"></i> Xóa
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

                <div id="recent" class="tab-content">
                    <?php if ($recent_result && $recent_result->num_rows > 0): ?>
                        <div class="section-header">
                            <h3 class="section-title">Đã xem gần đây</h3>
                        </div>
                        <div class="movie-grid">
                            <?php while ($row = $recent_result->fetch_assoc()): ?>
                                <div class="movie-card recent-card">
                                    <div class="movie-poster">
                                        <img src="<?php echo $row['poster']; ?>" alt="<?php echo $row['title']; ?>">
                                        <?php if (isset($row['watched_time']) && $row['watched_time'] > 0): ?>
                                            <div class="progress-bar" style="width: <?php echo min(100, round(($row['watched_time'] / 7200) * 100)); ?>%;"></div>
                                        <?php endif; ?>
                                        <div class="hover-overlay">
                                            <a href="movie_detail.php?id=<?php echo $row['id']; ?>" class="play-btn">
                                                <i class="fas fa-play" style="color: white;"></i>
                                            </a>
                                        </div>
                                        <?php if (isset($row['episode_number'])): ?>
                                            <div class="badge">Tập <?php echo $row['episode_number']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="movie-info">
                                        <h3 class="movie-title"><?php echo $row['title']; ?></h3>
                                        <div class="movie-meta">
                                            <span><?php echo isset($row['release_year']) ? $row['release_year'] : '2023'; ?></span>
                                            <span class="watched-time"><?php echo date('d/m/Y', strtotime($row['watched_at'])); ?></span>
                                        </div>
                                        <div class="movie-actions">
                                            <a href="movie_detail.php?id=<?php echo $row['id']; ?>" class="action-btn view-btn">
                                                <i class="fas fa-play"></i> Xem
                                            </a>
                                            <button class="action-btn remove-history-btn" data-id="<?php echo $row['id']; ?>">
                                                <i class="fas fa-times"></i> Xóa
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-library">
                            <div class="empty-icon">
                                <i class="fas fa-history"></i>
                            </div>
                            <p class="empty-message">Bạn chưa xem phim nào gần đây!</p>
                            <p style="color: #b3b3b3; margin-bottom: 20px;">Hãy xem một bộ phim để hiển thị tại đây</p>
                            <a href="index.php" class="browse-btn">Khám phá phim</a>
                        </div>
                    <?php endif; ?>
                </div>

            <?php else: ?>
                <div class="empty-library">
                    <div class="empty-icon">
                        <i class="fas fa-film"></i>
                    </div>
                    <p class="empty-message">Kho phim của bạn đang trống!</p>
                    <p style="color: #b3b3b3; margin-bottom: 20px;">Hãy thêm những bộ phim yêu thích để xem sau</p>
                    <a href="index.php" class="browse-btn">Khám phá phim</a>
                </div>
            <?php endif; ?>

            
        </div>        
       
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        $(document).ready(function() {
            // Xử lý chuyển tab
            $('.tab-btn').click(function() {
                // Loại bỏ active khỏi tất cả các nút
                $('.tab-btn').removeClass('active');
                // Thêm active vào nút được click
                $(this).addClass('active');
                
                // Lấy id của tab cần hiện
                var tabId = $(this).data('tab');
                
                // Ẩn tất cả các tab content
                $('.tab-content').removeClass('active');
                // Hiện tab content được chọn
                $('#' + tabId).addClass('active');
            });
            
            // Xử lý khi click nút xóa phim
            $('.remove-btn').click(function() {
                var movieId = $(this).data('id');
                if(confirm('Bạn có chắc muốn xóa phim này khỏi kho?')) {
                    $.ajax({
                        url: 'remove_bookmark.php',
                        type: 'POST',
                        data: {
                            movie_id: movieId
                        },
                        success: function(response) {
                            // Reload trang sau khi xóa thành công
                            location.reload();
                        }
                    });
                }
            });
            
            // Nạp dữ liệu cho các tab khi được chọn
            $('.tab-btn').on('click', function() {
                var tab = $(this).data('tab');
                
                if(tab !== 'all' && !$('#' + tab + ' .movie-grid').hasClass('loaded')) {
                    // Thêm class loaded để tránh nạp lại dữ liệu
                    $('#' + tab + ' .movie-grid').addClass('loaded');
                    
                    // Hiển thị thông báo đang tải
                    $('#' + tab + ' .movie-grid').html('<p class="text-center">Đang tải...</p>');
                    
                    // Gửi AJAX request
                    $.ajax({
                        url: 'get_movies.php',
                        type: 'GET',
                        data: {
                            type: tab
                        },
                        success: function(response) {
                            // Cập nhật nội dung
                            $('#' + tab + ' .movie-grid').html(response);
                        }
                    });
                }
            });
        });

        // Xử lý khi click nút xóa lịch sử xem
        $('.remove-history-btn').click(function() {
            var movieId = $(this).data('id');
            if(confirm('Bạn có chắc muốn xóa phim này khỏi lịch sử xem?')) {
                $.ajax({
                    url: 'remove_watch_history.php',
                    type: 'POST',
                    data: {
                        movie_id: movieId
                    },
                    success: function(response) {
                        // Reload trang sau khi xóa thành công
                        location.reload();
                    }
                });
            }
        });
    </script>
</body>
</html>


