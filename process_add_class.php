<?php
session_start();

// Oturum kontrolü
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php"); // Giriş sayfasına yönlendir
    exit();
}
global $db;
require_once "db_connection.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $className = $_POST['class_name'];
    $classCode = $_POST['class_code'];
    $classDescription = $_POST['class_description']; // Formdan gelen class_description değeri

    // Sınıfı veritabanına ekle
    $insertQuery = "INSERT INTO classes (class_name, class_code, class_description) VALUES (?, ?, ?)";
    $insertStmt = $db->prepare($insertQuery);
    $insertStmt->execute([$className, $classCode, $classDescription]); // class_description değeri ekleniyor

    header("Location: class_list.php");
    exit;
}
?>
