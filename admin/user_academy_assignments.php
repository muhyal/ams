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

// "user_type" kontrolü ekleniyor
if (!isset($_SESSION["admin_type"])) {
    // Hata: "user_type" tanımlı değil
    echo "Hata: Kullanıcı türü belirtilmemiş.";
    exit();
}


require_once(__DIR__ . '/../config/db_connection.php');
require_once('../config/config.php');

// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);

// Form submit kontrolü
if ($_SERVER["REQUEST_METHOD"] == "POST") {
if ($_SESSION["admin_type"] == 1) { // Sadece user_type 1 olan kullanıcılar için kontrol ekledik
    if (isset($_POST["update_submit"])) {
        // Güncelleme işlemleri
        foreach ($_POST["update_submit"] as $userId => $value) {
            // Kullanıcının ilişkilendirildiği akademileri al
            $selectedAcademies = $_POST["academies"][$userId];

            // Güncelleme işlemlerini gerçekleştir
            $updateQuery = "DELETE FROM user_academy_assignment WHERE user_id = :user_id";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);

            // Önce tüm mevcut ilişkileri sil
            if ($updateStmt->execute()) {
                // Silme işlemi başarılı olduysa, yeni ilişkileri ekleyin
                foreach ($selectedAcademies as $academyId) {
                    $insertQuery = "INSERT INTO user_academy_assignment (user_id, academy_id) VALUES (:user_id, :academy_id)";
                    $insertStmt = $db->prepare($insertQuery);
                    $insertStmt->bindParam(':academy_id', $academyId, PDO::PARAM_INT);
                    $insertStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);

                    if ($insertStmt->execute()) {
                        $successMessage = "Kullanıcının akademi ilişkileri başarıyla güncellendi.";
                    } else {
                        $errorMessage = "Hata: Kullanıcının akademi ilişkileri güncellenemedi.";
                    }
                }
            } else {
                $errorMessage = "Hata: Kullanıcının akademi ilişkileri silinemedi.";
            }
        }
    } elseif (isset($_POST["delete_submit"])) {
        // Silme işlemleri
        foreach ($_POST["delete_submit"] as $userId => $value) {
            $userIdToDelete = $userId;

            // Kullanıcıya ait tüm akademi ilişkilerini sil
            $deleteQuery = "DELETE FROM user_academy_assignment WHERE user_id = :user_id";
            $deleteStmt = $db->prepare($deleteQuery);
            $deleteStmt->bindParam(":user_id", $userIdToDelete, PDO::PARAM_INT);

            if ($deleteStmt->execute()) {
                $successMessage = "Kullanıcıya ait akademi ilişkileri başarıyla silindi.";
            } else {
                $errorMessage = "Hata: Kullanıcıya ait akademi ilişkileri silinemedi.";
            }
        }
    } elseif (isset($_POST["add_submit"])) {
        // Yetki ekleme formu gönderildiğinde
        $userId = $_POST["user_id"];
        $selectedAcademy = $_POST["add_academy"];

        // Seçilen akademiyi ekle
        $addQuery = "INSERT INTO user_academy_assignment (user_id, academy_id) VALUES (:user_id, :academy_id)";
        $addStatement = $db->prepare($addQuery);
        $addStatement->bindParam(':academy_id', $selectedAcademy, PDO::PARAM_INT);
        $addStatement->bindParam(':user_id', $userId, PDO::PARAM_INT);

        if ($addStatement->execute()) {
            // Ekleme işlemi başarılı olduysa, sayfayı yeniden yükle
            header("Location: ".$_SERVER['PHP_SELF']);
            exit();
        } else {
            $errorMessage = "Hata: İlişki eklenemedi.";
        }
    }
} else {
    $errorMessage = "Bu işlemi gerçekleştirmek için yeterli yetkiye sahip değilsiniz.";
}
}


// Kullanıcı bilgilerini ve yetkili olduğu akademileri çekmek için sorgu
$query = "SELECT u.id AS user_id, u.username, GROUP_CONCAT(a.id) AS assigned_academies, u.user_type
          FROM users u
          LEFT JOIN user_academy_assignment ua ON u.id = ua.user_id
          LEFT JOIN academies a ON ua.academy_id = a.id
          JOIN user_types ut ON u.user_type = ut.id
          WHERE ut.id IN (1, 2, 3)
          GROUP BY u.id, u.username, u.user_type";
$stmt = $db->query($query);
$userAssignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tüm akademileri çekmek için sorgu
$academyQuery = "SELECT id, name FROM academies";
$academyStmt = $db->query($academyQuery);
$academies = $academyStmt->fetchAll(PDO::FETCH_ASSOC);

// Tüm kullanıcı tiplerini çekmek için sorgu
$userTypeQuery = "SELECT id, type_name FROM user_types WHERE id IN (1, 2, 3)";
$userTypeStmt = $db->query($userTypeQuery);
$userTypes = $userTypeStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php require_once('../admin/partials/header.php'); ?>
<div class="container-fluid">
    <div class="row">
       <?php
        require_once(__DIR__ . '/partials/sidebar.php');
?>
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
                <h2 class="mb-3">Kullanıcı - Akademi İlişkileri</h2>
            </div>

            <?php if (isset($successMessage)): ?>
                <div class="alert alert-success" role="alert">
                    <?= $successMessage ?>
                </div>
            <?php endif; ?>

            <?php if (isset($errorMessage)): ?>
                <div class="alert alert-danger" role="alert">
                    <?= $errorMessage ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="thead-light">
                        <tr>
                            <th scope="col">Kullanıcı No</th>
                            <th scope="col">Kullanıcı Adı</th>
                            <th scope="col">İlişkili Akademiler</th>
                            <th scope="col">İşlemler</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($userAssignments) && !empty($academies)): ?>
                        <?php foreach ($userAssignments as $assignment): ?>
                            <tr>
                                <td><?= $assignment['user_id'] ?></td>
                                <td><?= $assignment['username'] ?></td>
                                <td>
                                    <?php foreach ($academies as $academy): ?>
                                        <?php
                                        // assigned_academies değeri null değilse ve bir dizeyse işlem yap
                                        if (!empty($assignment['assigned_academies']) && is_string($assignment['assigned_academies'])) {
                                            $checked = in_array($academy['id'], explode(",", $assignment['assigned_academies'])) ? "checked" : "";
                                        } else {
                                            $checked = "";
                                        }
                                        ?>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" name="academies[<?= $assignment['user_id'] ?>][]" value="<?= $academy['id'] ?>" <?= $checked ?>>
                                            <label class="form-check-label"><?= $academy['name'] ?></label>
                                        </div>
                                    <?php endforeach; ?>

                                </td>
                                <td>
                                    <input type="hidden" name="user_id" value="<?= $assignment['user_id'] ?>">
                                    <button type="submit" class="btn btn-primary" name="update_submit[<?= $assignment['user_id'] ?>]">Güncelle</button>
                                    <button type="submit" class="btn btn-danger" name="delete_submit[<?= $assignment['user_id'] ?>]">Sil</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>

                        </tbody>
                    </table>
                </div>
            </form>




            <?php require_once('../admin/partials/footer.php'); ?>
