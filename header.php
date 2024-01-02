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
global $db, $showErrors, $siteName, $siteShortName, $siteUrl, $config;
// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);
require_once "config.php";
// Oturum açıldıysa oturum değişkeni set edilir
$loggedIn = isset($_SESSION["user_id"]);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $siteName ?> - <?php echo $siteShortName ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@docsearch/css@3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <link href="./assets/dist/css/bootstrap.min.css" rel="stylesheet">
<body>
<header class="p-3 mb-3 border-bottom">
    <div class="container">
        <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-lg-start">
            <a href="<?php echo $siteUrl ?>" class="d-flex align-items-center mb-2 mb-lg-0 link-body-emphasis text-decoration-none">
                <?php echo $siteName ?> - <?php echo $siteShortName ?>
            </a>
            <ul class="nav col-12 col-lg-auto me-lg-auto mb-2 justify-content-center mb-md-0">
                <li><a href="<?php echo $siteUrl ?>" class="nav-link px-2 link-secondary"></a></li>
            </ul>
            <div class="dropdown text-end">
                <a href="<?php echo $siteUrl ?>" class="d-block link-body-emphasis text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="./assets/brand/default_pp.png" alt="mdo" width="32" height="32" class="rounded-circle">
                </a>
                <ul class="dropdown-menu text-small">
                    <?php if (!$loggedIn) { // Oturum açık değilse "Şifremi unuttum" linkini göster ?>
                        <li><a class="dropdown-item" href="user_login.php">Oturum aç</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="reset_password.php">Şifremi unuttum</a></li>
                    <?php } ?>

                    <?php if ($loggedIn) { // Oturum açıldıysa oturumu kapat butonunu göster ?>
                        <li><a class="dropdown-item" href="user_panel.php">Kullanıcı Paneli</a></li>
                        <li><a class="dropdown-item" href="user_profile_edit.php">Bilgileri güncelle</a></li>
                        <li><a class="dropdown-item" href="logout.php">Oturumu kapat</a></li>
                    <?php } ?>
                </ul>
            </div>
        </div>
    </div>
</header>

