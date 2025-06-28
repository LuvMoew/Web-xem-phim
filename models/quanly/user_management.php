<?php
include __DIR__ . '/../../config/config.php';

session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../pages/main.php");
    exit();
}

// Check if user is admin or regular user
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$currentUserId = $_SESSION['user_id'];

// Admin actions processing
if ($isAdmin) {
    // Delete User
    if (isset($_GET['delete_user']) && $_GET['delete_user']) {
        $deleteId = intval($_GET['delete_user']);
        $deleteQuery = "DELETE FROM users WHERE id = ?";
        $stmt = $conn->prepare($deleteQuery);
        $stmt->bind_param("i", $deleteId);
        $stmt->execute();
        header("Location: user_management.php?action=deleted");
        exit();
    }

    // Change User Role
    if (isset($_POST['change_role'])) {
        $userId = intval($_POST['user_id']);
        $newRole = $_POST['new_role'];
        $updateQuery = "UPDATE users SET role = ? WHERE id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("si", $newRole, $userId);
        $stmt->execute();
        header("Location: user_management.php?action=role_updated");
        exit();
    }
}

// User Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $updateFields = [];
    $paramTypes = '';
    $paramValues = [];

    // Update username
    if (!empty($_POST['username'])) {
        $updateFields[] = "username = ?";
        $paramTypes .= 's';
        $paramValues[] = $_POST['username'];
    }

    // Update first name
    if (!empty($_POST['first_name'])) {
        $updateFields[] = "first_name = ?";
        $paramTypes .= 's';
        $paramValues[] = $_POST['first_name'];
    }

    // Update last name
    if (!empty($_POST['last_name'])) {
        $updateFields[] = "last_name = ?";
        $paramTypes .= 's';
        $paramValues[] = $_POST['last_name'];
    }

    // Update avatar
    if (!empty($_FILES['avatar']['name'])) {
        $avatarDir = '../uploads/avatars/';
        if (!file_exists($avatarDir)) {
            mkdir($avatarDir, 0777, true);
        }
        
        $avatarName = uniqid() . '_' . basename($_FILES['avatar']['name']);
        $avatarPath = $avatarDir . $avatarName;
        
        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $avatarPath)) {
            $updateFields[] = "avatar = ?";
            $paramTypes .= 's';
            $paramValues[] = $avatarPath;
        }
    }

    // Update password
    if (!empty($_POST['new_password'])) {
        $hashedPassword = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $updateFields[] = "password = ?";
        $paramTypes .= 's';
        $paramValues[] = $hashedPassword;
    }

    // Construct and execute update query
    if (!empty($updateFields)) {
        $paramTypes .= 'i';
        $paramValues[] = $currentUserId;
        
        $updateQuery = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param($paramTypes, ...$paramValues);
        $stmt->execute();
        
        header("Location: user_management.php?action=profile_updated");
        exit();
    }
}

// Fetch user data
$userData = null; 
$users = [];

if ($isAdmin) {
    // Fetch all users for admin
    $userQuery = "SELECT id, username, email, first_name, last_name, role, registration_date FROM users ORDER BY registration_date DESC";
    $result = $conn->query($userQuery);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $users[] = $row; 
        }
    } else {
        die("Query error: " . $conn->error);
    }
} else {
    // Fetch current user details
    $userQuery = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($userQuery);
    
    if ($stmt) {
        $stmt->bind_param("i", $currentUserId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result) {
            $userData = $result->fetch_assoc();
            
            if (!$userData) {
                die("User information not found.");
            }
        } else {
            die("Query error: " . $stmt->error);
        }
        
        $stmt->close();
    } else {
        die("Query preparation error: " . $conn->error);
    }
}

// Fetch subscription information
$subscriptions = [];
$query = "SELECT package_name, transaction_id, amount, payment_method, payment_date, expiry_date, status 
          FROM subscriptions 
          WHERE user_id = ? 
          ORDER BY payment_date DESC";

$stmt = $conn->prepare($query);

if (!$stmt) {
    die("Query error: " . $conn->error);
}

$stmt->bind_param("i", $currentUserId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($subscription = $result->fetch_assoc()) {
        $subscriptions[] = $subscription;
    } 
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isAdmin ? 'Quản Lý Người Dùng' : 'Hồ Sơ Cá Nhân'; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/webfilm/assets/css/user.css">
</head>
<body>

<?php include __DIR__ . '/../../includes/nav.php'; ?>

    <div class="container">
        <header>
            <h1><?php echo $isAdmin ? 'Quản Lý Người Dùng' : 'Hồ Sơ Người Dùng'; ?></h1>
        </header>
        
        <?php if (isset($_GET['action'])): ?>
            <div class="alert <?php 
                switch($_GET['action']) {
                    case 'deleted': 
                    case 'role_updated': 
                    case 'profile_updated': 
                    case 'comment_added': 
                    case 'comment_deleted': 
                        echo 'alert-success'; 
                        break;
                    default: 
                        echo 'alert-warning';
                } 
            ?>">
                <?php 
                    switch($_GET['action']) {
                        case 'deleted': echo 'Đã xóa người dùng thành công!'; break;
                        case 'role_updated': echo 'Vai trò người dùng đã được cập nhật!'; break;
                        case 'profile_updated': echo 'Hồ sơ của bạn đã được cập nhật!'; break;
                        case 'comment_added': echo 'Đã thêm bình luận thành công!'; break;
                        case 'comment_deleted': echo 'Đã xóa bình luận thành công!'; break;
                    }
                ?>
                <span class="alert-close">&times;</span>
            </div>
        <?php endif; ?>
        
<!---------------- Panel quản lý ------------------- -->
        <?php if ($isAdmin): ?>
            <!-- Admin Panel -->
            
            <!-- Users Tab -->
            <div class="tab-content active" id="users">
                <div class="card">

                    <div class="card-body">
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Tên Đăng Nhập</th>
                                        <th>Họ Và Tên</th>
                                        <th>Email</th>
                                        <th>Vai Trò</th>
                                        <th>Ngày Đăng Ký</th>
                                        <th>Hành Động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?php echo $user['id']; ?></td>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td>
                                                <?php 
                                                    $fullName = trim(htmlspecialchars($user['first_name'] ?? '') . ' ' . htmlspecialchars($user['last_name'] ?? ''));
                                                    echo !empty($fullName) ? $fullName : '<em>Chưa cập nhật</em>'; 
                                                ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td>
                                                <form method="POST">
                                                    <select name="new_role" class="role-select" onchange="this.form.submit()">
                                                        <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>Người Dùng</option>
                                                        <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Quản Trị</option>
                                                    </select>
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <input type="hidden" name="change_role" value="1">
                                                </form>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($user['registration_date'])); ?></td>
                                            <td class="action-icons">
                                                <a href="?delete_user=<?php echo $user['id']; ?>" onclick="return confirm('Bạn có chắc muốn xóa người dùng này?')">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
<!--------------- User Profile -------------------------->
        <?php else: ?>
            <!-- <div class="tabs">
                <div class="tab active" data-tab="profile">Hồ Sơ</div>
                <div class="tab" data-tab="comments">Bình Luận Của Tôi</div>
            </div>
             -->
            <!-- Profile Tab -->
            <div class="tab-content active" id="profile">
                <div class="user-card">
                    <div class="user-header">
                    <?php
                        $avatarSrc = !empty($userData['avatar']) ? $userData['avatar'] : 'default-avatar.png';
                    ?>
                        <img class="user-avatar" src="<?php echo htmlspecialchars($avatarSrc); ?>" alt="Avatar">

                        <h2><?php 
                        // Use first_name and last_name or username if they're empty
                        $fullName = trim(htmlspecialchars($userData['first_name'] ?? '') . ' ' . htmlspecialchars($userData['last_name'] ?? ''));
                        echo !empty($fullName) ? $fullName : htmlspecialchars($userData['username']); 
                        ?></h2>
                        <!-- <div>
                            <span class="badge <?php echo $userData['role'] === 'admin' ? 'badge-admin' : 'badge-user'; ?>">
                                <?php echo $userData['role'] === 'admin' ? 'Quản Trị Viên' : 'Người Dùng'; ?>
                            </span>
                        </div> -->
                    </div>
                    
                    <div class="user-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="avatar">Ảnh Đại Diện</label>
                                <input type="file" name="avatar" id="avatar" class="form-control" accept="image/*">
                            </div>
                            
                            <div class="form-group">
                                <label for="username">Tên Đăng Nhập</label>
                                <input type="text" name="username" id="username" class="form-control" value="<?php echo htmlspecialchars($userData['username']); ?>">
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="first_name">Họ</label>
                                    <input type="text" name="first_name" id="first_name" class="form-control" value="<?php echo htmlspecialchars($userData['first_name'] ?? ''); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="last_name">Tên</label>
                                    <input type="text" name="last_name" id="last_name" class="form-control" value="<?php echo htmlspecialchars($userData['last_name'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" class="form-control" value="<?php echo htmlspecialchars($userData['email']); ?>" readonly>
                            </div>
                            
                            <div class="form-group">
                                <label for="new_password">Mật Khẩu Mới (Để trống nếu không thay đổi)</label>
                                <input type="password" name="new_password" id="new_password" class="form-control">
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" name="update_profile" value="1" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Cập Nhật Hồ Sơ
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-ticket-alt"></i> Thông Tin Gói Phim
                    </div>
                    <div class="card-body">
                        
                            <?php
                        if (count($subscriptions) > 0):
                            $package = $subscriptions[0]; // Get the most recent subscription
                        ?>
                        <div class="package-details">
                            <h3><?php echo htmlspecialchars($package['package_name']); ?></h3>
                            <p><i class="fas fa-calendar-plus"></i> Ngày Đăng Ký: 
                                <?php echo isset($package['payment_date']) ? date('d/m/Y', strtotime($package['payment_date'])) : 'Không xác định'; ?>
                            </p>
                            <p><i class="fas fa-calendar-times"></i> Ngày Hết Hạn: 
                                <?php echo isset($package['expiry_date']) ? date('d/m/Y', strtotime($package['expiry_date'])) : 'Không xác định'; ?>
                            </p>

                            <?php 
                                if (!empty($package['expiry_date'])) {
                                    $now = new DateTime();
                                    $endDate = new DateTime($package['expiry_date']);
                                    $interval = $now->diff($endDate);
                                    $daysLeft = $interval->format('%R%a');

                                    if ($daysLeft > 0) {
                                        echo '<p><i class="fas fa-clock"></i> Còn ' . $daysLeft . ' ngày sử dụng</p>';
                                    } else {
                                        echo '<p class="text-danger"><i class="fas fa-exclamation-circle"></i> Gói đã hết hạn</p>';
                                    }
                                } else {
                                    echo '<p class="text-danger"><i class="fas fa-exclamation-circle"></i> Không có ngày hết hạn</p>';
                                }
                            ?>
                        </div>
                        <?php else: ?>
                            <p>Bạn chưa đăng ký gói phim nào.</p>
                            <a href="../pages/packages.php" class="btn btn-primary">
                                <i class="fas fa-shopping-cart"></i> Đăng Ký Ngay
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Close alert message
            const alertClose = document.querySelector('.alert-close');
            if (alertClose) {
                alertClose.addEventListener('click', function() {
                    this.parentElement.style.display = 'none';
                });
            }
            
            // Automatically set hash to active tab when loaded
            const hash = window.location.hash.substring(1);
            if (hash) {
                const tab = document.querySelector(`.tab[data-tab="${hash}"]`);
                if (tab) {
                    tab.click();
                }
            }

            // Handle tab switching
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');
                    
                    // Remove active class from all tabs and content
                    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                    
                    // Add active class to current tab and content
                    this.classList.add('active');
                    document.getElementById(tabId).classList.add('active');
                    
                    // Update URL hash
                    window.location.hash = tabId;
                });
            });
            
            // Preview uploaded avatar
            const avatarInput = document.getElementById('avatar');
            if (avatarInput) {
                avatarInput.addEventListener('change', function() {
                    if (this.files && this.files[0]) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            document.querySelector('.user-avatar').src = e.target.result;
                        }
                        reader.readAsDataURL(this.files[0]);
                    }
                });
            }
        });
    </script>
</body>
</html>