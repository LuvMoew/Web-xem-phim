<?php
// Mảng chứa thông tin các thành viên
$members = [
    [
        'id' => 1,
        'name' => 'Nguyễn Văn ĐƯợc',
        'position' => 'Trưởng Nhóm',
        'description' => 'Xây dựng API & xử lý dữ liệu:
        PHP, MySQL, API thanh toán, xử lý logic.',
       'image' => '../assets/img/mio.jpg'
 // Nếu file PHP đang trong thư mục webfilm

    ],
    [
        'id' => 2,
        'name' => 'Phạm Thị Hồng Yến',
        'position' => 'Nhà Phát Triển',
        'description' => 'Thiết kế giao diện web phim:Thiết kế UI/UX, HTML, CSS, JavaScript, hiệu ứng.',
        'image' => '../assets/img/penut.jpg' // Avatar với tóc nâu và áo đỏ
    ],
    [
        'id' => 3,
        'name' => 'Vũ Trần Thế Anh',
        'position' => 'Nhà Thiết Kế',
        'description' => 'Hiển thị danh sách phim, xem phim, đánh giá, bình luận.',
        'image' => '../assets/img/heo1.jpg' // Avatar với tóc vàng
    ],
    [
        'id' => 4,
        'name' => 'Vũ Thị Quỳnh',
        'position' => 'Quản Lý Dự Án',
        'description' => 'Quản lý người dùng, phân quyền, bảo mật, tối ưu code.',
        'image' => '../assets/img/heo_2.jpg' // Avatar với kính và tóc vàng
    ]
];

// Lấy ID thành viên được chọn từ tham số URL (nếu có)
$selectedMember = isset($_GET['id']) ? (int)$_GET['id'] : 0;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giới Thiệu Thành Viên</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #2c3e50;
            color: #fff;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .logo {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: #000;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-right: 15px;
            color: white;
            font-size: 10px;
            text-align: center;
        }
        
        .group-name {
            font-size: 24px;
            font-weight: bold;
            color: #f1c40f;
        }
        
        .presentation-title {
            text-align: center;
            margin: 50px 0;
        }
        
        .presentation-title h1 {
            font-size: 72px;
            color: #f5e1ba;
            text-shadow: 3px 3px 0 rgba(0,0,0,0.2);
            letter-spacing: 2px;
        }
        
        .team-section {
            text-align: center;
        }
        
        .team-header {
            background-color: #f5e1ba;
            color: #2c3e50;
            display: inline-block;
            padding: 10px 30px;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 40px;
        }
        
        .team-members {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .member {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid white;
            transition: all 0.3s ease;
        }
        
        .avatar img {
            width: 100%; /* Giới hạn ảnh không vượt quá kích thước container */
            height: 100%; /* Giữ tỷ lệ khung hình */
            object-fit: cover;
        }
        
        /* Trang chi tiết thành viên */
        .member-detail {
            display: flex;
            align-items: flex-start;
            justify-content: flex-start;
            margin-top: 40px;
        }
        
        .member-info {
            padding-left: 40px;
            text-align: left;
        }
        
        .member-info h2 {
            font-size: 48px;
            color: #f1c40f;
            margin-bottom: 10px;
        }
        
        .member-info h3 {
            font-size: 24px;
            color: #ffffff;
            margin-bottom: 20px;
            font-weight: normal;
        }
        
        .member-info p {
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        .big-avatar {
            width: 300px;
            height: 300px;
            border-radius: 50%;
            overflow: hidden;
            border: 8px solid white;
        }
        
        .big-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .other-members {
            position: absolute; /* Định vị tuyệt đối */
            top: 50%; /* Canh giữa theo chiều dọc */
            right: 20px; /* Cách mép phải 20px */
            transform: translateY(-50%); /* Căn giữa theo chiều dọc */
            display: flex;
            flex-direction: column; /* Sắp xếp các phần tử theo chiều dọc */
            gap: 10px; /* Tạo khoảng cách giữa các mục */
            align-items: center;

        }
        
        .other-member {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid white;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .other-member:hover {
            transform: scale(1.1);
        }
        
        .other-member img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        /* Nút quay lại */
        .back-button {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #f1c40f;
            color: #2c3e50;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .back-button:hover {
            background-color: #f39c12;
        }

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

<?php include __DIR__ . '/../includes/nav.php'; ?>


    <div class="container">   

        <?php if ($selectedMember === 0): ?>
        <!-- Trang chính với danh sách tất cả thành viên -->
    <div class="fade-in">
        <div class="presentation-title" >
            <h1>TEAM 7</h1>
        </div>
        
        <div class="team-section">
            <div class="team-header">
                TEAM MEMBER
            </div>

            <div class="team-members">
                <?php foreach ($members as $member): ?>
                <a href="?id=<?php echo $member['id']; ?>" class="member">
                    <div class="avatar">
                        <img src="<?php echo $member['image']; ?>" alt="<?php echo $member['name']; ?>">
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div> 
</div>  
        <?php else: ?>
        <!-- Trang chi tiết thành viên -->
        <?php 
        $currentMember = null;
        foreach ($members as $member) {
            if ($member['id'] === $selectedMember) {
                $currentMember = $member;
                break;
            }
        }
        
        if ($currentMember): 
        ?>
        <div class="team-section" style="margin-top: 100px;">
         
        <div class="fade-in">
            <div class="member-detail">
                <div class="big-avatar">
                    <img src="<?php echo $currentMember['image']; ?>" alt="<?php echo $currentMember['name']; ?>">
                </div>
                
                <div class="member-info">
                    <h2>HỌ VÀ TÊN</h2>
                    <h3><?php echo $currentMember['name']; ?></h3>
                    
                    <h2>CHỨC VỤ</h2>
                    <h3><?php echo $currentMember['position']; ?></h3>
                    
                    <p><?php echo $currentMember['description']; ?></p>
                </div>
            </div>
        </div>
            <div class="other-members" >
                <?php foreach ($members as $member): ?>
                <?php if ($member['id'] !== $selectedMember): ?>
                <a href="?id=<?php echo $member['id']; ?>" class="other-member">
                    <img src="<?php echo $member['image']; ?>" alt="<?php echo $member['name']; ?>">
                </a>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
            
            <a href="?" class="back-button">Quay lại trang chính</a>
        </div>

        <?php else: ?>
        <p>Không tìm thấy thông tin thành viên.</p>
        <a href="?" class="back-button">Quay lại trang chính</a>
        <?php endif; ?>
        <?php endif; ?>
 


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Thêm hiệu ứng hover cho avatar
            const avatars = document.querySelectorAll('.avatar');
            avatars.forEach(avatar => {
                avatar.addEventListener('mouseenter', function() {
                    this.style.transform = 'scale(1.1)';
                });
                avatar.addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1)';
                });
            });
        });
        // ---------------------------------------------------
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
    }, { threshold: 1.0 });

    elements.forEach(el => observer.observe(el));
});
    </script>
</body>
</html>