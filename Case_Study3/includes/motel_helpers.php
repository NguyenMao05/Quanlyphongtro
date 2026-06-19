<?php
/**
 * Đảm bảo cột is_visible tồn tại (chủ trọ bật/tắt hiển thị công khai).
 */
function gtpt_ensure_motel_is_visible_column(PDO $conn): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    try {
        $stmt = $conn->query("SHOW COLUMNS FROM motel LIKE 'is_visible'");
        if ($stmt && $stmt->rowCount() === 0) {
            $conn->exec(
                'ALTER TABLE motel ADD COLUMN is_visible TINYINT(1) NOT NULL DEFAULT 1 COMMENT "1=hiển thị công khai" AFTER approve'
            );
        }
    } catch (PDOException $e) {
        // Bỏ qua nếu không có quyền ALTER; cần chạy sql/add_is_visible.sql thủ công.
    }
}

/** Điều kiện SQL: tin được duyệt và đang bật hiển thị. */
function gtpt_motel_public_sql(string $tableAlias = 'm'): string
{
    return "{$tableAlias}.approve = 1 AND COALESCE({$tableAlias}.is_visible, 1) = 1";
}

function gtpt_motel_is_publicly_visible(array $motel): bool
{
    return (int) ($motel['approve'] ?? 0) === 1 && (int) ($motel['is_visible'] ?? 1) === 1;
}

/** ID tin đăng (hỗ trợ cả cột ID / id từ PDO). */
function gtpt_motel_row_id(array $motel): int
{
    return (int) ($motel['ID'] ?? $motel['id'] ?? 0);
}

/** Tách chuỗi tiện ích (phân cách bằng dấu phẩy). */
function gtpt_parse_utilities_string(?string $utilities): array
{
    if ($utilities === null || trim($utilities) === '') {
        return [];
    }
    $items = [];
    foreach (explode(',', $utilities) as $part) {
        $part = trim($part);
        if ($part !== '') {
            $items[] = $part;
        }
    }
    return $items;
}

function gtpt_normalize_utility_key(string $utility): string
{
    return mb_strtolower(trim($utility), 'UTF-8');
}

/**
 * Gom danh sách tiện ích duy nhất từ nhiều dòng motel (giữ đúng chữ hoa lần đầu gặp).
 *
 * @param array<int, array<string, mixed>> $rows
 * @return string[]
 */
function gtpt_collect_utilities_from_rows(array $rows, string $column = 'utilities'): array
{
    $map = [];
    foreach ($rows as $row) {
        foreach (gtpt_parse_utilities_string($row[$column] ?? '') as $utility) {
            $key = gtpt_normalize_utility_key($utility);
            if (!isset($map[$key])) {
                $map[$key] = $utility;
            }
        }
    }
    $list = array_values($map);
    natcasesort($list);
    return array_values($list);
}

/**
 * Lọc phòng có đủ mọi tiện ích đã chọn.
 *
 * @param string[] $selectedUtilities
 * @return array{0: string, 1: array<int, string>}
 */
function gtpt_motel_utilities_filter_sql(array $selectedUtilities, string $alias = 'm'): array
{
    $sql = '';
    $params = [];
    foreach ($selectedUtilities as $utility) {
        $utility = trim($utility);
        if ($utility === '') {
            continue;
        }
        $sql .= " AND {$alias}.utilities LIKE ?";
        $params[] = '%' . $utility . '%';
    }
    return [$sql, $params];
}

/**
 * Chỉ giữ tiện ích hợp lệ (có trong danh sách cho phép).
 *
 * @param mixed $raw
 * @param string[] $allowedUtilities
 * @return string[]
 */
function gtpt_sanitize_selected_utilities($raw, array $allowedUtilities): array
{
    if (!is_array($raw)) {
        return [];
    }
    $allowedMap = [];
    foreach ($allowedUtilities as $utility) {
        $allowedMap[gtpt_normalize_utility_key($utility)] = $utility;
    }
    $selected = [];
    foreach ($raw as $item) {
        if (!is_string($item)) {
            continue;
        }
        $key = gtpt_normalize_utility_key($item);
        if (isset($allowedMap[$key])) {
            $selected[$key] = $allowedMap[$key];
        }
    }
    return array_values($selected);
}

/**
 * Gộp tiện ích từ checkbox và ô nhập (không trùng, giữ chữ gốc).
 *
 * @param string[] ...$lists
 * @return string[]
 */
function gtpt_merge_unique_utilities(array ...$lists): array
{
    $map = [];
    foreach ($lists as $list) {
        foreach ($list as $utility) {
            $utility = trim((string) $utility);
            if ($utility === '') {
                continue;
            }
            $key = gtpt_normalize_utility_key($utility);
            if (!isset($map[$key])) {
                $map[$key] = $utility;
            }
        }
    }
    return array_values($map);
}

/** Tiện ích nhập tay (tối đa 8 mục, mỗi mục 80 ký tự). */
function gtpt_parse_utilities_input(?string $input, int $maxItems = 8): array
{
    $items = gtpt_parse_utilities_string($input);
    $out = [];
    foreach ($items as $item) {
        if (mb_strlen($item) > 80) {
            $item = mb_substr($item, 0, 80);
        }
        $out[] = $item;
        if (count($out) >= $maxItems) {
            break;
        }
    }
    return $out;
}

/**
 * Tham số GET giữ lại khi lọc (trừ page và các key loại trừ).
 *
 * @param string[] $exclude
 * @return array<string, mixed>
 */
function gtpt_search_preserve_query(array $exclude = ['page']): array
{
    $query = [];
    foreach ($_GET as $key => $value) {
        if (in_array($key, $exclude, true)) {
            continue;
        }
        $query[$key] = $value;
    }
    return $query;
}

/**
 * Xóa phòng trọ khỏi DB và file ảnh (nếu có).
 * @return array{ok: bool, error?: string}
 */
function gtpt_delete_motel(PDO $conn, int $motel_id): array
{
    if ($motel_id <= 0) {
        return ['ok' => false, 'error' => 'Phòng trọ không hợp lệ!'];
    }

    $result = $conn->prepare('SELECT images FROM motel WHERE id = ? LIMIT 1');
    $result->execute([$motel_id]);
    $row = $result->fetch();

    if (!$row) {
        return ['ok' => false, 'error' => 'Phòng trọ không tồn tại!'];
    }

    if (!empty($row['images'])) {
        $image_path = dirname(__DIR__) . '/uploads/' . $row['images'];
        if (is_file($image_path)) {
            @unlink($image_path);
        }
    }

    $del = $conn->prepare('DELETE FROM motel WHERE id = ?');
    $del->execute([$motel_id]);

    if ($del->rowCount() < 1) {
        return ['ok' => false, 'error' => 'Không thể xóa phòng trọ!'];
    }

    return ['ok' => true];
}
