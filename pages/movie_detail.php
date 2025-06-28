<?php
include '../config/config.php';

session_start(); // Add session for admin check

// Kiểm tra nếu 'id' không tồn tại trong URL hoặc không phải số
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Lỗi: ID phim không hợp lệ!");
}

$id = intval($_GET['id']); // Chuyển đổi ID thành số nguyên để tránh lỗi SQL Injection

// Truy vấn lấy thông tin phim
$query = "SELECT * FROM movies WHERE id = $id";
$result = mysqli_query($conn, $query);

// Kiểm tra nếu truy vấn thất bại
if (!$result) {
    die("Lỗi truy vấn: " . mysqli_error($conn));
}

// Lấy dữ liệu phim
$movie = mysqli_fetch_assoc($result);

// Kiểm tra nếu phim không tồn tại
if (!$movie) {
    die("Lỗi: Không tìm thấy phim!");
}

// Kiểm tra vai trò admin
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Cập nhật lượt xem
mysqli_query($conn, "UPDATE movies SET views = views + 1 WHERE id = $id");

// Lấy tổng hợp đánh giá từ bảng comments
$movie_id = $movie['id'];
$query = "SELECT AVG(rating) AS avg_rating, COUNT(rating) AS total_votes FROM comments WHERE movie_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $movie_id);
$stmt->execute();
$result = $stmt->get_result();
$ratingData = $result->fetch_assoc();

// Nếu có đánh giá, hiển thị giá trị thực, nếu không thì mặc định
$avgRating = $ratingData['avg_rating'] ? round($ratingData['avg_rating'], 1) : 'N/A';
$totalVotes = $ratingData['total_votes'] ? $ratingData['total_votes'] : 0;

// -----------------------------------------------

// Kiểm tra quyền admin
$isAdmin = false;
$username = "Khách"; // Mặc định nếu chưa đăng nhập

if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
}

if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $isAdmin = true;
}

// Kiểm tra ID phim
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<p>Không tìm thấy phim!</p>";
    exit;
}

$movie_id = intval($_GET['id']);
$_SESSION['movie_id'] = $movie_id;
// Lấy thông tin phim
$query_movie = $conn->prepare("SELECT * FROM movies WHERE id = ?");
$query_movie->bind_param("i", $movie_id);
$query_movie->execute();
$result_movie = $query_movie->get_result();
$movie = $result_movie->fetch_assoc();

if (!$movie) {
    echo "<p>Phim không tồn tại!</p>";
    exit;
}

// Xử lý thêm tập phim
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_episode'])) {
    $episode_number = trim($_POST['episode_number']);
    $episode_title = trim($_POST['episode_title']);
    $video_url = trim($_POST['video_url']);

    if (!empty($episode_number) && !empty($episode_title) && !empty($video_url)) {
        // Kiểm tra số tập có tồn tại chưa
        $check_stmt = $conn->prepare("SELECT id FROM episodes WHERE movie_id = ? AND episode_number = ?");
        $check_stmt->bind_param("ii", $movie_id, $episode_number);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            echo "<script>alert('Số tập này đã tồn tại!');</script>";
        } else {
            // Thêm tập phim
            $stmt = $conn->prepare("INSERT INTO episodes (movie_id, episode_number, title, video_url) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $movie_id, $episode_number, $episode_title, $video_url);

            if ($stmt->execute()) {
                echo "<script>alert('Thêm tập phim thành công!'); window.location.href = 'movie_detail.php?id=$movie_id';</script>";
            } else {
                echo "<script>alert('Lỗi khi thêm tập phim: " . $conn->error . "');</script>";
            }

            $stmt->close();
        }
        $check_stmt->close();
    } else {
        echo "<script>alert('Vui lòng nhập đầy đủ thông tin!');</script>";
    }
}

// Xử lý cập nhật tập phim
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_episode'])) {
    $episode_id = intval($_POST['episode_id']);
    $episode_number = trim($_POST['episode_number']);
    $episode_title = trim($_POST['episode_title']);
    $video_url = trim($_POST['video_url']);

    if (!empty($episode_number) && !empty($episode_title) && !empty($video_url)) {
        // Kiểm tra nếu số tập mới đã tồn tại (trừ tập hiện tại)
        $check_stmt = $conn->prepare("SELECT id FROM episodes WHERE movie_id = ? AND episode_number = ? AND id != ?");
        $check_stmt->bind_param("iii", $movie_id, $episode_number, $episode_id);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            echo "<script>alert('Số tập này đã tồn tại!');</script>";
        } else {
            // Cập nhật tập phim
            $stmt = $conn->prepare("UPDATE episodes SET episode_number = ?, title = ?, video_url = ? WHERE id = ?");
            $stmt->bind_param("issi", $episode_number, $episode_title, $video_url, $episode_id);

            if ($stmt->execute()) {
                echo "<script>alert('Cập nhật tập phim thành công!'); window.location.href = 'movie_detail.php?id=$movie_id';</script>";
            } else {
                echo "<script>alert('Lỗi khi cập nhật tập phim!');</script>";
            }

            $stmt->close();
        }
        $check_stmt->close();
    } else {
        echo "<script>alert('Vui lòng nhập đầy đủ thông tin!');</script>";
    }
}
// Xử lý xóa tập phim
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_episode'])) {
    $episode_id = intval($_POST['episode_id']);

    $stmt = $conn->prepare("DELETE FROM episodes WHERE id = ?");
    $stmt->bind_param("i", $episode_id);

    if ($stmt->execute()) {
        echo "<script>alert('Xóa tập phim thành công!'); window.location.href = 'movie_detail.php?id=$movie_id';</script>";
    } else {
        echo "<script>alert('Lỗi khi xóa tập phim!');</script>";
    }

    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $movie['title']; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/mv_detail.css">


</head>
<body>

<?php include '../includes/nav.php'; ?>

<button id="backToTop" title="Lên đầu trang">↑</button>

<div class="hero-container">
    <div class="hero-backdrop" style="background-image: url('<?php echo $movie['poster']; ?>');"></div>
    <div class="hero-gradient"></div>
    
    <div class="hero-content">
        <div class="movie-poster">
            <img src="<?php echo $movie['poster']; ?>" alt="<?php echo $movie['title']; ?>">
        </div>
        <div class="movie-info">
            <h1><?php echo $movie['title']; ?></h1>
            <div class="movie-meta">
                <span><?php echo isset($movie['release_year']) ? $movie['release_year'] : '2025'; ?></span>
                <span class="meta-divider">|</span>
                <span><?php echo isset($movie['episodes']) ? $movie['episodes'] : '?? Tập'; ?></span>
                <span class="meta-divider">|</span>
                <span><?php echo isset($movie['status']) ? $movie['status'] : 'Đang tiến hành'; ?></span>
                <span class="meta-divider">|</span>
                <span>
                    <?php 
                    $genres = explode(',', $movie['genre']);
                    echo trim($genres[0]); // Hiển thị thể loại đầu tiên
                    if(count($genres) > 1) echo " +".count($genres)-1;
                    ?>
                </span>
            </div>
            
            <div class="movie-rating">
                <div class="rating-score">
                    <?php 
                    echo is_numeric($avgRating) ? $avgRating : 'Chưa có';
                    ?>
                </div>
                <div class="rating-stars">
                    <?php 
                    if (is_numeric($avgRating)) {
                        $stars = floor($avgRating);
                        $half = $avgRating - $stars >= 0.5;
                        
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= $stars) {
                                echo '<i class="fas fa-star"></i>'; // Sao đầy
                            } elseif ($i == $stars + 1 && $half) {
                                echo '<i class="fas fa-star-half-alt"></i>'; // Sao nửa
                            } else {
                                echo '<i class="far fa-star"></i>'; // Sao rỗng
                            }
                        }
                    } else {
                        echo "Chưa có đánh giá";
                    }
                    ?>
                </div>
                <div class="votes-count"><?php echo $totalVotes; ?> đánh giá</div>
            </div>

            
            <!-- <div class="movie-description">
                <?php echo mb_substr($movie['description'], 0, 200, 'UTF-8'); ?>
                <?php if(mb_strlen($movie['description'], 'UTF-8') > 200) echo '...'; ?>
            </div> -->
            
            <div class="movie-actions">
                
                <button class="btn btn-bookmark">
                    <i class="fas fa-bookmark"></i> Theo dõi
                </button>
    
                <?php if ($isAdmin): ?>
                <button class="btn btn-admin" onclick="openEditModal()">
                    <i class="fas fa-edit"></i> Sửa
                </button>
                <button class="btn btn-admin" onclick="deleteMovie(<?php echo $id; ?>)">
                    <i class="fas fa-trash"></i> Xóa
                </button>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- <div class="seasons-container">
            <div class="seasons-list">
                <a href="#" class="season-item">Season 01</a>
                <a href="#" class="season-item">Season 02</a>
                <a href="#" class="season-item">Season 03</a>
                <a href="#" class="season-item">Season 04</a>
            </div>
        </div-->
    </div>
</div> 
     
<div class="section">
    <div class="section-main">
        <h2 class="section-title">Nội dung</h2>
        <div class="description">
            <p><?php echo $movie['description']; ?></p>
        </div>
    </div>

<div class="container-main">                
    <?php if ($isAdmin): ?>
            <h3 class="mb-3">Quản lý Tập Phim</h3>
            <form action="movie_detail.php?id=<?php echo $movie_id; ?>" method="POST" class="border p-4 rounded shadow-sm bg-secondary">
                <input type="hidden" id="episode_id" name="episode_id"> <!-- Ẩn ID tập phim để sửa -->

                <div class="mb-3">
                    <label for="episode_number" class="form-label">Số tập:</label>
                    <input type="number" id="episode_number" name="episode_number" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="episode_title" class="form-label">Tiêu đề tập:</label>
                    <input type="text" id="episode_title" name="episode_title" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="video_url" class="form-label">URL video:</label>
                    <input type="text" id="video_url" name="video_url" class="form-control" required>
                </div>

                <button type="submit" name="add_episode" id="submit_button" class="btn btn-primary">Thêm Tập</button>
                <button type="submit" name="update_episode" id="update_button" class="btn btn-warning" style="display: none;">Cập Nhật</button>
                <button type="button" onclick="resetForm()" class="btn btn-secondary">Hủy</button>
            </form>
      
    <?php endif; ?>

<div class="comments-section episodes-container">
    <h2 class="section-title">Danh sách tập</h2>
    <?php
    require 'config.php'; // Kết nối database
    
    if (!isset($_GET['id'])) {
        echo "<p class='error-message'>Không tìm thấy tập phim!</p>";
    } else {
        $movie_id = intval($_GET['id']);
        
        // Lấy danh sách tập phim
        $query_episodes = $conn->prepare("SELECT * FROM episodes WHERE movie_id = ? ORDER BY episode_number ASC");
        $query_episodes->bind_param("i", $movie_id);
        $query_episodes->execute();
        $result_episodes = $query_episodes->get_result();
        $episodes = $result_episodes->fetch_all(MYSQLI_ASSOC);
        
        if (empty($episodes)) {
            echo "<p class='error-message'>Không tìm thấy tập phim!</p>";
        } else {
            if ($isAdmin) {
                echo '<div class="episodes-table-container">';
                echo '<table class="episodes-table">';
                echo '<thead><tr><th>Số tập</th><th>Tiêu đề</th><th>Hành động</th></tr></thead>';
                echo '<tbody>';
                foreach ($episodes as $episode) {
                    echo '<tr>';
                    echo '<td>' . $episode['episode_number'] . '</td>';
                    echo '<td>' . htmlspecialchars($episode['title']) . '</td>';
                    echo '<td class="episode-actions">';
                    echo '<a href="watch.php?episode_id=' . $episode['id'] . '" class="btn btn-watch">Xem</a> ';
                    echo '<button class="btn btn-edit" onclick="editEpisode(' . $episode['id'] . ', ' . $episode['episode_number'] . ', \'' . addslashes($episode['title']) . '\', \'' . addslashes($episode['video_url']) . '\')">Sửa</button> ';
                    echo '<form action="movie_detail.php?id=' . $movie_id . '" method="POST" class="inline-form">'
                        . '<input type="hidden" name="episode_id" value="' . $episode['id'] . '">'
                        . '<button type="submit" name="delete_episode" class="btn btn-delete" onclick="return confirm(\'Bạn có chắc muốn xóa tập này?\')">Xóa</button>'
                        . '</form>';
                    echo '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
                echo '</div>';
            } else {
                echo '<div class="episodes-grid">';
                foreach ($episodes as $episode) {
                    echo '<a href="watch.php?episode_id=' . $episode['id'] . '" class="episode-button">Tập ' . $episode['episode_number'] . '</a> ';
                }
                echo '</div>';
            }
        }
    }
    ?>
</div>
<!-- ----------------------------------------------------------- -->
<div class="comments-section ">
    <h2 class="section-title">Bình luận</h2>
    <div class="comment-form">
        <form id="commentForm">
            <input type="hidden" name="movie_id" value="<?php echo $id; ?>">
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
        $comments = mysqli_query($conn, "SELECT * FROM comments WHERE movie_id = $id ORDER BY created_at DESC");
        
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

<!-- Modal sửa phim (chỉ hiển thị khi là admin) -->
<?php if ($isAdmin): ?>
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Sửa thông tin phim</h2>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>
            <form id="editForm">
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                
                <div class="form-group">
                    <label for="title">Tên phim</label>
                    <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($movie['title']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="alt_title">Đạo diễn</label>
                    <input type="text" class="form-control" id="alt_title" name="alt_title" value="<?php echo htmlspecialchars($movie['alt_title'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="genre">Thể loại</label>
                    <input type="text" class="form-control" id="genre" name="genre" value="<?php echo htmlspecialchars($movie['genre']); ?>">
                </div>
                
                <div class="form-group" id="status">
                    <label for="status">Trạng thái</label>
                    <select class="form-control" id="status" name="status">
                        <option value="Đang tiến hành" <?php echo ($movie['status'] ?? '') == 'Đang tiến hành' ? 'selected' : ''; ?>>Đang tiến hành</option>
                        <option value="Hoàn thành" <?php echo ($movie['status'] ?? '') == 'Hoàn thành' ? 'selected' : ''; ?>>Hoàn thành</option>
                        <option value="Sắp chiếu" <?php echo ($movie['status'] ?? '') == 'Sắp chiếu' ? 'selected' : ''; ?>>Sắp chiếu</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="release_year">Năm phát hành</label>
                    <input type="text" class="form-control" id="release_year" name="release_year" value="<?php echo htmlspecialchars($movie['release_year'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="episodes">Thời lượng</label>
                    <input type="text" class="form-control" id="episodes" name="episodes" value="<?php echo htmlspecialchars($movie['episodes'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="poster">URL Poster</label>
                    <input type="text" class="form-control" id="poster" name="poster" value="<?php echo htmlspecialchars($movie['poster']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="description">Mô tả</label>
                    <textarea class="form-control" id="description" name="description" rows="5"><?php echo htmlspecialchars($movie['description']); ?></textarea>
                </div>
                
                <button type="submit" class="btn btn-play" style="background-color: #008080; color: aliceblue;">Lưu thay đổi</button>
            </form>
        </div>
    </div>
<?php endif; ?>
    
<!-- ----------------JS-------------------- -->
    <script>

        function editEpisode(id, number, title, url) {
            document.getElementById('episode_id').value = id;
            document.getElementById('episode_number').value = number;
            document.getElementById('episode_title').value = title;
            document.getElementById('video_url').value = url;

            document.getElementById('submit_button').style.display = 'none';
            document.getElementById('update_button').style.display = 'inline-block';
        }

        function resetForm() {
            document.getElementById('episode_id').value = '';
            document.getElementById('episode_number').value = '';
            document.getElementById('episode_title').value = '';
            document.getElementById('video_url').value = '';

            document.getElementById('submit_button').style.display = 'inline-block';
            document.getElementById('update_button').style.display = 'none';
        }

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

        
<?php if ($isAdmin): ?>
        // Hàm mở modal sửa phim -->
        function openEditModal() {
            document.getElementById("editModal").style.display = "block";
        }
        
        // Hàm đóng modal
        function closeEditModal() {
            document.getElementById("editModal").style.display = "none";
        }
        
        // Xử lý form sửa phim
        document.getElementById("editForm").addEventListener("submit", function(e) {
            e.preventDefault();
       
            let formData = new FormData(this);
            
            fetch("../models/movie/update_movie.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                if (data.trim() === "success") {
                    alert("Cập nhật thành công!");
                    location.reload();
                } else {
                    alert("Lỗi: " + data);
                }
            })
            .catch(error => console.error("Lỗi:", error));
        });
        
        // Hàm xóa phim
        function deleteMovie(id) {
            if (confirm("Bạn có chắc chắn muốn xóa phim này?")) {
                fetch(`../models/movie/delete_movie.php?id=${id}`)
                    .then(response => response.text())
                    .then(data => {
                        if (data.trim() === "success") {
                            alert("Xóa phim thành công!");
                            window.location.href = "/webFilm/pages/index.php"; // Chuyển về trang chủ
                        } else {
                            alert("Lỗi: " + data);
                        }
                    })
                    .catch(error => console.error("Lỗi:", error));
            }
        }
        
        // Đóng modal khi click bên ngoài
        window.onclick = function(event) {
            if (event.target == document.getElementById("editModal")) {
                closeEditModal();
            }
        }
<?php endif; ?>

// <!--------------BookMark-------------------->
document.querySelector(".btn-bookmark").addEventListener("click", function() {
    let movieId = <?php echo $_GET['id']; ?>;
    let userId = <?php echo $_SESSION['user_id']; ?>;
    
    fetch("../models/kho/save_bookmark.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `movie_id=${movieId}&user_id=${userId}`
    })
    .then(response => response.text())
    .then(data => alert(data))
    .catch(error => console.error("Lỗi:", error));
});

// <!-- -------------------------------------- -->
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