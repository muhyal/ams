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
    $username = $_POST["username"];
    $password = $_POST["password"];

    $query = "SELECT * FROM admins WHERE username = ?";

    try {
        $stmt = $db->prepare($query);
        $stmt->execute([$username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password, $admin["password"])) {
            $_SESSION["admin_id"] = $admin["id"];
            $_SESSION["admin_username"] = $admin["username"];
            $_SESSION["admin_role"] = $admin["role"]; // Kullanıcının rolünü ekleyin
            header("Location: admin_panel.php"); // Yönlendirme admin paneline
            exit();
        } else {
            echo '<div class="alert alert-danger" role="alert">
Hatalı e-posta adresi ya da şifre girdiniz.</div>';
        }
    } catch (PDOException $e) {
        echo "Hata: " . $e->getMessage();
    }
}
?>
        </div>
    </div>
</div>
<?php
require_once "footer.php";
?>