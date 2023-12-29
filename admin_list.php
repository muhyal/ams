<?php
global $db, $showErrors, $siteName, $siteShortName, $siteUrl;
session_start();
session_regenerate_id(true);

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

// Yönetici verilerini çekme
$query = "SELECT * FROM admins";
$stmt = $db->prepare($query);
$stmt->execute();
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                <h2>Yönetici Listesi</h2>
            </div>
<!-- Yönetici Listesi Tablosu -->

    <div class="table-responsive">
        <table class="table table-striped table-sm">
            <thead class="thead-light">
            <tr>
        <th>#</th>
        <th>Kullanıcı Adı</th>
        <th>E-posta</th>
        <th>Düzenle</th>
        <th>Sil</th>
    </tr>
            </thead>
            <tbody>
    <?php foreach ($admins as $admin): ?>
        <tr>
            <td><?php echo $admin['id']; ?></td>
            <td><?php echo $admin['username']; ?></td>
            <td><?php echo $admin['email']; ?></td>
            <td><a href="edit_admin.php?id=<?php echo $admin['id']; ?>">Düzenle</a></td>
            <td><a href="delete_admin.php?id=<?php echo $admin['id']; ?>">Sil</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
    </div>
<?php
require_once "footer.php";
?>