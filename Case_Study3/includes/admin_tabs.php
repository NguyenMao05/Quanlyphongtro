<?php
/** @var string $admin_page dashboard|motels|users|statistics */
$admin_page = $admin_page ?? 'dashboard';
?>
<nav class="admin-tabs mb-4">
    <a href="dashboard.php" class="<?php echo $admin_page === 'dashboard' ? 'active' : ''; ?>">
        <i class="fa-solid fa-gauge-high"></i> Tổng quan
    </a>
    <a href="manage_motels.php" class="<?php echo $admin_page === 'motels' ? 'active' : ''; ?>">
        <i class="fa-solid fa-building"></i> Phòng trọ
    </a>
    <a href="manage_users.php" class="<?php echo $admin_page === 'users' ? 'active' : ''; ?>">
        <i class="fa-solid fa-users"></i> Người dùng
    </a>
    <a href="statistics.php" class="<?php echo $admin_page === 'statistics' ? 'active' : ''; ?>">
        <i class="fa-solid fa-chart-column"></i> Thống kê
    </a>
</nav>
