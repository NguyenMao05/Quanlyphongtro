<?php
// Bảng điều khiển admin

include '../config/connect.php';
include '../includes/admin_init.php';

$error = '';
$success = isset($_GET['success']) ? trim($_GET['success']) : '';

try {
    $total_users = (int) $conn->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $total_motels = (int) $conn->query('SELECT COUNT(*) FROM motel')->fetchColumn();
    $pending_motels = (int) $conn->query('SELECT COUNT(*) FROM motel WHERE approve = 0')->fetchColumn();
    $approved_motels = (int) $conn->query('SELECT COUNT(*) FROM motel WHERE approve = 1')->fetchColumn();

    $sql = "SELECT m.*, u.name AS user_name
            FROM motel m
            JOIN users u ON m.user_id = u.id
            WHERE m.approve = 0
            ORDER BY m.created_at DESC
            LIMIT 10";
    $pending_list = $conn->query($sql)->fetchAll();
} catch (PDOException $e) {
    die('Lỗi: ' . $e->getMessage());
}
?>

<?php include '../includes/header.php'; ?>

<div class="dashboard-layout py-4 px-3">
    <?php $admin_page = 'dashboard'; include '../includes/admin_tabs.php'; ?>

    <div class="dashboard-toolbar">
        <div>
            <h1 class="h3 fw-bold mb-1"><i class="fa-solid fa-gauge-high text-primary"></i> Bảng điều khiển</h1>
            <p class="text-muted mb-0 small">Tổng quan hệ thống GTPT</p>
        </div>
        <a href="manage_motels.php" class="btn btn-outline-primary"><i class="fa-solid fa-building"></i> Quản lý phòng</a>
    </div>

    <div class="dashboard-stats">
        <div class="dashboard-stat-box"><strong><?php echo $total_users; ?></strong><span class="small text-muted d-block">Người dùng</span></div>
        <div class="dashboard-stat-box"><strong><?php echo $total_motels; ?></strong><span class="small text-muted d-block">Phòng trọ</span></div>
        <div class="dashboard-stat-box"><strong><?php echo $pending_motels; ?></strong><span class="small text-muted d-block">Chờ duyệt</span></div>
        <div class="dashboard-stat-box"><strong><?php echo $approved_motels; ?></strong><span class="small text-muted d-block">Đã duyệt</span></div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0 fw-bold"><i class="fa-solid fa-hourglass-half"></i> Phòng chờ duyệt (<?php echo count($pending_list); ?>)</h5>
        </div>
        <div class="card-body">
            <?php if (count($pending_list) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tên phòng</th>
                                <th>Chủ</th>
                                <th>Giá</th>
                                <th>Ngày đăng</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_list as $motel): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars(substr($motel['title'], 0, 40)); ?></strong></td>
                                    <td><?php echo htmlspecialchars($motel['user_name']); ?></td>
                                    <td><?php echo number_format($motel['price']); ?> đ</td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($motel['created_at'])); ?></td>
                                    <td>
                                        <a href="approve_motel.php?id=<?php echo (int)$motel['ID']; ?>&action=approve" class="btn btn-sm btn-success" title="Duyệt">
                                            <i class="fa-solid fa-check"></i>
                                        </a>
                                        <a href="approve_motel.php?id=<?php echo (int)$motel['ID']; ?>&action=reject" class="btn btn-sm btn-danger" title="Từ chối">
                                            <i class="fa-solid fa-xmark"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <a href="manage_motels.php" class="btn btn-warning btn-sm mt-3"><i class="fa-solid fa-list"></i> Xem tất cả</a>
            <?php else: ?>
                <div class="alert alert-success text-center mb-0">
                    <i class="fa-solid fa-circle-check"></i> Tất cả bài đăng đã được duyệt!
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
