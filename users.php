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
global $siteName, $siteShortName, $siteUrl, $db;
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

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once "config.php";

// Kullanıcıları ve rolleri veritabanından çekme
$query = "SELECT users.*, user_types.type_name 
          FROM users 
          LEFT JOIN user_types ON users.user_type = user_types.id";

$stmt = $db->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php
require_once "admin_panel_header.php";
?>
<style>
    /* Silinmiş kullanıcıları soluk yap */
    table.table-striped tbody tr.deleted-user {
        opacity: 0.5; /* Dilediğiniz solukluk seviyesini ayarlayabilirsiniz (0-1 arası) */
    }

    /* Soluk yapılacak öğelerin üstüne gelindiğinde farklı bir renk kullanabilirsiniz */
    table.table-striped tbody tr.deleted-user:hover {
        background-color: #f8f9fa; /* Farklı bir arkaplan rengi */
    }
</style>

<div class="container-fluid">
    <div class="row">
        <?php
        require_once "admin_panel_sidebar.php";
        ?>
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
                <h2>Kullanıcılar</h2>
            </div>

            <div class="table-responsive">
                <!-- Kullanıcı listesini gösterme -->
                <table class="table table-striped table-sm">
                    <thead class="thead-light">
                    <tr>
                        <th scope="col" class="text-sm">#</th>
                        <th scope="col" class="text-sm">Ad</th>
                        <th scope="col" class="text-sm">Soyad</th>
                        <th scope="col" class="text-sm">E-posta</th>
                        <!--<th scope="col" class="text-sm">T.C. Kimlik No</th>-->
                        <th scope="col" class="text-sm">Telefon</th>
                        <!--<th scope="col" class="text-sm">SMS Doğrulaması Gönderildi</th>-->
                        <!--<th scope="col" class="text-sm">SMS Doğrulaması Onaylandı</th>-->
                        <th scope="col" class="text-sm">SMS Doğrulama</th>
                        <!--<th scope="col" class="text-sm">SMS Doğrulama IP</th>
                        <!--<th scope="col" class="text-sm">E-Posta Doğrulaması Gönderildi</th>-->
                        <!--<th scope="col" class="text-sm">E-Posta Doğrulaması Onaylandı</th>-->
                        <th scope="col" class="text-sm">E-posta Doğrulama</th>
                        <!--<th scope="col" class="text-sm">E-posta Doğrulama IP</th>-->
                        <th scope="col" class="text-sm">Rolü</th>
                        <th scope="col" class="text-sm">İşlemler</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr <?php echo $user['deleted_at'] ? 'class="deleted-user"' : ''; ?>>
                            <th scope="row"><?= $user['id'] ?></th>
                            <td><?= $user['first_name'] ?></td>
                            <td><?= $user['last_name'] ?></td>
                            <td><?= $user['email'] ?></td>
                            <!--<td><?= $user['tc_identity'] ?></td>-->
                            <td><?= $user['phone'] ?></td>
                            <!--<td><?= $user['verification_time_sms_sent'] ?></td>-->
                            <!--<td><?= $user['verification_time_sms_confirmed'] ?></td>-->
                            <td><?= $user['verification_time_sms_confirmed'] ? '<i class="fas fa-check text-success"></i> Doğrulandı' : '<i class="fas fa-times text-danger"></i> Doğrulanmadı' ?></td>
                            <!--<td><?= $user['verification_ip_sms'] ?></td>-->
                            <!--<td><?= $user['verification_time_email_sent'] ?></td>-->
                            <!--<td><?= $user['verification_time_email_confirmed'] ?></td>-->
                            <td><?= $user['verification_time_email_confirmed'] ? '<i class="fas fa-check text-success"></i> Doğrulandı' : '<i class="fas fa-times text-danger"></i> Doğrulanmadı' ?></td>
                            <!--<td><?= $user['verification_ip_email'] ?></td>-->
                            <td><?= $user['type_name'] ?></td>
                            <td>
                                <?php if ($user['deleted_at']): ?>
                                    <a class="btn btn-primary btn-sm" href="restore_user.php?id=<?php echo $user["id"]; ?>" onclick="return confirm('Bu kullanıcıyı silmeyi geri almak istediğinizden emin misiniz?')">
                                        <i class="fa-solid fa-clock-rotate-left"></i>
                                    </a>
                                <?php else: ?>
                                <!-- Silinmeyen kullanıcılar için düzenleme ve silme bağlantıları -->
                                    <a class="btn btn-primary btn-sm" href="resend_verifications.php?id=<?php echo $user["id"]; ?>"><i class="fas fa-repeat fa-sm"></i></a>
                                    <a class="btn btn-primary btn-sm" href="user_profile.php?id=<?php echo $user['id']; ?>"><i class="fas fa-user fa-sm"></i></a>
                                    <a class="btn btn-warning btn-sm" href="edit_user.php?id=<?php echo $user['id']; ?>"><i class="fas fa-edit fa-sm"></i></a>
                                    <a class="btn btn-danger btn-sm" href="delete_user.php?id=<?php echo $user['id']; ?>"><i class="fas fa-trash-alt fa-sm"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<?php
require_once "footer.php";
?>
