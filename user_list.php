<?php
global $db;
session_start();

// Oturum kontrolü
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php"); // Giriş sayfasına yönlendir
    exit();
}

require_once "db_connection.php"; // Veritabanı bağlantısı

// Kullanıcı bilgilerini kullanabilirsiniz
$admin_id = $_SESSION["admin_id"];
$admin_username = $_SESSION["admin_username"];

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once "config.php";
global $siteName, $siteShortName, $siteUrl;
require_once "admin_panel_header.php";
// Kullanıcıları veritabanından çekme
$query = "SELECT * FROM users";
$stmt = $db->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
  <body>
    <nav class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0">
      <a class="navbar-brand col-sm-3 col-md-2 mr-0" href="<?php echo $siteUrl ?>"><?php echo $siteName ?> - <?php echo $siteShortName ?></a>
      <input class="form-control form-control-dark w-100" type="text" placeholder="Ara" aria-label="Ara">
      <ul class="navbar-nav px-3">
        <li class="nav-item text-nowrap">
          <a class="nav-link" href="logout.php">Oturumu kapat</a>
        </li>
      </ul>
    </nav>
    <div class="container-fluid">
      <div class="row">
          <?php
          require_once "admin_panel_sidebar.php";
          ?>
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
          <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
            <h1 class="h2">Genel Bakış</h1>
 </div>
<main>
  <h2>Kullanıcılar</h2>
  <div class="table-responsive">
    <table class="table table-striped table-sm">
      <thead>
         <tr>
 <th scope="col">#</th>
    <th scope="col">Ad</th>
    <th scope="col">Soyad</th>
    <th scope="col">E-posta</th>
    <th scope="col">T.C. Kimlik</th>
    <th scope="col">Telefon</th>
    <th scope="col">SMS Doğrulaması Gönderildi</th>
    <th scope="col">SMS Doğrulaması Onaylandı</th>
    <th scope="col">SMS Doğrulandı Mı?</th>
    <th scope="col">SMS Doğrulama IP</th>
    <th scope="col">E-Posta Doğrulaması Gönderildi</th>
    <th scope="col">E-Posta Doğrulaması Onaylandı</th>
    <th scope="col">E-posta Doğrulandı Mı?</th>
    <th scope="col">E-posta Doğrulama IP</th>
    <th scope="col">Sil</th>
    <th scope="col">Düzenle</th>
    </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
            <th scope="row"><?= $user['id'] ?></th>
            <td><?= $user['firstname'] ?></td>
            <td><?= $user['lastname'] ?></td>
            <td><?= $user['email'] ?></td>
            <td><?= $user['tc'] ?></td>
            <td><?= $user['phone'] ?></td>
            <td><?= $user['verification_time_sms_sent'] ?></td>
            <td><?= $user['verification_time_sms_confirmed'] ?></td>
            <td><?= $user['verification_time_sms_confirmed'] ? 'Doğrulandı' : 'Doğrulanmadı' ?></td>
            <td><?= $user['verification_ip_sms'] ?></td>
            <td><?= $user['verification_time_email_sent'] ?></td>
            <td><?= $user['verification_time_email_confirmed'] ?></td>
            <td><?= $user['verification_time_email_confirmed'] ? 'Doğrulandı' : 'Doğrulanmadı' ?></td>
            <td><?= $user['verification_ip_email'] ?></td>
            <td>
                <a href="delete_user.php?id=<?php echo $user["id"]; ?>">Sil</a>
            </td>
            <td>
                <a href="edit_user.php?id=<?php echo $user["id"]; ?>">Düzenle</a>
            </td>
            </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</main>
</div>
</div>

    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
   <script src="https://getbootstrap.com/docs/4.0/assets/js/vendor/jquery-slim.min.js"></script>
    <script src="https://getbootstrap.com/docs/4.0/assets/js/vendor/popper.min.js"></script>
    <script src="https://getbootstrap.com/docs/4.0/dist/js/bootstrap.min.js"></script>

    <!-- Icons -->
    <script src="https://unpkg.com/feather-icons/dist/feather.min.js"></script>
    <script>
      feather.replace()
    </script>
  </body>
<?php
require_once "footer.php";
?>
