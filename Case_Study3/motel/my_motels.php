<?php
// Danh sách phòng trọ của người dùng

include '../config/connect.php';
require_once __DIR__ . '/../includes/motel_helpers.php';

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
$error = isset($_GET['error']) ? trim($_GET['error']) : '';
$success = isset($_GET['success']) ? trim($_GET['success']) : '';

try {
    // Lấy danh sách phòng trọ của người dùng
    $sql = "SELECT m.*, d.name as district_name, c.name as category_name
            FROM motel m
            JOIN districts d ON m.district_id = d.id
            JOIN category c ON m.category_id = c.id
            WHERE m.user_id = ?
            ORDER BY m.created_at DESC";
    $result = $conn->prepare($sql);
    $result->execute([$user_id]);
    $motels = $result->fetchAll();

    $stat_total = count($motels);
    $stat_approved = 0;
    $stat_pending = 0;
    $stat_hidden = 0;
    foreach ($motels as $m) {
        if (($m['approve'] ?? 0) == 1) {
            $stat_approved++;
            if ((int) ($m['is_visible'] ?? 1) === 0) {
                $stat_hidden++;
            }
        } elseif (($m['approve'] ?? 0) == 0) {
            $stat_pending++;
        }
    }
    
} catch (PDOException $e) {
    die("Lỗi: " . $e->getMessage());
}

// Xử lý xóa phòng trọ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $motel_id = (int)($_POST['motel_id'] ?? 0);

    try {
        $result_check = $conn->prepare('SELECT id FROM motel WHERE id = ? AND user_id = ?');
        $result_check->execute([$motel_id, $user_id]);

        if ($result_check->fetch()) {
            $result = gtpt_delete_motel($conn, $motel_id);
            if ($result['ok']) {
                header('Location: my_motels.php?success=' . urlencode('Xóa phòng trọ thành công!'));
                exit();
            }
            $error = $result['error'] ?? 'Không thể xóa phòng trọ!';
        } else {
            $error = 'Phòng trọ không tồn tại hoặc không thuộc về bạn!';
        }
    } catch (PDOException $e) {
        $error = 'Lỗi: ' . $e->getMessage();
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="dashboard-layout py-4 px-3">
    <div class="dashboard-toolbar">
        <div>
            <h1 class="h3 fw-bold mb-1"><i class="fa-solid fa-key text-success"></i> Quản lý phòng trọ</h1>
            <p class="text-muted mb-0 small">Theo dõi tin đăng và trạng thái duyệt</p>
        </div>
        <a href="add_motel.php" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Đăng tin mới</a>
    </div>

    <div class="dashboard-stats">
        <div class="dashboard-stat-box"><strong><?php echo $stat_total; ?></strong><span class="small text-muted d-block">Tổng tin</span></div>
        <div class="dashboard-stat-box"><strong><?php echo $stat_approved; ?></strong><span class="small text-muted d-block">Đã duyệt</span></div>
        <div class="dashboard-stat-box"><strong><?php echo $stat_pending; ?></strong><span class="small text-muted d-block">Chờ duyệt</span></div>
        <div class="dashboard-stat-box"><strong><?php echo $stat_hidden; ?></strong><span class="small text-muted d-block">Đang ẩn</span></div>
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

    <?php if (count($motels) > 0): ?>
        <div class="listings-grid">
            <?php
            $uploads_path = '../uploads/';
            $detail_base = 'detail.php?id=';
            $edit_base = '/Case_Study3/motel/edit_motel.php?id=';
            $show_owner_actions = true;
            $card_layout = 'grid';
            foreach ($motels as $motel):
                include __DIR__ . '/../includes/motel_card.php';
            endforeach;
            ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fa-solid fa-house-circle-exclamation"></i>
            <p>Bạn chưa đăng phòng trọ nào.</p>
            <a href="add_motel.php" class="btn btn-primary"><i class="fa-solid fa-circle-plus"></i> Đăng phòng mới</a>
        </div>
    <?php endif; ?>
</div>

<!-- Modal xác nhận xóa -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fa-solid fa-trash-can"></i> Xác nhận xóa</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn xóa phòng trọ này? Hành động này không thể hoàn tác.</p>
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
