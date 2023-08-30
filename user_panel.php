<?php
global $db;
session_start();
require_once "db_connection.php"; // Veritabanı bağlantısı

// Oturum kontrolü yaparak giriş yapılmış mı diye kontrol ediyoruz
if (!isset($_SESSION["user_id"])) {
    header("Location: user_login.php"); // Kullanıcı giriş sayfasına yönlendirme
    exit();
}

// Kullanıcının bilgilerini veritabanından alıyoruz
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION["user_id"]]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) { // Kullanıcı bilgileri doğru şekilde alındı mı kontrol ediyoruz
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Kullanıcı Paneli</title>
    </head>
    <body>
    <h1>Hoş Geldiniz, <?php echo $user["firstname"] . " " . $user["lastname"]; ?>!</h1>
    <p>Profil bilgileriniz:</p>
    <ul>
        <li>Ad Soyad: <?php echo $user["firstname"] . " " . $user["lastname"]; ?></li>
        <li>E-posta: <?php echo $user["email"]; ?></li>
    </ul>

    <a href="logout.php">Çıkış Yap</a> <!-- Çıkış yapma bağlantısı -->
    </body>
    </html>
    <?php
} else {
    echo "Kullanıcı bilgileri alınamadı.";
}
?>
