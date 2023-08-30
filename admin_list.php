<?php
global $db;
require_once "db_connection.php";

// Yönetici verilerini çekme
$query = "SELECT * FROM admins";
$stmt = $db->prepare($query);
$stmt->execute();
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

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
