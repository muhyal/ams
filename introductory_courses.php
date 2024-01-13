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
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
                <h2>Tanışma Dersleri</h2>
            </div>

            <table class="table table-striped">
                <thead>
                <tr>
                    <th>Öğretmen</th>
                    <th>Akademi</th>
                    <th>Sınıf</th>
                    <th>Öğrenci</th>
                    <th>Ders</th>
                    <th>Ders Tarihi</th>
                    <th>Katılım</th>
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
        sc.course_date,
        sc.course_attendance
    FROM
        introductory_course_plans sc
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
                    echo "<td>" . date('d.m.Y H:i', strtotime($result['course_date'])) . "</td>";

                    // Ders katılımları
                    echo "<td>";

                    switch ($result['course_attendance']) {
                        case 0:
                            echo "<i class='fas fa-calendar-day text-primary'></i>"; // Henüz katılmadı ve planlandı durumu için takvim simgesi
                            break;
                        case 1:
                            echo "<i class='fas fa-calendar-check text-success'></i>"; // Katılım varsa yeşil tik
                            break;
                        case 2:
                            echo "<i class='fas fa-calendar-times text-danger'></i>"; // Katılmadı durumu için kırmızı çarpı
                            break;
                        case 3:
                            echo "<i class='fas fa-calendar-times text-warning'></i></a>"; // Mazeretli durumu için sarı çarpı
                            break;
                        default:
                            echo "<i class='fas fa-question text-secondary'></i>"; // Belirli bir duruma uygun işlem yapılmadıysa soru işareti
                            break;
                    }

                    echo "</td>";
                    echo "<td><a href='edit_introductory_course_plan.php?id={$result['id']}' class='btn btn-primary btn-sm'>Düzenle</a></td>";

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
