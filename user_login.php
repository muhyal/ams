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
global $siteHeroDescription, $showErrors, $siteName, $siteShortName, $siteUrl;
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
<main class="form-signin w-100 m-auto">
        <form method="post" action="user_login_process.php">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <img class="mb-4" src="./assets/brand/default_logo.png" alt="<?php echo $siteName ?>" title="<?php echo $siteName ?>" width="100" height="100">
            <h1 class="h3 mb-3 fw-normal">Kullanıcı Paneli</h1>
            <div class="form-floating">
                <input type="text" class="form-control" id="identifier" name="identifier" placeholder="E-posta / Kullanıcı adı" autofocus="" required>
                <label for="floatingInput">E-posta / Kullanıcı adı</label>
            </div>
            <div class="form-floating">
                <input type="password" class="form-control" name="password" id="password" placeholder="Şifre" required>
                <label for="floatingPassword">Şifre</label>
                <div class="form-group mt-3">
                    <a class="text-light-emphasis text-decoration-none" href="reset_password.php">Şifremi unuttum</a>
                </div>
            </div>
            <div class="form-group mt-3">
                <button class="btn btn-primary w-100 py-2" type="submit">
                    <i class="fas fa-sign-in-alt"></i> Oturum aç
                </button>
                <a href="<?php echo $siteUrl ?>" class="btn btn-secondary w-100 py-2 mt-2">
                    <i class="fas fa-home"></i> <?php echo $siteName ?> - <?php echo $siteShortName ?>
                </a>
            </div>
        </form>
    </main>
</form>
</div>
<?php
require_once "footer.php";
?>
