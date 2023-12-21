<?php
global $db;
require_once "db_connection.php";

// Yönetici verilerini çekme
$query = "SELECT * FROM admins";
$stmt = $db->prepare($query);
$stmt->execute();
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
require_once "config.php";
global $siteName, $siteShortName, $siteUrl;
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
<table>
    <tr>
        <th>#</th>
        <th>Kullanıcı Adı</th>
        <th>E-posta</th>
        <th>İşlemler</th>
    </tr>
    <?php foreach ($admins as $admin): ?>
        <tr>
            <td><?php echo $admin['id']; ?></td>
            <td><?php echo $admin['username']; ?></td>
            <td><?php echo $admin['email']; ?></td>
            <td>
                <a href="edit_admin.php?id=<?php echo $admin['id']; ?>">Düzenle</a>
                <a href="delete_admin.php?id=<?php echo $admin['id']; ?>">Sil</a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<?php
require_once "footer.php";
?>