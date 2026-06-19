<?php
/** @var string $admin_page dashboard|motels|users|statistics */
$admin_page = $admin_page ?? 'dashboard';
?>
<div class="col-lg-3 mb-4">
    <div class="list-group admin-sidebar sticky-lg-top">
        <a href="dashboard.php" class="list-group-item list-group-item-action <?php echo $admin_page === 'dashboard' ? 'active' : ''; ?>">
            <i class="fa-solid fa-gauge-high"></i> Dashboard
        </a>
        <a href="manage_motels.php" class="list-group-item list-group-item-action <?php echo $admin_page === 'motels' ? 'active' : ''; ?>">
            <i class="fa-solid fa-building"></i> Quản lý phòng trọ
        </a>
        <a href="manage_users.php" class="list-group-item list-group-item-action <?php echo $admin_page === 'users' ? 'active' : ''; ?>">
            <i class="fa-solid fa-users"></i> Quản lý người dùng
        </a>
        <a href="statistics.php" class="list-group-item list-group-item-action <?php echo $admin_page === 'statistics' ? 'active' : ''; ?>">
            <i class="fa-solid fa-chart-column"></i> Thống kê
        </a>
    </div>
</div>
