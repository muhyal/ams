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
// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);

// Oturum kontrolü
session_start();
session_regenerate_id(true);

if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php"); // Giriş sayfasına yönlendir
    exit();
}

require_once "config.php";
require_once "db_connection.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST["id"];
    $firstName = $_POST["first_name"];
    $lastName = $_POST["last_name"];
    $tcIdentity = $_POST["tc_identity"];
    $birthDate = $_POST["birth_date"];
    $phone = $_POST["phone"];
    $email = $_POST["email"];
    $selectedCourse = $_POST["course"];
    $selectedClass = $_POST["class"];

    // Veritabanındaki öğretmeni güncelle
    $query = "UPDATE teachers SET first_name = ?, last_name = ?, tc_identity = ?, birth_date = ?, phone = ?, email = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$firstName, $lastName, $tcIdentity, $birthDate, $phone, $email, $id]);

}


if (isset($_GET["id"])) {
    $teacher_id = $_GET["id"];
    $select_query = "SELECT teachers.*, courses.course_name, classes.class_name 
                     FROM teachers
                     LEFT JOIN teacher_courses ON teachers.id = teacher_courses.teacher_id
                     LEFT JOIN courses ON teacher_courses.course_id = courses.id
                     LEFT JOIN classes ON teacher_courses.class_id = classes.id
                     WHERE teachers.id = ?";
    $stmt = $db->prepare($select_query);
    $stmt->execute([$teacher_id]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
}
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
            <h2>Öğretmen Düzenle</h2>
        </div>

        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group mr-2">
                            <button onclick="history.back()" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Geri dön
                            </button>
                            <a href="teacher_list.php" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-list"></i> Öğretmen Listesi
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <br>
        <form method="post">
            <input type="hidden" name="id" value="<?php echo $teacher["id"]; ?>">

            <div class="mb-3">
                <label for="first_name" class="form-label">Adı:</label>
                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo $teacher["first_name"]; ?>" required>
            </div>

            <div class="mb-3">
                <label for="last_name" class="form-label">Soyadı:</label>
                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo $teacher["last_name"]; ?>" required>
            </div>

            <div class="mb-3">
                <label for="tc_identity" class="form-label">T.C. Kimlik No:</label>
                <input type="text" class="form-control" id="tc_identity" name="tc_identity" value="<?php echo $teacher["tc_identity"]; ?>" required>
            </div>

            <div class="mb-3">
                <label for="birth_date" class="form-label">Doğum Tarihi:</label>
                <input type="date" class="form-control" id="birth_date" name="birth_date" value="<?php echo $teacher["birth_date"]; ?>" required>
            </div>

            <div class="mb-3">
                <label for="phone" class="form-label">Telefon:</label>
                <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo $teacher["phone"]; ?>">
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">E-posta:</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo $teacher["email"]; ?>" required>
            </div>
            <div class="form-group mt-3">
            <button type="submit" name="edit_teacher" class="btn btn-primary">Öğretmeni Düzenle</button>
            </div>
        </form>
    </main>

<?php
require_once "footer.php";
?>