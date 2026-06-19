<?php
// ===== user/profile.php =====
// Trang hồ sơ người dùng

include '../config/connect.php';

// Khởi động session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: /Case_Study3/auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

try {
    // Lấy thông tin người dùng
    $sql = "SELECT * FROM users WHERE id = ?";
    $result = $conn->prepare($sql);
    $result->execute([$user_id]);
    $user = $result->fetch();
    
} catch (PDOException $e) {
    die("Lỗi: " . $e->getMessage());
}

// Xử lý upload ảnh đại diện
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_avatar') {
    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] === UPLOAD_ERR_NO_FILE) {
        $error = 'Vui lòng chọn một tệp ảnh!';
    } else {
        $file = $_FILES['avatar'];
        
        // Kiểm tra lỗi upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error = 'Lỗi khi tải lên tệp!';
        } else {
            // Kiểm tra loại tệp
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $file_type = mime_content_type($file['tmp_name']);
            
            if (!in_array($file_type, $allowed_types)) {
                $error = 'Chỉ hỗ trợ các định dạng: JPG, PNG, GIF, WebP!';
            } else {
                    try {
                        // Tạo tên tệp duy nhất
                        $upload_dir = '../uploads/';
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0755, true);
                        }
                        
                        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                        $new_filename = 'avatar_' . $user_id . '_' . time() . '.' . $file_extension;
                        $upload_path = $upload_dir . $new_filename;
                        
                        // Xóa ảnh cũ nếu có
                        if (!empty($user['Avatar'])) {
                            $old_file = '../' . $user['Avatar'];
                            if (file_exists($old_file)) {
                                unlink($old_file);
                            }
                        }
                        
                        // Di chuyển tệp
                        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                            // Lưu đường dẫn vào database
                            $avatar_path = 'uploads/' . $new_filename;
                            $sql_update = "UPDATE users SET avatar = ? WHERE id = ?";
                            $result_update = $conn->prepare($sql_update);
                            $result_update->execute([$avatar_path, $user_id]);
                            
                            $success = 'Cập nhật ảnh đại diện thành công!';
                            
                            // Lấy lại thông tin
                            $sql = "SELECT * FROM users WHERE id = ?";
                            $result = $conn->prepare($sql);
                            $result->execute([$user_id]);
                            $user = $result->fetch();
                        } else {
                            $error = 'Không thể lưu tệp ảnh!';
                        }
                    } catch (PDOException $e) {
                        $error = 'Lỗi: ' . $e->getMessage();
                    }
                }
            }
        }
    }


// Xử lý cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_info') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    if (empty($name) || empty($email)) {
        $error = 'Vui lòng điền đầy đủ thông tin!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không đúng định dạng!';
    } else {
        try {
            // Kiểm tra email không trùng với người dùng khác
            $sql_check = "SELECT id FROM users WHERE email = ? AND id != ?";
            $result_check = $conn->prepare($sql_check);
            $result_check->execute([$email, $user_id]);
            
            if ($result_check->rowCount() > 0) {
                $error = 'Email đã tồn tại!';
            } else {
                $sql_update = "UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?";
                $result_update = $conn->prepare($sql_update);
                $result_update->execute([$name, $email, $phone, $user_id]);
                
                $success = 'Cập nhật thông tin thành công!';
                
                // Cập nhật session
                $_SESSION['name'] = $name;
                
                // Lấy lại thông tin
                $sql = "SELECT * FROM users WHERE id = ?";
                $result = $conn->prepare($sql);
                $result->execute([$user_id]);
                $user = $result->fetch();
            }
        } catch (PDOException $e) {
            $error = 'Lỗi: ' . $e->getMessage();
        }
    }
}

// Xử lý đổi mật khẩu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $old_password = trim($_POST['old_password'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    
    if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'Vui lòng điền đầy đủ thông tin!';
    } elseif (!password_verify($old_password, $user['Password'])) {
        $error = 'Mật khẩu cũ không đúng!';
    } elseif (strlen($new_password) < 6) {
        $error = 'Mật khẩu mới phải có ít nhất 6 ký tự!';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Mật khẩu xác nhận không khớp!';
    } else {
        try {
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
            $sql_update = "UPDATE users SET password = ? WHERE id = ?";
            $result_update = $conn->prepare($sql_update);
            $result_update->execute([$hashed_password, $user_id]);
            
            $success = 'Đổi mật khẩu thành công!';
        } catch (PDOException $e) {
            $error = 'Lỗi: ' . $e->getMessage();
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="profile-layout">
    <div class="profile-header-card">
        <?php if (!empty($user['Avatar']) && file_exists('../' . $user['Avatar'])): ?>
            <img src="<?php echo('../' . $user['Avatar']); ?>" alt="Avatar">
        <?php else: ?>
            <span class="profile-avatar-placeholder"><i class="fa-solid fa-user"></i></span>
        <?php endif; ?>
        <div>
            <h1 class="h4 fw-bold mb-1"><?php echo htmlspecialchars($user['Name']); ?></h1>
            <p class="text-muted mb-2 small">@<?php echo htmlspecialchars($user['Username']); ?></p>
            <?php if ($user['Role'] == 1): ?>
                <span class="badge bg-danger">Admin</span>
            <?php else: ?>
                <span class="badge bg-success">Người dùng</span>
            <?php endif; ?>
        </div>
    </div>

    <nav class="nav profile-tabs">
        <a class="nav-link active" href="#profile" data-bs-toggle="tab"><i class="fa-solid fa-user"></i> Hồ sơ</a>
        <a class="nav-link" href="#avatar" data-bs-toggle="tab"><i class="fa-solid fa-image"></i> Ảnh đại diện</a>
        <a class="nav-link" href="#password" data-bs-toggle="tab"><i class="fa-solid fa-lock"></i> Đổi mật khẩu</a>
    </nav>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="profile">
            <div class="profile-panel">
                <h2><i class="fa-solid fa-user"></i> Thông tin hồ sơ</h2>
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
                                <input type="hidden" name="action" value="update_info">
                                
                                <div class="mb-3">
                                    <label for="name" class="form-label fw-bold">Họ và tên</label>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        id="name" 
                                        name="name" 
                                        value="<?php echo htmlspecialchars($user['Name']); ?>"
                                        required
                                    >
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label fw-bold">Email</label>
                                    <input 
                                        type="email" 
                                        class="form-control" 
                                        id="email" 
                                        name="email" 
                                        value="<?php echo htmlspecialchars($user['Email']); ?>"
                                        required
                                    >
                                </div>
                                
                                <div class="mb-3">
                                    <label for="phone" class="form-label fw-bold">Số điện thoại</label>
                                    <input 
                                        type="tel" 
                                        class="form-control" 
                                        id="phone" 
                                        name="phone" 
                                        value="<?php echo htmlspecialchars($user['Phone'] ?? ''); ?>"
                                    >
                                </div>
                                
                                <div class="mb-3">
                                    <label for="username" class="form-label fw-bold">Tên đăng nhập</label>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        id="username" 
                                        name="username" 
                                        value="<?php echo htmlspecialchars($user['Username']); ?>"
                                        disabled
                                    >
                                    <small class="text-muted">Tên đăng nhập không thể thay đổi</small>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa-solid fa-floppy-disk"></i> Lưu thông tin
                                </button>
                            </form>
            </div>
        </div>

        <div class="tab-pane fade" id="avatar">
            <div class="profile-panel">
                <h2><i class="fa-solid fa-image"></i> Ảnh đại diện</h2>
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
                            
                            <div class="text-center mb-4">
                                <div>
                                    <?php if (!empty($user['Avatar']) && file_exists('../' . $user['Avatar'])): ?>
                                        <img 
                                            src="<?php echo('../' . $user['Avatar']); ?>" 
                                            alt="Avatar">
                                    <?php else: ?>
                                        <div class="text-muted">
                                            <i class="fa-solid fa-image"></i>
                                            <p>Không có ảnh</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="upload_avatar">
                                
                                <div class="mb-3">
                                    <label for="avatar" class="form-label fw-bold">Chọn ảnh đại diện</label>
                                    <input 
                                        type="file" 
                                        class="form-control" 
                                        id="avatar" 
                                        name="avatar" 
                                        accept="image/*"
                                        required
                                    >
                                    <small class="text-muted d-block mt-2">
                                        <i class="fa-solid fa-circle-info"></i> Định dạng hỗ trợ: JPG, PNG, GIF, WebP. Kích thước tối đa: 5MB
                                    </small>
                                </div>
                                
                                <button type="submit" class="btn btn-info">
                                    <i class="fa-solid fa-cloud-arrow-up"></i> Tải lên ảnh đại diện
                                </button>
                            </form>
            </div>
        </div>

        <div class="tab-pane fade" id="password">
            <div class="profile-panel">
                <h2><i class="fa-solid fa-lock"></i> Đổi mật khẩu</h2>
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
                                <input type="hidden" name="action" value="change_password">
                                
                                <div class="mb-3">
                                    <label for="old_password" class="form-label fw-bold">Mật khẩu cũ</label>
                                    <input 
                                        type="password" 
                                        class="form-control" 
                                        id="old_password" 
                                        name="old_password" 
                                        placeholder="Nhập mật khẩu cũ"
                                        required
                                    >
                                </div>
                                
                                <div class="mb-3">
                                    <label for="new_password" class="form-label fw-bold">Mật khẩu mới</label>
                                    <input 
                                        type="password" 
                                        class="form-control" 
                                        id="new_password" 
                                        name="new_password" 
                                        placeholder="Nhập mật khẩu mới (tối thiểu 6 ký tự)"
                                        required
                                    >
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label fw-bold">Xác nhận mật khẩu mới</label>
                                    <input 
                                        type="password" 
                                        class="form-control" 
                                        id="confirm_password" 
                                        name="confirm_password" 
                                        placeholder="Nhập lại mật khẩu mới"
                                        required
                                    >
                                </div>
                                
                                <button type="submit" class="btn btn-warning">
                                    <i class="fa-solid fa-rotate"></i> Đổi mật khẩu
                                </button>
                            </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
