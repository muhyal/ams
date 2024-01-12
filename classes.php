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

global $db, $showErrors, $siteName, $siteShortName, $siteUrl;

// Oturum kontrolü
session_start();
session_regenerate_id(true);

if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php"); // Giriş sayfasına yönlendir
    exit();
}

require_once "db_connection.php"; // Veritabanı bağlantısı
require_once "config.php";

// Sınıf ekleme işlemi
if (isset($_POST["add_class"])) {
    $className = $_POST["class_name"];
    $classCode = $_POST["class_code"];
    $classDescription = $_POST["description"];

    $query = "INSERT INTO academy_classes (class_name, class_code, class_description) VALUES (?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->execute([$className, $classCode, $classDescription]);
}

// Sınıf düzenleme işlemi
if (isset($_POST["edit_class"])) {
    $id = $_POST["id"];
    $className = $_POST["class_name"];
    $classCode = $_POST["class_code"];
    $classDescription = $_POST["description"];

    $query = "UPDATE academy_classes SET class_name = ?, class_code = ?, class_description = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$className, $classCode, $classDescription, $id]);
}

// Sınıf silme işlemi
if (isset($_GET["delete_id"])) {
    $deleteId = $_GET["delete_id"];

    $query = "DELETE FROM academy_classes WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$deleteId]);
}

$classes = $db->query("SELECT * FROM academy_classes")->fetchAll(PDO::FETCH_ASSOC);
?>

<?php require_once "admin_panel_header.php"; ?>
<div class="container-fluid">
    <div class="row">
        <?php require_once "admin_panel_sidebar.php"; ?>
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
                <h2>Sınıf Yönetimi</h2>
            </div>

            <!-- Sınıf Düzenleme Formu -->
            <?php
            if (isset($_GET["edit_id"])) {
                $editId = $_GET["edit_id"];
                $editClass = $db->query("SELECT * FROM academy_classes WHERE id = $editId")->fetch(PDO::FETCH_ASSOC);
                ?>
                <h2>Sınıf Düzenle</h2>
                <form method="post">
                    <input type="hidden" name="id" value="<?php echo $editClass["id"]; ?>">
                    <div class="mb-3">
                        <label for="class_name" class="form-label">Sınıf Adı:</label>
                        <input type="text" id="class_name" name="class_name" class="form-control" value="<?php echo $editClass["class_name"]; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Sınıf Açıklaması:</label>
                        <input type="text" id="description" name="description" class="form-control" value="<?php echo $editClass["class_description"]; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="class_code" class="form-label">Sınıf Kodu:</label>
                        <input type="text" id="class_code" name="class_code" class="form-control" value="<?php echo $editClass["class_code"]; ?>" required>
                    </div>
                    <button type="submit" name="edit_class" class="btn btn-primary">Sınıf Düzenle</button>
                </form>
            <?php } ?>

            <!-- Sınıf Listesi -->
            <h2>Sınıf Listesi</h2>
            <table class="table">
                <thead>
                <tr>
                    <th scope="col">No</th>
                    <th scope="col">Sınıf Adı</th>
                    <th scope="col">Sınıf Açıklaması</th>
                    <th scope="col">Sınıf Kodu</th>
                    <th scope="col">İşlemler</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($classes as $class): ?>
                    <tr>
                        <td><?php echo $class["id"]; ?></td>
                        <td><?php echo $class["class_name"]; ?></td>
                        <td><?php echo $class["class_description"]; ?></td>
                        <td><?php echo $class["class_code"]; ?></td>
                        <td>
                            <a href="?edit_id=<?php echo $class["id"]; ?>" class="btn btn-warning">Düzenle</a>
                            <a href="?delete_id=<?php echo $class["id"]; ?>" onclick="return confirm('Sınıfı silmek istediğinizden emin misiniz?')" class="btn btn-danger">Sil</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Sınıf Ekleme Formu -->
            <h2>Sınıf Ekle</h2>
            <form method="post">
                <div class="mb-3">
                    <label for="class_name" class="form-label">Sınıf Adı:</label>
                    <input type="text" id="class_name" name="class_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Sınıf Açıklaması:</label>
                    <input type="text" id="description" name="description" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="class_code" class="form-label">Sınıf Kodu:</label>
                    <input type="text" id="class_code" name="class_code" class="form-control" required>
                </div>
                <button type="submit" name="add_class" class="btn btn-success">Sınıf Ekle</button>
            </form>
            <?php require_once "footer.php"; ?>
