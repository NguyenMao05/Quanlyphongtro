<?php
include 'config/connect.php';
require_once __DIR__ . '/includes/motel_helpers.php';
gtpt_ensure_motel_is_visible_column($conn);
$public_where = gtpt_motel_public_sql('m');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$items_per_page = 9;
$offset = ($page - 1) * $items_per_page;

try {
    $total_motels = (int) $conn->query("SELECT COUNT(*) FROM motel m WHERE $public_where")->fetchColumn();
    $total_pages = max(1, (int) ceil($total_motels / $items_per_page));

    $sql = "SELECT m.*, d.name as district_name, c.name as category_name
            FROM motel m
            JOIN districts d ON m.district_id = d.id
            JOIN category c ON m.category_id = c.id
            WHERE $public_where ORDER BY m.created_at DESC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(1, $items_per_page, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $motels = $stmt->fetchAll();

    $top_motels = $conn->query("SELECT m.*, d.name as district_name, c.name as category_name
        FROM motel m JOIN districts d ON m.district_id = d.id JOIN category c ON m.category_id = c.id
        WHERE $public_where ORDER BY m.count_view DESC LIMIT 12")->fetchAll();

    $new_motels = $conn->query("SELECT m.*, d.name as district_name, c.name as category_name
        FROM motel m JOIN districts d ON m.district_id = d.id JOIN category c ON m.category_id = c.id
        WHERE $public_where ORDER BY m.created_at DESC LIMIT 3")->fetchAll();
} catch (PDOException $e) {
    die('Lỗi: ' . $e->getMessage());
}

function motel_img($motel, $prefix = 'uploads/') {
    $path = $prefix . ($motel['images'] ?? '');
    return (!empty($motel['images']) && file_exists($path)) ? $path : null;
}
function motel_link($motel) {
    $id = $motel['ID'] ?? $motel['id'] ?? 0;
    return '/Case_Study3/motel/detail.php?id=' . (int) $id;
}
?>

<?php include 'includes/header.php'; ?>

<section class="home-hero">
    <div class="container gtpt-container">
        <div class="home-hero-grid">
            <div>
                <h1>Tìm phòng trọ <span style="color:var(--gtpt-accent)">đúng ý</span> trong vài phút</h1>
                <p class="home-hero-lead">Hàng trăm tin đăng đã duyệt, lọc theo giá và khu vực, xem bản đồ và liên hệ chủ trọ trực tiếp.</p>
                <form class="home-search-box" method="GET" action="/Case_Study3/motel/search.php">
                    <input type="text" name="keyword" placeholder="Nhập địa chỉ, quận, tên phòng...">
                    <button type="submit"><i class="fa-solid fa-magnifying-glass"></i> Tìm</button>
                </form>
            </div>
            <div class="home-stats-panel">
                <div class="home-stat-item">
                    <i class="fa-solid fa-house-chimney"></i>
                    <div><strong><?php echo number_format($total_motels); ?>+</strong><br><small class="text-muted">Phòng đang cho thuê</small></div>
                </div>
                <div class="home-stat-item">
                    <i class="fa-solid fa-shield-halved"></i>
                    <div><strong>100%</strong><br><small class="text-muted">Tin đăng được kiểm duyệt</small></div>
                </div>
                <div class="home-stat-item">
                    <i class="fa-solid fa-map-location-dot"></i>
                    <div><strong>Bản đồ</strong><br><small class="text-muted">Xem vị trí từng phòng</small></div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="container gtpt-container">
    <?php if (count($top_motels) > 0): ?>
    <section class="featured-week">
        <div class="featured-week-head">
            <div>
                <span class="featured-week-label"><i class="fa-solid fa-fire-flame-curved"></i> Nổi bật tuần này</span>
                <h2 class="featured-week-title">Top phòng được quan tâm</h2>
            </div>
            <a href="/Case_Study3/motel/search.php" class="btn btn-primary">
                Khám phá thêm <i class="fa-solid fa-arrow-right ms-1"></i>
            </a>
        </div>

        <div class="featured-week-slider">
            <button type="button" class="featured-week-nav featured-week-nav--prev" aria-label="Cuộn trái" data-featured-scroll="prev">
                <i class="fa-solid fa-chevron-left"></i>
            </button>
            <div class="featured-week-track" id="featuredWeekTrack" tabindex="0">
                <?php foreach ($top_motels as $rank => $item):
                    $rank_num = $rank + 1;
                    $item_img = motel_img($item);
                ?>
                <a href="<?php echo motel_link($item); ?>" class="featured-week-card">
                    <span class="featured-week-rank">#<?php echo $rank_num; ?></span>
                    <div class="featured-week-card-media">
                        <?php if ($item_img): ?>
                            <img src="<?php echo htmlspecialchars($item_img); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                        <?php else: ?>
                            <span class="featured-week-placeholder"><i class="fa-solid fa-image"></i></span>
                        <?php endif; ?>
                    </div>
                    <div class="featured-week-card-body">
                        <?php if ($rank_num === 1): ?>
                            <span class="featured-week-chip">Xem nhiều nhất</span>
                        <?php endif; ?>
                        <h3 class="featured-week-card-title"><?php echo htmlspecialchars($item['title']); ?></h3>
                        <p class="featured-week-meta">
                            <i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($item['district_name']); ?>
                            <?php if (!empty($item['category_name'])): ?>
                                <span class="featured-week-dot">·</span>
                                <?php echo htmlspecialchars($item['category_name']); ?>
                            <?php endif; ?>
                        </p>
                        <div class="featured-week-footer">
                            <strong class="featured-week-price"><?php echo number_format($item['price']); ?> đ<span>/tháng</span></strong>
                            <span class="featured-week-views"><i class="fa-solid fa-eye"></i> <?php echo number_format((int)($item['count_view'] ?? 0)); ?></span>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <button type="button" class="featured-week-nav featured-week-nav--next" aria-label="Cuộn phải" data-featured-scroll="next">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>
    </section>
    <?php endif; ?>

    <?php if (count($new_motels) > 0): ?>
    <section class="listings-section">
        <div class="listings-section-head">
            <h2><i class="fa-solid fa-clock"></i> Mới đăng</h2>
        </div>
        <div class="listings-grid">
            <?php foreach ($new_motels as $motel):
                $card_layout = 'grid';
                include 'includes/motel_card.php';
            endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <section class="listings-section pb-5">
        <div class="listings-section-head">
            <h2><i class="fa-solid fa-table-cells"></i> Tất cả phòng trọ</h2>
            <span class="badge bg-secondary rounded-pill"><?php echo number_format($total_motels); ?> tin</span>
        </div>
        <?php if (count($motels) > 0): ?>
            <div class="listings-grid">
                <?php foreach ($motels as $motel):
                    $card_layout = 'grid';
                    include 'includes/motel_card.php';
                endforeach; ?>
            </div>
            <?php if ($total_pages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
        <?php else: ?>
            <div class="empty-state"><p>Chưa có phòng trọ nào.</p></div>
        <?php endif; ?>
    </section>
</div>

<?php include 'includes/footer.php'; ?>
