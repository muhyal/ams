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

// Oturum kontrolü
session_start();
session_regenerate_id(true);

if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php"); // Giriş sayfasına yönlendir
    exit();
}
require_once "db_connection.php";
require_once "config.php";

// Yetki kontrolü fonksiyonu
function checkPermission() {
    if ($_SESSION["admin_type"] != 1) {
        // Yetki hatası
        echo "Bu işlemi gerçekleştirmek için yetkiniz yok!";
        exit();
    }
}

// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);

// Ders ekleme işlemi
if (isset($_POST["add_course"])) {
    checkPermission(); // Yetki kontrolü
    $courseName = htmlspecialchars($_POST["course_name"], ENT_QUOTES, 'UTF-8');
    $courseCode = htmlspecialchars($_POST["course_code"], ENT_QUOTES, 'UTF-8');
    $courseDescription = htmlspecialchars($_POST["description"], ENT_QUOTES, 'UTF-8');


    $query = "INSERT INTO courses (course_name, course_code, description) VALUES (?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->execute([$courseName, $courseCode, $courseDescription]);
}

// Ders düzenleme işlemi
if (isset($_POST["edit_course"])) {
    checkPermission(); // Yetki kontrolü
    $id = htmlspecialchars($_POST["id"], ENT_QUOTES, 'UTF-8');
    $courseName = htmlspecialchars($_POST["course_name"], ENT_QUOTES, 'UTF-8');
    $courseCode = htmlspecialchars($_POST["course_code"], ENT_QUOTES, 'UTF-8');
    $courseDescription = htmlspecialchars($_POST["description"], ENT_QUOTES, 'UTF-8');


    $query = "UPDATE courses SET course_name = ?, course_code = ?, description = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$courseName, $courseCode, $courseDescription, $id]);
}

// Ders silme işlemi
if (isset($_GET["delete_id"])) {
    checkPermission(); // Yetki kontrolü
    $deleteId = $_GET["delete_id"];

    $query = "DELETE FROM courses WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$deleteId]);
}

$courses = $db->query("SELECT * FROM courses")->fetchAll(PDO::FETCH_ASSOC);
?>
<?php require_once "admin_panel_header.php"; ?>
<div class="container-fluid">
    <div class="row">
        <?php require_once "admin_panel_sidebar.php"; ?>
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
                <h2>Ders Yönetimi</h2>
            </div>

            <!-- Ders Düzenleme Formu -->
            <?php
            if (isset($_GET["edit_id"])) {
                $editId = $_GET["edit_id"];
                $editCourse = $db->query("SELECT * FROM courses WHERE id = $editId")->fetch(PDO::FETCH_ASSOC);
                ?>
                <h2>Ders Düzenle</h2>
                <form method="post">
                    <input type="hidden" name="id" value="<?php echo $editCourse["id"]; ?>">
                    <div class="mb-3">
                        <label for="course_name" class="form-label">Ders Adı:</label>
                        <input type="text" id="course_name" name="course_name" class="form-control" value="<?php echo $editCourse["course_name"]; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Ders Açıklaması:</label>
                        <input type="text" id="description" name="description" class="form-control" value="<?php echo $editCourse["description"]; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="course_code" class="form-label">Ders Kodu:</label>
                        <input type="text" id="course_code" name="course_code" class="form-control" value="<?php echo $editCourse["course_code"]; ?>" required>
                    </div>
                    <button type="submit" name="edit_course" class="btn btn-primary">Ders Düzenle</button>
                </form>
            <?php } ?>

            <!-- Ders Listesi -->
            <h2>Ders Listesi</h2>
            <table class="table">
                <thead>
                <tr>
                    <th scope="col">No</th>
                    <th scope="col">Ders Adı</th>
                    <th scope="col">Ders Açıklaması</th>
                    <th scope="col">Ders Kodu</th>
                    <th scope="col">İşlemler</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($courses as $course): ?>
                    <tr>
                        <td><?php echo $course["id"]; ?></td>
                        <td><?php echo $course["course_name"]; ?></td>
                        <td><?php echo $course["description"]; ?></td>
                        <td><?php echo $course["course_code"]; ?></td>
                        <td>
                            <a href="?edit_id=<?php echo $course["id"]; ?>" class="btn btn-warning">Düzenle</a>
                            <a href="?delete_id=<?php echo $course["id"]; ?>" onclick="return confirm('Dersi silmek istediğinizden emin misiniz?')" class="btn btn-danger">Sil</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Ders Ekleme Formu -->
            <h2>Ders Ekle</h2>
            <form method="post">
                <div class="mb-3">
                    <label for="course_name" class="form-label">Ders Adı:</label>
                    <input type="text" id="course_name" name="course_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Ders Açıklaması:</label>
                    <input type="text" id="description" name="description" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="course_code" class="form-label">Ders Kodu:</label>
                    <input type="text" id="course_code" name="course_code" class="form-control" required>
                </div>
                <button type="submit" name="add_course" class="btn btn-success">Ders Ekle</button>
            </form>
            <?php require_once "footer.php"; ?>
