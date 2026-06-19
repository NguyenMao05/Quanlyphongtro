<?php
// Quản lý người dùng (admin)

include '../config/connect.php';
include '../includes/admin_init.php';

$error = '';
$success = '';

// Sửa người dùng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    $user_id = (int) ($_POST['user_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role = (int) ($_POST['role'] ?? 0);
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if ($user_id <= 0 || $name === '' || $username === '' || $email === '') {
        $error = 'Vui lòng điền đầy đủ thông tin bắt buộc!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không đúng định dạng!';
    } elseif (!in_array($role, [0, 1], true)) {
        $error = 'Vai trò không hợp lệ!';
    } elseif ($password !== '' && strlen($password) < 6) {
        $error = 'Mật khẩu mới phải có ít nhất 6 ký tự!';
    } elseif ($password !== '' && $password !== $confirm_password) {
        $error = 'Mật khẩu xác nhận không khớp!';
    } else {
        try {
            $result = $conn->prepare('SELECT ID FROM users WHERE ID = ?');
            $result->execute([$user_id]);

            if ($result->rowCount() === 0) {
                $error = 'Người dùng không tồn tại!';
            } else {
                $result = $conn->prepare('SELECT ID FROM users WHERE Username = ? AND ID != ?');
                $result->execute([$username, $user_id]);

                if ($result->rowCount() > 0) {
                    $error = 'Username đã tồn tại!';
                } else {
                    $result = $conn->prepare('SELECT ID FROM users WHERE Email = ? AND ID != ?');
                    $result->execute([$email, $user_id]);

                    if ($result->rowCount() > 0) {
                        $error = 'Email đã tồn tại!';
                    } else {
                        if ($user_id === (int) $_SESSION['user_id']) {
                            $role = (int) $_SESSION['role'];
                        }

                        if ($password !== '') {
                            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                            $sql = 'UPDATE users SET Name = ?, Username = ?, Email = ?, Phone = ?, Role = ?, Password = ? WHERE ID = ?';
                            $params = [$name, $username, $email, $phone, $role, $hashed_password, $user_id];
                        } else {
                            $sql = 'UPDATE users SET Name = ?, Username = ?, Email = ?, Phone = ?, Role = ? WHERE ID = ?';
                            $params = [$name, $username, $email, $phone, $role, $user_id];
                        }

                        $conn->prepare($sql)->execute($params);

                        if ($user_id === (int) $_SESSION['user_id']) {
                            $_SESSION['name'] = $name;
                            $_SESSION['username'] = $username;
                        }

                        $success = 'Cập nhật người dùng thành công!';
                    }
                }
            }
        } catch (PDOException $e) {
            $error = 'Lỗi: ' . $e->getMessage();
        }
    }
}

// Xóa người dùng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $user_id = (int)($_POST['user_id'] ?? 0);

    if ($user_id === (int)$_SESSION['user_id']) {
        $error = 'Không thể xóa chính bạn!';
    } else {
        try {
            $conn->prepare('DELETE FROM motel WHERE user_id = ?')->execute([$user_id]);
            $result = $conn->prepare('DELETE FROM users WHERE id = ?');
            $result->execute([$user_id]);

            if ($result->rowCount() > 0) {
                $success = 'Xóa người dùng thành công!';
            } else {
                $error = 'Người dùng không tồn tại!';
            }
        } catch (PDOException $e) {
            $error = 'Lỗi: ' . $e->getMessage();
        }
    }
}

try {
    $users = $conn->query('SELECT * FROM users ORDER BY ID ASC')->fetchAll();
} catch (PDOException $e) {
    die('Lỗi: ' . $e->getMessage());
}
?>

<?php include '../includes/header.php'; ?>

<div class="dashboard-layout py-4 px-3">
    <?php $admin_page = 'users'; include '../includes/admin_tabs.php'; ?>

    <div class="dashboard-toolbar">
        <div>
            <h1 class="h3 fw-bold mb-1"><i class="fa-solid fa-users text-primary"></i> Quản lý người dùng</h1>
            <p class="text-muted mb-0 small">Danh sách tài khoản trên hệ thống</p>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (count($users) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Họ tên</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Số điện thoại</th>
                                <th>Vai trò</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo (int)$user['ID']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($user['Name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($user['Username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['Email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['Phone'] ?? ''); ?></td>
                                    <td>
                                        <?php if ($user['Role'] == 1): ?>
                                            <span class="badge bg-danger">Admin</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Người dùng</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editUserModal<?php echo (int)$user['ID']; ?>">
                                            <i class="fa-solid fa-pen-to-square"></i> Sửa
                                        </button>

                                        <?php if ((int)$user['ID'] !== (int)$_SESSION['user_id']): ?>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn xóa người dùng này? Tất cả bài đăng của họ cũng sẽ bị xóa!');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="user_id" value="<?php echo (int)$user['ID']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">
                                                    <i class="fa-solid fa-trash-can"></i> Xóa
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted small">(Bạn)</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center mb-0">
                    <i class="fa-solid fa-circle-info"></i> Không có người dùng nào
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php foreach ($users as $user): ?>
        <div class="modal fade" id="editUserModal<?php echo (int)$user['ID']; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fa-solid fa-user-pen text-primary"></i> Sửa người dùng
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="user_id" value="<?php echo (int)$user['ID']; ?>">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold" for="name<?php echo (int)$user['ID']; ?>">Họ tên</label>
                                    <input type="text" class="form-control" id="name<?php echo (int)$user['ID']; ?>" name="name" value="<?php echo htmlspecialchars($user['Name']); ?>" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold" for="username<?php echo (int)$user['ID']; ?>">Username</label>
                                    <input type="text" class="form-control" id="username<?php echo (int)$user['ID']; ?>" name="username" value="<?php echo htmlspecialchars($user['Username']); ?>" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold" for="email<?php echo (int)$user['ID']; ?>">Email</label>
                                    <input type="email" class="form-control" id="email<?php echo (int)$user['ID']; ?>" name="email" value="<?php echo htmlspecialchars($user['Email']); ?>" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold" for="phone<?php echo (int)$user['ID']; ?>">Số điện thoại</label>
                                    <input type="tel" class="form-control" id="phone<?php echo (int)$user['ID']; ?>" name="phone" value="<?php echo htmlspecialchars($user['Phone'] ?? ''); ?>">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold" for="role<?php echo (int)$user['ID']; ?>">Vai trò</label>
                                    <select class="form-select" id="role<?php echo (int)$user['ID']; ?>" name="role" <?php echo (int)$user['ID'] === (int)$_SESSION['user_id'] ? 'disabled' : ''; ?>>
                                        <option value="0" <?php echo (int)$user['Role'] === 0 ? 'selected' : ''; ?>>Người dùng</option>
                                        <option value="1" <?php echo (int)$user['Role'] === 1 ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                    <?php if ((int)$user['ID'] === (int)$_SESSION['user_id']): ?>
                                        <input type="hidden" name="role" value="<?php echo (int)$user['Role']; ?>">
                                        <small class="text-muted">Không thể đổi vai trò của chính bạn.</small>
                                    <?php endif; ?>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold" for="password<?php echo (int)$user['ID']; ?>">Mật khẩu mới</label>
                                    <input type="password" class="form-control" id="password<?php echo (int)$user['ID']; ?>" name="password" placeholder="Để trống nếu không đổi">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold" for="confirmPassword<?php echo (int)$user['ID']; ?>">Xác nhận mật khẩu</label>
                                    <input type="password" class="form-control" id="confirmPassword<?php echo (int)$user['ID']; ?>" name="confirm_password" placeholder="Nhập lại mật khẩu mới">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-floppy-disk"></i> Lưu thay đổi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php include '../includes/footer.php'; ?>
