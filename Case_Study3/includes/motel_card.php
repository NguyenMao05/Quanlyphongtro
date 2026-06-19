<?php
if (!isset($motel)) {
    return;
}

require_once __DIR__ . '/motel_helpers.php';

$uploads_path = $uploads_path ?? 'uploads/';
$detail_base = $detail_base ?? '/Case_Study3/motel/detail.php?id=';
$edit_base = $edit_base ?? '/Case_Study3/motel/edit_motel.php?id=';
$show_owner_actions = $show_owner_actions ?? false;
$card_layout = $card_layout ?? 'grid';

$motel_id = gtpt_motel_row_id($motel);
$edit_url = $edit_base . $motel_id;
$image_path = $uploads_path . ($motel['images'] ?? '');
$has_image = !empty($motel['images']) && file_exists($image_path);
$detail_url = $detail_base . (int) $motel_id;

$wrapper_class = $card_layout === 'list' ? 'listings-list-item' : '';
$grid_class = $card_layout === 'grid' ? 'listings-grid-item' : '';
?>
<?php if ($card_layout === 'grid'): ?><div class="listings-grid-item"><?php endif; ?>
<?php if ($card_layout === 'list'): ?><div class="listings-list-item"><?php endif; ?>

<article class="listing-card-v2">
    <a href="<?php echo htmlspecialchars($detail_url); ?>" class="listing-media text-decoration-none">
        <?php if ($has_image): ?>
            <img src="<?php echo htmlspecialchars($image_path); ?>" alt="<?php echo htmlspecialchars($motel['title']); ?>">
        <?php else: ?>
            <div class="d-flex align-items-center justify-content-center h-100 min-vh-25">
                <i class="fa-solid fa-image fa-2x text-muted"></i>
            </div>
        <?php endif; ?>
        <span class="listing-price-badge"><?php echo number_format($motel['price'] ?? 0); ?> đ/tháng</span>
    </a>
    <div class="listing-body">
        <a href="<?php echo htmlspecialchars($detail_url); ?>" class="listing-title">
            <?php echo htmlspecialchars($motel['title']); ?>
        </a>
        <div class="listing-tags">
            <?php if (!empty($motel['category_name'])): ?>
                <span class="listing-tag"><?php echo htmlspecialchars($motel['category_name']); ?></span>
            <?php endif; ?>
            <?php if (!empty($motel['district_name'])): ?>
                <span class="listing-tag"><?php echo htmlspecialchars($motel['district_name']); ?></span>
            <?php endif; ?>
            <?php if ($show_owner_actions): ?>
                <?php if (($motel['approve'] ?? 0) == 1): ?>
                    <span class="listing-tag text-success">Đã duyệt</span>
                    <?php if ((int) ($motel['is_visible'] ?? 1) === 0): ?>
                        <span class="listing-tag text-secondary">Đang ẩn</span>
                    <?php endif; ?>
                <?php elseif (($motel['approve'] ?? 0) == 0): ?>
                    <span class="listing-tag text-warning">Chờ duyệt</span>
                <?php else: ?>
                    <span class="listing-tag text-danger">Từ chối</span>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php if (!empty($motel['address'])): ?>
            <p class="listing-meta mb-1">
                <i class="fa-solid fa-location-dot"></i>
                <?php echo htmlspecialchars($motel['address']); ?>
            </p>
        <?php endif; ?>
        <p class="listing-meta mb-0">
            <i class="fa-solid fa-ruler-combined"></i> <?php echo number_format($motel['area'] ?? 0); ?> m²
            · <i class="fa-solid fa-eye"></i> <?php echo (int) ($motel['count_view'] ?? 0); ?> lượt xem
        </p>
        <div class="listing-foot">
            <?php if ($show_owner_actions): ?>
                <div class="d-flex flex-column gap-2 w-100">
                    <a href="<?php echo htmlspecialchars($detail_url); ?>" class="btn btn-sm btn-outline-primary">
                        <i class="fa-solid fa-eye"></i> Xem
                    </a>
                    <?php if ($motel_id > 0): ?>
                        <a href="<?php echo htmlspecialchars($edit_url); ?>" class="btn btn-sm btn-warning text-dark">
                            <i class="fa-solid fa-pen-to-square"></i> Sửa tin
                        </a>
                    <?php endif; ?>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="openDeleteMotelModal(<?php echo (int) $motel_id; ?>)">
                        <i class="fa-solid fa-trash"></i> Xóa
                    </button>
                </div>
            <?php else: ?>
                <a href="<?php echo htmlspecialchars($detail_url); ?>" class="btn btn-sm btn-primary w-100">
                    Xem chi tiết <i class="fa-solid fa-arrow-right ms-1"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>
</article>

<?php if ($card_layout === 'grid' || $card_layout === 'list'): ?></div><?php endif; ?>
