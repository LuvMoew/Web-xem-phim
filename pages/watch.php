<?php
include "../config/config.php"; // Kết nối database
session_start();

// Nếu chưa đăng nhập, chuyển hướng sang login.php
if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/login.php");
    exit;
}
// ------------------

// Lấy movie_id từ GET hoặc SESSION
if (isset($_GET['movie_id'])) {
    $movie_id = intval($_GET['movie_id']); 
} elseif (isset($_SESSION['movie_id'])) {
    $movie_id = intval($_SESSION['movie_id']); 
} else {
    die("Lỗi: Không tìm thấy ID phim!"); 
}

if (!isset($_GET['episode_id'])) {
    echo "<p>Không tìm thấy tập phim!</p>";
    exit;
}

$episode_id = intval($_GET['episode_id']);

// Lấy thông tin tập phim
$query_episode = $conn->prepare("SELECT * FROM episodes WHERE id = ?");
$query_episode->bind_param("i", $episode_id);
$query_episode->execute();
$result_episode = $query_episode->get_result();
$episode = $result_episode->fetch_assoc();

if (!$episode) {
    echo "<p>Tập phim không tồn tại!</p>";
    exit;
}

// Lấy thông tin phim
$query_movie = $conn->prepare("SELECT * FROM movies WHERE id = ?");
$query_movie->bind_param("i", $episode['movie_id']);
$query_movie->execute();
$result_movie = $query_movie->get_result();
$movie = $result_movie->fetch_assoc();

// Lấy danh sách tập phim cùng bộ
$query_episodes = $conn->prepare("SELECT * FROM episodes WHERE movie_id = ? ORDER BY episode_number");
$query_episodes->bind_param("i", $episode['movie_id']);
$query_episodes->execute();
$result_episodes = $query_episodes->get_result();

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($movie['title']) . ' - Tập ' . $episode['episode_number']; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        .episodes-container { display: flex; gap: 10px; overflow-x: auto; padding: 10px; }
        .episode-item { padding: 10px; border: 1px solid #ddd; background: #f5f5f5; border-radius: 5px; }
        .comments { margin-top: 20px; }
        .comment-box { width: 100%; padding: 10px; border: 1px solid #ddd; }
        .edit-comment,
.delete-comment {
    border: none;
    border-radius: 4px;
    width: 32px;
    height: 32px;
    font-size: 14px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-left: 5px;
    transition: all 0.2s ease;
}

.edit-comment {
    background-color: #3498db;
    color: white;
}

.edit-comment:hover {
    background-color: #2980b9;
}

.delete-comment {
    background-color: #e74c3c;
    color: white;
}

.delete-comment:hover {
    background-color: #c0392b;
}

        body {
            font-family: Arial, sans-serif;
            background-color: #1a1a1a;
            color: #ffffff;
            margin: 0;
            padding: 0;
            margin-top: 100px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .video-container {
            position: relative;
            overflow: hidden;
            border-radius: 8px;
            background-color: #000;
            margin-bottom: 30px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }
        
        .episode-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        
        .btn {
            padding: 10px 15px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-play {
            background-color: #1e9e6b;
            color: white;
        }
        
        .btn-bookmark {
            background-color: #3a9188;
            color: white;
        }
        
        .btn-favorite {
            background-color: #b58b00;
            color: white;
        }
        
        .btn-admin {
            background-color: #d9534f;
            color: white;
        }
        
        .section {
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 20px;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 2px solid #333;
        }
        
        .episodes-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .episode-item {
            background-color: #2a2a2a;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            color: #fff;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        
        .episode-item:hover {
            background-color: #3a3a3a;
        }
        
        .episode-item.active {
            background-color: #1e9e6b;
        }
        
        .comments-section {
            background-color: #2a2a2a;
            padding: 20px;
            border-radius: 8px;
        }
        
        .comment-form textarea {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            background-color: #333;
            color: white;
            border: none;
            margin-bottom: 10px;
            min-height: 100px;
        }
        
        .comment-form button {
            background-color: #1e9e6b;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .comment-item {
            margin-top: 15px;
            padding: 15px;
            background-color: #333;
            border-radius: 5px;
        }
        
        .comment-header {
            margin-bottom: 10px;
            font-weight: bold;
        }
        
        .movie-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .rating {
            display: inline-block;
            color: gold;
            margin-right: 5px;
        }
        
        .rating-container {
            margin-bottom: 15px;
        }

        .star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
        }

        .star-rating input {
            display: none;
        }

        .star-rating label {
            cursor: pointer;
            font-size: 30px;
            color: #ccc;
            transition: color 0.2s;
        }

        .star-rating label:hover,
        .star-rating label:hover ~ label,
        .star-rating input:checked ~ label {
            color: gold;
        }

        .comment-rating {
            color: gold;
            font-size: 20px;
            margin-bottom: 5px;
        }
        
        .breadcrumb {
            background-color: transparent;
            padding: 0;
            margin-bottom: 20px;
        }
        
        .breadcrumb-item a {
            color: #1e9e6b;
            text-decoration: none;
        }
        
        .breadcrumb-item.active {
            color: #999;
        }
        
        .video-info {
            background-color: #2a2a2a;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .video-info-table {
            width: 100%;
        }
        
        .video-info-table td {
            padding: 8px 0;
            border-bottom: 1px solid #333;
        }
        
        .video-info-table td:first-child {
            font-weight: bold;
            color: #999;
            width: 150px;
        }
    </style>
</head>
<body>

<?php include '../includes/nav.php'; ?>

<div class="container">
    
    <h1><?php echo htmlspecialchars($movie['title']) . ' - Tập ' . $episode['episode_number']; ?></h1>
    <div style="text-align: center;">
        <video width="70%" controls>
            <source src="<?php echo htmlspecialchars($episode['video_url']); ?>" type="video/mp4">
            Trình duyệt của bạn không hỗ trợ phát video.
        </video>
    </div>
    
    
    <div class="movie-actions">
        
        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
        <button class="btn btn-admin" onclick="openEditModal()">
            <i class="fas fa-edit"></i> Sửa
        </button>
        <button class="btn btn-admin" onclick="deleteEpisode(<?= $episode['id'] ?>)">
            <i class="fas fa-trash"></i> Xóa
        </button>
        <?php endif; ?>
    </div>
    <div class="section">
        <h2>Danh sách tập phim</h2>
        <div class="episodes-container">
            <?php while ($ep = $result_episodes->fetch_assoc()): ?>
                <a href="watch.php?episode_id=<?php echo $ep['id']; ?>" class="episode-item">Tập <?php echo $ep['episode_number']; ?></a>
            <?php endwhile; ?>
        </div>
       
    </div>


    <div class="section comments-section ">
    <h2 class="section-title">Bình luận</h2>
    <div class="comment-form">
        <form id="commentForm">
        <input type="hidden" name="movie_id" value="<?php echo $movie_id; ?>">

            <div class="rating-container">
                <p>Đánh giá của bạn:</p>
                <div class="star-rating">
                    <input type="radio" id="star5" name="rating" value="5" required><label for="star5">&#9733;</label>
                    <input type="radio" id="star4" name="rating" value="4"><label for="star4">&#9733;</label>
                    <input type="radio" id="star3" name="rating" value="3"><label for="star3">&#9733;</label>
                    <input type="radio" id="star2" name="rating" value="2"><label for="star2">&#9733;</label>
                    <input type="radio" id="star1" name="rating" value="1"><label for="star1">&#9733;</label>
                </div>
            </div>
            <textarea name="comment" id="commentText" placeholder="Nhập bình luận của bạn..." required></textarea>
            <button type="submit">Gửi bình luận</button>
        </form>
    </div>
    <div id="comments">
    <?php
        $comments = mysqli_query($conn, "SELECT * FROM comments WHERE movie_id = $movie_id ORDER BY created_at DESC");
        if (mysqli_num_rows($comments) > 0) {
            while ($row = mysqli_fetch_assoc($comments)) {
                echo "<div class='comment-item' data-id='" . $row['id'] . "'>";
                echo "<div class='comment-header'>";
                echo "<span class='comment-user'>" . htmlspecialchars($row['user']) . "</span>";
                echo "<span class='comment-date'>" . date('d/m/Y H:i', strtotime($row['created_at'])) . "</span>";
               
                
                // Chỉ hiển thị nút sửa và xóa nếu là bình luận của user hoặc là admin
                if (isset($_SESSION['user_id']) && ($_SESSION['user_id'] == $row['user_id'] || $_SESSION['role'] === 'admin')) {
                    echo "<button class='edit-comment' title='Sửa'><i class='fas fa-edit'></i></button>";
                    echo "<button class='delete-comment' title='Xóa'><i class='fas fa-trash-alt'></i></button>";
                }
                echo "</div>"; // End comment-header
                
                echo "<div class='comment-rating'>";
                for ($i = 1; $i <= 5; $i++) {
                    echo ($i <= $row['rating']) ? "★" : "☆";
                }
                echo "</div>";
                echo "<div class='comment-content'>" . htmlspecialchars($row['comment']) . "</div>";
                echo "</div>"; // End comment-item
            }
        } else {
            echo "<p>Chưa có bình luận nào. Hãy là người đầu tiên bình luận!</p>";
        }
    ?>
    </div>
</div>
    
<script>
     // ----------------------CMT--------------------------
     document.addEventListener("DOMContentLoaded", function () {
    // Gửi bình luận mới
     document.getElementById("commentForm").addEventListener("submit", function (e) {
        e.preventDefault(); // Ngăn tải lại trang
        let formData = new FormData(this);
        formData.append("action", "add");

        fetch("../models/quanly/comment.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === "success") {
                alert("Bình luận đã được gửi!");
                location.reload(); // Reload trang để cập nhật bình luận
            } else {
                alert("Lỗi: " + data.message);
            }
        })
        .catch(error => console.error("Lỗi:", error));
    });

    // Xử lý sự kiện sửa bình luận
    document.querySelectorAll(".edit-comment").forEach(button => {
        button.addEventListener("click", function () {
            let commentItem = this.closest(".comment-item");
            let commentId = commentItem.getAttribute("data-id");
            let currentComment = commentItem.querySelector(".comment-content").textContent;
            let newComment = prompt("Nhập nội dung chỉnh sửa:", currentComment);

            if (newComment !== null && newComment.trim() !== "") {
                let formData = new FormData();
                formData.append("action", "edit");
                formData.append("comment_id", commentId);
                formData.append("comment", newComment);

                fetch("../models/quanly/comment.php", {
                    method: "POST",
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === "success") {
                        alert("Bình luận đã được cập nhật!");
                        location.reload();
                    } else {
                        alert("Lỗi: " + data.message);
                    }
                })
                .catch(error => console.error("Lỗi:", error));
            }
        });
    });

    // Xử lý sự kiện xóa bình luận
    document.querySelectorAll(".delete-comment").forEach(button => {
        button.addEventListener("click", function () {
            if (confirm("Bạn có chắc chắn muốn xóa bình luận này?")) {
                let commentItem = this.closest(".comment-item");
                let commentId = commentItem.getAttribute("data-id");

                let formData = new FormData();
                formData.append("action", "delete");
                formData.append("comment_id", commentId);

                fetch("../models/quanly/comment.php", {
                    method: "POST",
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === "success") {
                        alert("Bình luận đã được xóa!");
                        location.reload();
                    } else {
                        alert("Lỗi: " + data.message);
                    }
                })
                .catch(error => console.error("Lỗi:", error));
            }
        });
    });
});

</script>
</body>
</html>
