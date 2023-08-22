<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once "config.php";
global $siteName, $siteShortName, $siteUrl;
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $siteName ?> - <?php echo $siteShortName ?></title>
</head>
<body>
<h2><?php echo $siteName ?> - <?php echo $siteShortName ?></h2>

<a href="<?php echo $siteUrl ?>/agreement.php" target="_self">Sözleşmeleri görüntüleyin</a>

</body>
</html>

