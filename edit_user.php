<?php
global $db, $showErrors, $siteName, $siteShortName, $siteUrl;
// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);
require_once "config.php";

// Oturum kontrolü
session_start();
session_regenerate_id(true);

if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php"); // Giriş sayfasına yönlendir
    exit();
}

require_once "db_connection.php"; // Veritabanı bağlantısı

// Kullanıcı bilgilerini kullanabilirsiniz
$admin_id = $_SESSION["admin_id"];
$admin_username = $_SESSION["admin_username"];

// Kullanıcı ID'sini alın
if (isset($_GET["id"])) {
    $userId = $_GET["id"];
    $getUserQuery = "SELECT * FROM users LEFT JOIN user_types ON users.user_type = user_types.name WHERE users.id = ?";
    $stmt = $db->prepare($getUserQuery);
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Kullanıcı tiplerini almak için bir SELECT sorgusu
    $getUserTypesQuery = "SELECT id, name FROM user_types";
    $stmtUserTypes = $db->query($getUserTypesQuery);
    $userTypes = $stmtUserTypes->fetchAll(PDO::FETCH_ASSOC);
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
    $user_type = $_POST["user_type"];

    // Şifre değişikliği yapılacak mı kontrolü
    if (!empty($new_password)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $updateQuery = "UPDATE users SET tc = ?, firstname = ?, lastname = ?, email = ?, phone = ?, password = ?, user_type = ? WHERE id = ?";
        $stmt = $db->prepare($updateQuery);
        $stmt->execute([$tc, $firstname, $lastname, $email, $phone, $hashed_password, $user_type, $userId]);
    } else {
        $updateQuery = "UPDATE users SET tc = ?, firstname = ?, lastname = ?, email = ?, phone = ?, user_type = ? WHERE id = ?";
        $stmt = $db->prepare($updateQuery);
        $stmt->execute([$tc, $firstname, $lastname, $email, $phone, $user_type, $userId]);
    }


    // Kullanıcıyı güncelledikten sonra yönlendirme yapabilirsiniz
    header("Location: user_list.php");
    exit();
}
?>
<?php
require_once "admin_panel_header.php";
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

            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <div class="btn-group mr-2">
                                <button onclick="history.back()" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-arrow-left"></i> Geri dön
                                </button>
                                <a href="user_list.php" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-list"></i> Kullanıcı Listesi
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <br>

<?php if (isset($user)): ?>
     <form method="post" action="">

         <label class="form-label" for="user_type">Kullanıcı Tipi:</label>
         <select class="form-select" name="user_type" required>
             <?php foreach ($userTypes as $type): ?>
                 <option value="<?php echo $type['id']; ?>" <?php if ($user["user_type"] === $type['id']) echo "selected"; ?>><?php echo $type['name']; ?></option>
             <?php endforeach; ?>
         </select><br>

        <label class="form-label" for="tc">T.C. Kimlik No:</label>
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

           <div class="form-group">
                 <label class="form-label" for="new_password">Yeni Şifre (Güncelleme olmayacak ise boş bırakabilirsiniz):</label>
                 <div class="input-group">
                     <input class="form-control" type="password" name="new_password" id="new_password" required>
                     <div class="input-group-append">
                         <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('new_password')">Şifreyi Göster</button>
                     </div>
                     <div class="input-group-append">
                         <button type="button" class="btn btn-outline-secondary" onclick="copyPassword('new_password')">Kopyala</button>
                     </div>
                     <div class="input-group-append">
                         <button type="button" class="btn btn-outline-secondary" onclick="generatePassword('new_password')">Şifre Üret</button>
                     </div>
                 </div>
             </div>

             <script>
                 function togglePassword(passwordId) {
                     var passwordInput = document.getElementById(passwordId);
                     if (passwordInput.type === "new_password") {
                         passwordInput.type = "text";
                     } else {
                         passwordInput.type = "new_password";
                     }
                 }

                 function copyPassword(passwordId) {
                     var passwordInput = document.getElementById(passwordId);
                     passwordInput.select();
                     document.execCommand("copy");
                     alert("Şifre kopyalandı: " + passwordInput.value);
                 }

                 function generatePassword(passwordId) {
                     var generatedPasswordInput = document.getElementById(passwordId);
                     var xhr = new XMLHttpRequest();
                     xhr.onreadystatechange = function () {
                         if (xhr.readyState === 4 && xhr.status === 200) {
                             generatedPasswordInput.value = xhr.responseText;
                         }
                     };
                     xhr.open("GET", "generate_password.php", true);
                     xhr.send();
                 }
             </script>
        <button type="submit" class="btn btn-primary">Güncelle</button>
     </form>


<?php else: ?>
    <p>Kullanıcı bulunamadı.</p>
<?php endif; ?>

</main>

</div>
</div>

<?php
require_once "footer.php";
?>
