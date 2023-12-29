<?php
global $showErrors, $siteName, $siteShortName, $siteUrl;
// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);
require_once "config.php";

// Oturum kontrolü
session_start();
session_regenerate_id(true);

if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php"); // Giriş sayfasına yönlendir
    exit();
}

// CSRF token oluşturma veya varsa alınması
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION['csrf_token'];
?>
<?php
require_once "admin_login_header.php";
?>
<main class="form-signin w-100 m-auto">
    <form method="post" action="admin_login_process.php">

        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

        <img class="mb-4" src="./assets/brand/bootstrap-logo.svg" alt="" width="72" height="57">
        <h1 class="h3 mb-3 fw-normal">Oturum aç</h1>

        <div class="form-floating">
            <input type="text" class="form-control" id="identifier" name="identifier" placeholder="@doremuzikakademi.com">
            <label for="floatingInput">E-posta / Kullanıcı ad</label>
        </div>
        <div class="form-floating">
            <input type="password" class="form-control" name="password" id="password" placeholder="Şifre">
            <label for="floatingPassword">Şifre</label>
            <p><a href="admin_reset_password.php">Şifremi unuttum</a></p>
        </div>

        <button class="btn btn-primary w-100 py-2" type="submit">Sign in</button>
        <p class="mt-5 mb-3 text-body-secondary">&copy; <?php echo (new DateTime())->format('Y') ?>, <?php echo $siteName ?>.</p>
    </form>
</main>
<?php
require_once "footer.php";
?>
