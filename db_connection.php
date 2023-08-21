<?php
$host = "localhost"; // Veritabanı sunucusu
$db_name = "oim"; // Veritabanı adı
$username = "oim"; // Veritabanı kullanıcı adı
$password = "oim"; // Veritabanı şifresi

try {
    $db = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}
?>
