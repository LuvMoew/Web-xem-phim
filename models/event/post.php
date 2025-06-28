<?php
session_start();
include __DIR__ . '/../../config/config.php';

// Lấy bài viết theo ID
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "SELECT * FROM posts WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $post = $result->fetch_assoc();
    } else {
        echo "Bài viết không tồn tại!";
        exit();
    }
} else {
    echo "ID bài viết không hợp lệ!";
    exit();
}

// Thêm bình luận
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id']) && isset($_POST['content'])) {
    $content = trim($_POST['content']);
    $user_id = $_SESSION['user_id'];
    
    if (!empty($content)) {
        // Thực hiện truy vấn thêm bình luận
        $sql = "INSERT INTO comments (content, post_id, user_id) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            die('Lỗi chuẩn bị truy vấn: ' . $conn->error);
        }
        $stmt->bind_param("sii", $content, $id, $user_id);
        
        if ($stmt->execute()) {
            header("Location: post.php?id=$id"); // Tránh việc gửi lại form
            exit();
        } else {
            echo "Lỗi: " . $stmt->error; // Hiển thị lỗi SQL nếu có
        }
    } else {
        echo "Bình luận không thể để trống!"; // Thông báo nếu bình luận rỗng
    }
}

// Xóa bài viết
if (isset($_POST['delete']) && isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] == 'admin' || $_SESSION['role'] == 'admin') { // Kiểm tra quyền admin (sửa để kiểm tra cả hai biến)
        $sql = "DELETE FROM posts WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            header("Location: index.php"); // Quay lại trang chủ sau khi xóa
            exit();
        } else {
            echo "Lỗi khi xóa bài viết: " . $stmt->error;
        }
    } else {
        echo "Bạn không có quyền xóa bài viết này.";
    }
}

// Lấy bình luận cùng với username
$sql = "SELECT comments.comment AS content, comments.created_at, users.username 
        FROM comments 
        JOIN users ON comments.user_id = users.id
        WHERE comments.movie_id = ? 
        ORDER BY comments.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$comments = $stmt->get_result();

// Thêm bình luận
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id']) && isset($_POST['content'])) {
    $content = trim($_POST['content']);
    $user_id = $_SESSION['user_id'];
    
    if (!empty($content)) {
        // Thực hiện truy vấn thêm bình luận
        $sql = "INSERT INTO comments (comment, movie_id, user_id, created_at) VALUES (?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            die('Lỗi chuẩn bị truy vấn: ' . $conn->error);
        }
        $stmt->bind_param("sii", $content, $id, $user_id);
        
        if ($stmt->execute()) {
            header("Location: post.php?id=$id"); // Tránh việc gửi lại form
            exit();
        } else {
            echo "Lỗi: " . $stmt->error; // Hiển thị lỗi SQL nếu có
        }
    } else {
        echo "Bình luận không thể để trống!"; // Thông báo nếu bình luận rỗng
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($post['title']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background-color:rgb(136, 150, 161); }
        .container { max-width: 800px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1); }
        h1, h2 { color: #333; }
        .comment-box { margin-top: 20px; border-top: 1px solid #e5e5e5; padding-top: 20px; }
        .comment-box textarea { 
            width: 100%; 
            padding: 10px; 
            border: 1px solid #e5e5e5; 
            border-radius: 25px; 
            background-color: #f7f7f7;
            height: 40px;
        }
        .comment-box input[type="submit"] {
            display: none; 
        }
        .comments-list { margin-top: 20px; }
        .comment-item {
            margin-bottom: 15px;
            position: relative;
            display: flex;
            align-items: flex-start;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }
        .comment-content {
            flex-grow: 1;
        }
        .comment-username {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .comment-text {
            margin-bottom: 5px;
        }
        .comment-actions {
            display: flex;
            gap: 15px;
            font-size: 14px;
            color: #666;
        }
        .comment-actions a {
            text-decoration: none;
            color: #666;
        }
        .comment-time {
            color: #999;
            font-size: 14px;
            position: absolute;
            right: 0;
            top: 0;
        }
        .reaction-count {
            display: inline-flex;
            align-items: center;
            margin-left: 5px;
        }
        .popular-posts { 
            margin-top: 20px; 
        }
        .popular-posts h3 { 
            color: #191919;
            margin-bottom: 15px; 
        }
        .popular-posts ul { 
            list-style: none; 
            padding: 0; 
        }
        .popular-posts ul li { 
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .popular-posts ul li:last-child {
            border-bottom: none;
        }
        .popular-posts ul li a { 
            text-decoration: none; 
            color: #333; 
            font-weight: normal;
            line-height: 1.4;
            display: block;
        }
        .popular-posts ul li a:hover {
            color: #d61818;
        }
        .comment-tabs {
            display: flex;
            border-bottom: 1px solid #e5e5e5;
            margin-bottom: 20px;
        }
        .comment-tabs a {
            padding: 10px 20px;
            text-decoration: none;
            color: #666;
        }
        .comment-tabs a.active {
            color: #d61818;
            border-bottom: 2px solid #d61818;
        }
        .comment-count {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .comment-count span {
            color: #666;
            font-weight: normal;
        }
        header {
            text-align: center;
            margin-bottom: 20px;
        }
        header a {
            text-decoration: none;
            color: #d61818;
            font-size: 16px;
        }
        header a:hover {
            text-decoration: underline;
        }
        .edit-btn {
            max-width: 150px;
            display: inline-block;
            padding: 10px 20px;
            background-color: #d61818;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 16px;
            transition: background-color 0.3s ease;
            margin-top: 0px;
            margin-bottom: 20px;
        }

        .edit-btn:hover {
            background-color: #a91313;
        }

        .edit-btn:active {
            background-color: #8b0c0c;
        }

    </style>
</head>
<body>
<?php include __DIR__ . '/../../includes/nav.php'; ?>
    
    <div class="container" style="margin-top: 60px;">
        <div class="main-content">
            <h1><?php echo htmlspecialchars($post['title']); ?></h1>
            <p>
                <img src="../uploads/<?php echo htmlspecialchars($post['image']); ?>" alt="Hình ảnh" style="max-width: 100%; height: auto;">
            </p>
            <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>

            <?php
            // Kiểm tra cả hai biến user_role và role
            if (isset($_SESSION['user_id']) && (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin') || 
               (isset($_SESSION['role']) && $_SESSION['role'] == 'admin')) {
                // Hiển thị nút xóa và chỉnh sửa cho admin
                ?>
                <form action="post.php?id=<?php echo $id; ?>" method="POST">
                    <button type="submit" name="delete" onclick="return confirm('Bạn có chắc chắn muốn xóa bài viết này?')">Xóa bài viết</button>
                </form>
                
                <a href="edit_post.php?id=<?php echo $id; ?>" class="edit-btn">Chỉnh sửa bài viết</a>
                <?php
            }
            ?>

            <div class="comment-section">
                <div class="comment-count">
                    Ý kiến (<?php echo $comments->num_rows; ?>)
                </div>
                
                <?php if (isset($_SESSION['user_id'])) { ?>
                <div class="comment-box">
                    <form action="post.php?id=<?php echo $id; ?>" method="POST">
                        <textarea name="content" placeholder="Chia sẻ ý kiến của bạn" required></textarea>
                        <button type="submit" style="margin-top: 10px">Gửi bình luận</button>
                    </form>
                </div>
            <?php } else { ?>
                <p>Vui lòng <a href="login.php">đăng nhập</a> để bình luận.</p>
            <?php } ?>

                <!-- <div class="comment-tabs">
                    <a href="#" class="active">Quan tâm nhất</a>
                    <a href="#">Mới nhất</a>
                </div> -->
                
                <div class="comments-list">
                    <?php while ($comment = $comments->fetch_assoc()) { 
                        $first_letter = mb_substr($comment['username'], 0, 1, 'UTF-8');
                    ?>
                        <div class="comment-item">
                            <div class="user-avatar">
                                <?php echo htmlspecialchars($first_letter); ?>
                            </div>
                            <div class="comment-content">
                                <div class="comment-username">
                                    <?php echo htmlspecialchars($comment['username']); ?>
                                </div>
                                <div class="comment-text">
                                    <?php echo htmlspecialchars($comment['content']); ?>
                                </div>
                                <div class="comment-actions">
                                    <a href="#">Thích</a>
                                    <a href="#">Trả lời</a>
                                </div>
                            </div>
                            <div class="comment-time">
                                <?php 
                                $created_at = new DateTime($comment['created_at']);
                                $now = new DateTime();
                                $interval = $created_at->diff($now);
                                
                                if ($interval->d > 0) {
                                    echo $interval->d . ' ngày trước';
                                } elseif ($interval->h > 0) {
                                    echo $interval->h . ' h trước';
                                } elseif ($interval->i > 0) {
                                    echo $interval->i . ' phút trước';
                                } else {
                                    echo 'Vừa xong';
                                }
                                ?>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>

        <!-- <aside class="sidebar">
            <div class="popular-posts">
                <h2>Xem nhiều</h2>
                <ul>
                    <?php while ($popular = $popularPosts->fetch_assoc()) { ?>
                        <li>
                            <a href="post.php?id=<?php echo $popular['id']; ?>">
                                <?php echo htmlspecialchars($popular['title']); ?>
                                <span class="comment-count">(<?php echo $popular['comment_count']; ?> bình luận)</span>
                            </a>
                        </li>
                    <?php } ?>
                </ul>
            </div>
        </aside> -->
    </div>
</body>
</html>

<?php $conn->close(); ?>