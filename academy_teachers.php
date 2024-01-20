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

// Tüm akademileri getir
$query = "SELECT * FROM academies";
$stmt = $db->query($query);
$academies = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php
require_once "admin_panel_header.php";
?>
<div class="container-fluid">
    <div class="row">
        <?php require_once "admin_panel_sidebar.php"; ?>
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
                <h2>Tüm Akademiler ve Öğretmenleri</h2>
            </div>

            <div class="row">
                <?php foreach ($academies as $academy): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $academy["name"]; ?></h5>
                                <p class="card-text">
                                    İl: <?php echo $academy["city"]; ?><br>
                                    İlçe: <?php echo $academy["district"]; ?><br>
                                    Adres: <?php echo $academy["address"]; ?><br>
                                    E-posta: <?php echo $academy["email"]; ?><br>
                                    Çalışma Saatleri: <?php echo $academy["working_hours"]; ?>
                                </p>
                            </div>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">
                                    <strong>Öğretmenler:</strong><br>
                                    <?php
                                    $academyId = $academy["id"];
                                    $teachersInAcademyQuery = "SELECT DISTINCT users.*
                           FROM users
                           INNER JOIN course_plans ON users.id = course_plans.teacher_id
                           WHERE course_plans.academy_id = ? AND users.user_type = 4";
                                    $teachersInAcademyStmt = $db->prepare($teachersInAcademyQuery);
                                    $teachersInAcademyStmt->execute([$academyId]);
                                    $teachersInAcademy = $teachersInAcademyStmt->fetchAll(PDO::FETCH_ASSOC);

                                    foreach ($teachersInAcademy as $teacher) {
                                        echo $teacher["first_name"] . " " . $teacher["last_name"] . "<br>";

                                        // Öğretmenin verdiği dersleri listele
                                        $teacherId = $teacher["id"];
                                        $teacherCoursesQuery = "SELECT courses.course_name
                                               FROM courses
                                               INNER JOIN course_plans ON courses.id = course_plans.course_id
                                               WHERE course_plans.teacher_id = ?";
                                        $teacherCoursesStmt = $db->prepare($teacherCoursesQuery);
                                        $teacherCoursesStmt->execute([$teacherId]);
                                        $teacherCourses = $teacherCoursesStmt->fetchAll(PDO::FETCH_ASSOC);

                                        foreach ($teacherCourses as $teacherCourse) {
                                            echo "- " . $teacherCourse["course_name"] . "<br>";
                                        }
                                    }
                                    ?>
                                </li>
                            </ul>
                        </div>
                    </div>
                <?php endforeach; ?>

            </div>
        </main>
    </div>
</div>
<?php require_once "footer.php"; ?>
