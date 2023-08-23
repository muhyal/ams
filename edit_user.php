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

// Kullanıcının ID'sini alın
if (isset($_GET["id"])) {
    $userId = $_GET["id"];
    $getUserQuery = "SELECT * FROM users WHERE id = ?";
    $stmt = $db->prepare($getUserQuery);
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Form gönderildiğinde güncelleme işlemini gerçekleştirin
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Formdan gelen verileri alın
    $tc = $_POST["tc"];
    $firstname = $_POST["firstname"];
    $lastname = $_POST["lastname"];
    $email = $_POST["email"];
    $phone = $_POST["phone"];

    // Veritabanında güncelleme işlemi yapın
    $updateQuery = "UPDATE users SET tc = ?, firstname = ?, lastname = ?, email = ?, phone = ? WHERE id = ?";
    $stmt = $db->prepare($updateQuery);
    $stmt->execute([$tc, $firstname, $lastname, $email, $phone, $userId]);

    // Kullanıcıyı güncelledikten sonra yönlendirme yapabilirsiniz
    header("Location: user_list.php");
    exit();
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
     <form method="post" action="">
        <label class="form-label" for="tc">TC Kimlik No:</label>
        <input class="form-control" type="text" name="tc" value="<?php echo $user["tc"]; ?>" required><br>
        <label class="form-label" for="firstname">Ad:</label>
        <input class="form-control"type="text" name="firstname" value="<?php echo $user["firstname"]; ?>" required><br>
        <label class="form-label" for="lastname">Soyad:</label>
        <input class="form-control"type="text" name="lastname" value="<?php echo $user["lastname"]; ?>" required><br>
        <label for="email" class="form-label">E-posta:</label>
        <input class="form-control"type="email" name="email" class="form-control" aria-describedby="emailHelp" value="<?php echo $user["email"]; ?>" required>
        <div id="emailHelp" class="form-text">We'll never share your email with anyone else.</div>
        <br>
        <label class="form-label" for="phone">Telefon:</label>
        <input class="form-control" type="text" name="phone" value="<?php echo $user["phone"]; ?>" required><br>
        <button type="submit" class="btn btn-primary">Güncelle</button>
        <button onclick="history.back()" class="btn btn-primary">Geri dön</button>
    </form>


<?php else: ?>
    <p>Kullanıcı bulunamadı.</p>
<?php endif; ?>

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
