<?php
// Thống kê và báo cáo (admin)

include '../config/connect.php';
include '../includes/admin_init.php';

$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

if ($month < 1 || $month > 12) {
    $month = (int)date('m');
}
if ($year < 2000 || $year > (int)date('Y') + 1) {
    $year = (int)date('Y');
}

try {
    $sql = "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN approve = 1 THEN 1 ELSE 0 END) AS approved,
                SUM(CASE WHEN approve = 0 THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN approve = -1 THEN 1 ELSE 0 END) AS rejected,
                SUM(price) AS total_price,
                AVG(price) AS avg_price,
                MIN(price) AS min_price,
                MAX(price) AS max_price
            FROM motel
            WHERE MONTH(created_at) = ? AND YEAR(created_at) = ?";
    $result = $conn->prepare($sql);
    $result->execute([$month, $year]);
    $monthly_stats = $result->fetch();

    $top_motels = $conn->query(
        "SELECT m.*, u.name AS user_name
         FROM motel m
         JOIN users u ON m.user_id = u.id
         WHERE m.approve = 1
         ORDER BY m.count_view DESC
         LIMIT 5"
    )->fetchAll();

    $total_users = (int) $conn->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $total_revenue = (float) $conn->query('SELECT COALESCE(SUM(price), 0) FROM motel WHERE approve = 1')->fetchColumn();
} catch (PDOException $e) {
    die('Lỗi: ' . $e->getMessage());
}
?>

<?php include '../includes/header.php'; ?>

<div class="dashboard-layout py-4 px-3">
    <?php $admin_page = 'statistics'; include '../includes/admin_tabs.php'; ?>

    <div class="dashboard-toolbar">
        <div>
            <h1 class="h3 fw-bold mb-1"><i class="fa-solid fa-chart-column text-primary"></i> Thống kê và báo cáo</h1>
            <p class="text-muted mb-0 small">Số liệu theo tháng và top phòng xem nhiều</p>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Tháng</label>
                    <select name="month" class="form-select">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?php echo $m; ?>" <?php echo $month === $m ? 'selected' : ''; ?>>Tháng <?php echo $m; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Năm</label>
                    <select name="year" class="form-select">
                        <?php for ($y = 2020; $y <= (int)date('Y'); $y++): ?>
                            <option value="<?php echo $y; ?>" <?php echo $year === $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fa-solid fa-magnifying-glass"></i> Xem
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="dashboard-stats mb-4">
        <div class="dashboard-stat-box"><strong><?php echo $total_users; ?></strong><span class="small text-muted d-block">Tổng người dùng</span></div>
        <div class="dashboard-stat-box"><strong><?php echo number_format($total_revenue); ?> đ</strong><span class="small text-muted d-block">Giá trị tin đã duyệt</span></div>
    </div>

    <p class="fw-bold mb-2">Tháng <?php echo $month; ?>/<?php echo $year; ?></p>
    <div class="dashboard-stats mb-4">
        <div class="dashboard-stat-box"><strong><?php echo (int)($monthly_stats['total'] ?? 0); ?></strong><span class="small text-muted d-block">Bài đăng</span></div>
        <div class="dashboard-stat-box"><strong><?php echo (int)($monthly_stats['approved'] ?? 0); ?></strong><span class="small text-muted d-block">Đã duyệt</span></div>
        <div class="dashboard-stat-box"><strong><?php echo (int)($monthly_stats['pending'] ?? 0); ?></strong><span class="small text-muted d-block">Chờ duyệt</span></div>
        <div class="dashboard-stat-box"><strong><?php echo (int)($monthly_stats['rejected'] ?? 0); ?></strong><span class="small text-muted d-block">Bị từ chối</span></div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0 fw-bold"><i class="fa-solid fa-coins"></i> Thống kê giá (Tháng <?php echo $month; ?>/<?php echo $year; ?>)</h5>
        </div>
        <div class="card-body">
            <div class="row text-center g-3">
                <div class="col-6 col-md-3">
                    <p class="mb-1 text-muted small">Tổng giá</p>
                    <p class="fw-bold text-primary mb-0"><?php echo number_format($monthly_stats['total_price'] ?? 0); ?> đ</p>
                </div>
                <div class="col-6 col-md-3">
                    <p class="mb-1 text-muted small">Trung bình</p>
                    <p class="fw-bold text-success mb-0"><?php echo number_format($monthly_stats['avg_price'] ?? 0); ?> đ</p>
                </div>
                <div class="col-6 col-md-3">
                    <p class="mb-1 text-muted small">Thấp nhất</p>
                    <p class="fw-bold text-warning mb-0"><?php echo number_format($monthly_stats['min_price'] ?? 0); ?> đ</p>
                </div>
                <div class="col-6 col-md-3">
                    <p class="mb-1 text-muted small">Cao nhất</p>
                    <p class="fw-bold text-danger mb-0"><?php echo number_format($monthly_stats['max_price'] ?? 0); ?> đ</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0 fw-bold"><i class="fa-solid fa-fire-flame-curved text-danger"></i> Top 5 phòng xem nhiều</h5>
        </div>
        <div class="card-body">
            <?php if (count($top_motels) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tên phòng</th>
                                <th>Chủ</th>
                                <th>Giá</th>
                                <th>Lượt xem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_motels as $motel): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars(substr($motel['title'], 0, 40)); ?></strong></td>
                                    <td><?php echo htmlspecialchars($motel['user_name']); ?></td>
                                    <td><?php echo number_format($motel['price']); ?> đ</td>
                                    <td><span class="badge bg-primary"><?php echo (int)$motel['count_view']; ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info mb-0">
                    <i class="fa-solid fa-circle-info"></i> Không có dữ liệu
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
