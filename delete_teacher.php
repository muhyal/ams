<?php
require_once "db_connection.php";
global $db, $showErrors, $siteName, $siteShortName, $siteUrl;
// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);

// Oturum kontrolü
session_start();
session_regenerate_id(true);

if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php"); // Giriş sayfasına yönlendir
    exit();
}

require_once "config.php";

if (isset($_GET["id"])) {
    $id = $_GET["id"];

    // Öğretmeni veritabanından alın
    $query = "SELECT * FROM teachers WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$teacher) {
        echo "Öğretmen bulunamadı.";
        exit();
    }
}

if (isset($_POST["confirm_delete"])) {
    // Öğretmeni veritabanından sil
    $query = "DELETE FROM teachers WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);

    header("Location: teachers_list.php");
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
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
        <h2>Öğretmen Sil</h2>
    </div>
<p>Aşağıdaki öğretmeni silmek istediğinize emin misiniz?</p>
<p>Ad: <?php echo $teacher["first_name"]; ?></p>
<p>Soyad: <?php echo $teacher["last_name"]; ?></p>
<p>E-posta: <?php echo $teacher["email"]; ?></p>

<form method="post">
    <input type="hidden" name="id" value="<?php echo $id; ?>">
    <button type="submit" name="confirm_delete">Sil</button>
</form>
<?php
require_once "footer.php";
?>