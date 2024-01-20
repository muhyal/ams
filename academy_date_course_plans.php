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

require_once "config/db_connection.php";
require_once "config/config.php";

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
            <table id="listingTable" class="table table-striped table-sm" style="border: 1px solid #ddd;">

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
                    <th>1. <i class="fas fa-clipboard-check"></i></th>
                    <th>2. <i class="fas fa-clipboard-check"></i></th>
                    <th>3. <i class="fas fa-clipboard-check"></i></th>
                    <th>4. <i class="fas fa-clipboard-check"></i></th>
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
        AND (
            DATE(sc.course_date_1) = :selected_date
            OR DATE(sc.course_date_2) = :selected_date
            OR DATE(sc.course_date_3) = :selected_date
            OR DATE(sc.course_date_4) = :selected_date
        )";

                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':academy_id', $selectedAcademyId, PDO::PARAM_INT);
                    $stmt->bindParam(':selected_date', $selectedDate, PDO::PARAM_STR);
                    $stmt->execute();
                    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    $groupedTeachers = [];

                    foreach ($results as $result) {
                        $teacherName = $result['teacher_name'];

                        if (!isset($groupedTeachers[$teacherName])) {
                            $groupedTeachers[$teacherName] = [];
                        }

                        $groupedTeachers[$teacherName][] = $result;
                    }

                    foreach ($groupedTeachers as $teacherName => $teacherGroup) {
                        foreach ($teacherGroup as $index => $result) {
                            echo "<tr>";

                            if ($index === 0) {
                                echo "<td rowspan=" . count($teacherGroup) . " style='vertical-align: middle;'>{$result['teacher_name']}</td>";
                            }

                            echo "<td>{$result['academy_name']}</td>";
                            echo "<td>{$result['class_name']}</td>";
                            echo "<td>{$result['student_name']}</td>";
                            echo "<td>{$result['lesson_name']}</td>";

                            // Tarih ve saatleri Türkiye tarih ve saat dilimine göre biçimlendir
                            for ($i = 1; $i <= 4; $i++) {
                                $formattedDate = date('d.m.Y H:i', strtotime($result["course_date_$i"]));
                                $isBold = (date('Y-m-d', strtotime($result["course_date_$i"])) === $selectedDate) ? 'font-weight: bold;' : '';

                                echo "<td style='font-size: small; $isBold'>$formattedDate</td>";
                            }


                            // Ders katılımları
                            for ($i = 1; $i <= 4; $i++) {
                                echo "<td>";
                                // Check if the key exists in the array
                                if (isset($result["course_attendance_$i"])) {
                                    switch ($result["course_attendance_$i"]) {
                                        case 0:
                                            echo "<i class='fas fa-calendar-check text-primary'></i>"; // Henüz katılmadı ve planlandı durumu için takvim simgesi
                                            break;
                                        case 1:
                                            echo "<i class='fas fa-calendar-check text-success'></i>";
                                            break;
                                        case 2:
                                            echo "<i class='fas fa-calendar-check text-danger'></i>";
                                            break;
                                        case 3:
                                            echo "<i class='fas fa-calendar-times text-warning'></i>";
                                            break;
                                        default:
                                            echo "<i class='fas fa-question text-secondary'></i>"; // Belirli bir duruma uygun işlem yapılmadıysa soru işareti
                                            break;
                                    }
                                } else {
                                    // Handle the case where the key doesn't exist
                                    echo "-";
                                }
                                echo "</td>";
                            }

                            // Edit button
                            echo "<td><a href='edit_course_plan.php?id={$result['id']}' class='btn btn-primary btn-sm'>Düzenle</a></td>";

                            echo "</tr>";
                        }
                    }
                }
                ?>
                </tbody>
            </table>


            <!-- Tablo -->
            <table id="printTable" class="d-none">
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
        AND (
            DATE(sc.course_date_1) = :selected_date
            OR DATE(sc.course_date_2) = :selected_date
            OR DATE(sc.course_date_3) = :selected_date
            OR DATE(sc.course_date_4) = :selected_date
        )";

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
                        for ($i = 1; $i <= 4; $i++) {
                            echo "<td>" . date('d.m.Y H:i', strtotime($result["course_date_$i"])) . "</td>";
                        }


                        echo "</tr>";
                    } }

                ?>

                </tbody>
            </table>

            <!-- Yazdırma Düğmesi -->
            <div class="container mt-3">
                <?php
                if ($selectedAcademyId && $selectedDate) {
                    $selectedAcademy = $db->query("SELECT name FROM academies WHERE id = $selectedAcademyId")->fetchColumn();
                    echo "<p>Seçilen Akademi: $selectedAcademy</p>";
                    echo "<p>Seçilen Tarih: $selectedDate</p>";
                    echo '<button onclick="printTable()" class="btn btn-success"><i class="fas fa-print"></i> Yazdır</button>';
                }
                ?>
            </div>

        </main>
    </div>
</div>

<!-- Yazdırma İşlevi -->
<script>

    function groupTeachersByFullName(teachers) {
        const groupedTeachers = {};

        teachers.forEach(teacher => {
            const fullName = teacher.querySelector("td:first-child").textContent.trim();

            if (!groupedTeachers[fullName]) {
                groupedTeachers[fullName] = [];
            }

            groupedTeachers[fullName].push(teacher);
        });

        return groupedTeachers;
    }

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

    function switchTable(mode) {
        if (mode === 'listing') {
            document.getElementById('listingTable').classList.remove('d-none');
            document.getElementById('printTable').classList.add('d-none');
        } else if (mode === 'print') {
            document.getElementById('listingTable').classList.add('d-none');
            document.getElementById('printTable').classList.remove('d-none');
        }
    }

    function printTable() {
        var printWindow = window.open('', '_blank');

        // Open a new window and inject the table contents with the title
        printWindow.document.write('<html><head><title>Akademi Ders Planları</title></head><body>');

        // Get selected values from the form
        var selectedAcademy = document.getElementById("academySelect").value; // Örneğin, bir select elementinden değeri alınabilir
        var selectedDate = document.getElementById("dateSelect").value; // Örneğin, bir date input elementinden değeri alınabilir

        // Add the title
        var formattedDate = new Date(selectedDate).toLocaleDateString('tr-TR');
        printWindow.document.write('<tr><td colspan="14"><h4>Tarih: ' + formattedDate + '</h4></td></tr>');

        // Add the table
        printWindow.document.write('<table border="1">');

        // Add table headers
        printWindow.document.write('<tr>');
        printWindow.document.write('<th style="font-size: x-small">Grup</th>');
        printWindow.document.write('<th style="font-size: x-small">Öğretmen</th>');
        printWindow.document.write('<th style="font-size: x-small">Akademi</th>');
        printWindow.document.write('<th style="font-size: x-small">Sınıf</th>');
        printWindow.document.write('<th style="font-size: x-small">Öğrenci</th>');
        printWindow.document.write('<th style="font-size: x-small">Ders</th>');
        printWindow.document.write('<th style="font-size: x-small">1. Ders</th>');
        printWindow.document.write('<th style="font-size: x-small">2. Ders</th>');
        printWindow.document.write('<th style="font-size: x-small">3. Ders</th>');
        printWindow.document.write('<th style="font-size: x-small">4. Ders</th>');

        printWindow.document.write('</tr>');

        // Add table content
        var tableRows = document.getElementById("printTable").querySelectorAll("tbody tr");
        var groupedTeachers = groupTeachersByFullName(tableRows);

        for (var fullName in groupedTeachers) {
            if (groupedTeachers.hasOwnProperty(fullName)) {
                var teachersInGroup = groupedTeachers[fullName];

                teachersInGroup.forEach(function (teacher, index) {
                    printWindow.document.write('<tr>');

                    if (index === 0) {
                        printWindow.document.write('<td style="font-weight: bold; font-size: x-small;" rowspan="' + teachersInGroup.length + '">' + fullName + '</td>');
                    }

                    teacher.querySelectorAll("td").forEach(function (cell, cellIndex) {
                        if (cellIndex >= 5 && cellIndex <= 8) { // 1. Dersden 4. Derse kadar olan sütunları kontrol et
                            var courseDate = cell.textContent.trim(); // Boşlukları temizle
                            var formattedDate = new Date(courseDate.replace(/(\d{2})\.(\d{2})\.(\d{4})/g, "$3-$2-$1")).toISOString().split('T')[0]; // Format the date to match the input date format
                            if (formattedDate === selectedDate) {
                                printWindow.document.write('<td style="font-weight: bold; font-size: x-small; text-decoration: underline;">' + cell.textContent + '</td>');
                            } else {
                                printWindow.document.write('<td style="font-size: x-small;">' + cell.textContent + '</td>');
                            }
                        } else {
                            printWindow.document.write('<td style="font-size: x-small;">' + cell.textContent + '</td>');
                        }
                    });


                    printWindow.document.write('</tr>');
                });
            }
        }


        printWindow.document.write('</table>');

        printWindow.document.write('</body></html>');

        // Close the new window after printing
        printWindow.document.close();
        printWindow.print();
        printWindow.close();
    }

</script>

<?php require_once "footer.php"; ?>
