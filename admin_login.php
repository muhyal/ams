<?php
global $showErrors, $siteName, $siteShortName, $siteUrl;

// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);

require_once "config.php";
require_once "admin_login_header.php";
?>

<form class="form-signin" method="post" action="admin_login_process.php">
    <!-- CSRF Token -->
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

    <h1 class="h3 mb-3 font-weight-normal">Oturum aç</h1>

    <label for="identifier" class="sr-only">Kullanıcı adı veya E-posta</label>
    <input type="text" id="identifier" name="identifier" placeholder="Kullanıcı adı veya E-posta" class="form-control" required autofocus=""><br>


    <label for="password" class="sr-only">Şifre</label>
    <input type="password" id="password" name="password" placeholder="Şifre" class="form-control" required=""><br>

    <p><a href="admin_reset_password.php">Şifremi unuttum</a></p>

    <button class="btn btn-lg btn-primary btn-block" type="submit">Oturum aç</button>

    <p class="mt-5 mb-3 text-muted">© <?php echo (new DateTime())->format('Y') ?>, <?php echo $siteName ?>.</p>
</form>


<?php
require_once "footer.php";
?>
