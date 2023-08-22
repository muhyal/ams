<?php
global $db;
require_once "db_connection.php";

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
