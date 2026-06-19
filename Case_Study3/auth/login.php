<?php
// Trang đăng nhập

include '../config/connect.php';
require_once __DIR__ . '/../includes/recaptcha_verify.php';

$recaptcha_cfg = require __DIR__ . '/../config/recaptcha.php';
$recaptcha_site_key = $recaptcha_cfg['site_key'] ?? '';
$recaptcha_secret = $recaptcha_cfg['secret_key'] ?? '';
$captcha_min_attempts = (int)($recaptcha_cfg['min_attempts'] ?? 3);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    header('Location: /Case_Study3/index.php');
    exit();
}

$error = '';
$show_recaptcha = false;

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}

$login_attempts = (int) $_SESSION['login_attempts'];
$captcha_required = $login_attempts >= $captcha_min_attempts;
$show_recaptcha = $captcha_required;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $captcha_response = $_POST['g-recaptcha-response'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Vui lòng điền đầy đủ thông tin!';
    } elseif ($captcha_required) {
        $verify = verify_recaptcha_response($captcha_response, $recaptcha_secret);
        if (!$verify['ok']) {
            $error = recaptcha_error_message($verify);
            $show_recaptcha = true;
        }
    }

    if ($error === '') {
        try {
            $sql = 'SELECT * FROM users WHERE username = ?';
            $stmt = $conn->prepare($sql);
            $stmt->execute([$username]);

            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch();

                if (password_verify($password, $user['Password'])) {
                    $_SESSION['user_id'] = $user['ID'];
                    $_SESSION['username'] = $user['Username'];
                    $_SESSION['role'] = $user['Role'];
                    $_SESSION['name'] = $user['Name'];
                    $_SESSION['login_attempts'] = 0;
                    if ((int)$user['Role'] === 1) {
                        header('Location: /Case_Study3/admin/dashboard.php');
                    } else {
                        header('Location: /Case_Study3/index.php');
                    }
                    exit();
                }
                $_SESSION['login_attempts']++;
                $error = 'Tên đăng nhập hoặc mật khẩu sai!';
            } else {
                $_SESSION['login_attempts']++;
                $error = 'Tên đăng nhập hoặc mật khẩu sai!';
            }

            if ((int) $_SESSION['login_attempts'] >= $captcha_min_attempts) {
                $show_recaptcha = true;
            }
        } catch (PDOException $e) {
            $error = 'Lỗi: ' . $e->getMessage();
        }
    }
}

if ((int) $_SESSION['login_attempts'] >= $captcha_min_attempts) {
    $show_recaptcha = true;
}
?>

<!DOCTYPE html>
<html lang="vi" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - GTPT</title>
    <?php include '../includes/head_assets.php'; ?>
</head>
<body>
<div class="auth-split">
    <div class="auth-split-brand">
        <a href="/Case_Study3/index.php" class="text-white text-decoration-none d-inline-flex align-items-center gap-2 mb-4">
            <span class="site-logo-mark"><i class="fa-solid fa-building"></i></span>
            <strong class="fs-4">GTPT</strong>
        </a>
        <h1>Tìm phòng trọ dễ dàng hơn</h1>
        <p class="opacity-75 mb-0">Đăng nhập để quản lý tin đăng, theo dõi lượt xem và liên hệ người thuê nhanh chóng.</p>
        <ul class="mt-4 opacity-75 small">
            <li class="mb-2"><i class="fa-solid fa-check"></i> Tin đăng được kiểm duyệt</li>
            <li class="mb-2"><i class="fa-solid fa-check"></i> Bản đồ vị trí từng phòng</li>
            <li><i class="fa-solid fa-check"></i> Giao diện thân thiện</li>
        </ul>
    </div>
    <div class="auth-split-form">
        <div class="auth-box">
            <h2 class="fw-bold mb-4">Đăng nhập</h2>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($show_recaptcha): ?>
                <div class="alert alert-warning py-2 small">
                    <i class="fa-solid fa-shield-halved"></i>
                    Bạn đã đăng nhập sai nhiều lần. Vui lòng xác nhận CAPTCHA bên dưới.
                </div>
            <?php endif; ?>

            <form method="POST" id="loginForm">
                <div class="mb-3">
                    <label class="form-label">Tên đăng nhập</label>
                    <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Mật khẩu</label>
                    <input type="password" class="form-control" name="password" required>
                </div>
                <?php if ($show_recaptcha && $recaptcha_site_key !== ''): ?>
                    <div id="recaptcha-widget" class="g-recaptcha mb-2"></div>
                    <div id="recaptcha-load-error" class="alert alert-danger py-2 small d-none mb-3" role="alert"></div>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary w-100 py-2">Đăng nhập</button>
            </form>
            <p class="auth-footer-link mt-3">Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a></p>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if ($show_recaptcha && $recaptcha_site_key !== ''): ?>
<script>
(function () {
    var siteKey = <?php echo json_encode($recaptcha_site_key); ?>;

    window.onRecaptchaLoad = function () {
        var el = document.getElementById('recaptcha-widget');
        var errBox = document.getElementById('recaptcha-load-error');
        if (!el || typeof grecaptcha === 'undefined') {
            if (errBox) {
                errBox.textContent = 'Không tải được thư viện Google reCAPTCHA. Kiểm tra kết nối mạng.';
                errBox.classList.remove('d-none');
            }
            return;
        }
        try {
            grecaptcha.render(el, {
                sitekey: siteKey,
                'error-callback': function () {
                    if (errBox) {
                        errBox.textContent = 'CAPTCHA không tải được. Trong Google reCAPTCHA Admin, thêm domain đúng với URL trình duyệt (localhost hoặc 127.0.0.1) và chọn loại v2 checkbox.';
                        errBox.classList.remove('d-none');
                    }
                }
            });
        } catch (e) {
            if (errBox) {
                errBox.textContent = 'Lỗi hiển thị CAPTCHA: ' + e.message;
                errBox.classList.remove('d-none');
            }
        }
    };

    var form = document.getElementById('loginForm');
    if (form) {
        form.addEventListener('submit', function (e) {
            if (!document.getElementById('recaptcha-widget')) return;
            if (typeof grecaptcha === 'undefined') {
                e.preventDefault();
                alert('CAPTCHA chưa sẵn sàng. Vui lòng đợi vài giây rồi thử lại.');
                return;
            }
            if (!grecaptcha.getResponse()) {
                e.preventDefault();
                alert('Vui lòng tick ô "Tôi không phải người máy" trước khi đăng nhập.');
            }
        });
    }
})();
</script>
<script src="https://www.google.com/recaptcha/api.js?onload=onRecaptchaLoad&amp;render=explicit" async defer></script>
<?php endif; ?>
</body>
</html>
