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

$selectedPlanId = isset($_GET['id']) ? $_GET['id'] : null;


// course_plan_id'yi URL parametresinden al
$selectedCoursePlanId = isset($_GET['course_plan_id']) ? $_GET['course_plan_id'] : null;
$selectedCoursePlan = null;

// Eğer course_plan_id varsa, ilgili ders planını getir
if ($selectedCoursePlanId) {
    $querySelectedCoursePlan = "
        SELECT
            sc.id,
            CONCAT(u_teacher.first_name, ' ', u_teacher.last_name) AS teacher_name,
            u_teacher.id AS teacher_id,
            a.name AS academy_name,
            ac.class_name AS class_name,
            CONCAT(u_student.first_name, ' ', u_student.last_name) AS student_name,
            u_student.id AS student_id,
            c.course_name AS lesson_name,
            sc.course_date_1,
            sc.course_date_2,
            sc.course_date_3,
            sc.course_date_4,
            sc.course_attendance_1,
            sc.course_attendance_2,
            sc.course_attendance_3,
            sc.course_attendance_4
        FROM
            course_plans sc
            INNER JOIN users u_teacher ON sc.teacher_id = u_teacher.id AND u_teacher.user_type = 4
            INNER JOIN academies a ON sc.academy_id = a.id
            INNER JOIN academy_classes ac ON sc.class_id = ac.id
            INNER JOIN users u_student ON sc.student_id = u_student.id AND u_student.user_type = 6
            INNER JOIN courses c ON sc.course_id = c.id
        WHERE
            sc.id = :coursePlanId
    ";

    $stmtSelectedCoursePlan = $db->prepare($querySelectedCoursePlan);
    $stmtSelectedCoursePlan->bindParam(':coursePlanId', $selectedCoursePlanId, PDO::PARAM_INT);
    $stmtSelectedCoursePlan->execute();
    $selectedCoursePlan = $stmtSelectedCoursePlan->fetch(PDO::FETCH_ASSOC);
}

// Post işlemi kontrolü
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Formdan gelen verileri alın
    $teacher_id = $_POST["teacher_id"];
    $academy_id = $_POST["academy_id"];
    $class_id = $_POST["class_id"];
    $student_id = $_POST["student_id"];
    $course_id = $_POST["course_id"];
    $course_date = $_POST["course_date"]; // Use $_POST to get the value
    $course_attendance = isset($_POST["course_attendance"]) ? 1 : 0;


    $query = "INSERT INTO rescheduled_courses (course_plan_id, teacher_id, academy_id, class_id, student_id, course_id, 
          course_date,
          course_attendance)
          VALUES (:course_plan_id, :teacher_id, :academy_id, :class_id, :student_id, :course_id, 
                  :course_date,
                  :course_attendance)";

    $stmt = $db->prepare($query);

    $stmt->bindParam(":course_plan_id", $selectedCoursePlanId, PDO::PARAM_INT);
    $stmt->bindParam(":teacher_id", $teacher_id, PDO::PARAM_INT);
    $stmt->bindParam(":academy_id", $academy_id, PDO::PARAM_INT);
    $stmt->bindParam(":class_id", $class_id, PDO::PARAM_INT);
    $stmt->bindParam(":student_id", $student_id, PDO::PARAM_INT);
    $stmt->bindParam(":course_id", $course_id, PDO::PARAM_INT);
    $stmt->bindParam(":course_date", $course_date, PDO::PARAM_STR);
    $stmt->bindParam(":course_attendance", $course_attendance, PDO::PARAM_INT);

    // Sorguyu çalıştırın
    if ($stmt->execute()) {
        // Başarılı bir şekilde eklendiğinde yapılacak işlemler
        echo "Ders başarıyla planlandı.";
    } else {
        // Hata durumunda yapılacak işlemler
        echo "Ders planlanırken bir hata oluştu.";
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
            <h2>Telafi Dersi Planı Ekle</h2>
            <div class="btn-group mr-2">
                <button onclick="history.back()" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Geri dön
                </button>
                <a href="rescheduled_courses.php" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-list"></i> Tüm telafi dersleri
                </a>
            </div>
        </div>

        <form method="POST" action="add_rescheduled_course_plan.php?course_plan_id=<?= $selectedCoursePlanId ?>">
            <input type="hidden" name="course_plan_id" value="<?= $selectedCoursePlan['id'] ?? '' ?>">

            <?php
            if ($selectedCoursePlan) {
                echo "
        <div class='card border-success mb-3'>
            <div class='card-header bg-success text-white'>
                <h5 class='card-title'>Telafisi Yapılacak Dersin Bilgileri</h5>
            </div>
            <div class='card-body'>
                <p class='card-text'><strong>Öğretmen:</strong> " . $selectedCoursePlan['teacher_name'] . "</p>
                <p class='card-text'><strong>Akademi:</strong> " . $selectedCoursePlan['academy_name'] . "</p>
                <p class='card-text'><strong>Sınıf:</strong> " . $selectedCoursePlan['class_name'] . "</p>
                <p class='card-text'><strong>Öğrenci:</strong> " . $selectedCoursePlan['student_name'] . "</p>
                <p class='card-text'><strong>Ders:</strong> " . $selectedCoursePlan['lesson_name'] . "</p>
                
                <p class='card-text'><strong>Katılım Durumları:</strong> " . getAttendanceStatus($selectedCoursePlan) . "</p>
            </div>
        </div>";
            }

            function getAttendanceStatus($plan)
            {
                $attendanceStatus = "";
                $RePlan = false;

                for ($i = 1; $i <= 4; $i++) {
                    $dateKey = "course_date_" . $i;
                    $attendanceKey = "course_attendance_" . $i;

                    if (!empty($plan[$dateKey])) {
                        $formattedDate = date('d.m.Y H:i', strtotime($plan[$dateKey]));

                        if ($i == 4 && $plan[$attendanceKey] == 4) {
                            // If it's telafi yapılan mazeretli ders, set the flag
                            $RePlan = true;
                        }

                        $attendanceStatus .= "<p>$i. Ders Tarihi: $formattedDate - Derse Katılım Durumu: " . getAttendanceIcon($plan[$attendanceKey], $i, $formattedDate, $RePlan) . "</p>";
                    }
                }

                return $attendanceStatus;
            }

            function getAttendanceIcon($attendanceStatus, $lessonNumber, $formattedDate, $RePlan)
            {
                switch ($attendanceStatus) {
                    case 0:
                        return "<i class='fas fa-calendar-check text-primary'></i> <span class='text-primary'>Henüz Katılmadı</span>";
                    case 1:
                        return "<i class='fas fa-calendar-check text-success'></i> <span class='text-success'>Katıldı</span>";
                    case 2:
                        return "<i class='fas fa-calendar-check text-danger'></i> <span class='text-danger'>Katılmadı</span>";
                    case 3:
                        return "<i class='fas fa-calendar-check text-warning'></i> <span class='text-warning'>Mazeretli</span>";
                    default:
                        return "<i class='fas fa-question text-secondary'></i> <span class='text-secondary'>Belirsiz</span>";
                }
            }

            ?>

            <form action="" method="post">
                <div class="form-group">
                    <label for="rowId">Ders Planı Seç:</label>
                    <select class="form-control" name="rowId" required>
                        <?php
                        // Ders planlarını ve öğrenci bilgilerini, öğretmen bilgilerini ve ders adlarını çek
                        $selectPlansQuery = "SELECT cp.id, cp.course_fee, cp.debt_amount, cp.course_date_1, cp.course_date_2, cp.course_date_3, cp.course_date_4,
                                    u_student.first_name AS student_first_name, u_student.last_name AS student_last_name,
                                    u_teacher.first_name AS teacher_first_name, u_teacher.last_name AS teacher_last_name,
                                    c.course_name, a.name AS academy_name
                             FROM course_plans cp
                             JOIN users u_student ON cp.student_id = u_student.id
                             JOIN users u_teacher ON cp.teacher_id = u_teacher.id
                             JOIN courses c ON cp.course_id = c.id
                             JOIN academies a ON cp.academy_id = a.id
                             WHERE u_student.user_type = 6 OR u_teacher.user_type = 4";  // user type değeri 6 olan öğrencileri ve 4 olan öğretmenleri seç
                        $selectPlansStatement = $db->prepare($selectPlansQuery);
                        $selectPlansStatement->execute();
                        $coursePlans = $selectPlansStatement->fetchAll(PDO::FETCH_ASSOC);

                        // Ders planlarını seçenek listesine ekle
                        echo "<option value='' selected disabled>Seçim Yapın</option>";

                        foreach ($coursePlans as $plan) {
                            $courseDates = implode(", ", array_filter([$plan["course_date_1"], $plan["course_date_2"], $plan["course_date_3"], $plan["course_date_4"]]));
                            $optionClass = ($plan["debt_amount"] == 0) ? 'text-success' : 'text-danger';
                            $isSelected = ($plan["id"] == $selectedCoursePlanId) ? 'selected' : '';

                            echo "<option value='{$plan["id"]}' data-studentfirstname='{$plan["student_first_name"]}' data-studentlastname='{$plan["student_last_name"]}' 
        data-teacherfirstname='{$plan["teacher_first_name"]}' data-teacherlastname='{$plan["teacher_last_name"]}' 
        data-coursename='{$plan["course_name"]}' data-academyname='{$plan["academy_name"]}' 
        data-coursefee='{$plan["course_fee"]}' data-debtamount='{$plan["debt_amount"]}' data-coursedates='{$courseDates}' class='{$optionClass}' {$isSelected}>
        {$plan["student_first_name"]} {$plan["student_last_name"]} - {$plan["teacher_first_name"]} {$plan["teacher_last_name"]} 
        - {$plan["course_name"]} - Ders ücreti: {$plan["course_fee"]} TL - Kalan borç: {$plan["debt_amount"]} TL
        </option>";
                        }
                        ?>
                    </select>
                </div>
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
                <label for="course_date">Telafi Dersi Tarihi</label>
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