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

// Post işlemi kontrolü
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Formdan gelen verileri alın
    $teacher_id = htmlspecialchars($_POST["teacher_id"], ENT_QUOTES, 'UTF-8');
    $academy_id = htmlspecialchars($_POST["academy_id"], ENT_QUOTES, 'UTF-8');
    $class_id = htmlspecialchars($_POST["class_id"], ENT_QUOTES, 'UTF-8');
    $student_id = htmlspecialchars($_POST["student_id"], ENT_QUOTES, 'UTF-8');
    $course_id = htmlspecialchars($_POST["course_id"], ENT_QUOTES, 'UTF-8');
    $course_date_1 = htmlspecialchars($_POST["course_date"], ENT_QUOTES, 'UTF-8');
    $course_attendance_1 = isset($_POST["course_attendance"]) ? 1 : 0;
    $created_by_user_id = $_SESSION["admin_id"]; // Şu anki oturumu açık olan admin kullanıcısının ID'sini alıyoruz.
    $created_at = date('Y-m-d H:i:s'); // Şu anki tarih ve saat bilgisini alıyoruz.


    $query = "INSERT INTO introductory_course_plans (teacher_id, academy_id, class_id, student_id, course_id, 
      course_date, course_attendance, created_by_user_id, created_at)
      VALUES (:teacher_id, :academy_id, :class_id, :student_id, :course_id, 
              :course_date, :course_attendance, :created_by_user_id, :created_at)";



    $stmt = $db->prepare($query);

    $stmt->bindParam(":teacher_id", $teacher_id, PDO::PARAM_INT);
    $stmt->bindParam(":academy_id", $academy_id, PDO::PARAM_INT);
    $stmt->bindParam(":class_id", $class_id, PDO::PARAM_INT);
    $stmt->bindParam(":student_id", $student_id, PDO::PARAM_INT);
    $stmt->bindParam(":course_id", $course_id, PDO::PARAM_INT);
    $stmt->bindParam(":course_date", $course_date_1, PDO::PARAM_STR);
    $stmt->bindParam(":course_attendance", $course_attendance_1, PDO::PARAM_INT);
    $stmt->bindParam(":created_by_user_id", $created_by_user_id, PDO::PARAM_INT);
    $stmt->bindParam(":created_at", $created_at, PDO::PARAM_STR);


// Sorguyu çalıştırın
    if ($stmt->execute()) {
        // Başarılı bir şekilde eklendiğinde yapılacak işlemler
        $alertMessage = "Tanışma dersi başarıyla planlandı.";
        $alertType = "success";
    } else {
        // Hata durumunda yapılacak işlemler
        $alertMessage = "Tanışma dersi planlanırken bir hata oluştu.";
        $alertType = "danger";
    }
}

// Öğrencileri çek
$studentQuery = "SELECT id, CONCAT(first_name, ' ', last_name) AS full_name FROM users WHERE user_type = 6";
$studentStmt = $db->query($studentQuery);
$students = $studentStmt->fetchAll(PDO::FETCH_ASSOC);

// Öğretmenleri çek
$teacherQuery = "SELECT id, CONCAT(first_name, ' ', last_name) AS full_name FROM users WHERE user_type = 4";
$teacherStmt = $db->query($teacherQuery);
$teachers = $teacherStmt->fetchAll(PDO::FETCH_ASSOC);

// Akademileri veritabanından çek
$academyQuery = "SELECT id, name FROM academies";
$academyStmt = $db->query($academyQuery);
$academies = $academyStmt->fetchAll(PDO::FETCH_ASSOC);

// Sınıfları veritabanından çek
$classQuery = "SELECT id, class_name FROM academy_classes";
$classStmt = $db->query($classQuery);
$classes = $classStmt->fetchAll(PDO::FETCH_ASSOC);

// Dersleri veritabanından çek
$courseQuery = "SELECT id, course_name FROM courses";
$courseStmt = $db->query($courseQuery);
$courses = $courseStmt->fetchAll(PDO::FETCH_ASSOC);

// Header ve sidebar dosyalarını dahil et
require_once "admin_panel_header.php";
require_once "admin_panel_sidebar.php";
?>

    <!-- Ana içerik -->
    <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
            <h2>Tanışma Dersi Planı Ekle</h2>
            <div class="btn-group mr-2">
                <button onclick="history.back()" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Geri dön
                </button>
                <a href="introductory_courses.php" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-list"></i> Tüm tanışma dersleri
                </a>
            </div>
        </div>

        <?php if (!empty($alertMessage)): ?>
            <div id="alert-container" class="alert alert-<?php echo $alertType; ?> alert-dismissible fade show" role="alert">
                <?php echo $alertMessage; ?>
            </div>

            <script>
                // Sayfa yüklendikten sonra uyarıyı otomatik kapat
                $(document).ready(function(){
                    // Belirli bir uyarı türüne göre kontrol et ve kapat
                    if ($("#alert-container").length > 0) {
                        setTimeout(function(){
                            $("#alert-container").fadeOut("slow");
                        }, 5000);
                    }
                });
            </script>
        <?php endif; ?>

        <form method="POST" action="add_introductory_course_plan.php">
            <!-- Öğretmen Dropdown -->
            <div class="form-group mt-3">
                <label for="teacher_id">Öğretmen</label>
                <select name="teacher_id" class="form-control">
                    <?php foreach ($teachers as $teacher): ?>
                        <option value="<?= $teacher['id'] ?>"><?= $teacher['full_name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Akademi Dropdown -->
            <div class="form-group mt-3">
                <label for="academy_id">Akademi</label>
                <select name="academy_id" class="form-control">
                    <?php foreach ($academies as $academy): ?>
                        <option value="<?= $academy['id'] ?>"><?= $academy['name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Sınıf Dropdown -->
            <div class="form-group mt-3">
                <label for="class_id">Sınıf</label>
                <select name="class_id" class="form-control">
                    <?php foreach ($classes as $class): ?>
                        <option value="<?= $class['id'] ?>"><?= $class['class_name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Öğrenci Dropdown -->
            <div class="form-group mt-3">
                <label for="student_id">Öğrenci</label>
                <select name="student_id" class="form-control">
                    <?php foreach ($students as $student): ?>
                        <option value="<?= $student['id'] ?>"><?= $student['full_name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Ders Dropdown -->
            <div class="form-group mt-3">
                <label for="course_id">Ders</label>
                <select name="course_id" class="form-control">
                    <?php foreach ($courses as $course): ?>
                        <option value="<?= $course['id'] ?>"><?= $course['course_name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Ders Tarihleri -->
            <?php
            $today = date('Y-m-d\TH:i'); // Bugünün tarihi ve saati
            ?>

            <div class="form-group mt-3">
                <label for="course_date">Tanışma Dersi Tarihi</label>
                <input type="datetime-local" name="course_date" class="form-control" value="<?= $today ?>">
            </div>


            <!-- Ders Katılımları
            <div class="form-group mt-3">
                <label>Katılım Durumu</label><br>
                <div class="form-check form-check-inline">
                    <input type="checkbox" name="course_attendance" class="form-check-input">
                    <label class="form-check-label">Tanışma Dersine Katılım</label>
                </div>
            </div> -->

            <button type="submit" class="btn btn-success mt-3">Ekle</button>
        </form>
    </main>


<?php require_once "footer.php"; ?>