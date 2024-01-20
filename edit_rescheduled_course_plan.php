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

require_once "config/db_connection.php";
require_once "config/config.php";

// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);

// Form gönderildiyse
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $rowId = $_POST["rowId"]; // Eğer rowId POST ile gönderiliyorsa

    try {
        $academyId = htmlspecialchars($_POST["academyId"], ENT_QUOTES, 'UTF-8');
        $courseId = htmlspecialchars($_POST["courseId"], ENT_QUOTES, 'UTF-8');
        $classId = htmlspecialchars($_POST["classId"], ENT_QUOTES, 'UTF-8');
        $teacherId = htmlspecialchars($_POST["teacherId"], ENT_QUOTES, 'UTF-8');
        $studentId = htmlspecialchars($_POST["studentId"], ENT_QUOTES, 'UTF-8');
        $courseDate1 = htmlspecialchars($_POST["courseDate"], ENT_QUOTES, 'UTF-8');
        $attendance1 = htmlspecialchars($_POST["attendance"], ENT_QUOTES, 'UTF-8');


        // Veritabanında güncelleme sorgusunu hazırlayın
        $updateQuery = "UPDATE rescheduled_courses SET 
            academy_id = :academyId,
            course_id = :courseId,
            class_id = :classId,
            teacher_id = :teacherId,
            student_id = :studentId,
            course_date = :courseDate,
            course_attendance = :attendance
            WHERE id = :rowId";

        $updateStatement = $db->prepare($updateQuery);

        // Değişkenleri bağlayın
        $updateStatement->bindParam(':academyId', $academyId, PDO::PARAM_INT);
        $updateStatement->bindParam(':courseId', $courseId, PDO::PARAM_INT);
        $updateStatement->bindParam(':classId', $classId, PDO::PARAM_INT);
        $updateStatement->bindParam(':teacherId', $teacherId, PDO::PARAM_INT);
        $updateStatement->bindParam(':studentId', $studentId, PDO::PARAM_INT);
        $updateStatement->bindParam(':courseDate', $courseDate1, PDO::PARAM_STR);
        $updateStatement->bindParam(':attendance', $attendance1, PDO::PARAM_INT);
        $updateStatement->bindParam(':rowId', $rowId, PDO::PARAM_INT);

        // Sorguyu çalıştırın
        $updateStatement->execute();

        // Güncelleme işlemi tamamlandıktan sonra bir önceki sayfaya yönlendirin
        if (isset($_SESSION['previous_page'])) {
            $previousPage = $_SESSION['previous_page'];
            unset($_SESSION['previous_page']); // Session'dan URL'yi temizle
        } else {
            // Eğer geçerli bir URL değilse, varsayılan bir sayfaya yönlendirin
            $previousPage = "rescheduled_courses.php"; // Burada yönlendirmek istediğiniz sayfanın adını belirtin
        }

        header("Location: $previousPage");
        exit();


    } catch (PDOException $e) {
        // Hata durumunda işlemleri burada ele alın (hata mesajını gösterme, kayıt, vb.)
        echo "Error: " . $e->getMessage();
    }
}

// Eğer rowId GET ile gönderilmişse
if (isset($_GET["id"])) {
    $rowId = $_GET["id"];

    try {
        $selectQuery = "SELECT * FROM rescheduled_courses WHERE id = :rowId";
        $selectStatement = $db->prepare($selectQuery);
        $selectStatement->bindParam(':rowId', $rowId, PDO::PARAM_INT);
        $selectStatement->execute();

        // Alınan verilerle düzenleme formunu gösterin
        $result = $selectStatement->fetch(PDO::FETCH_ASSOC);

        // Eğer $result tanımlı değilse, varsayılan bir değerle tanımlayın
        if (!$result) {
            $result = array(
                'academy_id' => '',
                'course_id' => '',
                'class_id' => '',
                'teacher_id' => '',
                'student_id' => '',
                'course_date' => '',
                'course_attendance' => ''
            );
        }
    } catch (PDOException $e) {
        // Hata durumunda işlemleri burada ele alın (hata mesajını gösterme, kayıt, vb.)
        echo "Error: " . $e->getMessage();
    }
}


// Mevcut akademileri çek
$academyQuery = "SELECT * FROM academies";
$academyStatement = $db->query($academyQuery);
$academies = $academyStatement->fetchAll(PDO::FETCH_ASSOC);

// Mevcut dersleri çek
$courseQuery = "SELECT * FROM courses";
$courseStatement = $db->query($courseQuery);
$courses = $courseStatement->fetchAll(PDO::FETCH_ASSOC);

// Mevcut sınıfları çek
$classQuery = "SELECT * FROM academy_classes";
$classStatement = $db->query($classQuery);
$classes = $classStatement->fetchAll(PDO::FETCH_ASSOC);

// Mevcut öğretmenleri çek
$teacherQuery = "SELECT * FROM users WHERE user_type = 4";
$teacherStatement = $db->query($teacherQuery);
$teachers = $teacherStatement->fetchAll(PDO::FETCH_ASSOC);

// Mevcut öğrencileri çek
$studentQuery = "SELECT * FROM users WHERE user_type = 6";
$studentStatement = $db->query($studentQuery);
$students = $studentStatement->fetchAll(PDO::FETCH_ASSOC);
?>
<?php require_once "admin_panel_header.php"; ?>
    <div class="container-fluid">
    <div class="row">
<?php require_once "admin_panel_sidebar.php"; ?>
    <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
        <h2>Telafi Dersi Planı Düzenle</h2>
    </div>

    <form action='edit_rescheduled_course_plan.php' method='post'>
        <input type='hidden' name='rowId' value='<?php echo htmlspecialchars($rowId); ?>'>

        <div class="form-group">
            <label for='academyId'>Akademi:</label>
            <select class="form-control" id='academyId' name='academyId' required>
                <?php foreach ($academies as $academy) : ?>
                    <option value='<?php echo $academy["id"]; ?>' <?php echo ($academy["id"] == $result["academy_id"]) ? "selected" : ""; ?>>
                        <?php echo htmlspecialchars($academy["name"]); ?>
                    </option>

                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for='courseId'>Ders:</label>
            <select class="form-control" id='courseId' name='courseId' required>
                <?php foreach ($courses as $course) : ?>
                    <option value='<?php echo $course["id"]; ?>' <?php echo ($course["id"] == $result["course_id"]) ? "selected" : ""; ?>>
                        <?php echo htmlspecialchars($course["course_name"]); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for='classId'>Sınıf:</label>
            <select class="form-control" id='classId' name='classId' required>
                <?php foreach ($classes as $class) : ?>
                    <option value='<?php echo $class["id"]; ?>' <?php echo ($class["id"] == $result["class_id"]) ? "selected" : ""; ?>>
                        <?php echo htmlspecialchars($class["class_name"]); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for='teacherId'>Öğretmen:</label>
            <select class="form-control" id='teacherId' name='teacherId' required>
                <?php foreach ($teachers as $teacher) : ?>
                    <option value='<?php echo $teacher["id"]; ?>' <?php echo ($teacher["id"] == $result["teacher_id"]) ? "selected" : ""; ?>>
                        <?php echo htmlspecialchars($teacher["first_name"] . " " . $teacher["last_name"]); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for='studentId'>Öğrenci:</label>
            <select class="form-control" id='studentId' name='studentId' required>
                <?php foreach ($students as $student) : ?>
                    <option value='<?php echo $student["id"]; ?>' <?php echo ($student["id"] == $result["student_id"]) ? "selected" : ""; ?>>
                        <?php echo htmlspecialchars($student["first_name"] . " " . $student["last_name"]); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for='courseDate'>Ders Tarihi:</label>
            <input type='datetime-local' class="form-control" id='courseDate' name='courseDate' value="<?php echo date('Y-m-d\TH:i', strtotime($result['course_date'])); ?>" required>
        </div>

        <label for='attendance'>Katılım:</label>
        <select class="form-control" id='attendance' name='attendance' required>
            <option value='0' <?php echo ($result["course_attendance"] == 0) ? "selected" : ""; ?>>Henüz katılmadı</option>
            <option value='1' <?php echo ($result["course_attendance"] == 1) ? "selected" : ""; ?>>Katıldı</option>
            <option value='2' <?php echo ($result["course_attendance"] == 2) ? "selected" : ""; ?>>Katılmadı</option>
        </select>

        <button type='submit' class="btn btn-primary mt-3">Güncelle</button>

</form>

<?php require_once "footer.php"; ?>