<?php
global $db, $showErrors, $siteName, $siteShortName, $siteUrl, $config;
// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);
require_once "config.php";

session_start();
session_regenerate_id(true);

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
require_once "header.php";
?>
    <div class="px-4 py-5 my-5 text-center">
        <h4>Hoş Geldiniz, <?php echo $user["firstname"] . " " . $user["lastname"]; ?>!</h4>
        <div class="col-lg-6 mx-auto">
            <p>Profil bilgileriniz:</p>
            Ad Soyad: <?php echo $user["firstname"] . " " . $user["lastname"]; ?><br>
            E-posta: <?php echo $user["email"]; ?><br>
            <p><a href="logout.php">Çıkış Yap</a></p>
        </div>
    </div>

    <?php
} else {
    echo "Kullanıcı bilgileri alınamadı.";
}
?>
<?php
require_once "footer.php";
?>