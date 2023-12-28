<?php
global $db;
session_start();
require_once "db_connection.php"; // Veritabanı bağlantısı

if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php"); // Giriş sayfasına yönlendir
    exit();
}

if (isset($_GET["id"]) && is_numeric($_GET["id"])) {
    $user_id = $_GET["id"];

    // Silinmiş kullanıcıyı geri al
    $query = "UPDATE users SET deleted_at = NULL WHERE id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        // Başarıyla geri alındıysa kullanıcıları listeleme sayfasına yönlendir
        header("Location: user_list.php");
        exit();
    } else {
        // Hata durumunda kullanıcıyı listeleme sayfasına yönlendir
        echo "Kullanıcı geri alınamadı.";
        header("Location: user_list.php");
        exit();
    }
} else {
    // Geçersiz veya eksik kullanıcı kimliği durumunda kullanıcıları listeleme sayfasına yönlendir
    header("Location: user_list.php");
    exit();
}
?>
