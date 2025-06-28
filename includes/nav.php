<?php
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>nav</title>

    <style>
    .navbar {
        position: fixed;
        top: 12px;
        left: 12px;
        right: 12px;
        padding: 12px 20px;
        display: flex;
        justify-content: space-between; /* Căn hai đầu */
        align-items: center;
        z-index: 1000;
        background: rgba(255, 255, 255, 0.08);
        backdrop-filter: blur(10px);
        border-radius: 40px;
    }

    /* Nhóm Menu + Language về bên trái */
    .menu-container {
        display: flex;
        align-items: center;
        gap: 15px; /* Tạo khoảng cách giữa Menu và Language */
    }

    .menu {
        display: flex;
        align-items: center;
        gap: 3px;
        background: rgba(255, 255, 255, 0.9);
        border-radius: 20px;
        cursor: pointer;
        border: none;
        font-size: 14px;
        color: #333;
    }

    .menu .icon {
        font-size: 18px;
        padding: 5px;
        padding-right: 11px;
        padding-left: 11px;
        border-radius: 80px;
        background-color: rgb(70, 132, 156);
    }

    .menu .text {
        font-weight: 500;
        padding: 10px;
        text-decoration: none;
    }

    a  {
        text-decoration: none;
        color: white; /* Đổi màu link thành đen */

    }

    /* Language selector */
    .language-selector {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .language-select {
        background-color: rgba(1, 1, 1, 0.654);
        padding: 3px;
        font-size: 14px;
        border: 1px solid rgba(255, 255, 255, 0.622);
        color: white;
        border-radius: 7px;
    }

    /* Mục Home, About, Search nằm giữa */
    .nav-links {
        display: flex;
        gap: 20px;
        margin-left: auto; /* Đẩy nút login về phải */
    }

    .nav-links a {
        text-decoration: none;
        color: white;
        font-size: 16px;
        font-weight: bold;
    }

    /* Nút login về bên phải */
    .auth-buttons {
        margin-left: auto; /* Đẩy nút login về phải */
    }

    .btn-login {
        background: rgb(48, 80, 83);
        color: white;
        padding: 10px 20px;
        font-size: 14px;
        font-weight: bold;
        border: none;
        border-radius: 20px;
        cursor: pointer;
    }

    .btn-login:hover {
        color: rgb(48, 80, 83);
        font-weight: bold;
        background: rgb(145, 243, 252);

    }

    .nav-menu {
            display: none;
            position: absolute;
            top: 50px;
            left: 0;
            background: white;
            border: 1px solid #ccc;
            box-shadow: 2px 2px 5px rgba(0,0,0,0.2);
            padding: 10px;
            width: 200px;
            border-radius: 10px;
        }

        .nav-menu a {
            display: block;
            padding: 10px;
            text-decoration: none;
            color: black;
        }

        .nav-menu a:hover {
            background:rgb(155, 185, 206);
        }

        .icon{
            font-size: 24px;
            cursor: pointer;
            user-select: none;
        }
    </style>

</head>
<body>
    
<nav class="navbar">
    <!-- Bên trái: Menu & Language -->
    <div class="menu-container">
        <button class="menu">
            <a href="/webfilm/pages/index.php">
                <span class="text" style="color: black; ">HOME</span>
            </a>
            <span class="icon" onclick="toggleNav()">☰</span>

        </button>
        <!-- <div class="language-selector"> 
             <select class="language-select" name="lang" onchange="changeLanguage(this.value)">
                <option>🌐 Tiếng Việt</option>
                <option>🌐 English</option>
            </select>
        </div> -->
        
    </div>

    <div class="nav-menu" id="navMenu">
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
       
       <div class="dropdown-section">
           <a href="/webfilm/models/quanly/user_management.php" class="dropdown-item">
               <span class="dropdown-icon">👥</span>
               Quản Lý User
           </a>
       </div>

       <div class="dropdown-section">
           <a href="/webfilm/models/quanly/ql_cmt.php" class="dropdown-item">
               <span class="dropdown-icon">⭐</span>
               Quản lí tương tác
           </a>
       </div>

       <div class="dropdown-section">
           <a href="/webfilm/models/thongke/get_payment_stats.php" class="dropdown-item">
               <span class="dropdown-icon">⚙️</span>
               Thống kê hóa đơn
           </a>
       </div>


       <?php else: ?>
     

       <div class="dropdown-section"> 
           <a href="/webfilm/models/quanly/user_management.php" class="dropdown-item">
               <span class="dropdown-icon">👤</span>
               Hồ Sơ Của Tôi
           </a>
           <a href="/webfilm/pages/kho.php" class="dropdown-item">
               <span class="dropdown-icon">🎬</span>
               Tủ Phim
           </a>
       </div>

       <?php endif; ?>

       <div class="dropdown-section logout">
           <a href="/webfilm/public/login.php" class="dropdown-item logout-item">
               <span class="dropdown-icon">🚪</span>
               Đăng Xuất
           </a>
       </div>
    </div> 
</div>
    </div>

    <!-- Giữa: Home, About, Search -->
    <div class="nav-links">
        <a href="../pages/index.php">Home</a>
        <a href="../pages/contact.php">About</a>
        <a href="#"></a>
    </div>

    <script>
            function toggleNav() {
        var menu = document.getElementById("navMenu");
        menu.style.display = (menu.style.display === "block") ? "none" : "block";
    }

    // Bấm ra ngoài để đóng menu
    document.addEventListener("click", function(event) {
        var menu = document.getElementById("navMenu");
        var menuButton = document.querySelector(".icon");

        if (!menu.contains(event.target) && !menuButton.contains(event.target)) {
            menu.style.display = "none";
        }
    });
    </script>

</nav>
</body>