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

require_once "admin_panel_header.php";

// Akademileri veritabanından çek
$academiesQuery = "SELECT id, name FROM academies";
$academiesStmt = $db->query($academiesQuery);
$academies = $academiesStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once "admin_panel_sidebar.php"; ?>
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
                <h2>Öğretmen - Akademi - Sınıf - Öğrenci - Ders İlişkileri</h2>
            </div>

            <!-- Akademiyi ve Tarihi Seç Formu -->
            <div class="container mt-3">
                <form id="filterForm" method="post">
                    <div class="form-group">
                        <label for="academySelect">Akademiyi Seç:</label>
                        <select id="academySelect" name="academy_id" class="form-control" onchange="submitForm()">
                            <?php foreach ($academies as $academy): ?>
                                <option value="<?= $academy['id'] ?>" <?= ($_POST['academy_id'] ?? null) == $academy['id'] ? 'selected' : '' ?>>
                                    <?= $academy['name'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="dateSelect">Tarihi Seç:</label>
                        <input type="date" id="dateSelect" name="selected_date" class="form-control" value="<?= $_POST['selected_date'] ?? date('Y-m-d') ?>" onchange="submitForm()">
                    </div>
                </form>
            </div>

            <!-- Tablo -->
            <table class="table table-striped">
                <!-- Tablo Başlığı -->
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
                    <th>1. Katılım</th>
                    <th>2. Katılım</th>
                    <th>3. Katılım</th>
                    <th>4. Katılım</th>
                    <th>İşlemler</th>
                </tr>
                </thead>
                <!-- Tablo İçeriği -->
                <tbody>
                <?php
                // Akademi ID'sini ve seçilen tarihi al
                $selectedAcademyId = isset($_POST['academy_id']) ? $_POST['academy_id'] : null;
                $selectedDate = isset($_POST['selected_date']) ? $_POST['selected_date'] : date('Y-m-d');

                // Veritabanı sorgularını burada gerçekleştirin ve sonuçları tabloya ekleyin
                if ($selectedAcademyId) {
                    $query = "SELECT
                            sc.id,
                            CONCAT(u_teacher.first_name, ' ', u_teacher.last_name) AS teacher_name,
                            a.name AS academy_name,
                            ac.class_name AS class_name,
                            CONCAT(u_student.first_name, ' ', u_student.last_name) AS student_name,
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
                            INNER JOIN users u_teacher ON sc.teacher_id = u_teacher.id
                            INNER JOIN academies a ON sc.academy_id = a.id
                            INNER JOIN academy_classes ac ON sc.class_id = ac.id
                            INNER JOIN users u_student ON sc.student_id = u_student.id
                            INNER JOIN courses c ON sc.course_id = c.id
                        WHERE
                            sc.academy_id = :academy_id
                            AND DATE(sc.course_date_1) = :selected_date";

                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':academy_id', $selectedAcademyId, PDO::PARAM_INT);
                    $stmt->bindParam(':selected_date', $selectedDate, PDO::PARAM_STR);
                    $stmt->execute();
                    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($results as $result) {
                        echo "<tr>";
                        echo "<td>{$result['teacher_name']}</td>";
                        echo "<td>{$result['academy_name']}</td>";
                        echo "<td>{$result['class_name']}</td>";
                        echo "<td>{$result['student_name']}</td>";
                        echo "<td>{$result['lesson_name']}</td>";
                        // Tarih ve saatleri Türkiye tarih ve saat dilimine göre biçimlendir
                        echo "<td>" . date('d.m.Y H:i', strtotime($result['course_date_1'])) . "</td>";
                        echo "<td>" . date('d.m.Y H:i', strtotime($result['course_date_2'])) . "</td>";
                        echo "<td>" . date('d.m.Y H:i', strtotime($result['course_date_3'])) . "</td>";
                        echo "<td>" . date('d.m.Y H:i', strtotime($result['course_date_4'])) . "</td>";

                        // Ders katılımları
                        echo "<td>";
                        echo $result['course_attendance_1'] == 1
                            ? "<i class='fas fa-check text-success'></i>"
                            : "<i class='fas fa-times text-danger'></i>";
                        echo "</td>";
                        echo "<td>";
                        echo $result['course_attendance_2'] == 1
                            ? "<i class='fas fa-check text-success'></i>"
                            : "<i class='fas fa-times text-danger'></i>";
                        echo "</td>";
                        echo "<td>";
                        echo $result['course_attendance_3'] == 1
                            ? "<i class='fas fa-check text-success'></i>"
                            : "<i class='fas fa-times text-danger'></i>";
                        echo "</td>";
                        echo "<td>";
                        echo $result['course_attendance_4'] == 1
                            ? "<i class='fas fa-check text-success'></i>"
                            : "<i class='fas fa-times text-danger'></i>";
                        echo "</td>";
                        echo "<td><a href='edit_course_plan.php?id={$result['id']}' class='btn btn-primary btn-sm'>Düzenle</a></td>";
                        echo "</tr>";
                    }
                }
                ?>
                </tbody>
            </table>
        </main>
    </div>
</div>

<script>
    function submitForm() {
        document.getElementById("filterForm").submit();
    }

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
