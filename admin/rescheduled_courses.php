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
    header("Location: index.php"); // Giriş sayfasına yönlendir
    exit();
}

require_once(__DIR__ . '/../config/db_connection.php');
require_once('../config/config.php');
require_once "../src/functions.php";

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

require_once(__DIR__ . '/partials/header.php');
?>

<div class="container-fluid">
    <div class="row">
<?php
require_once(__DIR__ . '/partials/sidebar.php');
?>
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
                <h2>Telafi Dersleri</h2>
            </div>
            <?php
            // Check if the 'id' parameter is set in the URL
            $selectedRescheduledId = isset($_GET['id']) ? $_GET['id'] : null;
            $selectedRescheduled = null;

            // Fetch the selected rescheduled course
            if ($selectedRescheduledId) {
                $querySelectedRescheduled = "
                    SELECT
                        rc.id,
                        cp.id AS course_plan_id,
                        CONCAT(u_teacher.first_name, ' ', u_teacher.last_name) AS teacher_name,
                        u_teacher.id AS teacher_id,
                        a.name AS academy_name,
                        ac.class_name AS class_name,
                        CONCAT(u_student.first_name, ' ', u_student.last_name) AS student_name,
                        u_student.id AS student_id,
                        c.course_name AS lesson_name,
                        rc.course_date,
                        rc.course_attendance
                    FROM
                        rescheduled_courses rc
                        INNER JOIN course_plans cp ON rc.course_plan_id = cp.id
                        INNER JOIN users u_teacher ON cp.teacher_id = u_teacher.id AND u_teacher.user_type = 4
                        INNER JOIN academies a ON rc.academy_id = a.id AND a.id IN (" . implode(",", $allowedAcademies) . ")
                        INNER JOIN academy_classes ac ON cp.class_id = ac.id
                        INNER JOIN users u_student ON cp.student_id = u_student.id AND u_student.user_type = 6
                        INNER JOIN courses c ON cp.course_id = c.id
                    WHERE
                        rc.id = :rescheduledId
                ";

                $stmtSelectedRescheduled = $db->prepare($querySelectedRescheduled);
                $stmtSelectedRescheduled->bindParam(':rescheduledId', $selectedRescheduledId, PDO::PARAM_INT);
                $stmtSelectedRescheduled->execute();
                $selectedRescheduled = $stmtSelectedRescheduled->fetch(PDO::FETCH_ASSOC);
            }

            // Display the selected rescheduled course at the top
            if ($selectedRescheduled) {
                echo "<div class='card border-success mb-3'>";
                echo "<div class='card-header bg-success text-white'>";
                echo "<h5 class='card-title'>Seçilen Telafi Dersi Detayları</h5>";
                echo "</div>";
                echo "<div class='card-body'>";
                echo "<p><a href='course_plans.php?id={$selectedRescheduled['course_plan_id']}' class='btn btn-outline-success btn-sm'>İlgili Ders Planını Görüntüle</a></p>"; // View button added
                echo "<p class='card-text'><strong>Öğretmen:</strong> {$selectedRescheduled['teacher_name']}</p>";
                echo "<p class='card-text'><strong>Akademi:</strong> {$selectedRescheduled['academy_name']}</p>";
                echo "<p class='card-text'><strong>Sınıf:</strong> {$selectedRescheduled['class_name']}</p>";
                echo "<p class='card-text'><strong>Öğrenci:</strong> {$selectedRescheduled['student_name']}</p>";
                echo "<p class='card-text'><strong>Ders:</strong> {$selectedRescheduled['lesson_name']}</p>";
                echo "<p class='card-text'><strong>Telafi Dersi Tarihi:</strong> " . date('d.m.Y H:i', strtotime($selectedRescheduled['course_date'])) . "</p>";
                echo "<p class='card-text'><strong>Telafi Dersine Katılım:</strong> ";

                switch ($selectedRescheduled['course_attendance']) {
                    case 0:
                        echo "<i class='fas fa-calendar-check text-primary'></i>"; // Henüz katılmadı ve planlandı durumu için takvim simgesi
                        break;
                    case 1:
                        echo "<i class='fas fa-check text-success'></i>"; // Katılım varsa yeşil tik
                        break;
                    case 2:
                        echo "<i class='fas fa-times text-danger'></i>"; // Katılmadı durumu için kırmızı çarpı
                        break;
                    case 3:
                        echo "<a href='reschedule_the_course.php?id={$selectedRescheduled['id']}' class='btn btn-warning btn-sm'><i class='fas fa-calendar-times'></i></a>"; // Mazeretli durumu için sarı çarpı
                        break;
                    default:
                        echo "<i class='fas fa-question text-secondary'></i>"; // Belirli bir duruma uygun işlem yapılmadıysa soru işareti
                        break;
                }

                echo "</p>";
                echo "</div>";
                echo "</div>";
            }
            ?>

            <div class="table-responsive">

            <table id="rescheduledCoursesTable" class="table table-striped table-sm" style="border: 1px solid #ddd;">
                <thead>
                <tr>
                    <th>Hangi Planın Telafisi</th>
                    <th>Öğretmen</th>
                    <th>Akademi</th>
                    <th>Sınıf</th>
                    <th>Öğrenci</th>
                    <th>Ders</th>
                    <th>Telafi Dersi Tarihi</th>
                    <th>Telafi Dersine Katılım</th>
                    <th>İşlemler</th>
                </tr>
                </thead>
                <tbody>
                <?php
                // Veritabanı sorgularını burada gerçekleştirin ve sonuçları tabloya ekleyin
                $query = "
    SELECT
        rc.id,
        cp.id AS course_plan_id,
        CONCAT(u_teacher.first_name, ' ', u_teacher.last_name) AS teacher_name,
        u_teacher.id AS teacher_id,
        a.name AS academy_name,
        ac.class_name AS class_name,
        CONCAT(u_student.first_name, ' ', u_student.last_name) AS student_name,
        u_student.id AS student_id,
        c.course_name AS lesson_name,
        rc.course_date,
        rc.course_attendance
    FROM
        rescheduled_courses rc
        INNER JOIN course_plans cp ON rc.course_plan_id = cp.id
        INNER JOIN users u_teacher ON cp.teacher_id = u_teacher.id AND u_teacher.user_type = 4
        INNER JOIN academies a ON cp.academy_id = a.id AND a.id IN (" . implode(",", $allowedAcademies) . ")
        INNER JOIN academy_classes ac ON cp.class_id = ac.id
        INNER JOIN users u_student ON cp.student_id = u_student.id AND u_student.user_type = 6
        INNER JOIN courses c ON cp.course_id = c.id
";
                $stmt = $db->query($query);
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);


                foreach ($results as $result) {
                    echo "<tr>";
                    echo "<td><a href='course_plans.php?id={$result['course_plan_id']}' class='btn btn-outline-success btn-sm'>İlgili Ders Planını Görüntüle</a></td>"; // View button added
                    echo "<td><a href='user_profile.php?id={$result['teacher_id']}'>{$result['teacher_name']}</a></td>";
                    echo "<td>{$result['academy_name']}</td>";
                    echo "<td>{$result['class_name']}</td>";
                    echo "<td><a href='user_profile.php?id={$result['student_id']}'>{$result['student_name']}</a></td>";
                    echo "<td>{$result['lesson_name']}</td>";
                    // Tarih ve saatleri Türkiye tarih ve saat dilimine göre biçimlendir
                    echo "<td>" . date('d.m.Y H:i', strtotime($result['course_date'])) . "</td>";

                    // Ders katılımları
                    echo "<td>";

                    switch ($result['course_attendance']) {
                        case 0:
                            echo "<i class='fas fa-calendar-check text-primary'></i>"; // Henüz katılmadı ve planlandı durumu için takvim simgesi
                            break;
                        case 1:
                            echo "<i class='fas fa-calendar-check text-success'></i>";
                            break;
                        case 2:
                            echo "<i class='fas fa-times text-danger'></i>"; // Katılmadı durumu için kırmızı çarpı
                            break;
                        case 3:
                            echo "<a href='reschedule_the_course.php?id={$result['id']}' class='btn btn-warning btn-sm'><i class='fas fa-calendar-times'></i></a>"; // Mazeretli durumu için sarı çarpı
                            break;
                        default:
                            echo "<i class='fas fa-question text-secondary'></i>"; // Belirli bir duruma uygun işlem yapılmadıysa soru işareti
                            break;
                    }

                    echo "</td>";
                    echo "<td><a href='edit_rescheduled_course_plan.php?id={$result['id']}' class='btn btn-primary btn-sm'>Düzenle</a></td>";

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

<?php require_once('../admin/partials/footer.php'); ?>
