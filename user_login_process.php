<?php
global $db, $siteVerifyDescription, $showErrors, $siteName, $siteShortName, $siteUrl, $config;
// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);
require_once "config.php";
require_once "db_connection.php"; // Veritabanı bağlantısı
require_once "user_login_header.php";
?>

 <div class="px-4 py-5 my-5 text-center">
        <div class="col-lg-6 mx-auto">
<div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
    <?php
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
        echo '<div class="alert alert-danger" role="alert">
Hatalı e-posta adresi ya da şifre girdiniz.</div>';
    }
}
?>
</div>
        </div>
 </div>
<?php
require_once "footer.php";
?>