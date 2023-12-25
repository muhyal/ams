<?php
global $db, $showErrors, $siteName, $siteShortName, $siteUrl;
// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);
require_once "config.php";

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
    $new_password = $_POST["new_password"];

    // Şifre değişikliği yapılacak mı kontrolü
    if (!empty($new_password)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $updateQuery = "UPDATE users SET tc = ?, firstname = ?, lastname = ?, email = ?, phone = ?, password = ? WHERE id = ?";
        $stmt = $db->prepare($updateQuery);
        $stmt->execute([$tc, $firstname, $lastname, $email, $phone, $hashed_password, $userId]);
    } else {
        $updateQuery = "UPDATE users SET tc = ?, firstname = ?, lastname = ?, email = ?, phone = ? WHERE id = ?";
        $stmt = $db->prepare($updateQuery);
        $stmt->execute([$tc, $firstname, $lastname, $email, $phone, $userId]);
    }

    // Kullanıcıyı güncelledikten sonra yönlendirme yapabilirsiniz
    header("Location: user_list.php");
    exit();
}
?>



<div class="container-fluid">
    <div class="row">
        <?php
        require_once "admin_panel_sidebar.php";
        ?>

        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                <h1 class="h2">Kullanıcı Düzenle</h1>
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
        <div id="emailHelp" class="form-text">Geçerli bir e-posta adresi olmalıdır.</div>
        <br>
        <label class="form-label" for="phone">Telefon:</label>
        <input class="form-control" type="text" name="phone" value="<?php echo $user["phone"]; ?>" required><br>

         <label for="new_password">Yeni Şifre (Boş bırakabilirsiniz):</label>
         <input type="password" id="new_password" name="new_password"><br>

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

<?php
require_once "footer.php";
?>
