<?php
/**
 * @copyright Copyright (c) 2024, KUTBU
 *
 * @author Muhammed Yalçınkaya <muhammed.yalcinkaya@kutbu.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
global $db, $showErrors, $siteName, $siteShortName, $siteUrl;
session_start();
session_regenerate_id(true);

// Oturum kontrolü
if (!isset($_SESSION["admin_id"])) {
    header("Location: index.php"); // Giriş sayfasına yönlendir
    exit();
}

require_once(__DIR__ . '/../config/db_connection.php');
require_once('../config/config.php');
// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);

// Yönetici verilerini çekme (user_type değeri 1, 2 veya 3 olanlar)
$query = "SELECT * FROM users WHERE user_type IN (1, 2, 3)";
$stmt = $db->prepare($query);
$stmt->execute();
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php
require_once(__DIR__ . '/partials/header.php');
?>
<div class="container-fluid">
    <div class="row">
        <?php
        require_once(__DIR__ . '/partials/sidebar.php');
        ?>
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
                <h2>Yöneticiler</h2>
            </div>

            <!-- Yönetici Listesi Tablosu -->
            <div class="table-responsive">
                <div class="table-responsive">
                    <table id="adminsTable" class="table table-striped table-sm">
                        <thead class="thead-light">
                    <tr>
                        <th scope="col" class="text-sm">Kullanıcı Adı</th>
                        <th scope="col" class="text-sm">Ad</th>
                        <th scope="col" class="text-sm">Soyad</th>
                        <th scope="col" class="text-sm">Telefon</th>
                        <th scope="col" class="text-sm">E-posta</th>
                        <th scope="col" class="text-sm"></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($admins as $admin): ?>
                        <tr>
                            <td><?php echo $admin['username']; ?></td>
                            <td><?php echo $admin['first_name']; ?></td>
                            <td><?php echo $admin['last_name']; ?></td>
                            <td><?php echo $admin['phone']; ?></td>
                            <td><?php echo $admin['email']; ?></td>
                            <td>
                                <a class="btn btn-primary btn-sm" href="user_profile.php?id=<?php echo $admin['id']; ?>"><i class="fas fa-eye fa-sm"></i></a>
                                <a class="btn btn-warning btn-sm" href="edit_user.php?id=<?php echo $admin['id']; ?>"><i class="fas fa-edit fa-sm"></i></a>
                                <a class="btn btn-danger btn-sm" href="users.php?delete_user=1&id=<?php echo $admin['id']; ?>"><i class="fas fa-trash-alt fa-sm"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
<?php require_once('../admin/partials/footer.php'); ?>