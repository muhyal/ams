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

// Silme işlemi için formdan gelen ID'yi alın
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["id"])) {
    $userId = $_POST["id"];

    // Kullanıcının deleted_at sütunu üzerine silme tarihini güncelle
    $deleteQuery = "UPDATE users SET deleted_at = CURRENT_TIMESTAMP WHERE id = ?";
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
<?php
require_once "admin_panel_header.php";
?>
    <div class="container-fluid">
        <div class="row">
            <?php require_once "admin_panel_sidebar.php"; ?>
            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
                    <h2>Kullanıcı Sil</h2>
                </div>

                <?php if (isset($user)): ?>
                    <p><strong>Kullanıcı Adı:</strong> <?php echo $user["firstname"] . " " . $user["lastname"]; ?></p>
                    <p><strong>E-posta:</strong> <?php echo $user["email"]; ?></p>
                <?php else: ?>
                    <p>Kullanıcı bulunamadı.</p>
                <?php endif; ?>

                <form method="post" action="" class="mb-2">
                    <input type="hidden" name="id" value="<?php echo $userId; ?>">
                    <button type="submit" onclick="return confirm('Kullanıcıyı silmek istediğinizden emin misiniz?')" class="btn btn-danger">Kullanıcıyı Sil</button>
                    <a href="user_list.php" class="btn btn-secondary ml-2">Kullanıcı Listesine Geri Dön</a>
                </form>
            </main>
        </div>
    </div>

<?php require_once "footer.php"; ?>