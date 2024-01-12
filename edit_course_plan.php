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

// Form gönderildiyse
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $rowId = $_POST["rowId"]; // Eğer rowId POST ile gönderiliyorsa

    try {
        $academyId = $_POST["academyId"];
        $courseId = $_POST["courseId"];
        $classId = $_POST["classId"];
        $teacherId = $_POST["teacherId"];
        $studentId = $_POST["studentId"];
        $courseDate1 = $_POST["courseDate1"];
        $courseDate2 = $_POST["courseDate2"];
        $courseDate3 = $_POST["courseDate3"];
        $courseDate4 = $_POST["courseDate4"];
        $attendance1 = $_POST["attendance1"];
        $attendance2 = $_POST["attendance2"];
        $attendance3 = $_POST["attendance3"];
        $attendance4 = $_POST["attendance4"];

        // Veritabanında güncelleme sorgusunu hazırlayın
        $updateQuery = "UPDATE course_plans SET 
            academy_id = :academyId,
            course_id = :courseId,
            class_id = :classId,
            teacher_id = :teacherId,
            student_id = :studentId,
            course_date_1 = :courseDate1,
            course_date_2 = :courseDate2,
            course_date_3 = :courseDate3,
            course_date_4 = :courseDate4,
            course_attendance_1 = :attendance1,
            course_attendance_2 = :attendance2,
            course_attendance_3 = :attendance3,
            course_attendance_4 = :attendance4
            WHERE id = :rowId";

        $updateStatement = $db->prepare($updateQuery);

        // Değişkenleri bağlayın
        $updateStatement->bindParam(':academyId', $academyId, PDO::PARAM_INT);
        $updateStatement->bindParam(':courseId', $courseId, PDO::PARAM_INT);
        $updateStatement->bindParam(':classId', $classId, PDO::PARAM_INT);
        $updateStatement->bindParam(':teacherId', $teacherId, PDO::PARAM_INT);
        $updateStatement->bindParam(':studentId', $studentId, PDO::PARAM_INT);
        $updateStatement->bindParam(':courseDate1', $courseDate1, PDO::PARAM_STR);
        $updateStatement->bindParam(':courseDate2', $courseDate2, PDO::PARAM_STR);
        $updateStatement->bindParam(':courseDate3', $courseDate3, PDO::PARAM_STR);
        $updateStatement->bindParam(':courseDate4', $courseDate4, PDO::PARAM_STR);
        $updateStatement->bindParam(':attendance1', $attendance1, PDO::PARAM_INT);
        $updateStatement->bindParam(':attendance2', $attendance2, PDO::PARAM_INT);
        $updateStatement->bindParam(':attendance3', $attendance3, PDO::PARAM_INT);
        $updateStatement->bindParam(':attendance4', $attendance4, PDO::PARAM_INT);
        $updateStatement->bindParam(':rowId', $rowId, PDO::PARAM_INT);

        // Sorguyu çalıştırın
        $updateStatement->execute();

        // Güncelleme işlemi tamamlandıktan sonra bir önceki sayfaya yönlendirin
        if (isset($_SESSION['previous_page'])) {
            $previousPage = $_SESSION['previous_page'];
            unset($_SESSION['previous_page']); // Session'dan URL'yi temizle
        } else {
            // Eğer geçerli bir URL değilse, varsayılan bir sayfaya yönlendirin
            $previousPage = "course_plans.php"; // Burada yönlendirmek istediğiniz sayfanın adını belirtin
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
        $selectQuery = "SELECT * FROM course_plans WHERE id = :rowId";
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
                'course_date_1' => '',
                'course_date_2' => '',
                'course_date_3' => '',
                'course_date_4' => '',
                'course_attendance_1' => '',
                'course_attendance_2' => '',
                'course_attendance_3' => '',
                'course_attendance_4' => ''
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
        <h2>Ders Planı Düzenle</h2>
    </div>

    <form action='edit_course_plan.php' method='post'>
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
            <label for='courseDate1'>1. Ders Tarihi:</label>
            <input type='datetime-local' class="form-control" id='courseDate1' name='courseDate1' value="<?php echo date('Y-m-d\TH:i', strtotime($result['course_date_1'])); ?>" required>
        </div>

        <label for='courseDate2'>2. Ders Tarihi:</label>
        <input type='datetime-local' class="form-control" id='courseDate2' name='courseDate2' value="<?php echo date('Y-m-d\TH:i', strtotime($result['course_date_2'])); ?>" required>

        <label for='courseDate3'>3. Ders Tarihi:</label>
        <input type='datetime-local' class="form-control" id='courseDate3' name='courseDate3' value="<?php echo date('Y-m-d\TH:i', strtotime($result['course_date_3'])); ?>" required>

        <label for='courseDate4'>4. Ders Tarihi:</label>
        <input type='datetime-local' class="form-control" id='courseDate4' name='courseDate4' value="<?php echo date('Y-m-d\TH:i', strtotime($result['course_date_4'])); ?>" required>

        <label for='attendance1'>1. Katılım:</label>
        <select class="form-control" id='attendance1' name='attendance1' required>
            <option value='0' <?php echo ($result["course_attendance_1"] == 0) ? "selected" : ""; ?>>Henüz katılmadı</option>
            <option value='1' <?php echo ($result["course_attendance_1"] == 1) ? "selected" : ""; ?>>Katıldı</option>
            <option value='2' <?php echo ($result["course_attendance_1"] == 2) ? "selected" : ""; ?>>Katılmadı</option>
            <option value='3' <?php echo ($result["course_attendance_1"] == 3) ? "selected" : ""; ?>>Mazeretli</option>
        </select>

        <label for='attendance2'>2. Katılım:</label>
        <select class="form-control" id='attendance2' name='attendance2' required>
            <option value='0' <?php echo ($result["course_attendance_2"] == 0) ? "selected" : ""; ?>>Henüz katılmadı</option>
            <option value='1' <?php echo ($result["course_attendance_2"] == 1) ? "selected" : ""; ?>>Katıldı</option>
            <option value='2' <?php echo ($result["course_attendance_3"] == 2) ? "selected" : ""; ?>>Katılmadı</option>
            <option value='3' <?php echo ($result["course_attendance_3"] == 3) ? "selected" : ""; ?>>Mazeretli</option>
        </select>

        <label for='attendance3'>3. Katılım:</label>
        <select class="form-control" id='attendance3' name='attendance3' required>
            <option value='0' <?php echo ($result["course_attendance_3"] == 0) ? "selected" : ""; ?>>Henüz katılmadı</option>
            <option value='1' <?php echo ($result["course_attendance_3"] == 1) ? "selected" : ""; ?>>Katıldı</option>
            <option value='2' <?php echo ($result["course_attendance_3"] == 2) ? "selected" : ""; ?>>Katılmadı</option>
            <option value='3' <?php echo ($result["course_attendance_3"] == 3) ? "selected" : ""; ?>>Mazeretli</option>
        </select>

        <label for='attendance4'>4. Katılım:</label>
        <select class="form-control" id='attendance4' name='attendance4' required>
            <option value='0' <?php echo ($result["course_attendance_4"] == 0) ? "selected" : ""; ?>>Henüz katılmadı</option>
            <option value='1' <?php echo ($result["course_attendance_4"] == 1) ? "selected" : ""; ?>>Katıldı</option>
            <option value='2' <?php echo ($result["course_attendance_4"] == 2) ? "selected" : ""; ?>>Katılmadı</option>
            <option value='3' <?php echo ($result["course_attendance_4"] == 3) ? "selected" : ""; ?>>Mazeretli</option>
        </select>

        <button type='submit' class="btn btn-primary mt-3">Güncelle</button>

</form>

<?php require_once "footer.php"; ?>