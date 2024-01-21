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
require_once(__DIR__ . '/partials/header.php');
?>
<div class="container-fluid">
    <div class="row">
       <?php
        require_once(__DIR__ . '/partials/sidebar.php');
?>
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
                <h2>Muhasebe</h2>
            </div>

            <?php
            $counter = 0; // Satır sayısını takip etmek için sayaç
            // Her akademi için kartları oluştur
            foreach ($academies as $academy) {
                // Akademi bilgilerini al
                $academyId = $academy['id'];
                $academyName = $academy['name'];

                // Akademiye ait muhasebe girişlerini çek
                $sql_academy_entries = "
                    SELECT 
                        a.id,
                        a.amount,
                        a.payment_date,
                        a.payment_method,
                        a.payment_notes,
                        cp.id AS course_plan_id,
                        cp.academy_id,
                        cp.course_id,
                        cp.class_id,
                        cp.teacher_id,
                        cp.student_id
                    FROM accounting a
                    LEFT JOIN course_plans cp ON a.course_plan_id = cp.id
                    WHERE a.course_plan_id IN (SELECT id FROM course_plans WHERE academy_id = ?)
                ";

                $stmt_academy_entries = $db->prepare($sql_academy_entries);
                $stmt_academy_entries->execute([$academyId]);
                $academy_entries = $stmt_academy_entries->fetchAll(PDO::FETCH_ASSOC);

                // Öğrenci sayısı
                $sql_student_count = "SELECT COUNT(DISTINCT student_id) AS student_count FROM course_plans WHERE academy_id = ?";
                $stmt_student_count = $db->prepare($sql_student_count);
                $stmt_student_count->execute([$academyId]);
                $student_count = $stmt_student_count->fetchColumn();

                // Öğretmen sayısı
                $sql_teacher_count = "SELECT COUNT(DISTINCT teacher_id) AS teacher_count FROM course_plans WHERE academy_id = ?";
                $stmt_teacher_count = $db->prepare($sql_teacher_count);
                $stmt_teacher_count->execute([$academyId]);
                $teacher_count = $stmt_teacher_count->fetchColumn();

                // Toplam borç
                $sql_total_debt = "SELECT SUM(debt_amount) AS total_debt FROM course_plans WHERE academy_id = ?";
                $stmt_total_debt = $db->prepare($sql_total_debt);
                $stmt_total_debt->execute([$academyId]);
                $total_debt = $stmt_total_debt->fetchColumn();

                // Toplam alınan ödeme
                $sql_total_payment = "SELECT SUM(amount) AS total_payment FROM accounting WHERE course_plan_id IN (SELECT id FROM course_plans WHERE academy_id = ?)";
                $stmt_total_payment = $db->prepare($sql_total_payment);
                $stmt_total_payment->execute([$academyId]);
                $total_payment = $stmt_total_payment->fetchColumn();

                // Toplam planlanan ders sayısı
                $sql_total_course_plans = "SELECT COUNT(*) AS total_course_plans FROM course_plans WHERE academy_id = ?";
                $stmt_total_course_plans = $db->prepare($sql_total_course_plans);
                $stmt_total_course_plans->execute([$academyId]);
                $total_course_plans = $stmt_total_course_plans->fetchColumn();

                // Kartı oluştur
                if ($counter % 3 == 0) {
                    // Yeni bir satır başlat
                    echo '<div class="row">';
                }

                echo '<div class="col-md-4">
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="card-title">' . $academyName . '</h5>
                        </div>
                        <div class="card-body">
                            <p>Toplam Planlanan Ders Sayısı: ' . $total_course_plans . '</p>
                            <p>Toplam Alınan Ödeme: ' . $total_payment . ' TL</p>
                            <p>Toplam Borç: ' . $total_debt . ' TL</p>    
                            <p>Mevcut Öğrenci Sayısı: ' . $student_count . '</p>
                            <p>Mevcut Öğretmen Sayısı: ' . $teacher_count . '</p>                      
                        </div>
                    </div>
                </div>';

                if ($counter % 3 == 2 || $counter == count($academies) - 1) {
                    // Satırın sonu veya son akademiyse satırı kapat
                    echo '</div>';
                }

                $counter++;
            }

            ?>

            <?php require_once('../admin/partials/footer.php'); ?>
        </main>
    </div>
</div>
