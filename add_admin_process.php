<?php
global $db, $showErrors, $siteName, $siteShortName, $siteUrl;
session_start();
// Oturum kontrolü
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php"); // Giriş sayfasına yönlendir
    exit();
}
require_once "db_connection.php";
require_once "config.php";
require_once "admin_panel_header.php";
// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $email = $_POST["email"];
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT); // Şifre hashleme

    $query = "INSERT INTO admins (username, email, password) VALUES (?, ?, ?)";

    try {
        $stmt = $db->prepare($query);
        $stmt->execute([$username, $email, $password]);
        echo "Yönetici kaydedildi!";
    } catch (PDOException $e) {
        echo "Hata: " . $e->getMessage();
    }
}
?>
