<?php
session_start();

// Data for trending movies
$trendingMovies = [
    [
        'id' => 1,
        'title' => 'Tiệm Ăn Của Quỷ',
        'image' => 'https://occ-0-58-64.1.nflxso.net/dnm/api/v6/mAcAr9TxZIVbINe88xb3Teg5_OA/AAAABVjoe1Mik7tMv2lvXJ7efiK6C1RKC2K87sRK9Iq5sOW4cxn9f4omP6ig1Rk19GlD2Rkk_fT2PiB13w8M2Js_U4piNT3c_f2Ubuo.webp?r=326&quot',
        'genre' => 'Kinh dị'
    ],
    [
        'id' => 2,
        'title' => 'Trò Chơi Con Mực',
        'image' => 'https://occ-0-58-64.1.nflxso.net/dnm/api/v6/mAcAr9TxZIVbINe88xb3Teg5_OA/AAAABW1AAajnF8nuslA78bPW5Kk2n8v3h9zR6BPnnHv6KKMurbDgjDDamnGc9JOxcadngaJOX3vtqqbTRp_YvjG6Wos1Q5JC7_K3gPrr_MjEA9a1-YdoWnqRQHMqCoP_Ddrx4l8W.webp?r=f3c&quot',
        'genre' => 'Hành động'
    ],
    [
        'id' => 3,
        'title' => 'Mai',
        'image' => 'https://occ-0-58-64.1.nflxso.net/dnm/api/v6/mAcAr9TxZIVbINe88xb3Teg5_OA/AAAABZcdeMf4uVu2pi4WeYl_GDZ2yUlPOnE2RICEWzhdXbrzcmdQU0V-ciPNY1diaH5UmfCQ568hAL8C6ZN9_HB9lV-5O-RLztMD_78.webp?r=2c0&quot',
        'genre' => 'Tình cảm'
    ],
    [
        'id' => 4,
        'title' => 'Trung Tâm Chăm Sóc',
        'image' => 'https://occ-0-58-64.1.nflxso.net/dnm/api/v6/mAcAr9TxZIVbINe88xb3Teg5_OA/AAAABXfxlp8bXL5Z1lrkSAqWVBHOU8NU6L0XTI0s7fyTSmG8LBWjTLbWcbnKlbJbWdsK8CErc51huoRD7d1u8i6qD5CAL36nCWx0v4dFmB95HwK_LdNVfAxzRAoWAbzbVuKD9exY.webp?r=ebc&quot',
        'genre' => 'Hài hước'
    ],
    [
        'id' => 5,
        'title' => 'Tài Oai',
        'image' => 'https://occ-0-58-64.1.nflxso.net/dnm/api/v6/mAcAr9TxZIVbINe88xb3Teg5_OA/AAAABbJ3cbuE7whXLrzsJYgih7rwirXH-gJlcpQ-u-3gqLeggcoZFuLHd2tppVZEdwfh6vrGiConGko42gFDYyDrtvho1xMjxxHDbQk.webp?r=43b&quot',
        'genre' => 'Hài hước'
    ],
    [
        'id' => 6,
        'title' => 'Tài Oai',
        'image' => 'https://occ-0-58-64.1.nflxso.net/dnm/api/v6/mAcAr9TxZIVbINe88xb3Teg5_OA/AAAABbbOseP7yP013s7ezKhLvPc1nlNJHZSPPa_j9fUvl1wgvCdKadynhH5RzjjU8oAcSbI0NZNjNY2pFLKXcWMtfNDutY7pCyXSN7o.webp?r=5bf&quot',
        'genre' => 'Hành động'
    ],
    [
        'id' => 7,
        'title' => 'Tài Oai',
        'image' => 'https://occ-0-58-64.1.nflxso.net/dnm/api/v6/mAcAr9TxZIVbINe88xb3Teg5_OA/AAAABeIR-l8fwNNkj-JT7_1D6r42U7GS2zamKydVGwjsISCApb7Qc0TbB3t50UEqPtJqL6Zwahl54TPB4RUHd8-XAuXTvdBsIpGPkgw.webp?r=17e&quot',
        'genre' => 'Tình cảm'
    ],
    [
        'id' => 8,
        'title' => 'Tài Oai',
        'image' => 'https://occ-0-58-64.1.nflxso.net/dnm/api/v6/mAcAr9TxZIVbINe88xb3Teg5_OA/AAAABSQcjx139EcSkvgFJd2I13vZFSrPge1c_cvE6Ae5Cdd3w_SMRGysH6K1G139Pwt3tSVagawrn6-4KcR_h5TX4PLjIk2YQ5PL_p4.webp?r=c9d&quot',
        'genre' => 'Kinh dị'
    ],
    [
        'id' => 9,
        'title' => 'Tài Oai',
        'image' => 'https://occ-0-58-64.1.nflxso.net/dnm/api/v6/mAcAr9TxZIVbINe88xb3Teg5_OA/AAAABR41nmz_owMs9_AsFXlgBimp9rESoz47Nwx269TPPakBMV_DdNtzO7NRDXIdv5MpLX3QhBSisQJ5lGxDEkGjd8Sf6qcY28xW8wk.webp?r=5d6&quot',
        'genre' => 'Hoạt hình'
    ]
];

// Data for features
$features = [
    [
        'title' => 'Thưởng thức trên TV của bạn',
        'description' => 'Xem trên TV thông minh, Playstation, Xbox, Chromecast, Apple TV, đầu phát Blu-ray và nhiều thiết bị khác.',
        'icon' => 'tv-icon.png'
    ],
    [
        'title' => 'Tải xuống nội dung để xem ngoại tuyến',
        'description' => 'Lưu lại những nội dung yêu thích một cách dễ dàng và luôn có thứ để xem.',
        'icon' => 'download-icon.png'
    ],
    [
        'title' => 'Xem ở mọi nơi',
        'description' => 'Phát trực tuyến không giới hạn phim và chương trình truyền hình trên điện thoại, máy tính bảng, máy tính xách tay và TV.',
        'icon' => 'device-icon.png'
    ],
    [
        'title' => 'Tạo hồ sơ cho trẻ em',
        'description' => 'Đưa các em vào những cuộc phiêu lưu với nhân vật được yêu thích trong một không gian riêng. Tính năng này đi kèm miễn phí với tư cách thành viên của bạn.',
        'icon' => 'kids-icon.png'
    ]
];

require_once($_SERVER['DOCUMENT_ROOT'] . '/webFilm/config/config.php');

// Kiểm tra ID có hợp lệ không
$movieId = isset($_GET['id']) ? intval($_GET['id']) : 1;

// Kiểm tra kết nối
if (!$conn) {
    die("Lỗi kết nối cơ sở dữ liệu: " . $conn->connect_error);
}

// Truy vấn phim
$sql = "SELECT * FROM movies WHERE id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Lỗi prepare: " . $conn->error);
}

$stmt->bind_param("i", $movieId);

if (!$stmt->execute()) {
    die("Lỗi execute: " . $stmt->error);
}

$result = $stmt->get_result();
$movie = $result->fetch_assoc();

// Kiểm tra xem phim có tồn tại không
if (!$movie) {
    $title = "Không tìm thấy phim";
    $image = "default.jpg"; // Ảnh mặc định nếu không có phim
    $genre = "Không có thể loại";
    $description = "Phim này không tồn tại hoặc đã bị xóa.";
} else {
    $title = $movie['title'];
    $image = !empty($movie['image']) ? $movie['image'] : "default.jpg";
    $genre = $movie['genre'];
    $description = $movie['description'];
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/pl_index.css">
    <title>FILM</title>
    <style>
        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.8s ease-out, transform 0.8s ease-out;
        }

        .fade-in.visible {
            opacity: 1;
            transform: translateY(0);
        }

    </style>

</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="fade-in">

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
                
                <button class="cta-button">
                    Xem ngay
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M8 5V19L19 12L8 5Z" fill="white"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div class="container">
        <section class="trending-section">
            <h2>Hiện đang thịnh hành</h2>

            <section class="filter-section">
                <label for="genreFilter">Lọc theo thể loại:</label>
                <select id="genreFilter">
                    <option value="all">Tất cả</option>
                    <option value="Hoạt hình">Hoạt hình</option>
                    <option value="Hành động">Hành động</option>
                    <option value="Tình cảm">Tình cảm</option>
                    <option value="Kinh dị">Kinh dị</option>
                    <option value="Hài hước">Hài hước</option>

                </select>
            </section>

            <button class="slider-button prev">❮</button>

            <div class="trending-slider">
            <?php foreach($trendingMovies as $movie): ?>
                <div class="movie-card" data-genre="<?php echo $movie['genre']; ?>">
                    <div class="movie-number"><?php echo $movie['id']; ?></div>
                    <img src="<?php echo $movie['image']; ?>" alt="<?php echo $movie['title']; ?>">
                    <p class="movie-genre"><?php echo $movie['genre']; ?></p>
                </div>
            <?php endforeach; ?>
            </div>

            <button class="slider-button next">❯</button>

        </section>
        </div>

        <div class="fade-in">
        <section class="features-section">
            <h2>Thêm lý do để tham gia</h2>
            <div class="features-grid">
                <?php foreach($features as $feature): ?>
                    <div class="feature-card">
                        <img src="<?php echo $feature['icon']; ?>" alt="<?php echo $feature['title']; ?>">
                        <h3><?php echo $feature['title']; ?></h3>
                        <p><?php echo $feature['description']; ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</div>


<div class="fade-in">
<?php include '../includes/FAQ.php'; ?>
</div>

<?php include '../includes/footer.php'; ?>
<script>

    //fade in
    document.addEventListener("DOMContentLoaded", function () {
    const elements = document.querySelectorAll(".fade-in");

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add("visible");
            } else {
                entry.target.classList.remove("visible"); // Ẩn đi khi cuộn lên
            }
        });
    }, { threshold: 0.5 });

    elements.forEach(el => observer.observe(el));
});



    //roll phim
        document.addEventListener('DOMContentLoaded', function() {
            const slider = document.querySelector('.trending-slider');
            const prevButton = document.querySelector('.prev');
            const nextButton = document.querySelector('.next');
            const cardWidth = 268; // 260px + 8px gap

            prevButton.addEventListener('click', () => {
                slider.scrollLeft -= cardWidth;
            });

            nextButton.addEventListener('click', () => {
                slider.scrollLeft += cardWidth;
            });

            // Hide/show buttons based on scroll position
            slider.addEventListener('scroll', () => {
                prevButton.style.display = slider.scrollLeft <= 0 ? 'none' : 'block';
                nextButton.style.display = 
                    slider.scrollLeft >= slider.scrollWidth - slider.clientWidth 
                    ? 'none' : 'block';
            });

            // Initial button visibility
            prevButton.style.display = 'none';
        });

        document.addEventListener('DOMContentLoaded', function () {
            const genreFilter = document.getElementById('genreFilter');
            const movieCards = document.querySelectorAll('.movie-card');

            genreFilter.addEventListener('change', function () {
                const selectedGenre = this.value; // Không cần toLowerCase()

                movieCards.forEach(card => {
                    const genre = card.getAttribute('data-genre'); // Lấy thể loại của từng phim

                    if (selectedGenre === 'all' || genre === selectedGenre) {
                        card.style.display = 'block'; // Hiển thị phim nếu chọn "Tất cả" hoặc đúng thể loại
                    } else {
                        card.style.display = 'none'; // Ẩn phim không thuộc thể loại đã chọn
                    }
                });
            });
        });

        // popup
        function openPopup(title, image, genre) {
            document.getElementById("popupTitle").innerText = title;
            document.getElementById("popupImage").src = image;
            document.getElementById("popupImage").alt = title;
            document.getElementById("popupGenre").innerText = genre;
            document.getElementById("popupDescription").innerText = "Đây là phần mô tả chi tiết của phim " + title + ". Xem ngay để có trải nghiệm hấp dẫn!";

            document.getElementById("moviePopup").style.display = "flex";
        }

        function closePopup() {
            document.getElementById("moviePopup").style.display = "none";

        }

        document.querySelectorAll('.movie-card img').forEach(img => {
        img.addEventListener('click', function() {
            const movieCard = this.closest('.movie-card');
            const title = movieCard.querySelector('.movie-number').nextSibling.textContent.trim();
            const image = this.src;
            const genre = movieCard.getAttribute('data-genre');

            document.getElementById('popupTitle').textContent = title;
            document.getElementById('popupImage').src = image;
            document.getElementById('popupGenre').textContent = genre;
            document.getElementById('moviePopup').style.display = 'flex';
        });
    });

        function closePopup() {
        document.getElementById('moviePopup').style.display = 'none';
    }

</script>
</body>
</html>