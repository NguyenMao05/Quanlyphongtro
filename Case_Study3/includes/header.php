<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_path = $_SERVER['PHP_SELF'] ?? '';
function nav_active($needle, $current_path) {
    return strpos($current_path, $needle) !== false ? 'active' : '';
}
?>

<!DOCTYPE html>
<html lang="vi" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GTPT Tìm phòng trọ</title>
    <?php include __DIR__ . '/head_assets.php'; ?>
</head>
<body class="gtpt-site">
    <header class="site-header">
        <div class="container gtpt-container">
            <div class="site-header-inner">
                <a class="site-logo" href="/Case_Study3/index.php">
                    <span class="site-logo-mark"><i class="fa-solid fa-building"></i></span>
                    <span>GTPT</span>
                </a>

                <button type="button" class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Mở menu">
                    <i class="fa-solid fa-bars"></i>
                </button>

                <nav class="site-nav" id="siteNav">
                    <a href="/Case_Study3/index.php" class="<?php echo nav_active('index.php', $current_path); ?>">
                        <i class="fa-solid fa-house"></i> Trang chủ
                    </a>
                    <a href="/Case_Study3/motel/search.php" class="<?php echo nav_active('search.php', $current_path); ?>">
                        <i class="fa-solid fa-magnifying-glass"></i> Tìm phòng
                    </a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="/Case_Study3/motel/my_motels.php" class="<?php echo nav_active('my_motels.php', $current_path); ?>">
                            <i class="fa-solid fa-key"></i> Phòng của tôi
                        </a>
                        <a href="/Case_Study3/motel/add_motel.php" class="nav-cta <?php echo nav_active('add_motel.php', $current_path); ?>">
                            <i class="fa-solid fa-plus"></i> Đăng tin
                        </a>
                        <a href="/Case_Study3/user/profile.php" class="<?php echo nav_active('profile.php', $current_path); ?>">
                            <i class="fa-solid fa-user"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </a>
                        <?php if (($_SESSION['role'] ?? 0) == 1): ?>
                            <a href="/Case_Study3/admin/dashboard.php" class="<?php echo nav_active('/admin/', $current_path); ?>">
                                <i class="fa-solid fa-gauge-high"></i> Quản trị
                            </a>
                        <?php endif; ?>
                        <a href="/Case_Study3/auth/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Thoát</a>
                    <?php else: ?>
                        <a href="/Case_Study3/auth/login.php" class="<?php echo nav_active('login.php', $current_path); ?>">
                            <i class="fa-solid fa-right-to-bracket"></i> Đăng nhập
                        </a>
                        <a href="/Case_Study3/auth/register.php" class="nav-cta <?php echo nav_active('register.php', $current_path); ?>">
                            <i class="fa-solid fa-user-plus"></i> Đăng ký
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </header>

    <main class="main-content">
