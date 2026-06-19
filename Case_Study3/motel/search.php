<?php
// Trang tìm kiếm và lọc phòng trọ

include '../config/connect.php';
require_once __DIR__ . '/../includes/motel_helpers.php';
gtpt_ensure_motel_is_visible_column($conn);

session_start();

// Lấy dữ liệu lọc
$keyword = trim($_GET['keyword'] ?? '');
$price_min = isset($_GET['price_min']) && $_GET['price_min'] != '' ? (int) $_GET['price_min'] : 0;
$price_max = isset($_GET['price_max']) && $_GET['price_max'] != '' ? (int) $_GET['price_max'] : 999999999;
$area_min = isset($_GET['area_min']) && $_GET['area_min'] != '' ? (int) $_GET['area_min'] : 0;
$area_max = isset($_GET['area_max']) && $_GET['area_max'] != '' ? (int) $_GET['area_max'] : 999999999;
$district_id = isset($_GET['district_id']) && $_GET['district_id'] != '' ? (int) $_GET['district_id'] : 0;
$category_id = isset($_GET['category_id']) && $_GET['category_id'] != '' ? (int) $_GET['category_id'] : 0;

$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}

$items_per_page = 3;
$offset = ($page - 1) * $items_per_page;

/**
 * @return array{0: string, 1: array<int, mixed>}
 */
function build_search_base_where(
    string $keyword,
    int $price_min,
    int $price_max,
    int $area_min,
    int $area_max,
    int $district_id,
    int $category_id
): array {
    $where = 'WHERE ' . gtpt_motel_public_sql('m');
    $params = [];

    if ($keyword !== '') {
        $where .= ' AND (m.title LIKE ? OR m.address LIKE ?)';
        $params[] = '%' . $keyword . '%';
        $params[] = '%' . $keyword . '%';
    }

    $where .= ' AND m.price BETWEEN ? AND ?';
    $params[] = $price_min;
    $params[] = $price_max;

    $where .= ' AND m.area BETWEEN ? AND ?';
    $params[] = $area_min;
    $params[] = $area_max;

    if ($district_id > 0) {
        $where .= ' AND m.district_id = ?';
        $params[] = $district_id;
    }

    if ($category_id > 0) {
        $where .= ' AND m.category_id = ?';
        $params[] = $category_id;
    }

    return [$where, $params];
}

try {
    [$base_where, $base_params] = build_search_base_where(
        $keyword,
        $price_min,
        $price_max,
        $area_min,
        $area_max,
        $district_id,
        $category_id
    );

    // Tổng kết quả trước khi lọc tiện ích (để hiện bộ lọc tiện ích)
    $sql_base_count = "SELECT COUNT(*) AS total FROM motel m $base_where";
    $result_base_count = $conn->prepare($sql_base_count);
    $result_base_count->execute($base_params);
    $base_total_motels = (int) $result_base_count->fetchColumn();

    // Tiện ích có trong các phòng khớp bộ lọc chính
    $sql_utilities = "SELECT m.utilities FROM motel m $base_where AND m.utilities IS NOT NULL AND TRIM(m.utilities) <> ''";
    $result_utilities = $conn->prepare($sql_utilities);
    $result_utilities->execute($base_params);
    $available_utilities = gtpt_collect_utilities_from_rows($result_utilities->fetchAll());

    $utilities_input = trim($_GET['utilities_input'] ?? '');
    $selected_utilities = gtpt_parse_utilities_input($utilities_input);

    [$utility_sql, $utility_params] = gtpt_motel_utilities_filter_sql($selected_utilities, 'm');
    $where = $base_where . $utility_sql;
    $params = array_merge($base_params, $utility_params);

    $sql_count = "SELECT COUNT(*) AS total FROM motel m $where";
    $result_count = $conn->prepare($sql_count);
    $result_count->execute($params);
    $total_motels = (int) $result_count->fetchColumn();
    $total_pages = max(1, (int) ceil($total_motels / $items_per_page));

    if ($page > $total_pages) {
        $page = $total_pages;
        $offset = ($page - 1) * $items_per_page;
    }

    $sql = "SELECT m.*, u.name AS user_name, u.phone AS user_phone, d.name AS district_name, c.name AS category_name
            FROM motel m
            JOIN users u ON m.user_id = u.id
            JOIN districts d ON m.district_id = d.id
            JOIN category c ON m.category_id = c.id
            $where
            ORDER BY m.created_at DESC
            LIMIT ? OFFSET ?";
    $result = $conn->prepare($sql);

    $paramIndex = 1;
    foreach ($params as $param) {
        $result->bindValue($paramIndex++, $param);
    }
    $result->bindValue($paramIndex++, $items_per_page, PDO::PARAM_INT);
    $result->bindValue($paramIndex, $offset, PDO::PARAM_INT);
    $result->execute();
    $motels = $result->fetchAll();

    $sql_districts = 'SELECT * FROM districts';
    $districts = $conn->query($sql_districts)->fetchAll();

    $sql_categories = 'SELECT * FROM category';
    $categories = $conn->query($sql_categories)->fetchAll();

    $preserve_query = gtpt_search_preserve_query(['page', 'utilities_input']);
} catch (PDOException $e) {
    die('Lỗi: ' . $e->getMessage());
}
?>

<?php include '../includes/header.php'; ?>

<div class="search-layout py-4 px-3">
    <h1 class="mb-4 fw-bold"><i class="fa-solid fa-magnifying-glass text-success"></i> Tìm kiếm phòng trọ</h1>

    <div class="search-filter-bar">
        <form method="GET" action="">
            <div class="search-filter-grid">
                <div>
                    <label class="form-label small fw-bold">Từ khóa</label>
                    <input type="text" name="keyword" class="form-control" placeholder="Địa chỉ, tên phòng..." value="<?php echo htmlspecialchars($keyword); ?>">
                </div>
                <div>
                    <label class="form-label small fw-bold">Khu vực</label>
                    <select name="district_id" class="form-select">
                        <option value="">Tất cả</option>
                        <?php foreach ($districts as $dist): ?>
                            <option value="<?php echo (int) $dist['ID']; ?>" <?php echo $district_id == $dist['ID'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($dist['Name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label small fw-bold">Loại phòng</label>
                    <select name="category_id" class="form-select">
                        <option value="">Tất cả</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo (int) $cat['ID']; ?>" <?php echo $category_id == $cat['ID'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['Name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label small fw-bold">Giá từ (đ)</label>
                    <input type="number" name="price_min" class="form-control" value="<?php echo $price_min > 0 ? (int) $price_min : ''; ?>">
                </div>
                <div>
                    <label class="form-label small fw-bold">Giá đến (đ)</label>
                    <input type="number" name="price_max" class="form-control" value="<?php echo $price_max < 999999999 ? (int) $price_max : ''; ?>">
                </div>
                <div>
                    <label class="form-label small fw-bold">Diện tích từ (m²)</label>
                    <input type="number" name="area_min" class="form-control" value="<?php echo $area_min > 0 ? (int) $area_min : ''; ?>">
                </div>
                <div>
                    <label class="form-label small fw-bold">Diện tích đến (m²)</label>
                    <input type="number" name="area_max" class="form-control" value="<?php echo $area_max < 999999999 ? (int) $area_max : ''; ?>">
                </div>
            </div>
            <div class="search-filter-actions">
                <a href="search.php" class="btn btn-outline-secondary"><i class="fa-solid fa-rotate-left"></i> Đặt lại</a>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass"></i> Áp dụng bộ lọc</button>
            </div>
        </form>
    </div>

    <?php if ($base_total_motels > 0): ?>
        <div class="search-utility-filter">
            <h3 class="mb-0"><i class="fa-solid fa-bolt text-warning"></i> Lọc thêm theo tiện ích</h3>
            <p class="small text-muted mb-2">
                Nhập tiện ích cần có
                (trong <?php echo number_format($base_total_motels); ?> phòng phù hợp bộ lọc trên).
            </p>
            <form method="GET" action="">
                <?php foreach ($preserve_query as $name => $value): ?>
                    <?php if (is_array($value)): ?>
                        <?php foreach ($value as $item): ?>
                            <input type="hidden" name="<?php echo htmlspecialchars($name); ?>[]" value="<?php echo htmlspecialchars((string) $item); ?>">
                        <?php endforeach; ?>
                    <?php else: ?>
                        <input type="hidden" name="<?php echo htmlspecialchars($name); ?>" value="<?php echo htmlspecialchars((string) $value); ?>">
                    <?php endif; ?>
                <?php endforeach; ?>

                <div class="search-utility-input mb-3">
                    <label for="utilities_input" class="form-label small fw-bold mb-1">Nhập tiện ích cần có</label>
                    <input
                        type="text"
                        class="form-control"
                        id="utilities_input"
                        name="utilities_input"
                        list="utilitySuggestions"
                        placeholder="VD: Wifi, Máy lạnh, Bếp (cách nhau bằng dấu phẩy)"
                        value="<?php echo htmlspecialchars($utilities_input); ?>"
                        autocomplete="off"
                    >
                    <?php if (count($available_utilities) > 0): ?>
                        <datalist id="utilitySuggestions">
                            <?php foreach ($available_utilities as $utility): ?>
                                <option value="<?php echo htmlspecialchars($utility); ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                    <?php endif; ?>
                    <small class="text-muted">Gõ tên tiện ích, nhiều tiện ích thì cách nhau bằng dấu phẩy.</small>
                </div>

                <div class="search-utility-filter-actions">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-filter"></i> Áp dụng tiện ích
                    </button>
                    <?php if ($utilities_input !== ''): ?>
                        <a href="?<?php echo htmlspecialchars(http_build_query($preserve_query)); ?>" class="btn btn-outline-secondary btn-sm">
                            <i class="fa-solid fa-xmark"></i> Bỏ lọc tiện ích
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <div class="listings-section-head mb-3">
        <h2 class="mb-0 h5">
            <i class="fa-solid fa-list"></i>
            <?php echo number_format($total_motels); ?> kết quả
            <?php if (count($selected_utilities) > 0): ?>
                <span class="text-muted fw-normal small">
                    · <?php echo count($selected_utilities); ?> tiện ích đã chọn
                </span>
            <?php endif; ?>
        </h2>
    </div>

    <?php if (count($motels) > 0): ?>
        <div class="listings-list">
            <?php
            $uploads_path = '../uploads/';
            $detail_base = 'detail.php?id=';
            $show_owner_actions = false;
            $card_layout = 'list';
            foreach ($motels as $motel):
                include __DIR__ . '/../includes/motel_card.php';
            endforeach;
            ?>
        </div>

        <?php if ($total_pages > 1): ?>
            <nav aria-label="Phân trang kết quả" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo htmlspecialchars(http_build_query(array_merge($_GET, ['page' => 1]))); ?>">Đầu tiên</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo htmlspecialchars(http_build_query(array_merge($_GET, ['page' => $page - 1]))); ?>">Trước</a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <?php if ($i == $page): ?>
                            <li class="page-item active"><span class="page-link"><?php echo $i; ?></span></li>
                        <?php else: ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo htmlspecialchars(http_build_query(array_merge($_GET, ['page' => $i]))); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo htmlspecialchars(http_build_query(array_merge($_GET, ['page' => $page + 1]))); ?>">Sau</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo htmlspecialchars(http_build_query(array_merge($_GET, ['page' => $total_pages]))); ?>">Cuối cùng</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>

    <?php else: ?>
        <div class="empty-state">
            <i class="fa-solid fa-magnifying-glass"></i>
            <?php if ($base_total_motels > 0 && count($selected_utilities) > 0): ?>
                <p>Không có phòng nào có đủ tiện ích đã chọn. Thử bỏ bớt tiện ích hoặc đổi bộ lọc chính.</p>
                <a href="?<?php echo htmlspecialchars(http_build_query($preserve_query)); ?>" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-xmark"></i> Bỏ lọc tiện ích
                </a>
            <?php else: ?>
                <p>Không tìm thấy phòng phù hợp. Thử đổi bộ lọc hoặc từ khóa khác.</p>
                <a href="search.php" class="btn btn-secondary"><i class="fa-solid fa-rotate-left"></i> Đặt lại bộ lọc</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
