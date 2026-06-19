<?php
// Duyệt / từ chối bài đăng phòng trọ

include '../config/connect.php';
include '../includes/admin_init.php';

$motel_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($motel_id <= 0 || !in_array($action, ['approve', 'reject'], true)) {
    header('Location: manage_motels.php?error=' . urlencode('Yêu cầu không hợp lệ.'));
    exit();
}

try {
    if ($action === 'approve') {
        $sql = 'UPDATE motel SET approve = 1 WHERE id = ?';
        $message = 'Duyệt phòng trọ thành công!';
    } else {
        $sql = 'UPDATE motel SET approve = -1 WHERE id = ?';
        $message = 'Từ chối phòng trọ thành công!';
    }

    $result = $conn->prepare($sql);
    $result->execute([$motel_id]);

    if ($result->rowCount() < 1) {
        header('Location: manage_motels.php?error=' . urlencode('Không tìm thấy phòng trọ hoặc trạng thái không đổi.'));
        exit();
    }

    header('Location: manage_motels.php?success=' . urlencode($message));
    exit();
} catch (PDOException $e) {
    die('Lỗi: ' . $e->getMessage());
}
