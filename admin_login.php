<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once "config.php";
global $siteName, $siteShortName, $siteUrl;
require_once "admin_login_header.php";
?>

   <form class="form-signin" method="post" action="admin_login_process.php">
      <h1 class="h3 mb-3 font-weight-normal">Oturum aç</h1>

        <label for="username" class="sr-only">Kullanıcı adı</label>
        <input type="text" id="username" name="username" placeholder="Kullanıcı adı" class="form-control" required="" autofocus=""><br>

        <label for="password" class="sr-only">Şifre</label>
        <input type="password" id="password" name="password" placeholder="Şifre" class="form-control" required=""><br>

       <p><a href="admin_reset_password.php">Şifremi unuttum</a></p>
        <button class="btn btn-lg btn-primary btn-block" type="submit">Oturum aç</button>
   <p class="mt-5 mb-3 text-muted">© <?php echo (new DateTime())->format('Y') ?>, <?php echo $siteName ?>.</p>
    </form>

<?php
require_once "footer.php";
?>
