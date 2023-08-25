<?php
session_start();

// Oturum kontrolü
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php"); // Giriş sayfasına yönlendir
    exit();
}
global $db;
require_once "db_connection.php"; // Veritabanı bağlantısı

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $classId = $_POST["class_id"];

    $deleteQuery = "DELETE FROM classes WHERE id = ?";
    $stmt = $db->prepare($deleteQuery);
    $stmt->execute([$classId]);

    header("Location: class_list.php"); // Sınıf listesine yönlendir
}
?>
