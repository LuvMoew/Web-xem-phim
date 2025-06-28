
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>header</title>
    <link rel="stylesheet" href="../assets/css/header.css">

    <style>
        /* Hiệu ứng phóng to & mờ dần khi chuyển trang */
        .scale-up {
            transform: scale(1.2);
            opacity: 0;
        }

    </style>

</head>
<body>
    
<?php include '../includes/nav.php'; ?>

    <div class="social-links">
        <a href="#">Instagram</a>
        <a href="#">Twitter</a>
        <a href="#">Facebook</a>
    </div>

    <section class="parallax">
        <div class="parallax-background"></div>
        <div class="parallax-layer"></div>
        <div class="parallax-fixed"></div>
    <div class="hero">
        <div class="hero-content">
            <h1 class="hero-title">KHÁM PHÁ</h1>
            <h2 class="hero-subtitle">CÁC CÂU CHUYỆN</h2>
            <h3 class="hero-subtitle">ĐĂNG NHẬP NGAY</h3>
            <button class="explore-btn" onclick="scaleUpAndRedirect('../public/login.php')">Explore</button>
        </div>
    </div>
</section>

    <?php
    // Xử lý logic PHP ở đây nếu cần
    ?>

<script>
    function scaleUpAndRedirect(url) {
        document.body.classList.add("scale-up"); // Thêm hiệu ứng phóng to và mờ dần
        setTimeout(() => {
            window.location.href = url;
        }, 500); // Chờ hiệu ứng hoàn tất rồi mới chuyển trang
    }

    document.addEventListener("scroll", function() {
    let scrollTop = window.scrollY;
    document.querySelector(".parallax-layer").style.transform = `translateY(${scrollTop * 0.1}px)`;
    document.querySelector(".parallax-fixed").style.transform = `translateY(${scrollTop * 0.6}px)`;
    document.querySelector(".hero-content").style.transform = `translateY(${scrollTop * 0.2}px)`;
});

</script>
</body>
</html>