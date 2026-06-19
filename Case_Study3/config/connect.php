<?php
// Kết nối database bằng PDO

$host = 'localhost';
$dbname = 'gtpt';
$username = 'root';
$password = '';
// $conn = new PDO('mysql:host=localhost;dbname=quanlyhoc_db', 'root','');
//         $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;",$username,$password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Lỗi kết nối database: " . $e->getMessage());
}
?>
 