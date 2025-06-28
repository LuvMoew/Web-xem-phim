<?php
session_start();
include __DIR__ . '/../../config/config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Khởi tạo biến tìm kiếm
$search_term = '';
$where_clause = '';

// Xử lý tìm kiếm
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = $conn->real_escape_string($_GET['search']);
    $where_clause = " WHERE title LIKE '%$search_term%' OR content LIKE '%$search_term%'";
}

// Lấy danh sách bài viết
$sql = "SELECT * FROM posts" . $where_clause . " ORDER BY created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>VnExpress - Báo Tiếng Việt</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/WebTinTuc/fontawesome-free-6.4.2-web/css/all.min.css">
    <style>
        :root {
            --primary-color: #c00;
            --secondary-color: #2575fc;
            --background-color:rgba(130, 150, 156, 0.85);
            --card-background: #ffffff;
            --text-color: #333;
            --light-text: #666;
            --border-color: #eaeaea;
            --hover-color: #e8f4fd;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Categories Navigation */
        .categories {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            padding: 15px 0;
            background-color: #fff;
            border-bottom: 2px solid var(--border-color);
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 100;
            justify-content: center;
        }
        
        .categories a {
            color: var(--text-color);
            white-space: nowrap;
            font-size: 0.95em;
            text-decoration: none;
            font-weight: 500;
            padding: 5px 10px;
            border-radius: 4px;
            transition: all 0.2s ease;
        }
        
        .categories a:hover {
            color: var(--primary-color);
            background-color: var(--hover-color);
        }
        
        /* User Controls */
        .user-controls {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 15px;
            padding: 15px 20px;
            background-color: var(--card-background);
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .welcome {
            font-size: 0.9em;
            color: var(--light-text);
        }
        
        .create-post-link {
            display: flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            color: var(--primary-color);
            font-size: 0.9em;
            font-weight: bold;
            padding: 6px 12px;
            border-radius: 4px;
            border: 1px solid var(--primary-color);
            transition: all 0.2s;
        }
        
        .create-post-link:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .user-controls i.fa-right-from-bracket {
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            color: var(--light-text);
            transition: all 0.2s;
        }
        
        .user-controls i.fa-right-from-bracket:hover {
            background-color: #f1f1f1;
            color: var(--primary-color);
        }
        
        /* Search Box */
        .search-area {
            margin: 0 auto 30px;
            display: flex;
            max-width: 500px;
        }
        
        .search-box {
            display: flex;
            width: 100%;
            border: 1px solid var(--border-color);
            border-radius: 25px;
            overflow: hidden;
            background: white;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        }
        
        .search-box input {
            flex: 1;
            border: none;
            padding: 12px 15px;
            font-size: 0.95em;
            outline: none;
        }
        
        .search-box .search-button {
            background: var(--secondary-color);
            color: white;
            border: none;
            padding: 0 20px;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .search-box .search-button:hover {
            background: #1b62db;
        }
        
        /* Search Results */
        .search-results {
            padding: 15px 20px;
            background-color: var(--card-background);
            border-radius: 6px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .search-results h3 {
            color: var(--primary-color);
            font-weight: 500;
        }
        
        /* News Sections */
        .main-content {
            display: flex;
            flex-direction: column;
            gap: 30px;
            padding: 20px 0;
        }
        
        .feature-news {
            display: flex;
            gap: 30px;
            background-color: var(--card-background);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .feature-news img {
            width: 50%;
            border-radius: 6px;
            object-fit: cover;
            max-height: 400px;
        }
        
        .feature-news .news-content {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .feature-news h2 {
            font-size: 1.8em;
            line-height: 1.3;
        }
        
        .feature-news h2 a {
            color: var(--text-color);
            text-decoration: none;
            transition: color 0.2s;
        }
        
        .feature-news h2 a:hover {
            color: var(--secondary-color);
        }
        
        .feature-news p {
            font-size: 1.05em;
            color: var(--light-text);
            line-height: 1.6;
        }
        
        .new-items {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .news-item {
            background-color: var(--card-background);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: transform 0.2s;
        }
        
        .news-item:hover {
            transform: translateY(-3px);
        }
        
        .news-item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        
        .news-item h3 {
            padding: 15px;
            font-size: 1.1em;
            line-height: 1.4;
        }
        
        .news-item h3 a {
            color: var(--text-color);
            text-decoration: none;
            transition: color 0.2s;
        }
        
        .news-item h3 a:hover {
            color: var(--secondary-color);
        }
        
        .news-grid {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .news-item.horizontal-layout {
            display: flex;
            flex-direction: column;
            background-color: var(--card-background);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .news-item.horizontal-layout h3 {
            padding: 15px 15px 10px;
            margin: 0;
            font-size: 1.2em;
        }
        
        .content-wrapper {
            display: flex;
            gap: 20px;
            padding: 0 15px 15px;
        }
        
        .news-item.horizontal-layout img {
            width: 200px;
            height: 150px;
            border-radius: 6px;
            object-fit: cover;
        }
        
        .text-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        .text-content p {
            margin-bottom: 15px;
            color: var(--light-text);
        }
        
        .timestamp {
            color: #999;
            font-size: 0.8em;
            font-style: italic;
        }
        
        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 40px;
            background-color: var(--card-background);
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        /* Responsive */
        @media (max-width: 900px) {
            .feature-news {
                flex-direction: column;
            }
            
            .feature-news img {
                width: 100%;
            }
            
            .new-items {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 600px) {
            .content-wrapper {
                flex-direction: column;
            }
            
            .news-item.horizontal-layout img {
                width: 100%;
                height: 180px;
            }
            
            .categories {
                justify-content: flex-start;
                overflow-x: auto;
                padding-bottom: 10px;
            }
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/../../includes/nav.php'; ?>


    <div class="container">
        <div class="user-controls" style="margin-top: 80px;">
            <span class="welcome">Chào mừng, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <?php if ($_SESSION['role'] == 'admin'): ?>
                <a href="create_post.php" class="create-post-link">
                    <i class="fa-solid fa-plus"></i>
                    Tạo bài viết mới
                </a>
            <?php endif; ?>
            <i class="fa-solid fa-right-from-bracket" onclick="window.location.href='logout.php'" title="Đăng xuất"></i>
        </div>
        
        <!-- <div class="categories">
            <a href="#">Thời sự</a>
            <a href="#">Góc nhìn</a>
            <a href="#">Thế giới</a>
            <a href="#">Video</a>
            <a href="#">Podcasts</a>
            <a href="#">Kinh doanh</a>
            <a href="#">Bất động sản</a>
            <a href="#">Khoa học</a>
            <a href="#">Giải trí</a>
            <a href="#">Thể thao</a>
            <a href="#">Pháp luật</a>
            <a href="#">Giáo dục</a>
            <a href="#">Sức khỏe</a>
            <a href="#">Đời sống</a>
            <a href="#">Du lịch</a>
            <a href="#">Công nghệ</a>
            <a href="#">Xe</a>
            <a href="#">Ý kiến</a>
            <a href="#">Tâm sự</a>
        </div> -->
        
        <div class="search-area">
            <form id="search-form" action="" method="GET" class="search-box">
                <input type="search" name="search" placeholder="Tìm kiếm bài viết..." value="<?php echo htmlspecialchars($search_term); ?>">
                <button type="submit" class="search-button">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
            </form>
        </div>
        
        <?php if ($search_term): ?>
        <div class="search-results">
            <h3>Kết quả tìm kiếm cho: "<?php echo htmlspecialchars($search_term); ?>"</h3>
        </div>
        <?php endif; ?>
        
        <div class="main-content">
            <?php if ($result->num_rows > 0): ?>
                <?php 
                $feature_news = $result->fetch_assoc();
                ?>
                <div class="feature-news">
                    <?php if (!empty($feature_news['image'])): ?>
                        <img src="../uploads/<?php echo $feature_news['image']; ?>" alt="<?php echo $feature_news['title']; ?>">
                    <?php endif; ?>

                    <div class="news-content">
                        <h2><a href="post.php?id=<?php echo $feature_news['id']; ?>"><?php echo $feature_news['title']; ?></a></h2>
                        <p><?php echo substr($feature_news['content'], 0, 300); ?>...</p>
                        <p class="timestamp"><?php echo $feature_news['created_at']; ?></p>
                    </div>
                </div>

                <div class="new-items">
                    <?php 
                    for ($i = 0; $i < 2; $i++) {
                        if ($row = $result->fetch_assoc()) {
                            echo "<div class='news-item'>";
                            if (!empty($row['image'])) {
                                echo "<img src='../uploads/". $row['image'] . "' alt='" . $row['title'] . "'>";
                            }
                            echo "<h3><a href='post.php?id=" . $row['id'] . "'>" . $row['title'] . "</a></h3>";
                            echo "</div>";
                        }
                    }
                    ?>
                </div>
                
                <div class="news-grid">
                    <?php
                    while ($row = $result->fetch_assoc()) {
                        echo "<div class='news-item horizontal-layout'>";
                        echo "<h3><a href='post.php?id=" . $row['id'] . "'>" . $row['title'] . "</a></h3>";
                        echo "<div class='content-wrapper'>";
                        if (!empty($row['image'])) {
                            echo "<img src='../uploads/" . $row['image'] . "' alt='" . $row['title'] . "'>";
                        }
                        echo "<div class='text-content'>";
                        echo "<p>" . substr($row['content'], 0, 150) . "...</p>";
                        echo "<p class='timestamp'>Được đăng vào: " . $row['created_at'] . "</p>";
                        echo "</div>";
                        echo "</div>";
                        echo "</div>";
                    }
                    ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p>Không có bài viết nào. Vui lòng thử lại sau.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Xử lý sự kiện tìm kiếm đã được tối giản với form submit
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>