<?php
// Trang đăng ký 
include '../config/connect.php';

// Khởi động session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Nếu đã đăng nhập thì chuyển hướng về trang chủ
if (isset($_SESSION['user_id'])) {
    header("Location: /Case_Study3/index.php");
    exit();
}

$error = '';
$success = '';

// Xử lý form đăng ký
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    // Kiểm tra dữ liệu rỗng
    if (empty($name) || empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Vui lòng điền đầy đủ thông tin!';
    }
    // Kiểm tra email đúng định dạng
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không đúng định dạng!';
    }
    // Kiểm tra password >= 6 ký tự
    elseif (strlen($password) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự!';
    }
    // Kiểm tra xác nhận mật khẩu
    elseif ($password !== $confirm_password) {
        $error = 'Mật khẩu xác nhận không khớp!';
    }
    else {
        // Kiểm tra username không trùng
        try {
            $sql = "SELECT id FROM users WHERE username = ?";
            $result = $conn->prepare($sql);
            $result->execute([$username]);
            
            if ($result->rowCount() > 0) {
                $error = 'Username đã tồn tại!';
            }
            else {
                // Kiểm tra email không trùng
                $sql = "SELECT id FROM users WHERE email = ?";
                $result = $conn->prepare($sql);
                $result->execute([$email]);
                
                if ($result->rowCount() > 0) {
                    $error = 'Email đã tồn tại!';
                }
                else {
                    // Mã hóa password
                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                    
                    // Thêm user mới vào database
                    $sql = "INSERT INTO users (name, username, email, password, phone, role) VALUES (?, ?, ?, ?, ?, 0)";
                    $result = $conn->prepare($sql);
                    $result->execute([$name, $username, $email, $hashed_password, $phone]);
                    
                    $success = 'Đăng ký thành công! Vui lòng <a href="login.php" class="alert-link">đăng nhập</a> để tiếp tục.';
                }
            }
        } catch (PDOException $e) {
            $error = 'Lỗi: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký - GTPT</title>
    <?php include '../includes/head_assets.php'; ?>
</head>
<body>
<div class="auth-split">
    <div class="auth-split-brand">
        <a href="/Case_Study3/index.php" class="text-white text-decoration-none d-inline-flex align-items-center gap-2 mb-4">
            <span class="site-logo-mark"><i class="fa-solid fa-building"></i></span>
            <strong class="fs-4">GTPT</strong>
        </a>
        <h1>Tham gia cộng đồng GTPT</h1>
        <p class="opacity-75 mb-0">Tạo tài khoản miễn phí để đăng tin, quản lý phòng trọ và tiếp cận người thuê.</p>
    </div>
    <div class="auth-split-form">
    <div class="auth-box auth-box-wide">
        <h2 class="fw-bold mb-4">Đăng ký tài khoản</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-circle-exclamation"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-circle-check"></i> <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-3">
                <label for="name" class="form-label">Họ và tên</label>
                <input 
                    type="text" 
                    class="form-control" 
                    id="name" 
                    name="name" 
                    placeholder="Nhập họ và tên"
                    value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                    required
                >
            </div>
            
            <div class="mb-3">
                <label for="username" class="form-label">Tên đăng nhập</label>
                <input 
                    type="text" 
                    class="form-control" 
                    id="username" 
                    name="username" 
                    placeholder="Nhập tên đăng nhập"
                    value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                    required
                >
            </div>
            
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input 
                    type="email" 
                    class="form-control" 
                    id="email" 
                    name="email" 
                    placeholder="Nhập email"
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                    required
                >
            </div>
            
            <div class="mb-3">
                <label for="phone" class="form-label">Số điện thoại</label>
                <input 
                    type="tel" 
                    class="form-control" 
                    id="phone" 
                    name="phone" 
                    placeholder="Nhập số điện thoại"
                    value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                >
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">Mật khẩu</label>
                <input 
                    type="password" 
                    class="form-control" 
                    id="password" 
                    name="password" 
                    placeholder="Nhập mật khẩu (tối thiểu 6 ký tự)"
                    required
                >
            </div>
            
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Xác nhận mật khẩu</label>
                <input 
                    type="password" 
                    class="form-control" 
                    id="confirm_password" 
                    name="confirm_password" 
                    placeholder="Nhập lại mật khẩu"
                    required
                >
            </div>
            
            <button type="submit" class="btn btn-primary w-100 py-2">Đăng ký</button>
        </form>
        
        <p class="auth-footer-link mt-3">Đã có tài khoản? <a href="login.php">Đăng nhập tại đây</a></p>
    </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
