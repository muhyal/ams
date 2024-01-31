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
global $db, $showErrors, $siteName, $siteShortName, $siteUrl, $config, $oimVersion, $siteHeroDescription;
// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);
require_once(__DIR__ . '/../../config/db_connection.php');
require_once(__DIR__ . '/../../config/config.php');
require_once(__DIR__ . '/../../src/functions.php');
// Oturum açıldıysa oturum değişkeni set edilir
$loggedIn = isset($_SESSION["user_id"]);
?>
<!doctype html>
<html lang="en" data-bs-theme="auto">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $siteName ?> - <?php echo $siteShortName ?></title>
    <meta name="description" content="<?php echo $siteHeroDescription ?>">
    <meta name="robots" content="noindex, nofollow" />
    <meta name="author" content="Muhammed Yalçınkaya">
    <meta name="generator" content="<?php echo $siteShortName ?> - <?php echo $oimVersion ?>">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="../../assets/js/color-modes.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <!-- SignaturePad kütüphanesi CDN üzerinden eklendi -->
    <script src="https://unpkg.com/signature_pad"></script>
    <style>
        .grecaptcha-badge {
            visibility: hidden !important;
        }

        .form-signin {
            max-width: 330px;
            padding: 1rem;
        }

        .form-signin .form-floating:focus-within {
            z-index: 2;
        }

        .form-signin input[type="email"] {
            margin-bottom: -1px;
            border-bottom-right-radius: 0;
            border-bottom-left-radius: 0;
        }

        .form-signin input[type="password"] {
            margin-bottom: 10px;
            border-top-left-radius: 0;
            border-top-right-radius: 0;
        }

        .bd-placeholder-img {
            font-size: 1.125rem;
            text-anchor: middle;
            -webkit-user-select: none;
            -moz-user-select: none;
            user-select: none;
        }

        @media (min-width: 768px) {
            .bd-placeholder-img-lg {
                font-size: 3.5rem;
            }
        }

        .b-example-divider {
            width: 100%;
            height: 3rem;
            background-color: rgba(0, 0, 0, .1);
            border: solid rgba(0, 0, 0, .15);
            border-width: 1px 0;
            box-shadow: inset 0 .5em 1.5em rgba(0, 0, 0, .1), inset 0 .125em .5em rgba(0, 0, 0, .15);
        }

        .b-example-vr {
            flex-shrink: 0;
            width: 1.5rem;
            height: 100vh;
        }

        .bi {
            vertical-align: -.125em;
            fill: currentColor;
        }

        .nav-scroller {
            position: relative;
            z-index: 2;
            height: 2.75rem;
            overflow-y: hidden;
        }

        .nav-scroller .nav {
            display: flex;
            flex-wrap: nowrap;
            padding-bottom: 1rem;
            margin-top: -1px;
            overflow-x: auto;
            text-align: center;
            white-space: nowrap;
            -webkit-overflow-scrolling: touch;
        }

        .btn-bd-primary {
            --bd-violet-bg: #712cf9;
            --bd-violet-rgb: 112.520718, 44.062154, 249.437846;

            --bs-btn-font-weight: 600;
            --bs-btn-color: var(--bs-white);
            --bs-btn-bg: var(--bd-violet-bg);
            --bs-btn-border-color: var(--bd-violet-bg);
            --bs-btn-hover-color: var(--bs-white);
            --bs-btn-hover-bg: #6528e0;
            --bs-btn-hover-border-color: #6528e0;
            --bs-btn-focus-shadow-rgb: var(--bd-violet-rgb);
            --bs-btn-active-color: var(--bs-btn-hover-color);
            --bs-btn-active-bg: #5a23c8;
            --bs-btn-active-border-color: #5a23c8;
        }

        .bd-mode-toggle {
            z-index: 1500;
        }

        .bd-mode-toggle .dropdown-menu .active .bi {
            display: block !important;
        }
    </style>
<body>
<header class="p-3 mb-3 border-bottom">
    <div class="container">
        <div class="d-flex flex-wrap align-items-center justify-content-between justify-content-lg-start">
            <a href="<?php echo $siteUrl ?>" class="d-flex align-items-center mb-1 mt-1 mb-lg-0 link-body-emphasis text-decoration-none">
                <img id="logo-header" src="/assets/brand/default_logo_dark.png" alt="<?php echo $siteName ?> - <?php echo $siteShortName ?>" title="<?php echo $siteName ?> - <?php echo $siteShortName ?>" width="15%" height="15%">
            </a>

            <ul class="nav col-12 col-lg-auto me-lg-auto mb-2 justify-content-center mb-md-0">
                <li><a href="<?php echo $siteUrl ?>" class="nav-link px-2 link-secondary"></a></li>
            </ul>


            <div class="dropdown text-end ml-auto">
                <button class="btn btn-link text-decoration-none dropdown-toggle text-dark-emphasis" type="button" id="languageDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-globe"></i>
                </button>
                <ul class="dropdown-menu" aria-labelledby="languageDropdown">
                    <li><a class="dropdown-item" href="?lang=tr">Türkçe</a></li>
                    <li><a class="dropdown-item" href="?lang=en">English</a></li>
                    <!-- Diğer dil seçenekleri buraya eklenebilir -->
                </ul>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    var selectedLanguage = "<?php echo $selectedLanguage; ?>";
                    var languageDropdown = document.getElementById('languageDropdown');

                    languageDropdown.addEventListener('show.bs.dropdown', function () {
                        var dropdownItems = this.nextElementSibling.querySelectorAll('.dropdown-item');

                        dropdownItems.forEach(function(item) {
                            item.classList.remove('active');
                            if (item.getAttribute('href') === '?lang=' + selectedLanguage) {
                                item.classList.add('active');
                            }
                        });
                    });
                });
            </script>

            <div class="dropdown text-end ml-auto">
                <?php
                // Kullanıcının oturum açıp açmadığını kontrol et
                if ($loggedIn) {
                    // Kullanıcının profiline ait profil fotoğrafını veritabanından al
                    $user_id = $_SESSION['user_id'];
                    $profilePhotoPath = getProfilePhotoPathFromDB($user_id);

                    if ($profilePhotoPath) {
                        // Profil fotoğrafı varsa göster
                        echo '<a href="' . $siteUrl . '" class="d-block link-body-emphasis text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                <img src="' . $profilePhotoPath . '" alt="Profil Fotoğrafı" width="32" height="32" class="rounded-circle">
            </a>';
                    } else {
                        // Profil fotoğrafı yoksa varsayılan fotoğrafı göster
                        echo '<a href="' . $siteUrl . '" class="d-block link-body-emphasis text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                <img src="../../assets/brand/default_pp.png" alt="Profil Fotoğrafı" width="32" height="32" class="rounded-circle">
            </a>';
                    }
                } else {
                    // Kullanıcı oturum açmamışsa varsayılan profil fotoğrafını göster
                    echo '<a href="' . $siteUrl . '" class="d-block link-body-emphasis text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                <img src="../../assets/brand/default_pp.png" alt="Profil Fotoğrafı" width="32" height="32" class="rounded-circle">
            </a>';
                }
                // Kullanıcının profiline ait profil fotoğrafını veritabanından al
                function getProfilePhotoPathFromDB($user_id) {
                    global $db; // Global değişkeni kullanarak $db'yi fonksiyon içinde kullanabiliriz.

                    // SQL sorgusunu hazırla
                    $query = "SELECT profile_photo FROM users WHERE id = :user_id";

                    // Sorguyu hazırla ve bağlantı üzerinden çalıştır
                    $statement = $db->prepare($query);
                    $statement->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                    $statement->execute();
                    $profile_photo = $statement->fetchColumn();

                    // Eğer kullanıcının profil fotoğrafı varsa geri döndür
                    return isset($profile_photo) ? $profile_photo : null;
                }
                ?>

                <ul class="dropdown-menu text-small">
                    <?php if (!$loggedIn) { // Oturum açık değilse "Şifremi unuttum" linkini göster ?>
                        <li><a class="dropdown-item" href="/login.php">Oturum aç</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/reset_password.php">Şifremi unuttum</a></li>
                    <?php } ?>

                    <?php if ($loggedIn) { // Oturum açıldıysa ?>
                        <?php if ($_SESSION['user_type'] == 1) { // Eğer kullanıcı yönetici ise ?>
                            <li><a class="dropdown-item" href="/admin/panel.php">Yönetici Paneli</a></li>
                        <?php } ?>
                        <li><a class="dropdown-item" href="/user/panel.php">Kullanıcı Paneli</a></li>
                        <li><a class="dropdown-item" href="/user/profile_edit.php">Bilgileri güncelle</a></li>
                        <li><a class="dropdown-item" href="/logout.php">Oturumu kapat</a></li>
                    <?php } ?>
                </ul>

            </div>

        </div>
    </div>
</header>
<svg xmlns="http://www.w3.org/2000/svg" class="d-none">
    <symbol id="check2" viewBox="0 0 16 16">
        <path d="M13.854 3.646a.5.5 0 0 1 0 .708l-7 7a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6.5 10.293l6.646-6.647a.5.5 0 0 1 .708 0z"/>
    </symbol>
    <symbol id="circle-half" viewBox="0 0 16 16">
        <path d="M8 15A7 7 0 1 0 8 1v14zm0 1A8 8 0 1 1 8 0a8 8 0 0 1 0 16z"/>
    </symbol>
    <symbol id="moon-stars-fill" viewBox="0 0 16 16">
        <path d="M6 .278a.768.768 0 0 1 .08.858 7.208 7.208 0 0 0-.878 3.46c0 4.021 3.278 7.277 7.318 7.277.527 0 1.04-.055 1.533-.16a.787.787 0 0 1 .81.316.733.733 0 0 1-.031.893A8.349 8.349 0 0 1 8.344 16C3.734 16 0 12.286 0 7.71 0 4.266 2.114 1.312 5.124.06A.752.752 0 0 1 6 .278z"/>
        <path d="M10.794 3.148a.217.217 0 0 1 .412 0l.387 1.162c.173.518.579.924 1.097 1.097l1.162.387a.217.217 0 0 1 0 .412l-1.162.387a1.734 1.734 0 0 0-1.097 1.097l-.387 1.162a.217.217 0 0 1-.412 0l-.387-1.162A1.734 1.734 0 0 0 9.31 6.593l-1.162-.387a.217.217 0 0 1 0-.412l1.162-.387a1.734 1.734 0 0 0 1.097-1.097l.387-1.162zM13.863.099a.145.145 0 0 1 .274 0l.258.774c.115.346.386.617.732.732l.774.258a.145.145 0 0 1 0 .274l-.774.258a1.156 1.156 0 0 0-.732.732l-.258.774a.145.145 0 0 1-.274 0l-.258-.774a1.156 1.156 0 0 0-.732-.732l-.774-.258a.145.145 0 0 1 0-.274l.774-.258c.346-.115.617-.386.732-.732L13.863.1z"/>
    </symbol>
    <symbol id="sun-fill" viewBox="0 0 16 16">
        <path d="M8 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM8 0a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 0zm0 13a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 13zm8-5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2a.5.5 0 0 1 .5.5zM3 8a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2A.5.5 0 0 1 3 8zm10.657-5.657a.5.5 0 0 1 0 .707l-1.414 1.415a.5.5 0 1 1-.707-.708l1.414-1.414a.5.5 0 0 1 .707 0zm-9.193 9.193a.5.5 0 0 1 0 .707L3.05 13.657a.5.5 0 0 1-.707-.707l1.414-1.414a.5.5 0 0 1 .707 0zm9.193 2.121a.5.5 0 0 1-.707 0l-1.414-1.414a.5.5 0 0 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .707zM4.464 4.465a.5.5 0 0 1-.707 0L2.343 3.05a.5.5 0 1 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .708z"/>
    </symbol>
</svg>

<  <div class="dropdown position-fixed bottom-0 end-0 mb-3 me-3 bd-mode-toggle">
    <button class="btn btn-bd-primary py-2 dropdown-toggle d-flex align-items-center"
            id="bd-theme"
            type="button"
            aria-expanded="false"
            data-bs-toggle="dropdown"
            aria-label="Toggle theme (auto)">
        <svg class="bi my-1 theme-icon-active" width="1em" height="1em"><use href="#circle-half"></use></svg>
        <span class="visually-hidden" id="bd-theme-text"><?= translate('theme_mode', $selectedLanguage) ?></span>
    </button>
    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="bd-theme-text">
        <li>
            <button type="button" class="dropdown-item d-flex align-items-center" data-bs-theme-value="light" aria-pressed="false">
                <svg class="bi me-2 opacity-50 theme-icon" width="1em" height="1em"><use href="#sun-fill"></use></svg>
                <?= translate('light', $selectedLanguage) ?>
                <svg class="bi ms-auto d-none" width="1em" height="1em"><use href="#check2"></use></svg>
            </button>
        </li>
        <li>
            <button type="button" class="dropdown-item d-flex align-items-center" data-bs-theme-value="dark" aria-pressed="false">
                <svg class="bi me-2 opacity-50 theme-icon" width="1em" height="1em"><use href="#moon-stars-fill"></use></svg>
                <?= translate('dark', $selectedLanguage) ?>
                <svg class="bi ms-auto d-none" width="1em" height="1em"><use href="#check2"></use></svg>
            </button>
        </li>
        <li>
            <button type="button" class="dropdown-item d-flex align-items-center active" data-bs-theme-value="auto" aria-pressed="true">
                <svg class="bi me-2 opacity-50 theme-icon" width="1em" height="1em"><use href="#circle-half"></use></svg>
                <?= translate('auto', $selectedLanguage) ?>
                <svg class="bi ms-auto d-none" width="1em" height="1em"><use href="#check2"></use></svg>
            </button>
        </li>
    </ul>
</div>

