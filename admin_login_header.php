<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once "config.php";
global $siteName, $siteShortName, $siteUrl;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $siteName ?> - <?php echo $siteShortName ?></title>
    <link href="/admin/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom styles for this template -->
    <link href="/admin/css/admin_login.css" rel="stylesheet"></head>
<!-- Custom styles for this template -->
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
            <!-- <form class="col-12 col-lg-auto mb-3 mb-lg-0 me-lg-3" role="search">
              <input type="search" class="form-control" placeholder="Ara..." aria-label="Ara">
            </form> -->
            <div class="dropdown text-end">
                <a href="<?php echo $siteUrl ?>" class="d-block link-body-emphasis text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="https://github.com/mdo.png" alt="mdo" width="32" height="32" class="rounded-circle">
                </a>
                <ul class="dropdown-menu text-small">
                    <li><a class="dropdown-item" href="admin_login.php">Oturum aç</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="logout.php">Oturumu kapat</a></li>
                </ul>
            </div>
        </div>
    </div>
</header>

