<?php
global $db;
session_start();
require_once "db_connection.php"; // Veritabanı bağlantısı

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];

    // Kullanıcıyı veritabanında kontrol et
    $query = "SELECT * FROM users WHERE email = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user["password"])) {
        // Kullanıcı girişi başarılı, oturum başlat
        $_SESSION["user_id"] = $user["id"];
        header("Location: user_panel.php"); // Kullanıcı paneline yönlendirme
        exit();
    } else {
        echo "Hatalı e-posta veya şifre.";
    }
}
?>
