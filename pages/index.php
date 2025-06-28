<?php
include __DIR__ . '/../config/config.php';

session_start();
$_SESSION['admin'] = true; // hoặc false nếu không phải admin

// Lấy danh sách phim sắp ra mắt
$result = $conn->query("SELECT * FROM upcoming_movies ORDER BY release_date ASC LIMIT 5");
$upcoming_movies = $result->fetch_all(MYSQLI_ASSOC);


// ----THông tin user
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

$userInfo = null;
if ($conn && $userId) {
    try {
        // Lấy thông tin user từ database
        $stmt = $conn->prepare("SELECT id, username, fullname, avatar FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $userInfo = $result->fetch_assoc();
        }
        
        $stmt->close();
    } catch (Exception $e) {
        error_log("Error fetching user info: " . $e->getMessage());
    }
}

// Xác định tên hiển thị - ưu tiên fullname, nếu không có thì dùng username
$displayName = '';
$avatarUrl = '/uploads/avt.jpg'; // Bắt đầu từ gốc website
if ($userInfo) {
    $displayName = !empty($userInfo['fullname']) ? $userInfo['fullname'] : $userInfo['username'];
    
    // Nếu có avatar thì sử dụng, nếu không thì dùng avatar mặc định
    if (!empty($userInfo['avatar'])) {
        $avatarUrl = $userInfo['avatar'];
    }
}

// ---------------------------------
// Đặt ở đầu file, trước phần HTML
$moviesPerPage = 15;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $moviesPerPage;

// Xác định tab hiện tại
$tabFilter = isset($_GET['tab']) ? $_GET['tab'] : 'newest';

// Xây dựng truy vấn SQL dựa trên tab
$query = "SELECT * FROM movies";

// Thêm điều kiện WHERE tùy thuộc vào tab
if ($tabFilter == 'movie') {
    $query .= " WHERE type = 'movie'";
} else if ($tabFilter == 'series') {
    $query .= " WHERE type = 'series'";
}

// Thêm điều kiện ORDER BY và LIMIT
$query .= " ORDER BY id DESC LIMIT $offset, $moviesPerPage";
$result = mysqli_query($conn, $query);

// Cập nhật truy vấn đếm tổng số phim
$totalQuery = "SELECT COUNT(*) as total FROM movies";
if ($tabFilter == 'movie') {
    $totalQuery .= " WHERE type = 'movie'";
} else if ($tabFilter == 'series') {
    $totalQuery .= " WHERE type = 'series'";
}
$totalResult = mysqli_query($conn, $totalQuery);
$totalRow = mysqli_fetch_assoc($totalResult);
$totalMovies = $totalRow['total'];
$totalPages = ceil($totalMovies / $moviesPerPage);

?>



<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang Chủ Phim</title>
    <link rel="stylesheet" href="../assets/css/index.css">

</head>
<body>
<!-- Top Navigation Bar -->
<div class="top-nav" style="margin-bottom: 20px;">
    
        <a href="#" class="nav-item active">
            <div class="nav-icon">🏠</div>
            TRANG CHỦ
        </a>

        <div class="fade-in">
            <a href="contact.php" class="nav-item">
                <div class="nav-icon">👤</div>
                CONTACT
            </a>
        </div>

        <a href="/webfilm/models/event/event.php" class="nav-item">
            <div class="nav-icon">🗓️</div>
            EVENT
        </a>
        
    <!-- Update the existing user-section HTML -->
    <div class="user-section">
        <div class="user-info">
        <!-- <div class="user-avatar" style="background-image: url('<?php echo htmlspecialchars($avatarUrl); ?>');"></div> -->

        <div class="user-profile-container">
    <!-- Thay thế cách hiển thị avatar -->
            <span class="user-name">Welcome! <?php echo htmlspecialchars($displayName); ?></span>
        </div>

    </div>
        <!-- User Dropdown Menu -->
        <!-- <div id="userDropdownMenu" class="user-dropdown-menu">
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
       
            <div class="dropdown-section">
                <h3>Quản Lý Quản Trị</h3>
                <a href="user_management.php" class="dropdown-item">
                    <span class="dropdown-icon">👥</span>
                    Quản Lý Người Dùng
                </a>
            </div>

            <div class="dropdown-section">
                <h3>Thống Kê</h3>
                <a href="stats_likes.php" class="dropdown-item">
                    <span class="dropdown-icon">❤️</span>
                    Thống Kê Lượt Thích
                </a>
                <a href="stats_views.php" class="dropdown-item">
                    <span class="dropdown-icon">👁️</span>
                    Thống Kê Lượt Xem
                </a>
                <a href="stats_ratings.php" class="dropdown-item">
                    <span class="dropdown-icon">⭐</span>
                    Thống Kê Đánh Giá
                </a>
            </div>

            <div class="dropdown-section">
                <h3>Quản Lý Nội Dung</h3>
                <a href="comment_management.php" class="dropdown-item">
                    <span class="dropdown-icon">💬</span>
                    Quản Lý Bình Luận
                </a>
            </div>
            <?php else: ?>
          
                //User
            <div class="dropdown-section"> 
                <h3>Tài Khoản Cá Nhân</h3>
                <a href="user_management.php" class="dropdown-item">
                    <span class="dropdown-icon">👤</span>
                    Hồ Sơ Của Tôi
                </a>
                <a href="watch_history.php" class="dropdown-item">
                    <span class="dropdown-icon">🎬</span>
                    Tủ Phim
                </a>
            </div>

            <div class="dropdown-section">
                <h3>Cài Đặt</h3>
                <a href="account_settings.php" class="dropdown-item">
                    <span class="dropdown-icon">⚙️</span>
                    Cài Đặt Tài Khoản
                </a>
            </div>
            <?php endif; ?>

            <div class="dropdown-section logout">
                <a href="../public/login.php" class="dropdown-item logout-item">
                    <span class="dropdown-icon">🚪</span>
                    Đăng Xuất
                </a>
            </div>
         </div> --> 
    </div>
    <!-- ------------------------------------------- -->
</div>
    
<!-- Stats Bar
<div class="stats-bar">
        <div class="stat-box">
            <div class="stat-icon">💿</div>
            <div>
                <div style="font-size: 12px;">Phiên bản</div>
                <div>v2.24.166.16</div>
            </div>
        </div>
        
        <div class="stat-box">
            <div class="stat-icon">👁️</div>
            <div>
                <div style="font-size: 12px;">Trực tuyến</div>
                <div>2,297+</div>
            </div>
        </div>
        <div class="stat-box">
            <div class="stat-icon">▶️</div>
            <div>
                <div style="font-size: 12px;">Đang xem</div>
                <div>1,228+</div>
            </div>
        </div>
</div> -->

<!-- Popup hiển thị phim -->
<div id="moviePopup" class="modal-container">
        <div class="modal-content">
            <div class="close-button" onclick="closePopup()">✕</div>
            
            <div class="movie-header">
                <img id="popupImage" class="movie-backdrop" src="" alt="">
                <h1 id="popupTitle" class="movie-title"></h1>
            </div>
            
            <div class="movie-info">
                <div class="movie-meta">
                    <span>2024</span>
                    <span>T18</span>
                    <span id="popupGenre"></span>
                </div>
                
            <p id="popupDescription" class="movie-description"></p>
            
            <button class="cta-button" id="viewDetailBtn">
                Xem ngay
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M8 5V19L19 12L8 5Z" fill="white"/>
                </svg>

            </button>
            </div>
        </div>
</div>

<!-- ----------------------------------------------------- -->
    
<?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin'): ?>
        <a href="../models/movie/admin_add_upcoming.php" style="margin-left: 20px; padding: 6px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;">+ Thêm phim </a>
<?php endif; ?>    
<!-- Main Content: Featured Section and Quick Actions -->
<div class="main-content">

    <!-- Featured Movie/Game Slider -->
    <div class="featured-slider">
        <?php foreach ($upcoming_movies as $index => $movie): ?>
        <div class="featured-slide" style="background-image: url('uploads/<?php echo basename($movie['image']); ?>');">
                <div class="featured-content">
                    <h1><?php echo htmlspecialchars($movie['title']); ?></h1>
                    <p>Ra mắt vào: <?php echo date('d/m/Y', strtotime($movie['release_date'])); ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Quick Actions Section (Replaces Events Bar) -->

    <div class="quick-actions">
        <h2 class="events-title">THAO TÁC NHANH</h2>
        <div class="action-buttons">
        
        <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin'): ?>
            <a href="../models/quanly/user_management.php" class="action-button">
                    <div class="action-icon">👥</div>
                    <div class="action-text">
                        <div>QUẢN LÝ </div>
                        <div style="font-size: 12px; color: #8a8d94;">User</div>
                    </div>
                </a>   
                <!-- <a href="../models/thongke/statistics.php" class="action-button">
                    <div class="action-icon">⭐</div>
                    <div class="action-text">
                        <div>THỐNG KÊ</div>
                        <div style="font-size: 12px; color: #8a8d94;">Tương tác</div>

                    </div>
                </a> -->
                <a href="../models/thongke/get_payment_stats.php" class="action-button">
                    <div class="action-icon">📊</div>
                    <div class="action-text">
                        <div>THỐNG KÊ </div>
                        <div style="font-size: 12px; color: #8a8d94;">Hóa đơn</div>

                    </div>
                </a>
                <a href="../models/quanly/ql_cmt.php" class="action-button">
                    <div class="action-icon">💬</div>
                    <div class="action-text">
                        <div>QUẢN LÝ</div>
                        <div style="font-size: 12px; color: #8a8d94;">Tương tác</div>
                    </div>
                </a>

                
                <!-- user -->
            <?php else: ?>
                <a href="../models/quanly/user_management.php" class="action-button">
                    <div class="action-icon">❤️</div>
                    <div class="action-text">
                        <div>TÀI KHOẢN</div>
                    </div>
                </a>
                <a href="kho.php" class="action-button">
                    <div class="action-icon">⭐</div>
                    <div class="action-text">
                        <div>KHO</div>
                    </div>
                </a>
                
            <?php endif; ?>

            <a href="../public/login.php" class="action-button logout-button">
                <div class="action-icon">🚪</div>
                <div class="action-text">
                    <div>ĐĂNG XUẤT</div>
                </div>
            </a>
        </div>
    </div>
    
</div>
    
    <!-- Search and Filter Section -->
<!-- Tab Bar -->
<div class="tab-bar">
    <div class="tab <?php echo (!isset($_GET['tab']) || $_GET['tab'] == 'newest') ? 'active' : ''; ?>" data-tab="newest">🆕 MỚI NHẤT</div>
    <div class="tab <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'movie') ? 'active' : ''; ?>" data-tab="movie">🎬 PHIM RẠP</div>
    <div class="tab <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'series') ? 'active' : ''; ?>" data-tab="series">📺 PHIM BỘ</div>
</div>

<!-- Modify the search-filter-section to improve button placement -->
<div class="search-filter-section">
    <div class="search-filters">
        <input type="text" id="search" placeholder="Tìm kiếm phim...">
        <select id="filter-genre">
            <option value="">Thể loại</option>
            <?php
            // Lấy danh sách thể loại từ database
            $genreQuery = mysqli_query($conn, "SELECT DISTINCT genre FROM movies ORDER BY genre");
            while($genreRow = mysqli_fetch_assoc($genreQuery)) {
                $genre = $genreRow['genre'];
                echo "<option value='$genre'>$genre</option>";
            }
            ?>
        </select>
        <select id="sort-movies">
            <option value="views">Lượt xem</option>
            <option value="year">Năm phát hành</option>
        </select>
    </div>
    
    <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin'): ?>
            <a href="../models/movie/admin_upload.php" class="btn-post">
                <span>+</span> Đăng phim mới
            </a>
    <?php endif; ?>
</div>
    
<!-- Movies Grid with Pagination -->
<div class="movie-grid-container">
    <div class="movie-grid" id="movie-grid">
        <?php
        // Đã xóa phần trùng lặp, chỉ sử dụng $result từ trên
        while ($row = mysqli_fetch_assoc($result)) {
            if (!isset($row['id'])) {
                die("Lỗi: ID của phim không tồn tại!"); // Debug lỗi
            }
            
            // Đặt giá trị mặc định cho type nếu chưa có
            $type = isset($row['type']) ? $row['type'] : 'movie';

            // Lấy đánh giá trung bình từ bảng comments
            $movie_id = $row['id'];
            $ratingQuery = mysqli_query($conn, "SELECT AVG(rating) AS avg_rating FROM comments WHERE movie_id = $movie_id");
            $ratingRow = mysqli_fetch_assoc($ratingQuery);
            $rating = $ratingRow['avg_rating'] ? round($ratingRow['avg_rating'], 1) : null;
            
            // Hiển thị phim
            echo "<div class='movie-card' data-id='{$row['id']}' data-title='{$row['title']}' data-genre='{$row['genre']}' data-description='{$row['description']}' data-views='{$row['views']}' data-likes='{$row['likes']}' data-type='{$type}'>";
            echo "<img src='{$row['poster']}' alt='{$row['title']}'>";
            echo "<div class='movie-info'>";
            echo "<h3 class='movie-title'>{$row['title']}</h3>";
            echo "<div class='movie-stats'>";
            echo "<span>👁️ {$row['views']}</span>";
            // Hiển thị đánh giá sao nếu có
            if ($rating) {
                echo "<span style='margin-left: 10px;'>⭐ {$rating}</span>";
            }
            
            // Thêm nhãn phim rạp/phim bộ
            if ($type == 'movie') {
                echo "<span class='movie-type movie'>🎬</span>";
            } else {
                echo "<span class='movie-type series'>📺</span>";
            }
            
            echo "</div>";
            echo "</div>";
            echo "</div>";
        }
        ?>
    </div>
    
    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?><?php echo isset($_GET['tab']) ? '&tab='.$_GET['tab'] : ''; ?>">Trước</a>
        <?php endif; ?>
        
        <?php
        $startPage = max(1, $page - 2);
        $endPage = min($totalPages, $page + 2);
        
        for ($i = $startPage; $i <= $endPage; $i++) {
            $activeClass = ($i == $page) ? 'active' : '';
            echo "<a href='?page=$i" . (isset($_GET['tab']) ? '&tab='.$_GET['tab'] : '') . "' class='$activeClass'>$i</a>";
        }
        ?>
        
        <?php if ($page < $totalPages): ?>
            <a href="?page=<?php echo $page + 1; ?><?php echo isset($_GET['tab']) ? '&tab='.$_GET['tab'] : ''; ?>">Tiếp</a>
        <?php endif; ?>
    </div>
    <?php endif; ?> 
</div>
    
<!-- ---------------------------JS------------------------------------------- -->
<script>
    // Xử lý chuyển đổi tab
    document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // Lấy loại tab (newest, movie, series)
                const tabType = this.getAttribute('data-tab');
                
                // Chuyển hướng đến trang với tab mới
                window.location.href = `?tab=${tabType}`;
            });
        });
                
       
    // Hàm lọc phim theo tab 
    function filterMoviesByTab(tabType) {
    // Chuyển hướng đến URL mới với tham số tab
    let currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('tab', tabType);
    
    // Reset về trang 1 khi chuyển tab
    currentUrl.searchParams.set('page', 1);
    
    // Điều hướng đến URL mới
    window.location.href = currentUrl.toString();
}
// -----------------------------------------------------------
        
    // Store all movies for client-side filtering
    let allMovies = []; 
    // Initialize the movies array once the DOM is loaded
    document.addEventListener("DOMContentLoaded", function() {
            allMovies = Array.from(document.querySelectorAll(".movie-card"));
        });
        
    //Tìm kiếm  
    document.getElementById("search").addEventListener("input", function() {
            let searchValue = this.value.toLowerCase();
            let grid = document.getElementById("movie-grid");
            
            // Clear the current grid
            grid.innerHTML = "";
            
            // Filter movies by search value
            let filteredMovies = allMovies.filter(movie => {
                let title = movie.getAttribute("data-title").toLowerCase();
                return title.includes(searchValue);
            });
            
            // Display only the first 20 filtered movies
            filteredMovies.slice(0, 20).forEach(movie => {
                grid.appendChild(movie.cloneNode(true));
            });
        });

    //Lọc
    document.getElementById("filter-genre").addEventListener("change", function() {
        let genreValue = this.value.toLowerCase();
        let grid = document.getElementById("movie-grid");
        
        // Clear the current grid
        grid.innerHTML = "";
        
        // Filter movies by genre
        let filteredMovies = allMovies.filter(movie => {
            let genre = (movie.getAttribute("data-genre") || "").toLowerCase();
            
            // Debug
            console.log("Movie: " + movie.querySelector(".movie-title").innerText + 
                        ", Genre: " + genre + 
                        ", Filter: " + genreValue +
                        ", Match: " + (genreValue === "" || genre.includes(genreValue)));
            
            return genreValue === "" || genre.includes(genreValue);
        });
        
        console.log("Filtered count: " + filteredMovies.length);
        
        // Display only the first 20 filtered movies
        filteredMovies.slice(0, 20).forEach(movie => {
            grid.appendChild(movie.cloneNode(true));
        });
    });

    //Lọc
    document.getElementById("sort-movies").addEventListener("change", function() {
        let sortBy = this.value;
        let grid = document.getElementById("movie-grid");
        
        // Clear the current grid
        grid.innerHTML = "";
        
        // Create a copy of all movies to sort
        let sortedMovies = [...allMovies];
        
        // Sort movies
        sortedMovies.sort((a, b) => {
            let aValue = parseInt(a.getAttribute("data-" + sortBy));
            let bValue = parseInt(b.getAttribute("data-" + sortBy));
            return bValue - aValue;
        });
        
        // Display only the first 20 sorted movies
        sortedMovies.slice(0, 20).forEach(movie => {
            grid.appendChild(movie.cloneNode(true));
        });
    });

// --------------- popup----------------------
    document.addEventListener("DOMContentLoaded", function () {
        const popup = document.getElementById("moviePopup");
        const popupImage = document.getElementById("popupImage");
        const popupTitle = document.getElementById("popupTitle");
        const popupGenre = document.getElementById("popupGenre");
        const popupDescription = document.getElementById("popupDescription");
        const viewDetailBtn = document.getElementById("viewDetailBtn");

        let selectedMovieId = null; // Lưu ID phim hiện tại trong popup

        // Lắng nghe sự kiện click trên toàn bộ document (event delegation)
        document.addEventListener("click", function (event) {
            // Kiểm tra nếu click vào ảnh phim trong .movie-card
            if (event.target.matches(".movie-card img")) {
                event.stopPropagation(); // Ngăn chặn sự kiện click lan ra ngoài

                const card = event.target.closest(".movie-card"); // Tìm thẻ cha .movie-card
                selectedMovieId = card.getAttribute("data-id"); // Lưu ID phim

                const title = card.querySelector(".movie-title").innerText;
                const genre = card.getAttribute("data-genre");
                const description = card.getAttribute("data-description");

                popupImage.src = event.target.src;
                popupTitle.innerText = title;
                popupGenre.innerText = genre || "Chưa có thể loại";
                popupDescription.innerText = description || "Chưa có mô tả";

                popup.style.display = "flex";
            }

            // Kiểm tra nếu click vào nút "Xem ngay"
            if (event.target === viewDetailBtn && selectedMovieId) {
                window.location.href = `movie_detail.php?id=${selectedMovieId}`;
            }

            // Kiểm tra nếu click vào nền ngoài popup để đóng
            if (event.target === popup) {
                popup.style.display = "none";
            }

            // Kiểm tra nếu click vào nút đóng
            if (event.target.classList.contains("close-button")) {
                popup.style.display = "none";
            }
        });
    });

//---------------Tự động chuyển Sliderlet currentIndex = 0;---------------------
    document.addEventListener("DOMContentLoaded", function () {
        let currentIndex = 0;
        const slides = document.querySelectorAll(".featured-slide");

        function changeSlide() {
            slides.forEach(slide => slide.classList.remove("active"));
            slides[currentIndex].classList.add("active");

            currentIndex = (currentIndex + 1) % slides.length;
        }

        // Chạy slide tự động mỗi 3 giây
        setInterval(changeSlide, 3000);

        // Đảm bảo slide đầu tiên hiển thị ngay từ đầu
        changeSlide();
    });

//---------------Menu Qtri --------------------------------
    document.addEventListener('DOMContentLoaded', function() {
    const userAvatar = document.querySelector('.user-avatar');
    const userDropdownMenu = document.getElementById('userDropdownMenu');

        function toggleUserMenu() {
            userDropdownMenu.classList.toggle('active');
            
            // Create or remove overlay
            if (userDropdownMenu.classList.contains('active')) {
                createOverlay();
            } else {
                removeOverlay();
            }
        }

        function createOverlay() {
            const overlay = document.createElement('div');
            overlay.classList.add('overlay');
            overlay.addEventListener('click', toggleUserMenu);
            document.body.appendChild(overlay);
        }

        function removeOverlay() {
            const overlay = document.querySelector('.overlay');
            if (overlay) {
                overlay.remove();
            }
        }

        userAvatar.addEventListener('click', toggleUserMenu);

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            if (!userAvatar.contains(event.target) && 
                !userDropdownMenu.contains(event.target)) {
                userDropdownMenu.classList.remove('active');
                removeOverlay();
            }
        });
    });

</script>
</body>
</html>