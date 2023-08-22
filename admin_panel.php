<?php
session_start();

// Oturum kontrolü
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php"); // Giriş sayfasına yönlendir
    exit();
}

// Kullanıcı bilgilerini kullanabilirsiniz
$admin_id = $_SESSION["admin_id"];
$admin_username = $_SESSION["admin_username"];

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once "config.php";
global $siteName, $siteShortName, $siteUrl;
require_once "admin_panel_header.php";
?>

     <nav class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0">
      <a class="navbar-brand col-sm-3 col-md-2 mr-0" href="<?php echo $siteUrl ?>"><?php echo $siteName ?> - <?php echo $siteShortName ?></a>
      <input class="form-control form-control-dark w-100" type="text" placeholder="Search" aria-label="Search">
      <ul class="navbar-nav px-3">
        <li class="nav-item text-nowrap">
          <a class="nav-link" href="logout.php">Oturumu kapat</a>
        </li>
      </ul>
    </nav>

    <div class="container-fluid">
      <div class="row">
        <nav class="col-md-2 d-none d-md-block bg-light sidebar">
          <div class="sidebar-sticky">
            <!-- Yönetici paneli içeriği burada -->
            <ul class="nav flex-column">
              <li class="nav-item">
                <a class="nav-link" href="register.php">
                 Kullanıcı Kaydet
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="user_list.php">
                  Kullanıcı Listesi
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="delete_user.php">
                 Kullanıcı Sil
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="edit_user.php">
                 Kullanıcı Düzenle
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="admin_register.php">
                  Yönetici Kaydet
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="agreement.php">
                  Sözleşmeleri Görüntüle
                </a>
              </li>
            </ul>

          </div>
        </nav>

        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
          <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
            <h1 class="h2">Dashboard</h1>
            <div class="btn-toolbar mb-2 mb-md-0">

            </div>
          </div>

<?php
require_once "footer.php";
?>
