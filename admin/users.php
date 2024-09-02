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
 */

global $siteName, $siteShortName, $siteUrl, $db;
// Oturum kontrolü
session_start();
session_regenerate_id(true);

if (!isset($_SESSION["admin_id"])) {
    header("Location: index.php"); // Giriş sayfasına yönlendir
    exit();
}

require_once(__DIR__ . '/../config/db_connection.php');

// Kullanıcı bilgilerini kullanabilirsiniz
$admin_id = $_SESSION["admin_id"];
$admin_username = $_SESSION["admin_username"];

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once('../config/config.php');

// Kullanıcıları ve rolleri veritabanından çekme
$query = "
    SELECT
        users.*,
        user_types.type_name,
        (
            SELECT v2.verification_time_email_confirmed
            FROM verifications v2
            WHERE v2.user_id = users.id
            ORDER BY v2.sent_at DESC
            LIMIT 1
        ) AS latest_verification_time_email_confirmed,
        (
            SELECT v2.verification_time_sms_confirmed
            FROM verifications v2
            WHERE v2.user_id = users.id
            ORDER BY v2.sent_at DESC
            LIMIT 1
        ) AS latest_verification_time_sms_confirmed
    FROM users
    LEFT JOIN user_types ON users.user_type = user_types.id
";


$stmt = $db->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (isset($_GET["action"]) && $_GET["action"] === "restore" && isset($_GET["id"])) {
    $user_id = $_GET["id"];

    $query = "UPDATE users SET deleted_at = NULL WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id]);

    // Reload the page to reflect the changes
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}


if (isset($_GET["action"]) && $_GET["action"] === "delete" && isset($_GET["id"])) {
    $user_id = $_GET["id"];

    // Fetch user information
    $queryUser = "SELECT * FROM users WHERE id = ?";
    $stmtUser = $db->prepare($queryUser);
    $stmtUser->execute([$user_id]);
    $userToDelete = $stmtUser->fetch(PDO::FETCH_ASSOC);

    // Check if the user_type is 1 or 2
    if ($userToDelete['user_type'] == 1 || $userToDelete['user_type'] == 2) {
        // Users with user_type 1 or 2 cannot be deleted
        // Display a JavaScript popup with an error message
        echo '<script>alert("Hata: Yönetici veya koordinatör rolündeki kullanıcılar silinemez!"); window.location.href="'.$_SERVER['PHP_SELF'].'";</script>';
        exit();
    }

    $query = "UPDATE users SET deleted_at = CURRENT_TIMESTAMP, deleted_by_user_id = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$admin_id, $user_id]);

    // Reload the page to reflect the changes
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}



?>
<?php require_once(__DIR__ . '/partials/header.php'); ?>
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
        <?php require_once(__DIR__ . '/partials/sidebar.php'); ?>
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
                <h2>Kullanıcılar</h2>
            </div>

            <div class="table-responsive">
                <!-- Kullanıcı listesini gösterme -->
                <table id="usersTable" class="table table-striped table-sm">
                    <thead class="thead-light">
                    <tr>
                        <th scope="col" class="text-sm">Fotoğraf</th>
                        <th scope="col" class="text-sm">Tam Ad</th>
                        <th scope="col" class="text-sm">E-posta</th>
                        <th scope="col" class="text-sm">T.C. Kimlik No</th>
                        <th scope="col" class="text-sm">Telefon</th>
                        <th scope="col" class="text-sm">Rolü</th>
                        <th scope="col" class="text-sm"><i class="fas fa-mobile-alt text-dark"></i></th>
                        <th scope="col" class="text-sm"><i class="fas fa-envelope text-dark"></i></th>
                        <th scope="col" class="text-sm"><i class="fas fa-user-lock text-dark"></th>
                        <th scope="col" class="text-sm"><i class="fas fa-check-double"></i></th>
                        <th scope="col" class="text-sm">İşlemler</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr <?php echo $user['deleted_at'] ? 'class="deleted-user"' : ''; ?>>
                            <td>
                                <?php if (!empty($user['profile_photo'])): ?>
                                    <img src="<?= $user['profile_photo'] ?>" alt="<?= $user['first_name'] ?> <?= $user['last_name'] ?> Fotoğrafı"  style="border-radius: 50%; width: 50px; height: 50px;">
                                <?php else: ?>
                                    <img src="/assets/brand/default_pp.png" alt="Varsayılan Profil Fotoğrafı" style="border-radius: 50%; width: 50px; height: 50px;">
                                <?php endif; ?>
                            </td>
                            <td><?= $user['first_name'] ?> <?= $user['last_name'] ?></td>
                            <td><?= $user['email'] ?></td>
                            <td><?= $user['tc_identity'] ?></td>
                            <td><?= $user['phone'] ?></td>
                            <td><?= $user['type_name'] ?></td>
                            <td><?= $user['latest_verification_time_sms_confirmed'] ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>' ?></td>
                            <td><?= $user['latest_verification_time_email_confirmed'] ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>' ?></td>

                            <td>
                                <?php
                                if ($user['deleted_at']) {
                                    echo '<i class="fas fa-trash-alt text-danger"></i>';
                                } elseif ($user['is_active']) {
                                    echo '<i class="far fa-check-circle text-success"></i>';
                                } else {
                                    echo '<i class="far fa-times-circle text-warning"></i>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php
                                echo $user['two_factor_enabled'] ? '<i class="fas fa-lock text-success"></i>' : '<i class="fas fa-unlock text-secondary"></i>';
                                ?>
                            </td>

                            <td>
                                <?php if ($user['deleted_at']): ?>
                                    <a class="btn btn-primary btn-sm" href="user_profile.php?id=<?php echo $user['id']; ?>"><i class="fas fa-eye fa-sm"></i></a>
                                    <a class="btn btn-secondary btn-sm" href="send_verifications.php?id=<?php echo $user['id']; ?>"><i class="fas fa-user-check fa-sm"></i></a>
                                    <a class="btn btn-warning btn-sm" href="reset_password_and_send.php?id=<?php echo $user['id']; ?>"><i class="fas fa-lock-open fa-sm"></i></a>
                                    <a class="btn btn-warning btn-sm" href="edit_user.php?id=<?php echo $user['id']; ?>"><i class="fas fa-edit fa-sm"></i></a>
                                    <a class="btn btn-success btn-sm" href="?action=restore&id=<?php echo $user['id']; ?>" onclick="return confirm('Bu kullanıcıyı silmeyi geri almak istediğinizden emin misiniz?')">
                                        <i class="fa-solid fa-clock-rotate-left"></i>
                                    </a>
                                <?php else: ?>
                                    <!-- Silinmeyen kullanıcılar için düzenleme ve silme bağlantıları -->
                                    <a class="btn btn-primary btn-sm" href="user_profile.php?id=<?php echo $user['id']; ?>"><i class="fas fa-eye fa-sm"></i></a>
                                    <a class="btn btn-success btn-sm" href="send_verifications.php?id=<?php echo $user['id']; ?>"><i class="fas fa-feather-alt fa-sm"></i></a>
                                    <a class="btn btn-warning btn-sm" href="reset_password_and_send.php?id=<?php echo $user['id']; ?>"><i class="fas fa-lock-open fa-sm"></i></a>
                                    <a class="btn btn-warning btn-sm" href="edit_user.php?id=<?php echo $user['id']; ?>"><i class="fas fa-edit fa-sm"></i></a>
                                    <a class="btn btn-danger btn-sm" href="?action=delete&id=<?php echo $user['id']; ?>" onclick="return confirm('Bu kullanıcıyı silmek istediğinizden emin misiniz?')">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
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

<?php require_once('../admin/partials/footer.php'); ?>
