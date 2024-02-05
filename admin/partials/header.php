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
global $db, $showErrors, $siteName, $siteShortName, $siteUrl, $siteHeroDescription, $oimVersion, $adminUsername;
require_once(__DIR__ . '/../../config/db_connection.php');
require_once(__DIR__ . '/../../config/config.php');

require_once(__DIR__ . '/../../src/functions.php');
$option = getConfigurationFromDatabase($db);
extract($option, EXTR_IF_EXISTS);

// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);
// Kullanıcı adını al
$adminUsername = $_SESSION['admin_username'];
$adminFirstName = $_SESSION['admin_first_name'];
$adminLastName = $_SESSION['admin_last_name'];

logoutUser();
?>
<!doctype html>
<html lang="en" data-bs-theme="auto">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $option['site_name']; ?> - <?php echo $option['site_short_name']; ?></title>
    <meta name="description" content="<?php echo $option['site_hero_description']; ?>">
    <meta name="robots" content="noindex, nofollow" />
    <meta name="author" content="Muhammed Yalçınkaya">
    <meta name="generator" content="<?php echo $option['site_short_name']; ?> - <?php echo $option['version']; ?>">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    <script src="../../assets/js/dashboard.js"></script>
    <script src="https://unpkg.com/@popperjs/core@2"></script>
    <link href="https://cdn.datatables.net/v/bs5/jszip-3.10.1/dt-1.13.8/af-2.6.0/b-2.4.2/b-colvis-2.4.2/b-html5-2.4.2/b-print-2.4.2/date-1.5.1/fc-4.3.0/fh-3.4.0/r-2.5.0/datatables.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/v/bs5/jszip-3.10.1/dt-1.13.8/af-2.6.0/b-2.4.2/b-colvis-2.4.2/b-html5-2.4.2/b-print-2.4.2/date-1.5.1/fc-4.3.0/fh-3.4.0/r-2.5.0/datatables.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="../../assets/js/color-modes.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/pikaday/css/pikaday.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/pikaday/pikaday.js"></script>
    <link href="../../assets/css/dashboard.css" rel="stylesheet">

    <style>
        .grecaptcha-badge {
            visibility: hidden !important;
        }
        .separator {
            margin: 0 3px; /* Boşluk ayarlayabilirsiniz */
            border-right: 1px solid white; /* Dikey çizgi ekleyebilirsiniz */
            padding: 0 10px; /* İsteğe bağlı padding ayarı */
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
</head>

  <body>

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

  <div class="dropdown position-fixed bottom-0 end-0 mb-3 me-3 bd-mode-toggle">
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

  <header class="navbar sticky-top bg-dark flex-md-nowrap p-0 shadow" data-bs-theme="dark">
      <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3 fs-6 text-white d-flex justify-content-center align-items-center" href="<?php echo $option['site_url']; ?>/admin/index.php">
          <img id="logo" src="/assets/brand/default_logo_dark.png" alt="<?php echo $option['site_name']; ?> - <?php echo $option['site_short_name']; ?>" title="<?php echo $option['site_name']; ?> - <?php echo $option['site_short_name']; ?>" width="50%" height="auto">
      </a>
      <div id="navbarSearch" class="navbar-search w-100 collapse">
          <div class="input-group mb-3">
              <input name="q" id="searchQuery" class="form-control rounded-0 border-0" type="text" placeholder="Aranacak içerik..." aria-label="Search">
              <div class="input-group-append">
                  <button type="button" class="btn btn-primary" onclick="performSearch()">Ara</button>
              </div>
          </div>
      </div>

      <div class="container text-light ml-auto"">
          <!-- Yeni butonu menüsü -->
          <div class="btn-group d-none d-md-block">
              <button type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                  <i class="fas fa-plus"></i> Yeni
              </button>
              <ul class="dropdown-menu">
                  <li><a class="dropdown-item" href="/admin/add_user.php"><i class="fas fa-user"></i> Kullanıcı</a></li>
                  <li><a class="dropdown-item" href="/admin/add_course_plan.php"><i class="fas fa-user"></i> Ders Planla</a></li>
                  <li><a class="dropdown-item" href="/admin/add_introductory_course_plan.php"><i class="fas fa-user"></i> Tanışma Dersi Planla</a></li>
                  <li><a class="dropdown-item" href="/admin/add_rescheduled_course_plan.php"><i class="fas fa-user"></i> Telafi Dersi Planla</a></li>
                  <li><a class="dropdown-item" href="/admin/add_payment.php"><i class="fas fa-file-invoice-dollar"></i> Ödeme Ekle</a></li>
              </ul>
          </div>
          <style>
              @media (max-width: 767px) {
                  #datetime-container {
                      display: none;
                  }
              }
          </style>
          <!-- Saat ve tarih -->
          <div id="datetime-container" class="dropdown text-end ml-auto">
              <i class="fas fa-clock-four"></i>
              <?php
              $current_datetime = date("d.m.Y H:i");
              echo "<span id='current-datetime' class='dropdown text-end ml-auto'>$current_datetime</span>";
              ?>
          </div>

            <!-- Profil düzenleme ve çıkış bağlantıları -->
          <div class="row">
              <div class="col-md-12">
                  <div class="d-flex justify-content-end align-items-center">

                      <div class="dropdown text-end ml-auto" id="greeting"></div>
                      <script>
                          document.addEventListener("DOMContentLoaded", function() {
                              var adminFirstName = "<?php echo $adminFirstName; ?>";
                              var adminLastName = "<?php echo $adminLastName; ?>";

                              // Mobil kontrolü (örneğin, ekran genişliği 767px'den küçükse)
                              if (window.innerWidth <= 767) {
                                  var abbreviatedFirstName = adminFirstName.charAt(0); // İlk harfi al
                                  document.getElementById("greeting").innerText = "Selam 👋, " + abbreviatedFirstName + ' ' + adminLastName + " 🍀";
                              } else {
                                  document.getElementById("greeting").innerText = "Selam 👋, " + adminFirstName + ' ' + adminLastName + " 🍀";
                              }
                          });
                      </script>

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

                      <!-- Boşluk ekleyin -->
                      <div class="separator"></div>
                      <a class="nav-link d-flex align-items-center text-light-emphasis" href="/admin/profile_edit.php">
                          <i class="fas fa-user-gear"></i>
                      </a>
                      <!-- Boşluk ekleyin -->
                      <div class="separator"></div>
                      <a class="nav-link d-flex align-items-center text-light-emphasis" href="?action=logout">
                          <i class="fas fa-door-closed"></i>
                      </a>


                      <ul class="navbar-nav flex-row d-md-none">
                          <li class="nav-item text-nowrap">
                              <button class="nav-link px-3 text-white" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSearch" aria-controls="navbarSearch" aria-expanded="false" aria-label="Toggle search">
                                  <svg class="bi"><use xlink:href="#search"/></svg>
                              </button>
                          </li>
                          <li class="nav-item text-nowrap">
                              <button class="nav-link px-3 text-white" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
                                  <svg class="bi"><use xlink:href="#list"/></svg>
                              </button>
                          </li>
                      </ul>
                  </div>
              </div>
          </div>
      </div>
  </header>
