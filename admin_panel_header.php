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
    <!-- Bootstrap core CSS -->
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.1.3/dist/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">    <link href="admin/css/admin_panel.css" rel="stylesheet">
  <body>
  <nav class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0">
      <a class="navbar-brand col-sm-3 col-md-2 mr-0" href="<?php echo $siteUrl ?>/admin_panel.php"><?php echo $siteName ?> - <?php echo $siteShortName ?></a>

      <div class="search-form ml-auto">
          <form action="search_results.php" method="get" class="form-inline">
              <input type="text" id="searchQuery" name="q" class="form-control form-control-dark" placeholder="Aranacak metin..." aria-label="Ara" required>
              <select id="searchType" name="search_type" class="form-control">
                  <option value="user">Kullanıcı</option>
                  <option value="student">Öğrenci</option>
                  <option value="teacher">Öğretmen</option>
                  <option value="course">Ders</option>
                  <option value="class">Sınıf</option>
              </select>
              <button type="submit" class="btn btn-primary">Ara</button>
          </form>
      </div>



      <ul class="navbar-nav px-3">
          <li class="nav-item text-nowrap">
              <a class="nav-link" href="logout.php">Oturumu kapat</a>
          </li>
      </ul>
  </nav>
