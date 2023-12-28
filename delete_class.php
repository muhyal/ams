<?php
global $db, $showErrors, $siteName, $siteShortName, $siteUrl;
session_start();
// Oturum kontrolü
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php"); // Giriş sayfasına yönlendir
    exit();
}
require_once "db_connection.php";
require_once "config.php";
// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);

if (isset($_GET['id'])) {
    $classId = $_GET['id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Sınıfı sil
        $deleteQuery = "DELETE FROM classes WHERE id = ?";
        $deleteStmt = $db->prepare($deleteQuery);
        $deleteStmt->execute([$classId]);

        header("Location: class_list.php");
        exit;
    }

    // Sınıf bilgilerini veritabanından çek
    $query = "SELECT * FROM classes WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$classId]);
    $classData = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    header("Location: class_list.php");
    exit;
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
                <h2>Sınıf Sil</h2>
            </div>

<p><strong>Sınıf Adı:</strong> <?php echo $classData['class_name']; ?></p>
<p><strong>Sınıf Kodu:</strong> <?php echo $classData['class_code']; ?></p>
<p><strong>Sınıf Açıklaması:</strong> <?php echo $classData['class_description']; ?></p>

<form method="post">
    <button type="submit">Sınıfı Sil</button>
</form>
            <?php
            require_once "footer.php";
            ?>
