<?php
// Chỉnh sửa phòng trọ

include '../config/connect.php';
require_once __DIR__ . '/../includes/motel_helpers.php';

gtpt_ensure_motel_is_visible_column($conn);

// Khởi động session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: /Case_Study3/auth/login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$is_admin = (int) ($_SESSION['role'] ?? 0) === 1;
$motel_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';
$success = '';

if ($motel_id <= 0) {
    header("Location: my_motels.php");
    exit();
}

try {
    // Lấy thông tin phòng trọ (chủ tin hoặc admin)
    if ($is_admin) {
        $sql = 'SELECT * FROM motel WHERE id = ?';
        $result = $conn->prepare($sql);
        $result->execute([$motel_id]);
    } else {
        $sql = 'SELECT * FROM motel WHERE id = ? AND user_id = ?';
        $result = $conn->prepare($sql);
        $result->execute([$motel_id, $user_id]);
    }

    if ($result->rowCount() == 0) {
        header('Location: ' . ($is_admin ? '/Case_Study3/admin/manage_motels.php' : 'my_motels.php'));
        exit();
    }
    
    $motel = $result->fetch();
    
    // Lấy danh sách quận huyện
    $sql_districts = "SELECT * FROM districts";
    $result_districts = $conn->query($sql_districts);
    $districts = $result_districts->fetchAll();
    
    // Lấy danh sách loại phòng
    $sql_categories = "SELECT * FROM category";
    $result_categories = $conn->query($sql_categories);
    $categories = $result_categories->fetchAll();
    
} catch (PDOException $e) {
    die("Lỗi: " . $e->getMessage());
}

// Xử lý form cập nhật phòng trọ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (int)$_POST['price'] ?? 0;
    $area = (int)$_POST['area'] ?? 0;
    $address = trim($_POST['address'] ?? '');
    $latlng = trim($_POST['latlng'] ?? '');
    $district_id = (int)$_POST['district_id'] ?? 0;
    $category_id = (int)$_POST['category_id'] ?? 0;
    $utilities = trim($_POST['utilities'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $images = $motel['images']; // Giữ ảnh cũ
    $is_approved = (int) ($motel['approve'] ?? 0) === 1;
    if ($is_approved) {
        $is_visible = isset($_POST['is_visible']) ? 1 : 0;
    } else {
        $is_visible = (int) ($motel['is_visible'] ?? 1);
    }
    
    // Kiểm tra dữ liệu rỗng
    if (empty($title) || empty($price) || empty($area) || empty($address) || $district_id == 0 || $category_id == 0) {
        $error = 'Vui lòng điền đầy đủ thông tin bắt buộc!';
    }
    else {
        try {
            // Xử lý upload ảnh mới nếu có
            if (!empty($_FILES['images']['name'])) {
                $upload_dir = '../uploads/';
                
                // Tạo folder uploads nếu chưa tồn tại
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_name = $_FILES['images']['name'];
                $file_tmp = $_FILES['images']['tmp_name'];
                $file_error = $_FILES['images']['error'];
                $file_size = $_FILES['images']['size'];
                
                // Kiểm tra lỗi upload
                if ($file_error === 0) {
                    // Kiểm tra loại file (chỉ cho phép ảnh)
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    $file_type = mime_content_type($file_tmp);
                    
                    // Kiểm tra kích thước file (tối đa 5MB)
                    if ($file_size > 5 * 1024 * 1024) {
                        $error = 'Kích thước ảnh không được vượt quá 5MB!';
                    } elseif (!in_array($file_type, $allowed_types)) {
                        $error = 'Chỉ hỗ trợ các định dạng: JPG, PNG, GIF, WebP!';
                    } else {
                        // Xóa ảnh cũ nếu tồn tại
                        if (!empty($motel['images']) && file_exists($upload_dir . $motel['images'])) {
                            unlink($upload_dir . $motel['images']);
                        }
                        
                        // Tạo tên file duy nhất
                        $new_file_name = time() . '_' . uniqid() . '.' . pathinfo($file_name, PATHINFO_EXTENSION);
                        $upload_path = $upload_dir . $new_file_name;
                        
                        // Chuyển file
                        if (move_uploaded_file($file_tmp, $upload_path)) {
                            $images = $new_file_name;
                        } else {
                            $error = 'Lỗi: Không thể upload ảnh!';
                        }
                    }
                } else {
                    $error = 'Lỗi khi upload ảnh!';
                }
            }
            
            // Nếu không có lỗi từ upload, cập nhật phòng trọ vào database
            if (empty($error)) {
                // Cập nhật phòng trọ
                $sql = 'UPDATE motel SET title = ?, description = ?, price = ?, area = ?, address = ?,
                        latlng = ?, category_id = ?, district_id = ?, utilities = ?, phone = ?, images = ?, is_visible = ? WHERE id = ?';
                $result = $conn->prepare($sql);
                $result->execute([$title, $description, $price, $area, $address, $latlng, $category_id, $district_id, $utilities, $phone, $images, $is_visible, $motel_id]);
                
                $success = 'Cập nhật phòng trọ thành công!';
                
                // Lấy lại thông tin
                $sql = "SELECT * FROM motel WHERE id = ?";
                $result = $conn->prepare($sql);
                $result->execute([$motel_id]);
                $motel = $result->fetch();
            }
        } catch (PDOException $e) {
            $error = 'Lỗi: ' . $e->getMessage();
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="form-page-shell">
    <div class="form-page-head">
        <h1><i class="fa-solid fa-pen-to-square text-warning"></i> Chỉnh sửa phòng trọ</h1>
        <p class="text-muted mb-0 small">
            <?php echo $is_admin ? 'Admin — chỉnh sửa tin đăng' : 'Cập nhật thông tin tin đăng của bạn'; ?>
        </p>
    </div>
    <div class="form-page-card">
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fa-solid fa-circle-exclamation"></i> <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fa-solid fa-circle-check"></i> <?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <!-- Tiêu đề -->
                        <div class="mb-3">
                            <label for="title" class="form-label fw-bold">Tiêu đề phòng <span class="text-danger">*</span></label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="title" 
                                name="title" 
                                placeholder="VD: Phòng đơn gần ĐH Vinh"
                                value="<?php echo htmlspecialchars($motel['title']); ?>"
                                required
                            >
                        </div>
                        
                        <!-- Mô tả -->
                        <div class="mb-3">
                            <label for="description" class="form-label fw-bold">Mô tả chi tiết</label>
                            <textarea 
                                class="form-control" 
                                id="description" 
                                name="description" 
                                rows="5"
                                placeholder="Mô tả chi tiết về phòng trọ..."
                            ><?php echo htmlspecialchars($motel['description']); ?></textarea>
                        </div>
                        
                        <!-- Ảnh phòng -->
                        <div class="mb-3">
                            <label for="images" class="form-label fw-bold">Ảnh phòng</label>
                            
                            <!-- Hiển thị ảnh hiện tại -->
                            <?php if (!empty($motel['images']) && file_exists('../uploads/' . $motel['images'])): ?>
                                <div class="mb-3">
                                    <img src="../uploads/<?php echo htmlspecialchars($motel['images']); ?>" alt="<?php echo htmlspecialchars($motel['title']); ?>" class="img-thumbnail">
                                    <p class="text-muted small">Ảnh hiện tại</p>
                                </div>
                            <?php endif; ?>
                            
                            <input 
                                type="file" 
                                class="form-control" 
                                id="images" 
                                name="images"
                                accept="image/*"
                            >
                            <small class="text-muted">Chọn ảnh JPG, PNG, GIF hoặc WebP (tối đa 5MB) để thay đổi</small>
                            <div id="preview"></div>
                        </div>
                        
                        <!-- Giá -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="price" class="form-label fw-bold">Giá (đ/tháng) <span class="text-danger">*</span></label>
                                <input 
                                    type="number" 
                                    class="form-control" 
                                    id="price" 
                                    name="price" 
                                    placeholder="3000000"
                                    value="<?php echo htmlspecialchars($motel['price']); ?>"
                                    required
                                >
                            </div>
                            
                            <!-- Diện tích -->
                            <div class="col-md-6 mb-3">
                                <label for="area" class="form-label fw-bold">Diện tích (m²) <span class="text-danger">*</span></label>
                                <input 
                                    type="number" 
                                    class="form-control" 
                                    id="area" 
                                    name="area" 
                                    placeholder="25"
                                    value="<?php echo htmlspecialchars($motel['area']); ?>"
                                    required
                                >
                            </div>
                        </div>
                        
                        <!-- Địa chỉ -->
                        <div class="mb-3">
                            <label for="address" class="form-label fw-bold">Địa chỉ <span class="text-danger">*</span></label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="address" 
                                name="address" 
                                placeholder="123 Đ. Lê Lợi, Hoàn Kiếm, Hà Nội"
                                value="<?php echo htmlspecialchars($motel['address']); ?>"
                                required
                            >
                        </div>
                        
                        <!-- Tọa độ GPS -->
                        <div class="mb-3">
                            <label for="latlng" class="form-label fw-bold">Tọa độ GPS (vĩ độ, kinh độ)</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="latlng" 
                                name="latlng" 
                                placeholder="10.762622, 106.660172"
                                value="<?php echo htmlspecialchars($motel['latlng']); ?>"
                            >
                            <small class="text-muted">Lấy trên Google Maps → nhấn chuột phải vào vị trí → copy tọa độ. VD: 10.762622, 106.660172</small>
                        </div>
                        
                        <!-- Quận huyện -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="district_id" class="form-label fw-bold">Khu vực <span class="text-danger">*</span></label>
                                <select name="district_id" id="district_id" class="form-select" required>
                                    <option value="">-- Chọn khu vực --</option>
                                    <?php foreach ($districts as $dist): ?>
                                        <option value="<?php echo $dist['ID']; ?>" <?php echo $motel['district_id'] == $dist['ID'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($dist['Name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Loại phòng -->
                            <div class="col-md-6 mb-3">
                                <label for="category_id" class="form-label fw-bold">Loại phòng <span class="text-danger">*</span></label>
                                <select name="category_id" id="category_id" class="form-select" required>
                                    <option value="">-- Chọn loại phòng --</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['ID']; ?>" <?php echo $motel['category_id'] == $cat['ID'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['Name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Tiện ích -->
                        <div class="mb-3">
                            <label for="utilities" class="form-label fw-bold">Tiện ích (cách nhau bằng dấu phẩy)</label>
                            <textarea 
                                class="form-control" 
                                id="utilities" 
                                name="utilities" 
                                rows="3"
                                placeholder="VD: Máy lạnh, Wifi, Bếp, Nóng lạnh"
                            ><?php echo htmlspecialchars($motel['utilities']); ?></textarea>
                        </div>
                        
                        <!-- Số điện thoại -->
                        <div class="mb-3">
                            <label for="phone" class="form-label fw-bold">Số điện thoại liên hệ</label>
                            <input 
                                type="tel" 
                                class="form-control" 
                                id="phone" 
                                name="phone" 
                                placeholder="0987654321"
                                value="<?php echo htmlspecialchars($motel['phone']); ?>"
                            >
                        </div>
                        
                        <?php
                        $is_approved = (int) ($motel['approve'] ?? 0) === 1;
                        $is_visible_on = (int) ($motel['is_visible'] ?? 1) === 1;
                        ?>
                        <!-- Trạng thái duyệt & hiển thị -->
                        <div class="mb-3 p-3 bg-light rounded">
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                                <strong>Trạng thái duyệt:</strong>
                                <?php if ($is_approved): ?>
                                    <span class="badge bg-success">Đã duyệt</span>
                                <?php elseif ((int) ($motel['approve'] ?? 0) === 0): ?>
                                    <span class="badge bg-warning">Chờ duyệt</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Bị từ chối</span>
                                <?php endif; ?>
                                <?php if ($is_approved): ?>
                                    <?php if ($is_visible_on): ?>
                                        <span class="badge bg-primary">Đang hiển thị</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Đang ẩn</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>

                            <div class="form-check form-switch">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    role="switch"
                                    id="is_visible"
                                    name="is_visible"
                                    value="1"
                                    <?php echo $is_visible_on ? 'checked' : ''; ?>
                                    <?php echo $is_approved ? '' : 'disabled'; ?>
                                >
                                <label class="form-check-label fw-bold" for="is_visible">
                                    Hiển thị tin đăng trên trang chủ &amp; tìm kiếm
                                </label>
                            </div>
                            <?php if ($is_approved): ?>
                                <p class="small text-muted mb-0 mt-2">
                                    Tắt công tắc để tạm ẩn phòng khỏi người xem.
                                </p>
                            <?php else: ?>
                                <p class="small text-muted mb-0 mt-2">
                                    Sau khi admin duyệt tin, bạn có thể bật hoặc tắt hiển thị tại đây.
                                </p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-warning flex-grow-1">
                                <i class="fa-solid fa-pen-to-square"></i> Cập nhật
                            </button>
                            <a href="<?php echo $is_admin ? '/Case_Study3/admin/manage_motels.php' : 'my_motels.php'; ?>" class="btn btn-secondary flex-grow-1">
                                <i class="fa-solid fa-circle-xmark"></i> Hủy
                            </a>
                        </div>
                    </form>
    </div>
</div>

<script>
// Preview ảnh khi chọn
document.getElementById('images').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('preview');
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(event) {
            preview.innerHTML = '<img src="' + event.target.result + '" class="img-thumbnail" alt="Preview">';
        };
        reader.readAsDataURL(file);
    } else {
        preview.innerHTML = '';
    }
});
</script>

<?php include '../includes/footer.php'; ?>
