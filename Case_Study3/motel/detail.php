<?php
// Trang chi tiết phòng trọ

include '../config/connect.php';
require_once __DIR__ . '/../includes/motel_helpers.php';
gtpt_ensure_motel_is_visible_column($conn);
$public_where = gtpt_motel_public_sql('m');

// Khởi động session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Lấy ID phòng trọ
$motel_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($motel_id <= 0) {
    header("Location: /Case_Study3/index.php");
    exit();
}

try {
    $viewer_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;

    // Lấy thông tin phòng trọ (công khai hoặc chủ tin đang xem tin của mình)
    $sql = "SELECT m.*, u.Name AS user_name, u.Phone AS user_phone, u.Email AS user_email, u.Avatar AS owner_avatar,
                   d.name AS district_name, c.name AS category_name
            FROM motel m
            JOIN users u ON m.user_id = u.ID
            JOIN districts d ON m.district_id = d.ID
            JOIN category c ON m.category_id = c.ID
            WHERE m.id = ? AND ($public_where OR m.user_id = ?)";
    $result = $conn->prepare($sql);
    $result->execute([$motel_id, $viewer_id]);
    
    if ($result->rowCount() == 0) {
        header("Location: /Case_Study3/index.php");
        exit();
    }
    
    $motel = $result->fetch();

    $motel_row_id = gtpt_motel_row_id($motel);
    $viewer_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
    $is_owner = $viewer_id > 0 && $viewer_id === (int) ($motel['user_id'] ?? 0);
    $is_admin = isset($_SESSION['role']) && (int) $_SESSION['role'] === 1;
    $can_edit_motel = $is_owner || $is_admin;

    $listing_phone = trim((string) ($motel['phone'] ?? ''));
    $profile_phone = trim((string) ($motel['user_phone'] ?? ''));
    $contact_phone = $listing_phone !== '' ? $listing_phone : $profile_phone;
    $contact_email = trim((string) ($motel['user_email'] ?? ''));
    $owner_name = trim((string) ($motel['user_name'] ?? ''));
    $owner_avatar = trim((string) ($motel['owner_avatar'] ?? ''));
    $avatar_src = ($owner_avatar !== '' && file_exists(__DIR__ . '/../' . ltrim($owner_avatar, '/')))
        ? '../' . ltrim($owner_avatar, '/')
        : '';

    // Tăng lượt xem
    $sql_view = "UPDATE motel SET count_view = count_view + 1 WHERE id = ?";
    $result_view = $conn->prepare($sql_view);
    $result_view->execute([$motel_id]);
    
    // Cập nhật lượt xem cho hiển thị
    $motel['count_view']++;
    
    // Lấy các phòng tương tự (cùng khu vực)
    $sql_similar = "SELECT m.*, u.Name AS user_name, d.name AS district_name, c.name AS category_name
                    FROM motel m
                    JOIN users u ON m.user_id = u.ID
                    JOIN districts d ON m.district_id = d.ID
                    JOIN category c ON m.category_id = c.ID
                    WHERE m.district_id = ? AND m.id != ? AND $public_where
                    ORDER BY m.created_at DESC
                    LIMIT 3";
    $result_similar = $conn->prepare($sql_similar);
    $result_similar->execute([$motel['district_id'], $motel_id]);
    $similar_motels = $result_similar->fetchAll();

    require_once __DIR__ . '/../includes/map_helpers.php';
    $map_position = resolve_motel_map_position($motel);
    
} catch (PDOException $e) {
    die("Lỗi: " . $e->getMessage());
}
?>

<?php include '../includes/header.php'; ?>

<div class="detail-layout-v2 py-4 px-3">
    <nav class="detail-breadcrumb mb-3">
        <a href="/Case_Study3/index.php">Trang chủ</a>
        <i class="fa-solid fa-chevron-right"></i>
        <a href="/Case_Study3/motel/search.php">Tìm phòng</a>
        <i class="fa-solid fa-chevron-right"></i>
        <span><?php echo htmlspecialchars($motel['title']); ?></span>
    </nav>

    <div class="detail-gallery mb-3">
        <?php if (!empty($motel['images']) && file_exists('../uploads/' . $motel['images'])): ?>
            <img src="../uploads/<?php echo htmlspecialchars($motel['images']); ?>" alt="<?php echo htmlspecialchars($motel['title']); ?>">
        <?php else: ?>
            <div class="detail-gallery-placeholder"><i class="fa-solid fa-image"></i></div>
        <?php endif; ?>
    </div>

    <div class="detail-layout-grid">
        <div>
            <h1 class="detail-title mb-2"><?php echo htmlspecialchars($motel['title']); ?></h1>
            <p class="text-muted mb-3">
                <i class="fa-solid fa-calendar"></i> <?php echo date('d/m/Y', strtotime($motel['created_at'])); ?>
                · <i class="fa-solid fa-eye"></i> <?php echo number_format($motel['count_view']); ?> lượt xem
            </p>

            <div class="detail-specs-row">
                <span class="detail-spec-chip"><i class="fa-solid fa-ruler-combined"></i> <?php echo number_format($motel['area']); ?> m²</span>
                <span class="detail-spec-chip"><i class="fa-solid fa-door-open"></i> <?php echo htmlspecialchars($motel['category_name']); ?></span>
                <span class="detail-spec-chip"><i class="fa-solid fa-map-pin"></i> <?php echo htmlspecialchars($motel['district_name']); ?></span>
            </div>

            <div class="detail-content-block">
                <h6 class="fw-bold mb-2"><i class="fa-solid fa-location-dot"></i> Địa chỉ</h6>
                <p class="mb-0"><?php echo htmlspecialchars($motel['address']); ?></p>
            </div>

            <?php if (!empty(trim($motel['description'] ?? ''))): ?>
            <div class="detail-content-block">
                <h6 class="fw-bold mb-2"><i class="fa-solid fa-align-left"></i> Mô tả</h6>
                <p class="mb-0"><?php echo nl2br(htmlspecialchars($motel['description'])); ?></p>
            </div>
            <?php endif; ?>

            <?php if (!empty($motel['utilities'])): ?>
            <div class="detail-content-block">
                <h6 class="fw-bold mb-2"><i class="fa-solid fa-bolt"></i> Tiện ích</h6>
                <ul class="utility-list mb-0">
                    <?php foreach (explode(',', $motel['utilities']) as $utility): ?>
                        <?php if (trim($utility) !== ''): ?>
                            <li><i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars(trim($utility)); ?></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <!-- Bản đồ (Leaflet + OpenStreetMap) -->
            <?php if ($map_position !== null): ?>
                <div class="card mb-4 shadow-sm map-card">
                    <div class="card-header bg-dark text-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <h5 class="mb-0 fw-bold"><i class="fa-solid fa-map-location-dot"></i> Bản đồ</h5>
                        <?php if (($map_position['source'] ?? '') === 'address'): ?>
                            <small class="text-muted">Vị trí ước lượng từ địa chỉ</small>
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-0">
                        <div
                            id="motel-map"
                            data-lat="<?php echo htmlspecialchars((string) $map_position['lat']); ?>"
                            data-lng="<?php echo htmlspecialchars((string) $map_position['lng']); ?>"
                            data-title="<?php echo htmlspecialchars($motel['title']); ?>"
                            data-address="<?php echo htmlspecialchars($motel['address']); ?>"
                        ></div>
                    </div>
                    <div class="card-footer d-flex flex-wrap gap-2 justify-content-between align-items-center">
                        <small class="text-muted">
                            <i class="fa-solid fa-location-dot"></i>
                            <?php echo htmlspecialchars($motel['address']); ?>
                        </small>
                        <a id="map-external-link" href="#" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i> Mở Google Maps
                        </a>
                    </div>
                </div>
                <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
                <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
                <script src="/Case_Study3/assets/js/motel-map.js"></script>
            <?php elseif (!empty(trim($motel['address'] ?? '')) || !empty(trim($motel['latlng'] ?? ''))): ?>
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0 fw-bold"><i class="fa-solid fa-map-location-dot"></i> Bản đồ</h5>
                    </div>
                    <div class="card-body">
                        <div class="map-fallback">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            Không xác định được vị trí. Nhập tọa độ GPS đúng định dạng <code>vĩ độ,kinh độ</code> (vd: <code>10.762622, 106.660172</code>) khi đăng phòng.
                        </div>
                        <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($motel['address']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary mt-2">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i> Tìm địa chỉ trên Google Maps
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <aside>
            <div class="detail-booking-card">
                <div class="booking-price"><?php echo number_format($motel['price']); ?> đ<small class="fs-6 text-muted fw-normal">/tháng</small></div>
                <hr>
                <div class="text-center mb-3">
                    <?php if ($avatar_src !== ''): ?>
                        <img src="<?php echo htmlspecialchars($avatar_src); ?>" alt="" class="avatar-lg mb-2">
                    <?php else: ?>
                        <span class="avatar-placeholder d-inline-flex mb-2"><i class="fa-solid fa-user"></i></span>
                    <?php endif; ?>
                    <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($owner_name !== '' ? $owner_name : 'Chủ trọ'); ?></h6>
                    <small class="text-muted">Chủ trọ</small>
                </div>

                <h6 class="fw-bold small text-muted text-uppercase mb-2">Thông tin liên hệ</h6>
                <?php if ($contact_phone !== '' || $contact_email !== ''): ?>
                    <ul class="list-unstyled owner-contact-list mb-3">
                        <?php if ($contact_phone !== ''): ?>
                            <li>
                                <i class="fa-solid fa-phone"></i>
                                <span>
                                    <span class="d-block small text-muted">Điện thoại</span>
                                    <strong><?php echo htmlspecialchars($contact_phone); ?></strong>
                                    <?php if ($listing_phone === '' && $profile_phone !== ''): ?>
                                        <span class="d-block small text-muted">(từ hồ sơ chủ trọ)</span>
                                    <?php endif; ?>
                                </span>
                            </li>
                        <?php endif; ?>
                        <?php if ($contact_email !== ''): ?>
                            <li>
                                <i class="fa-solid fa-envelope"></i>
                                <span>
                                    <span class="d-block small text-muted">Email</span>
                                    <strong class="text-break"><?php echo htmlspecialchars($contact_email); ?></strong>
                                </span>
                            </li>
                        <?php endif; ?>
                    </ul>
                    <?php if ($contact_phone !== ''): ?>
                        <?php $tel_href = preg_replace('/[^\d+]/', '', $contact_phone); ?>
                        <a href="tel:<?php echo htmlspecialchars($tel_href); ?>" class="btn btn-primary w-100 mb-2">
                            <i class="fa-solid fa-phone"></i> Gọi ngay
                        </a>
                    <?php endif; ?>
                    <?php if ($contact_email !== ''): ?>
                        <a href="mailto:<?php echo htmlspecialchars($contact_email); ?>" class="btn btn-outline-primary w-100 mb-3">
                            <i class="fa-solid fa-envelope"></i> Gửi email
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="small text-muted mb-3">Chưa có số điện thoại hoặc email liên hệ. Vui lòng xem địa chỉ hoặc tin khác.</p>
                <?php endif; ?>

                <?php if ($can_edit_motel && $motel_row_id > 0): ?>
                    <hr>
                    <h6 class="fw-bold small text-muted text-uppercase mb-2">Quản lý tin</h6>
                    <a href="/Case_Study3/motel/edit_motel.php?id=<?php echo $motel_row_id; ?>" class="btn btn-warning w-100 text-dark mb-2">
                        <i class="fa-solid fa-pen-to-square"></i> Sửa tin đăng
                    </a>
                    <a href="/Case_Study3/motel/my_motels.php" class="btn btn-outline-secondary btn-sm w-100">
                        <i class="fa-solid fa-key"></i> Phòng của tôi
                    </a>
                <?php endif; ?>

                <?php if (count($similar_motels) > 0): ?>
                    <h6 class="fw-bold small text-muted text-uppercase mb-2">Gợi ý khác</h6>
                    <?php foreach ($similar_motels as $similar): ?>
                        <a href="detail.php?id=<?php echo (int) ($similar['ID'] ?? $similar['id'] ?? 0); ?>" class="similar-item d-block mb-2 p-2 rounded border text-decoration-none">
                            <span class="similar-item-title d-block small fw-bold"><?php echo htmlspecialchars($similar['title']); ?></span>
                            <span class="price-tag small"><?php echo number_format($similar['price']); ?> đ</span>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
