<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once "config.php";
global $siteName, $siteShortName, $siteUrl;
require_once "user_login_header.php";
?>
<div class="px-4 py-5 my-5 text-center">
    <h1 class="display-5 fw-bold text-body-emphasis"><?php echo $siteName ?> - <?php echo $siteShortName ?></h1>
    <div class="col-lg-6 mx-auto">
      <p class="lead mb-4">Quickly design and customize responsive mobile-first sites with Bootstrap, the world’s most popular front-end open source toolkit, featuring Sass variables and mixins, responsive grid system, extensive prebuilt components, and powerful JavaScript plugins.</p>
      <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
       <a href="<?php echo $siteUrl ?>/agreement.php" class="btn btn-primary btn-lg px-4 gap-3" role="button" aria-pressed="true">Sözleşmeleri görüntüleyin</a>
     <a href="<?php echo $siteUrl ?>/" class="btn btn-outline-secondary btn-lg px-4" role="button" aria-pressed="true"><?php echo $siteName ?> sitesine git</a>
      </div>
    </div>
  </div>
<?php
require_once "footer.php";
?>
-