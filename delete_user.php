<?php
session_start();

// Oturum kontrolü
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php"); // Giriş sayfasına yönlendir
    exit();
}
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

// Silme işlemi için formdan gelen ID'yi alın
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["id"])) {
    $userId = $_POST["id"];

    // Kullanıcıyı silme işlemini gerçekleştirin
    $deleteQuery = "DELETE FROM users WHERE id = ?";
    $stmt = $db->prepare($deleteQuery);
    $stmt->execute([$userId]);

    // Kullanıcı silindikten sonra yönlendirme yapabilirsiniz
    header("Location: user_list.php");
    exit();
}

// Silme formunu göstermek için kullanıcının bilgilerini çekin
if (isset($_GET["id"])) {
    $userId = $_GET["id"];
    $getUserQuery = "SELECT * FROM users WHERE id = ?";
    $stmt = $db->prepare($getUserQuery);
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}
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
        <nav class="col-md-2 d-none d-md-block bg-light sidebar">
            <div class="sidebar-sticky">
                <!-- Yönetici paneli içeriği burada -->
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="admin_panel.php">
                            Genel bakış
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">
                            Kullanıcı Kaydet
                        </a>
                    </li>
                    <li class="nav-item">

                        <a class="nav-link" href="user_list.php">
                            <span data-feather="users"></span>
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
                            <span data-feather="file"></span>
                            Sözleşmeleri Görüntüle
                        </a>
                    </li>
                </ul>


            </div>
        </nav>

        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                <h1 class="h2">Genel Bakış</h1>
            </div>

<?php if (isset($user)): ?>
    <p>Kullanıcı Adı: <?php echo $user["firstname"] . " " . $user["lastname"]; ?></p>
    <p>E-posta: <?php echo $user["email"]; ?></p>
<?php else: ?>
    <p>Kullanıcı bulunamadı.</p>
<?php endif; ?>

<form method="post" action="">
    <input type="hidden" name="id" value="<?php echo $userId; ?>">
    <button type="submit" onclick="return confirm('Kullanıcıyı silmek istediğinizden emin misiniz?')">Kullanıcıyı Sil</button>
</form>

            <a href="user_list.php">Kullanıcı Listesine Geri Dön</a>
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
