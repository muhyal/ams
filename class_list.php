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
require_once "admin_panel_header.php";
// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);
?>
<div class="container-fluid">
    <div class="row">
        <?php
        require_once "admin_panel_sidebar.php";
        ?>
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
                <h2>Sınıf Listesi</h2>
            </div>
<a href="add_class.php">Sınıf Ekle</a>
<table border="1">
    <tr>
        <th>ID</th>
        <th>Sınıf Adı</th>
        <th>Sınıf Kodu</th>
        <th>Sınıf Açıklaması</th>
        <th>İşlemler</th>
    </tr>
    <?php


    $selectQuery = "SELECT * FROM classes";
    $stmt = $db->prepare($selectQuery);
    $stmt->execute();
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($classes as $class) {
        echo "<tr>";
        echo "<td>{$class['id']}</td>";
        echo "<td>{$class['class_name']}</td>";
        echo "<td>{$class['class_code']}</td>";
        echo "<td>{$class['class_description']}</td>";
        echo '<td>
                  <a href="edit_class.php?id='.$class['id'].'">Düzenle</a>
                  <a href="delete_class.php?id='.$class['id'].'">Sil</a>
                  </td>';
        echo "</tr>";
    }
    ?>
</table>
<?php
require_once "footer.php";
?>