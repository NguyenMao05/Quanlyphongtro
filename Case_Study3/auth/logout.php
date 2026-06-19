<?php
// Trang đăng xuất

// Khởi động session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Xóa tất cả session
session_destroy();

// Chuyển hướng về trang chủ
header("Location: /Case_Study3/index.php");
exit();
?>
