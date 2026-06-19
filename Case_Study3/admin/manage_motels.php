<?php
// Quản lý phòng trọ (admin)

include '../config/connect.php';
include '../includes/admin_init.php';
require_once __DIR__ . '/../includes/motel_helpers.php';

$error = isset($_GET['error']) ? trim($_GET['error']) : '';
$success = isset($_GET['success']) ? trim($_GET['success']) : '';

try {
    $sql = "SELECT m.*, u.name AS user_name, d.name AS district_name, c.name AS category_name
            FROM motel m
            JOIN users u ON m.user_id = u.id
            JOIN districts d ON m.district_id = d.id
            JOIN category c ON m.category_id = c.id
            ORDER BY m.created_at DESC";
    $motels = $conn->query($sql)->fetchAll();
} catch (PDOException $e) {
    die('Lỗi: ' . $e->getMessage());
}

// Xóa phòng trọ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $motel_id = (int)($_POST['motel_id'] ?? 0);

    try {
        $result = gtpt_delete_motel($conn, $motel_id);
        if ($result['ok']) {
            header('Location: manage_motels.php?success=' . urlencode('Xóa phòng trọ thành công!'));
            exit();
        }
        $error = $result['error'] ?? 'Không thể xóa phòng trọ!';
    } catch (PDOException $e) {
        $error = 'Lỗi: ' . $e->getMessage();
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="dashboard-layout py-4 px-3">
    <?php $admin_page = 'motels'; include '../includes/admin_tabs.php'; ?>

    <div class="dashboard-toolbar">
        <div>
            <h1 class="h3 fw-bold mb-1"><i class="fa-solid fa-building text-primary"></i> Quản lý phòng trọ</h1>
            <p class="text-muted mb-0 small">Duyệt, từ chối hoặc xóa tin đăng</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-secondary"><i class="fa-solid fa-gauge-high"></i> Tổng quan</a>
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
            <?php if (count($motels) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Tên phòng</th>
                                <th>Chủ</th>
                                <th>Giá</th>
                                <th>Khu vực</th>
                                <th>Trạng thái</th>
                                <th>Lượt xem</th>
                                <th>Ngày đăng</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($motels as $motel): ?>
                                <tr>
                                    <td><?php echo (int)$motel['ID']; ?></td>
                                    <td><strong><?php echo htmlspecialchars(substr($motel['title'], 0, 30)); ?></strong></td>
                                    <td><?php echo htmlspecialchars($motel['user_name']); ?></td>
                                    <td><?php echo number_format($motel['price']); ?> đ</td>
                                    <td><?php echo htmlspecialchars($motel['district_name']); ?></td>
                                    <td>
                                        <?php if ($motel['approve'] == 1): ?>
                                            <span class="badge bg-success">Đã duyệt</span>
                                        <?php elseif ($motel['approve'] == 0): ?>
                                            <span class="badge bg-warning text-dark">Chờ duyệt</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Bị từ chối</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo (int)$motel['count_view']; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($motel['created_at'])); ?></td>
                                    <td>
                                        <a href="/Case_Study3/motel/detail.php?id=<?php echo (int)$motel['ID']; ?>" class="btn btn-sm btn-info" title="Xem">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                        <a href="/Case_Study3/motel/edit_motel.php?id=<?php echo (int)$motel['ID']; ?>" class="btn btn-sm btn-warning" title="Sửa">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                        <?php if ($motel['approve'] != 1): ?>
                                            <a href="approve_motel.php?id=<?php echo (int)$motel['ID']; ?>&action=approve" class="btn btn-sm btn-success" title="Duyệt">
                                                <i class="fa-solid fa-check"></i>
                                            </a>
                                            <a href="approve_motel.php?id=<?php echo (int)$motel['ID']; ?>&action=reject" class="btn btn-sm btn-danger" title="Từ chối">
                                                <i class="fa-solid fa-xmark"></i>
                                            </a>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger" title="Xóa" onclick="openDeleteMotelModal(<?php echo (int)$motel['ID']; ?>)">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center mb-0">
                    <i class="fa-solid fa-circle-info"></i> Không có phòng trọ nào
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Xác nhận xóa</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn xóa phòng trọ này?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <form method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="motel_id" id="deleteMotelId" value="">
                    <button type="submit" class="btn btn-danger">Xóa</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
function openDeleteMotelModal(motelId) {
    document.getElementById('deleteMotelId').value = motelId;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>
