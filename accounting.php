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

// Bugünün tarihini al
$currentDate = date('d-m-Y');

// Uyarı mesajları için değişkenler
$alertMessage = '';
$alertColor = '';

// Akademileri çek
$sql_academies = "SELECT * FROM academies";
$stmt_academies = $db->query($sql_academies);
$academies = $stmt_academies->fetchAll(PDO::FETCH_ASSOC);

// Öğrenci bilgilerini çek
$sql_students = "SELECT * FROM users WHERE user_type = 6";
$stmt_students = $db->query($sql_students);
$students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);


// Ders bilgilerini çek
$sql_courses = "SELECT * FROM courses";
$stmt_courses = $db->query($sql_courses);
$courses = $stmt_courses->fetchAll(PDO::FETCH_ASSOC);

// Ödeme yöntemlerini çek
$sql_payment_methods = "SELECT * FROM payment_methods";
$stmt_payment_methods = $db->query($sql_payment_methods);
$payment_methods = $stmt_payment_methods->fetchAll(PDO::FETCH_ASSOC);

// Form gönderildi mi kontrolü
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Veri ekleme
    if (isset($_POST["add_entry"])) {
        // Academy_id_add değerini al
        $academy_id_add = isset($_POST["academy_id_add"]) ? $_POST["academy_id_add"] : null;
        $student_id_add = $_POST["student_id_add"];
        $course_id_add = $_POST["course_id_add"];
        $amount_add = $_POST["amount_add"];
        $payment_date_add = $_POST["payment_date_add"];

        // Ödeme yöntemini sayısal değere dönüştürün
        $payment_method_add = isset($_POST["payment_method_add"]) ? $_POST["payment_method_add"] : '';
        $payment_method_id = $payment_method_add; // Değişiklik yapıldı

        // Bakiye güncelleme
        $sql_update_balance = "UPDATE accounting SET balance = balance + ? WHERE academy_id = ?";
        $stmt_update_balance = $db->prepare($sql_update_balance);
        $stmt_update_balance->execute([$amount_add, $academy_id_add]);

        // SQL sorgusunu güncelleyin
        $sql = "INSERT INTO accounting (academy_id, student_id, course_id, amount, payment_date, payment_method) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$academy_id_add, $student_id_add, $course_id_add, $amount_add, $payment_date_add, $payment_method_id]);

        if ($stmt->rowCount()) {
            $alertMessage = 'Muhasebe kaydı başarıyla eklendi.';
            $alertColor = 'success';
        } else {
            $alertMessage = 'Muhasebe kaydı eklenirken bir hata oluştu.';
            $alertColor = 'danger';
        }
    }
}

// Muhasebe girdilerini çek
$sql_entries = "
    SELECT 
        accounting.id,
        accounting.course_plan_id,
        accounting.amount,
        accounting.payment_date,
        accounting.payment_method,
        accounting.payment_notes
    FROM accounting
";

$stmt_entries = $db->query($sql_entries);
$entries = $stmt_entries->fetchAll(PDO::FETCH_ASSOC) ?? [];

// Check if it's an AJAX request to fetch course plans
if (isset($_GET['action']) && $_GET['action'] == 'fetch_course_plans' && isset($_GET['student_id'])) {
    // Fetch course plans for the selected student
    $studentId = $_GET['student_id'];

    $sql_course_plans = "
        SELECT 
            course_plans.*,
            academies.name AS academy_name,
            courses.course_name,
            academy_classes.class_name,
            teachers.first_name AS teacher_first_name,
            teachers.last_name AS teacher_last_name
        FROM course_plans
        LEFT JOIN academies ON course_plans.academy_id = academies.id
        LEFT JOIN courses ON course_plans.course_id = courses.id
        LEFT JOIN academy_classes ON course_plans.class_id = academy_classes.id
        LEFT JOIN users AS teachers ON course_plans.teacher_id = teachers.id
        WHERE course_plans.student_id = ?
    ";

    $stmt_course_plans = $db->prepare($sql_course_plans);
    $stmt_course_plans->execute([$studentId]);
    $course_plans = $stmt_course_plans->fetchAll(PDO::FETCH_ASSOC);

    // Display course plans in a table
    echo '<table class="table table-bordered mt-3">
            <thead class="thead-dark">
                <tr>
                    <th>Akademi</th>
                    <th>Ders</th>
                    <th>Sınıf</th>
                    <th>Öğretmen</th>
                </tr>
            </thead>
            <tbody>';

    foreach ($course_plans as $course_plan) {
        echo '<tr>
                <td>' . $course_plan['academy_name'] . '</td>
                <td>' . $course_plan['course_name'] . '</td>
                <td>' . $course_plan['class_name'] . '</td>
                <td>' . $course_plan['teacher_first_name'] . ' ' . $course_plan['teacher_last_name'] . '</td>
            </tr>';
    }

    echo '</tbody></table>';
    exit; // Terminate the script after sending the response
}


// Form gönderildi mi kontrolü
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Veri ekleme
    if (isset($_POST["add_entry"])) {
        // Diğer değişkenleri al
        $academy_id_add = isset($_POST["academy_id_add"]) ? $_POST["academy_id_add"] : null;
        $student_id_add = $_POST["student_id_add"];
        $amount_add = $_POST["amount_add"];
        $payment_date_add = $_POST["payment_date_add"];
        $payment_method_add = isset($_POST["payment_method_add"]) ? $_POST["payment_method_add"] : '';
        $payment_method_id = $payment_method_add;

        // Seçilen kurs planları için ödeme girişini yapın
        if (isset($_POST["course_plan_ids"]) && is_array($_POST["course_plan_ids"])) {
            foreach ($_POST["course_plan_ids"] as $course_plan_id) {
                // Bakiye güncelleme
                $sql_update_balance = "UPDATE accounting SET balance = balance + ? WHERE academy_id = ?";
                $stmt_update_balance = $db->prepare($sql_update_balance);
                $stmt_update_balance->execute([$amount_add, $academy_id_add]);

                // SQL sorgusunu güncelleyin
                $sql = "INSERT INTO accounting (academy_id, student_id, course_plan_id, amount, payment_date, payment_method) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $db->prepare($sql);
                $stmt->execute([$academy_id_add, $student_id_add, $course_plan_id, $amount_add, $payment_date_add, $payment_method_id]);
            }
        }

        if ($stmt->rowCount()) {
            $alertMessage = 'Muhasebe kaydı başarıyla eklendi.';
            $alertColor = 'success';
        } else {
            $alertMessage = 'Muhasebe kaydı eklenirken bir hata oluştu.';
            $alertColor = 'danger';
        }
    }
}


?>
<?php
require_once "admin_panel_header.php";
?>
<div class="container-fluid">
    <div class="row">
        <?php require_once "admin_panel_sidebar.php"; ?>
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
                <h2>Muhasebe</h2>
            </div>

            <!-- Uyarı Mesajları -->
            <?php if (!empty($alertMessage)): ?>
                <div class="alert alert-<?= $alertColor ?>" role="alert">
                    <?= $alertMessage ?>
                </div>
            <?php endif; ?>

            <!-- Veri Ekleme Formu -->
            <h5>Kayıt Ekle</h5>
            <form method="post">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mt-3">
                            <label for="academy_id_add">Akademi:</label>
                            <select name="academy_id_add" class="form-control" id="academyDropdown" onchange="fetchStudentsByAcademy()">
                                <?php foreach ($academies as $academy): ?>
                                    <option value="<?= $academy['id'] ?>"><?= $academy['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group mt-3">
                            <label for="student_id_add">Öğrenci:</label>
                            <select name="student_id_add" class="form-control" id="studentDropdown"></select>
                        </div>

                        <div id="coursePlansContainer" class="mt-3"></div>

                        <div class="form-group mt-3">
                            <label for="course_id_add">Ders:</label>
                            <select name="course_id_add" class="form-control">
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?= $course['id'] ?>"><?= $course['course_name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>


                    <div class="col-md-6">
                        <div class="form-group mt-3">
                            <label for="amount_add">Tutar:</label>
                            <input type="text" name="amount_add" class="form-control" required>
                        </div>

                        <div class="form-group mt-3">
                            <label for="payment_date_add">Tarih:</label>
                            <input type="datetime-local" name="payment_date_add" class="form-control" onchange="validateDate()" required>
                        </div>

                        <div class="form-group mt-3">
                            <label for="payment_method_add">Ödeme yöntemi:</label>
                            <select name="payment_method_add" class="form-control">
                                <?php foreach ($payment_methods as $method): ?>
                                    <option value="<?= $method['id'] ?>"><?= $method['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-group mt-3">
                <button type="submit" name="add_entry" class="btn btn-primary">Giriş Ekle</button>
                </div>
            </form>

            <script>
                function getCurrentDateTime() {
                    var currentDate = new Date();
                    var year = currentDate.getFullYear();
                    var month = (currentDate.getMonth() + 1).toString().padStart(2, '0');
                    var day = currentDate.getDate().toString().padStart(2, '0');
                    var hours = currentDate.getHours().toString().padStart(2, '0');
                    var minutes = currentDate.getMinutes().toString().padStart(2, '0');
                    var seconds = currentDate.getSeconds().toString().padStart(2, '0');
                    return `${year}-${month}-${day}T${hours}:${minutes}`;
                }

                function validateDate() {
                    var dateInput = document.querySelector('input[name="payment_date_add"]');
                    if (!dateInput.value) {
                        // Eğer tarih seçilmemişse, otomatik doldur
                        dateInput.value = getCurrentDateTime();
                    }
                }

                // Sayfa yüklendiğinde tarih alanını otomatik doldur
                document.addEventListener("DOMContentLoaded", function () {
                    validateDate();
                });
            </script>
            <script>
                function fetchAndDisplayCoursePlans(studentId) {
                    // AJAX request to fetch course plans
                    var xhttp = new XMLHttpRequest();
                    xhttp.onreadystatechange = function () {
                        if (this.readyState == 4 && this.status == 200) {
                            document.getElementById("coursePlansContainer").innerHTML = this.responseText;

                            // Create checkboxes for course plans
                            var coursePlansContainer = document.getElementById('coursePlansCheckboxes');
                            var coursePlans = JSON.parse(this.responseText);

                            coursePlansContainer.innerHTML = ''; // Clear previous content

                            for (var i = 0; i < coursePlans.length; i++) {
                                var coursePlan = coursePlans[i];
                                var checkbox = document.createElement('input');
                                checkbox.type = 'checkbox';
                                checkbox.name = 'course_plan_ids[]';
                                checkbox.value = coursePlan.id;
                                checkbox.id = 'course_plan_' + coursePlan.id;

                                var label = document.createElement('label');
                                label.htmlFor = 'course_plan_' + coursePlan.id;
                                label.appendChild(document.createTextNode(coursePlan.academy_name + ' - ' + coursePlan.course_name + ' - ' + coursePlan.class_name + ' - ' + coursePlan.teacher_first_name + ' ' + coursePlan.teacher_last_name));

                                var div = document.createElement('div');
                                div.appendChild(checkbox);
                                div.appendChild(label);

                                coursePlansContainer.appendChild(div);
                            }
                        }
                    };
                    xhttp.open("GET", "accounting.php?action=fetch_course_plans&student_id=" + studentId, true);
                    xhttp.send();
                }

                // Function to fetch students based on the selected academy
                function fetchStudentsByAcademy() {
                    // Get the selected academy ID
                    var academyId = document.getElementById("academyDropdown").value;

                    // AJAX request to fetch students
                    var xhttp = new XMLHttpRequest();
                    xhttp.onreadystatechange = function () {
                        if (this.readyState == 4 && this.status == 200) {
                            // Update the student dropdown
                            document.getElementById("studentDropdown").innerHTML = this.responseText;
                        }
                    };
                    xhttp.open("GET", "accounting.php?action=fetch_students_by_academy&academy_id=" + academyId, true);
                    xhttp.send();
                }

                // Initial fetch for the default selected academy
                fetchStudentsByAcademy();
            </script>


            <?php require_once "footer.php"; ?>
        </main>
    </div>
</div>
