<?php
// Kiểm tra đăng nhập admin (include sau connect.php)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || (int)($_SESSION['role'] ?? 0) !== 1) {
    header('Location: /Case_Study3/auth/login.php');
    exit();
}
