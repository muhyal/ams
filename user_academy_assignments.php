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
    header("Location: admin_login.php"); // Giriş sayfasına yönlendir
    exit();
}

require_once "db_connection.php";
require_once "config.php";

// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);

// Form gönderildiğinde
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["update_submit"])) {
        // Güncelleme formu gönderildiğinde
        $userId = $_POST["user_id"];
        $selectedAcademies = isset($_POST["academies"]) ? $_POST["academies"] : array();

        // Kullanıcının mevcut yetkili olduğu akademileri çek
        $currentAcademiesQuery = "SELECT academy_id FROM user_academy_assignment WHERE user_id = :user_id";
        $currentAcademiesStatement = $db->prepare($currentAcademiesQuery);
        $currentAcademiesStatement->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $currentAcademiesStatement->execute();
        $currentAcademies = $currentAcademiesStatement->fetchAll(PDO::FETCH_COLUMN);

        // Yeni seçilen akademileri ekle
        $addAcademies = array_diff($selectedAcademies, $currentAcademies);
        foreach ($addAcademies as $academyId) {
            $addQuery = "INSERT INTO user_academy_assignment (user_id, academy_id) VALUES (:user_id, :academy_id)";
            $addStatement = $db->prepare($addQuery);
            $addStatement->bindParam(':academy_id', $academyId, PDO::PARAM_INT);
            $addStatement->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $addStatement->execute();
        }

        // Kaldırılan akademileri sil
        $removeAcademies = array_diff($currentAcademies, $selectedAcademies);
        foreach ($removeAcademies as $academyId) {
            $removeQuery = "DELETE FROM user_academy_assignment WHERE user_id = :user_id AND academy_id = :academy_id";
            $removeStatement = $db->prepare($removeQuery);
            $removeStatement->bindParam(':academy_id', $academyId, PDO::PARAM_INT);
            $removeStatement->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $removeStatement->execute();
        }

        // Güncelleme işlemi başarılı olduysa, sayfayı yeniden yükle
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    } elseif (isset($_POST["delete_submit"])) {
        // Silme formu gönderildiğinde
        $userId = $_POST["user_id"];

        // Kullanıcının yetkilendirildiği akademileri sil
        $deleteQuery = "DELETE FROM user_academy_assignment WHERE user_id = :user_id";
        $deleteStatement = $db->prepare($deleteQuery);
        $deleteStatement->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $deleteStatement->execute();

        // Silme işlemi başarılı olduysa, sayfayı yeniden yükle
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    } elseif (isset($_POST["add_submit"])) {
        // Yetki ekleme formu gönderildiğinde
        $userId = $_POST["user_id"];
        $selectedAcademy = $_POST["add_academy"];

        // Seçilen akademiyi ekle
        $addQuery = "INSERT INTO user_academy_assignment (user_id, academy_id) VALUES (:user_id, :academy_id)";
        $addStatement = $db->prepare($addQuery);
        $addStatement->bindParam(':academy_id', $selectedAcademy, PDO::PARAM_INT);
        $addStatement->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $addStatement->execute();

        // Ekleme işlemi başarılı olduysa, sayfayı yeniden yükle
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    }
}

// Kullanıcı bilgilerini ve yetkili olduğu akademileri çekmek için sorgu
$query = "SELECT ua.user_id, u.username, GROUP_CONCAT(a.id) AS assigned_academies
          FROM user_academy_assignment ua
          JOIN users u ON ua.user_id = u.id
          JOIN academies a ON ua.academy_id = a.id
          JOIN user_types ut ON u.user_type = ut.id
          WHERE u.user_type NOT IN (4,5,6)
          GROUP BY ua.user_id, u.username";
$stmt = $db->query($query);
$userAssignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tüm akademileri çekmek için sorgu
$academyQuery = "SELECT id, name FROM academies";
$academyStmt = $db->query($academyQuery);
$academies = $academyStmt->fetchAll(PDO::FETCH_ASSOC);

// Tüm kullanıcı tiplerini çekmek için sorgu
$userTypeQuery = "SELECT id, type_name FROM user_types WHERE id NOT IN (4,5,6)";
$userTypeStmt = $db->query($userTypeQuery);
$userTypes = $userTypeStmt->fetchAll(PDO::FETCH_ASSOC);

?>
<?php require_once "admin_panel_header.php"; ?>
<div class="container-fluid">
    <div class="row">
        <?php require_once "admin_panel_sidebar.php"; ?>
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
                <h2 class="mb-3">Kullanıcı - Akademi İlişkileri</h2>
            </div>

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
                        <?php foreach ($userAssignments as $assignment): ?>
                            <tr>
                                <td><?= $assignment['user_id'] ?></td>
                                <td><?= $assignment['username'] ?></td>
                                <td>
                                    <?php foreach ($academies as $academy): ?>
                                        <?php
                                        $checked = in_array($academy['id'], explode(",", $assignment['assigned_academies'])) ? "checked" : "";
                                        ?>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" name="academies[]" value="<?= $academy['id'] ?>" <?= $checked ?>>
                                            <label class="form-check-label"><?= $academy['name'] ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                </td>
                                <td>
                                    <input type="hidden" name="user_id" value="<?= $assignment['user_id'] ?>">
                                    <button type="submit" class="btn btn-primary" name="update_submit">Güncelle</button>
                                    <button type="submit" class="btn btn-danger" name="delete_submit">Sil</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    <h3>İlişki Ekle</h3>
                    <div class="form-row">
                        <div class="col-md-4 mb-3">
                            <label for="add_user">Kullanıcı seç:</label>
                            <select class="form-control" name="user_id" id="add_user">
                                <?php foreach ($userAssignments as $assignment): ?>
                                    <option value="<?= $assignment['user_id'] ?>"><?= $assignment['username'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="add_academy">Akademi seç:</label>
                            <select class="form-control" name="add_academy" id="add_academy">
                                <?php foreach ($academies as $academy): ?>
                                    <option value="<?= $academy['id'] ?>"><?= $academy['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <button type="submit" class="btn btn-success" name="add_submit">İlişki Ekle</button>
                        </div>
                    </div>
                </div>
            </form>

            <?php require_once "footer.php"; ?>
