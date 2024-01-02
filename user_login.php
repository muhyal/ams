<?php
/**
 * @copyright Copyright (c) 2024, KUTBU
 *
 * @author Muhammed Yalçınkaya <muhammed.yalcinkaya@kutbu.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
global $showErrors, $siteName, $siteShortName, $siteUrl;
// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);
require_once "config.php";

// Oturum kontrolü
session_start();
session_regenerate_id(true);


// CSRF token oluşturma veya varsa alınması
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION['csrf_token'];
require_once "header.php";
?>
<div class="d-grid gap-2 d-sm-flex justify-content-sm-center">

<form class="form-signin" method="post" action="user_login_process.php">

    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

    <h1 class="h3 mb-3 font-weight-normal">Oturum aç</h1>

    <label for="username" class="sr-only">E-posta adresi ya da kullanıcı adı</label>
    <input type="text" id="identifier" name="identifier" placeholder="E-posta / Kullanıcı adı" class="form-control" required="" autofocus=""><br>

    <label for="password" class="sr-only">Şifreniz</label>
    <input type="password" id="password" name="password" placeholder="Şifre" class="form-control" required=""><br>

    <p><a href="reset_password.php">Şifremi unuttum</a><p>

    <button class="btn btn-lg btn-primary btn-block" type="submit">Oturum aç</button>
    <p class="mt-5 mb-3 text-muted">© <?php echo (new DateTime())->format('Y') ?>, <?php echo $siteName ?>.</p>
</form>
</div>
<?php
require_once "footer.php";
?>
