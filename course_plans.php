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
require_once "inc/functions.php";

// Kullanıcı ve akademi ilişkisini çekmek için bir SQL sorgusu
$getUserAcademyQuery = "SELECT academy_id FROM user_academy_assignment WHERE user_id = :user_id";
$stmtUserAcademy = $db->prepare($getUserAcademyQuery);
$stmtUserAcademy->bindParam(':user_id', $_SESSION["admin_id"], PDO::PARAM_INT);
$stmtUserAcademy->execute();
$associatedAcademies = $stmtUserAcademy->fetchAll(PDO::FETCH_COLUMN);

// Eğer kullanıcı hiçbir akademide ilişkilendirilmemişse veya bu akademilerden hiçbiri yoksa, uygun bir işlemi gerçekleştirin
if (empty($associatedAcademies)) {
    echo "Kullanıcınız bu işlem için yetkili değil!";
    exit();
}

// Eğitim danışmanının erişebileceği akademilerin listesini güncelle
$allowedAcademies = $associatedAcademies;

// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);

require_once "admin_panel_header.php";
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once "admin_panel_sidebar.php"; ?>
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
            <?php
            // İlgili ders planını getir
            $selectedPlanId = isset($_GET['id']) ? $_GET['id'] : null;
            $selectedPlan = null;

            if ($selectedPlanId) {
                $querySelectedPlan = "
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
        INNER JOIN academies a ON sc.academy_id = a.id AND a.id IN (" . implode(",", $allowedAcademies) . ")
        INNER JOIN academy_classes ac ON sc.class_id = ac.id
        INNER JOIN users u_student ON sc.student_id = u_student.id AND u_student.user_type = 6
        INNER JOIN courses c ON sc.course_id = c.id
    WHERE
        sc.id = :planId
                ";

                $stmtSelectedPlan = $db->prepare($querySelectedPlan);
                $stmtSelectedPlan->bindParam(':planId', $selectedPlanId, PDO::PARAM_INT);
                $stmtSelectedPlan->execute();
                $selectedPlan = $stmtSelectedPlan->fetch(PDO::FETCH_ASSOC);
            }
            ?>

            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
                <h2>Ders Planları</h2>
            </div>

            <?php
            if ($selectedPlan) {
                echo "
        <div class='card border-success mb-3'>
            <div class='card-header bg-success text-white'>
                <h5 class='card-title'>Seçilen Ders Planı Detayları</h5>
            </div>
            <div class='card-body'>
                <p class='card-text'><strong>Öğretmen:</strong> {$selectedPlan['teacher_name']}</p>
                <p class='card-text'><strong>Akademi:</strong> {$selectedPlan['academy_name']}</p>
                <p class='card-text'><strong>Sınıf:</strong> {$selectedPlan['class_name']}</p>
                <p class='card-text'><strong>Öğrenci:</strong> {$selectedPlan['student_name']}</p>
                <p class='card-text'><strong>Ders:</strong> {$selectedPlan['lesson_name']}</p>
            </div>
        </div>";
            }
            ?>


            <table class="table table-striped table-sm" style="border: 1px solid #ddd;">
                <thead>
                <tr>
                    <th>Öğretmen</th>
                    <th>Akademi</th>
                    <th>Sınıf</th>
                    <th>Öğrenci</th>
                    <th>Ders</th>
                    <th>1. Ders</th>
                    <th>2. Ders</th>
                    <th>3. Ders</th>
                    <th>4. Ders</th>
                    <th><i class="fas fa-clipboard-check"></i> 1</th>
                    <th><i class="fas fa-clipboard-check"></i> 2</th>
                    <th><i class="fas fa-clipboard-check"></i> 3</th>
                    <th><i class="fas fa-clipboard-check"></i> 4</th>
                    <th>İşlemler</th>

                </tr>
                </thead>
                <tbody>
                <?php
                // Veritabanı sorgularını burada gerçekleştirin ve sonuçları tabloya ekleyin
                $query = "
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
        INNER JOIN academies a ON sc.academy_id = a.id AND a.id IN (" . implode(",", $allowedAcademies) . ")
        INNER JOIN academy_classes ac ON sc.class_id = ac.id
        INNER JOIN users u_student ON sc.student_id = u_student.id AND u_student.user_type = 6
        INNER JOIN courses c ON sc.course_id = c.id
";
                $stmt = $db->query($query);
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($results as $result) {
                    echo "<tr>";
                    echo "<td><a href='user_profile.php?id={$result['teacher_id']}'>{$result['teacher_name']}</a></td>";
                    echo "<td>{$result['academy_name']}</td>";
                    echo "<td>{$result['class_name']}</td>";
                    echo "<td><a href='user_profile.php?id={$result['student_id']}'>{$result['student_name']}</a></td>";
                    echo "<td>{$result['lesson_name']}</td>";

                    // Tarih ve saatleri Türkiye tarih ve saat dilimine göre biçimlendir
                    echo "<td style='font-size: small;'>" . date('d.m.Y H:i', strtotime($result['course_date_1'])) . "</td>";
                    echo "<td style='font-size: small;'>" . date('d.m.Y H:i', strtotime($result['course_date_2'])) . "</td>";
                    echo "<td style='font-size: small;'>" . date('d.m.Y H:i', strtotime($result['course_date_3'])) . "</td>";
                    echo "<td style='font-size: small;'>" . date('d.m.Y H:i', strtotime($result['course_date_4'])) . "</td>";

                    // Ders katılımları ve Yeniden Planla bağlantıları
                    for ($i = 1; $i <= 4; $i++) {
                        echo "<td>";

                        // Check if there is a corresponding entry in rescheduled_courses
                        $rescheduledQuery = "SELECT id FROM rescheduled_courses WHERE course_plan_id = :coursePlanId";
                        $rescheduledStmt = $db->prepare($rescheduledQuery);
                        $rescheduledStmt->bindParam(':coursePlanId', $result['id'], PDO::PARAM_INT);
                        $rescheduledStmt->execute();
                        $rescheduledId = $rescheduledStmt->fetchColumn();

                        if ($result["course_attendance_$i"] == 0) {
                            echo "<i class='fas fa-calendar-check text-primary'></i>";
                        } elseif ($result["course_attendance_$i"] == 1) {
                            echo "<i class='fas fa-calendar-check text-success'></i>";
                        } elseif ($result["course_attendance_$i"] == 2) {
                            echo "<i class='fas fa-calendar-check text-danger'></i>";
                        } elseif ($result["course_attendance_$i"] == 3 && $rescheduledId) {
                            // Change the icon or style for Yeniden Planla if there is a corresponding entry
                            echo "<a href='rescheduled_courses.php?id={$rescheduledId}' class='btn btn-success btn-sm'><i class='fas fa-calendar-check'></i></a>";
                        } elseif ($result["course_attendance_$i"] == 3) {
                            // Show the Yeniden Planla icon without a corresponding entry
                            echo "<a href='add_rescheduled_course_plan.php?course_plan_id={$result['id']}' class='btn btn-warning btn-sm'><i class='fas fa-calendar-alt'></i></a>";
                        } else {
                            echo "<i class='fas fa-question text-secondary'></i>";
                        }

                        echo "</td>";
                    }

                    // İşlemler column (Actions)
                    echo "<td><a href='edit_course_plan.php?id={$result['id']}' class='btn btn-primary btn-sm'><i class='fas fa-edit'></i></a></td>";

                    echo "</tr>";
                }

                ?>
                </tbody>
            </table>
        </main>
    </div>
</div>

<script>
    function editRow(rowId) {
        // Implement edit logic using modal or other UI components
        console.log('Editing row with ID: ' + rowId);
    }

    function deleteRow(rowId) {
        // Implement delete logic using modal or other UI components
        console.log('Deleting row with ID: ' + rowId);
    }
</script>

<?php require_once "footer.php"; ?>
