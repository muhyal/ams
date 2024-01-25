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
global $db, $showErrors, $siteName, $siteShortName, $siteUrl, $config;
// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);
require_once('../config/config.php');

session_start();
session_regenerate_id(true);

require_once(__DIR__ . '/../config/db_connection.php');

// Oturum kontrolü yaparak giriş yapılmış mı diye kontrol ediyoruz
if (!isset($_SESSION["user_id"])) {
    header("Location: /login.php"); // Kullanıcı giriş sayfasına yönlendirme
    exit();
}


// Hata ve başarı mesajlarını kontrol et
$errors = isset($_SESSION["error_message"]) ? [$_SESSION["error_message"]] : [];
$successMessage = isset($_SESSION["success_message"]) ? $_SESSION["success_message"] : null;

// Hata ve başarı mesajlarını temizle
unset($_SESSION["error_message"]);
unset($_SESSION["success_message"]);

$query = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($query);
if (!$stmt) {
    die('Query preparation failed.');
}

if (!$stmt->execute([$_SESSION["user_id"]])) {
    die('Query execution failed.');
}

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    // Print debug information and exit
    echo 'User information not retrieved. Debug info:';
    var_dump($stmt->errorInfo());
    exit();
}



// Öğrenciye ait ödemeleri çekmek için sorgu
$query_payments = "SELECT
    a.*,
    cp.id AS course_plan_id,
    cp.course_date_1,
    cp.course_date_2,
    cp.course_date_3,
    cp.course_date_4,
    cp.course_attendance_1,
    cp.course_attendance_2,
    cp.course_attendance_3,
    cp.course_attendance_4,
    academies.name AS academy_name,
    courses.course_name,
    academy_classes.class_name,
    teachers.first_name AS teacher_first_name,
    teachers.last_name AS teacher_last_name
FROM
    accounting a
INNER JOIN
    course_plans cp ON a.course_plan_id = cp.id
LEFT JOIN
    academies ON cp.academy_id = academies.id
LEFT JOIN
    courses ON cp.course_id = courses.id
LEFT JOIN
    academy_classes ON cp.class_id = academy_classes.id
LEFT JOIN
    users AS teachers ON cp.teacher_id = teachers.id AND teachers.user_type = 4
WHERE
    cp.student_id = ?
ORDER BY
    a.payment_date ASC";
$stmt_payments = $db->prepare($query_payments);
$stmt_payments->execute([$user['id']]);
$student_payments = $stmt_payments->fetchAll(PDO::FETCH_ASSOC);


if ($user) { // Kullanıcı bilgileri doğru şekilde alındı mı kontrol ediyoruz

    // Öğrenci ders planlarını alıyoruz
    $student_id = $user['id'];
    $select_student_courses_query = "
SELECT 
    course_plans.*, 
    academies.name AS academy_name, 
    courses.course_name,
    academy_classes.class_name, 
    teachers.first_name AS teacher_first_name, 
    teachers.last_name AS teacher_last_name,
    course_plans.course_date_1,
    course_plans.course_date_2,
    course_plans.course_date_3,
    course_plans.course_date_4,
    course_plans.course_attendance_1,
    course_plans.course_attendance_2,
    course_plans.course_attendance_3,
    course_plans.course_attendance_4
FROM 
    course_plans
LEFT JOIN academies ON course_plans.academy_id = academies.id
LEFT JOIN courses ON course_plans.course_id = courses.id
LEFT JOIN academy_classes ON course_plans.class_id = academy_classes.id
LEFT JOIN users AS teachers ON course_plans.teacher_id = teachers.id AND teachers.user_type = 4
WHERE 
    course_plans.student_id = ?
ORDER BY 
    course_plans.course_date_1 ASC, 
    course_plans.course_date_2 ASC, 
    course_plans.course_date_3 ASC, 
    course_plans.course_date_4 ASC";

    $stmt_student_courses = $db->prepare($select_student_courses_query);
    $stmt_student_courses->execute([$student_id]);
    $student_courses = $stmt_student_courses->fetchAll(PDO::FETCH_ASSOC);


    $select_student_introductory_courses_query = "
SELECT 
    introductory_course_plans.*, 
    academies.name AS academy_name, 
    courses.course_name,
    academy_classes.class_name, 
    teachers.first_name AS teacher_first_name, 
    teachers.last_name AS teacher_last_name,
    introductory_course_plans.course_date,
    introductory_course_plans.course_attendance
FROM 
    introductory_course_plans
LEFT JOIN academies ON introductory_course_plans.academy_id = academies.id
LEFT JOIN courses ON introductory_course_plans.course_id = courses.id
LEFT JOIN academy_classes ON introductory_course_plans.class_id = academy_classes.id
LEFT JOIN users AS teachers ON introductory_course_plans.teacher_id = teachers.id AND teachers.user_type = 4
WHERE 
    introductory_course_plans.student_id = ?
ORDER BY 
    introductory_course_plans.course_date ASC";

    $stmt_student_introductory_courses = $db->prepare($select_student_introductory_courses_query);
    $stmt_student_introductory_courses->execute([$student_id]);
    $student_introductory_courses = $stmt_student_introductory_courses->fetchAll(PDO::FETCH_ASSOC);


    $select_student_rescheduled_courses_query = "
SELECT 
    rescheduled_courses.*, 
    academies.name AS academy_name, 
    courses.course_name,
    academy_classes.class_name, 
    teachers.first_name AS teacher_first_name, 
    teachers.last_name AS teacher_last_name,
    rescheduled_courses.course_date,
    rescheduled_courses.course_attendance
FROM 
    rescheduled_courses
LEFT JOIN academies ON rescheduled_courses.academy_id = academies.id
LEFT JOIN courses ON rescheduled_courses.course_id = courses.id
LEFT JOIN academy_classes ON rescheduled_courses.class_id = academy_classes.id
LEFT JOIN users AS teachers ON rescheduled_courses.teacher_id = teachers.id AND teachers.user_type = 4
WHERE 
    rescheduled_courses.student_id = ?
ORDER BY 
    rescheduled_courses.course_date ASC";

    $stmt_student_rescheduled_courses = $db->prepare($select_student_rescheduled_courses_query);
    $stmt_student_rescheduled_courses->execute([$student_id]);
    $student_rescheduled_courses = $stmt_student_rescheduled_courses->fetchAll(PDO::FETCH_ASSOC);


    $teacher_id = $user['id'];
    $select_teacher_rescheduled_courses_query = "
SELECT 
    rescheduled_courses.*, 
    academies.name AS academy_name, 
    courses.course_name,
    academy_classes.class_name, 
    teachers.first_name AS teacher_first_name, 
    teachers.last_name AS teacher_last_name,
    students.first_name AS student_first_name,
    students.last_name AS student_last_name,
    rescheduled_courses.course_date,
    rescheduled_courses.course_attendance
FROM 
    rescheduled_courses
LEFT JOIN academies ON rescheduled_courses.academy_id = academies.id
LEFT JOIN courses ON rescheduled_courses.course_id = courses.id
LEFT JOIN academy_classes ON rescheduled_courses.class_id = academy_classes.id
LEFT JOIN users AS teachers ON rescheduled_courses.teacher_id = teachers.id AND teachers.user_type = 4
LEFT JOIN users AS students ON rescheduled_courses.student_id = students.id
WHERE 
    rescheduled_courses.teacher_id = ?
ORDER BY 
    rescheduled_courses.course_date ASC";

    $stmt_teacher_rescheduled_courses = $db->prepare($select_teacher_rescheduled_courses_query);
    $stmt_teacher_rescheduled_courses->execute([$teacher_id]);
    $teacher_rescheduled_courses = $stmt_teacher_rescheduled_courses->fetchAll(PDO::FETCH_ASSOC);



// Öğretmen ders planı  bilgilerini çekmek için öğretmen ID'sini alıyoruz
    $teacher_id = $user['id'];
    $select_teacher_courses_query = "
SELECT 
    course_plans.*, 
    academies.name AS academy_name, 
    courses.course_name, 
    academy_classes.class_name,
    users.first_name AS student_first_name,
    users.last_name AS student_last_name,
    course_plans.course_date_1,
    course_plans.course_date_2,
    course_plans.course_date_3,
    course_plans.course_date_4,
    course_plans.course_attendance_1,
    course_plans.course_attendance_2,
    course_plans.course_attendance_3,
    course_plans.course_attendance_4
FROM 
    course_plans
LEFT JOIN academies ON course_plans.academy_id = academies.id
LEFT JOIN courses ON course_plans.course_id = courses.id
LEFT JOIN academy_classes ON course_plans.class_id = academy_classes.id
LEFT JOIN users ON course_plans.student_id = users.id AND users.user_type = 6
WHERE 
    course_plans.teacher_id = ?
ORDER BY 
    CASE
        WHEN course_plans.course_date_1 IS NOT NULL THEN course_plans.course_date_1
        WHEN course_plans.course_date_2 IS NOT NULL THEN course_plans.course_date_2
        WHEN course_plans.course_date_3 IS NOT NULL THEN course_plans.course_date_3
        WHEN course_plans.course_date_4 IS NOT NULL THEN course_plans.course_date_4
    END ASC";

    $stmt_teacher_courses = $db->prepare($select_teacher_courses_query);
    $stmt_teacher_courses->execute([$teacher_id]);
    $teacher_courses = $stmt_teacher_courses->fetchAll(PDO::FETCH_ASSOC);

    // Öğretmen tanışma derslerini çekmek için sorgu
    $select_teacher_introductory_courses_query = "
SELECT 
    introductory_course_plans.*, 
    academies.name AS academy_name, 
    courses.course_name,
    academy_classes.class_name, 
    students.first_name AS student_first_name, 
    students.last_name AS student_last_name,
    introductory_course_plans.course_date,
    introductory_course_plans.course_attendance
FROM 
    introductory_course_plans
LEFT JOIN academies ON introductory_course_plans.academy_id = academies.id
LEFT JOIN courses ON introductory_course_plans.course_id = courses.id
LEFT JOIN academy_classes ON introductory_course_plans.class_id = academy_classes.id
LEFT JOIN users AS students ON introductory_course_plans.student_id = students.id AND students.user_type = 6
WHERE 
    introductory_course_plans.teacher_id = ?
ORDER BY 
    introductory_course_plans.course_date ASC";

// Öğretmen tanışma derslerini çekmek için kod
    $teacher_id = $user['id'];

    $stmt_teacher_introductory_courses = $db->prepare($select_teacher_introductory_courses_query);
    $stmt_teacher_introductory_courses->execute([$teacher_id]);
    $teacher_introductory_courses = $stmt_teacher_introductory_courses->fetchAll(PDO::FETCH_ASSOC);



    // Ödeme yöntemlerini çekmek için sorgu
    $query_payment_methods = "SELECT * FROM payment_methods";
    $stmt_payment_methods = $db->prepare($query_payment_methods);
    $stmt_payment_methods->execute();
    $payment_methods = $stmt_payment_methods->fetchAll(PDO::FETCH_ASSOC);


    $today = date("Y-m-d");

    $select_teacher_todays_courses_query = "
SELECT 
    icp.id,
    icp.academy_id,
    icp.course_id,
    icp.class_id,
    icp.teacher_id,
    icp.student_id,
    icp.course_date,
    icp.course_attendance,
    'Tanışma Dersi' as course_type,
    c.course_name,
    a.name as academy_name,
    u.username as teacher_username,
    u2.username as student_username,
    u2.first_name as student_first_name,
    u2.last_name as student_last_name,
    cl.class_name
FROM 
    introductory_course_plans icp
JOIN
    users u ON icp.teacher_id = u.id
JOIN
    users u2 ON icp.student_id = u2.id
JOIN
    courses c ON icp.course_id = c.id
JOIN
    academies a ON icp.academy_id = a.id
JOIN
    academy_classes cl ON icp.class_id = cl.id
WHERE 
    u.user_type = 4 AND
    DATE(icp.course_date) = ?  
UNION ALL

SELECT 
    cp.id,
    cp.academy_id,
    cp.course_id,
    cp.class_id,
    cp.teacher_id,
    cp.student_id,
    cp.course_date_1 as course_date,
    cp.course_attendance_1 as course_attendance,
    'Ders' as course_type,
    c.course_name,
    a.name as academy_name,
    u.username as teacher_username,
    u2.username as student_username,
    u2.first_name as student_first_name,
    u2.last_name as student_last_name,
    cl.class_name
FROM 
    course_plans cp
JOIN
    users u ON cp.teacher_id = u.id
JOIN
    users u2 ON cp.student_id = u2.id
JOIN
    courses c ON cp.course_id = c.id
JOIN
    academies a ON cp.academy_id = a.id
JOIN
    academy_classes cl ON cp.class_id = cl.id
WHERE 
    u.user_type = 4 AND
    DATE(cp.course_date_1) = ? AND cp.course_date_1 >= CURDATE()  
UNION ALL

SELECT 
    rc.id,
    rc.academy_id,
    rc.course_id,
    rc.class_id,
    rc.teacher_id,
    rc.student_id,
    rc.course_date,
    rc.course_attendance,
    'Telafi Dersi' as course_type,
    c.course_name,
    a.name as academy_name,
    u.username as teacher_username,
    u2.username as student_username,
    u2.first_name as student_first_name,
    u2.last_name as student_last_name,
    cl.class_name
FROM 
    rescheduled_courses rc
JOIN
    users u ON rc.teacher_id = u.id
JOIN
    users u2 ON rc.student_id = u2.id
JOIN
    courses c ON rc.course_id = c.id
JOIN
    academies a ON rc.academy_id = a.id
JOIN
    academy_classes cl ON rc.class_id = cl.id
WHERE 
    u.user_type = 4 AND
    DATE(rc.course_date) = ? AND rc.course_date >= CURDATE()  
ORDER BY course_date DESC
";

    $stmt_teacher_todays_courses = $db->prepare($select_teacher_todays_courses_query);
    $stmt_teacher_todays_courses->execute([
        $today,
        $today,
        $today
    ]);
    $teacher_todays_courses = $stmt_teacher_todays_courses->fetchAll(PDO::FETCH_ASSOC);



    require_once(__DIR__ . '/partials/header.php');
    ?>
    <div class="container mt-5">
        <div class="row">
                        <div class="container">
                            <div class="row">
                                <?php
                                foreach ($errors as $error) {
                                    echo "<div id='error-alert' class='alert alert-danger' role='alert'>$error</div>";
                                }

                                if ($successMessage) {
                                    echo "<div id='success-alert' class='alert alert-success' role='alert'>$successMessage</div>";
                                }
                                ?>
                                <div class="col-lg-4">
                                    <div class="card">
                                        <div class="card-body">
                                            <h4 class="card-title text-center mb-4">Selam 👋 <?php echo $user["first_name"] . " " . $user["last_name"]; ?>!</h4>
                                            <div class="text-center mt-4 mb-4">
                                                <a href="profile_edit.php" class="btn btn-sm btn-primary mr-2">
                                                    <i class="fas fa-user-edit"></i> Bilgileri güncelle
                                                </a>
                                                <a href="../logout.php" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-sign-out-alt"></i> Oturumu kapat
                                                </a>
                                            </div>
                                            <ul class="list-group list-group-flush">
                                                <li class="list-group-item">Ad: <?= $user['first_name'] ?>
                                                <li class="list-group-item">Soyad: <?= $user['last_name'] ?></li>
                                                <li class="list-group-item">E-posta: <?= $user['email'] ?></li>
                                                <li class="list-group-item">T.C. Kimlik No: <?= $user['tc_identity'] ?></li>
                                                <li class="list-group-item">Telefon: <?= $user['phone'] ?></li>
                                                <li class="list-group-item">SMS Onay Durumu: <?= $user['verification_time_sms_confirmed'] ? '<i class="fas fa-check text-success"></i> Doğrulandı' : '<i class="fas fa-times text-danger"></i> Doğrulanmadı' ?></li>
                                                <li class="list-group-item">E-posta Onay Durumu: <?= $user['verification_time_email_confirmed'] ? '<i class="fas fa-check text-success"></i> Doğrulandı' : '<i class="fas fa-times text-danger"></i> Doğrulanmadı' ?></li>

                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-4">
                                    <div class="card">
                                        <div class="card-body">
                                            <ul class="list-group list-group-flush">
                                                <?php if ($user['user_type'] == 6): ?>
                                                    <li class="list-group-item">Fatura Türü: <?= $user['invoice_type'] == 'individual' ? 'Bireysel' : 'Kurumsal' ?></li>
                                                    <?php if ($user['invoice_type'] == 'corporate'): ?>
                                                        <li class="list-group-item">Şirket Ünvanı: <?= $user['tax_company_name'] ?></li>
                                                        <li class="list-group-item">Vergi Dairesi: <?= $user['tax_office'] ?></li>
                                                        <li class="list-group-item">Vergi Numarası: <?= $user['tax_number'] ?></li>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                                <li class="list-group-item">Doğum Tarihi: <?php echo isset($user['birth_date']) ? date("d.m.Y", strtotime($user['birth_date'])) : ''; ?></li>
                                                <li class="list-group-item">Şehir: <?= $user['city'] ?></li>
                                                <li class="list-group-item">İlçe: <?= $user['district'] ?></li>
                                                <li class="list-group-item">Kan Grubu: <?= $user['blood_type'] ?></li>
                                                <li class="list-group-item">Bilinen Sağlık Sorunu: <?= $user['health_issue'] ?></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-4">
                                    <div class="card">
                                        <div class="card-body">
                                            <ul class="list-group list-group-flush">
                                                <li class="list-group-item">SMS Gönderildi: <?php echo $user['verification_time_sms_sent'] ? date(DATETIME_FORMAT, strtotime($user['verification_time_sms_sent'])) : 'Veri yok'; ?></li>
                                                <li class="list-group-item">SMS Onaylandı: <?php echo $user['verification_time_sms_confirmed'] ? date(DATETIME_FORMAT, strtotime($user['verification_time_sms_confirmed'])) : 'Veri yok'; ?></li>
                                                <li class="list-group-item">SMS Onay IP: <?= $user['verification_ip_sms'] ? $user['verification_ip_sms'] : 'Veri yok'; ?></li>
                                                <li class="list-group-item">E-posta Gönderildi: <?php echo $user['verification_time_email_sent'] ? date(DATETIME_FORMAT, strtotime($user['verification_time_email_sent'])) : 'Veri yok'; ?></li>
                                                <li class="list-group-item">E-posta Onaylandı: <?php echo $user['verification_time_email_confirmed'] ? date(DATETIME_FORMAT, strtotime($user['verification_time_email_confirmed'])) : 'Veri yok'; ?></li>
                                                <li class="list-group-item">E-posta Onay IP: <?= $user['verification_ip_email'] ? $user['verification_ip_email'] : 'Veri yok'; ?></li>
                                                <li class="list-group-item">Silinme Tarihi: <?= $user['deleted_at'] ? date(DATETIME_FORMAT, strtotime($user['deleted_at'])) : 'Veri yok'; ?></li>
                                                <li class="list-group-item">Oluşturulma Tarihi: <?= $user['created_at'] ? date(DATETIME_FORMAT, strtotime($user['created_at'])) : 'Veri yok'; ?></li>
                                                <li class="list-group-item">Son Güncelleme Tarihi: <?= $user['updated_at'] ? date(DATETIME_FORMAT, strtotime($user['updated_at'])) : 'Veri yok'; ?></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>


            <div class="col-lg-12 mt-3">
                <div class="card">
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <?php if ($user['user_type'] == '4'): ?>
                                <!-- Öğretmenin Diğer Bilgileri -->

                                <div class="card mt-4">
                                    <div class="card-header">
                                        <h5 class="card-title">Bugünkü planım</h5>
                                    </div>
                            <div class="card-body">
                                <table class="table">
                                    <thead>
                                    <tr>
                                        <th>Ders Türü</th>
                                        <th>Öğrenci</th>
                                        <th>Ders</th>
                                        <th>Sınıf</th>
                                        <th>Akademi</th>
                                        <th>Ders Tarihi</th>
                                        <th>Katılım</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($teacher_todays_courses as $course): ?>
                                        <tr>
                                            <td><?php echo isset($course['course_type']) ? $course['course_type'] : ''; ?></td>
                                            <td style="font-size: small;"><?php echo isset($course['student_username']) ? $course['student_username'] : ''; ?></td>
                                            <td><?php echo isset($course['course_name']) ? $course['course_name'] : ''; ?></td>
                                            <td><?php echo isset($course['class_name']) ? $course['class_name'] : ''; ?></td>
                                            <td><?php echo isset($course['academy_name']) ? $course['academy_name'] : ''; ?></td>
                                            <td><?php echo isset($course['course_date']) ? date("d.m.Y H:i", strtotime($course['course_date'])) : ''; ?></td>
                                            <td>
                                                <?php
                                                $attendanceStatus = isset($course["course_attendance"]) ? $course["course_attendance"] : '';

                                                switch ($attendanceStatus) {
                                                    case 0:
                                                        echo "<i class='fas fa-calendar-day text-primary'></i> Planlandı";
                                                        break;
                                                    case 1:
                                                        echo "<i class='fas fa-calendar-check text-success'></i> Katıldı";
                                                        break;
                                                    case 2:
                                                        echo "<i class='fas fa-calendar-times text-danger'></i> Katılmadı";
                                                        break;
                                                    case 3:
                                                        echo "<i class='fas fa-calendar-times text-warning'></i> Mazeretli";
                                                        break;
                                                    default:
                                                        echo "<i class='fas fa-question text-secondary'></i> Belirsiz";
                                                        break;
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                                </div>



                            <div class="card mt-4">
                                    <div class="card-header">
                                        <h5 class="card-title">Eğitim Planlarım</h5>
                                    </div>
                                    <div class="card-body">
                                        <table class="table">
                                            <thead>
                                            <tr>
                                                <th>Öğrenci</th>
                                                <th>Ders</th>
                                                <th>Sınıf</th>
                                                <th>Akademi</th>
                                                <th>1. Ders</th>
                                                <th>2. Ders</th>
                                                <th>3. Ders</th>
                                                <th>4. Ders</th>
                                                <th>1. <i class="fas fa-clipboard-check"></i></th>
                                                <th>2. <i class="fas fa-clipboard-check"></i></th>
                                                <th>3. <i class="fas fa-clipboard-check"></i></th>
                                                <th>4. <i class="fas fa-clipboard-check"></i></th>
                                            </tr>
                                            </thead>
                                            <tbody>

                                            <?php foreach ($teacher_courses as $course): ?>
                                                <tr>
                                                    <td style="font-size: small;"><?php echo isset($course['student_first_name']) ? $course['student_first_name'] . ' ' . $course['student_last_name'] : ''; ?></td>
                                                    <td><?php echo isset($course['course_name']) ? $course['course_name'] : ''; ?></td>
                                                    <td><?php echo isset($course['class_name']) ? $course['class_name'] : ''; ?></td>
                                                    <td><?php echo isset($course['academy_name']) ? $course['academy_name'] : ''; ?></td>
                                                    <td style="font-size: small;"><?php echo isset($course['course_date_1']) ? date("d.m.Y H:i", strtotime($course['course_date_1'])) : ''; ?></td>
                                                    <td style="font-size: small;"><?php echo isset($course['course_date_2']) ? date("d.m.Y H:i", strtotime($course['course_date_2'])) : ''; ?></td>
                                                    <td style="font-size: small;"><?php echo isset($course['course_date_3']) ? date("d.m.Y H:i", strtotime($course['course_date_3'])) : ''; ?></td>
                                                    <td style="font-size: small;"><?php echo isset($course['course_date_4']) ? date("d.m.Y H:i", strtotime($course['course_date_4'])) : ''; ?></td>
                                                    <?php for ($i = 1; $i <= 4; $i++): ?>
                                                        <td>
                                                            <?php
                                                            $attendanceStatus = $course["course_attendance_$i"];

                                                            switch ($attendanceStatus) {
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
                                                            ?>
                                                        </td>
                                                    <?php endfor; ?>
                                                </tr>


                                            <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Öğretmenin Diğer Bilgileri -->
                                <div class="card mt-4">
                                    <div class="card-header">
                                        <h5 class="card-title">Tanışma Derslerim</h5>
                                    </div>
                                    <div class="card-body">
                                        <table class="table">
                                            <thead>
                                            <tr>
                                                <th>Öğrenci</th>
                                                <th>Ders</th>
                                                <th>Sınıf</th>
                                                <th>Akademi</th>
                                                <th>Ders Tarihi</th>
                                                <th>Katılım</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <?php foreach ($teacher_introductory_courses as $introductory_course): ?>
                                                <tr>
                                                    <td style="font-size: small;"><?php echo isset($introductory_course['student_first_name']) ? $introductory_course['student_first_name'] . ' ' . $introductory_course['student_last_name'] : ''; ?></td>
                                                    <td><?php echo isset($introductory_course['course_name']) ? $introductory_course['course_name'] : ''; ?></td>
                                                    <td><?php echo isset($introductory_course['class_name']) ? $introductory_course['class_name'] : ''; ?></td>
                                                    <td><?php echo isset($introductory_course['academy_name']) ? $introductory_course['academy_name'] : ''; ?></td>
                                                    <td><?php echo isset($introductory_course['course_date']) ? date("d.m.Y H:i", strtotime($introductory_course['course_date'])) : ''; ?></td>
                                                    <td>
                                                        <?php
                                                        $attendanceStatus = $introductory_course["course_attendance"];

                                                        switch ($attendanceStatus) {
                                                            case 0:
                                                                echo "<i class='fas fa-calendar-day text-primary'></i> Planlandı"; // Henüz katılmadı ve planlandı durumu için takvim simgesi
                                                                break;
                                                            case 1:
                                                                echo "<i class='fas fa-calendar-check text-success'></i> Katıldı"; // Katılım varsa yeşil tik
                                                                break;
                                                            case 2:
                                                                echo "<i class='fas fa-calendar-times text-danger'></i> Katılmadı"; // Katılmadı durumu için kırmızı çarpı
                                                                break;
                                                            case 3:
                                                                echo "<i class='fas fa-calendar-times text-warning'></i></a> Mazeretli"; // Mazeretli durumu için sarı çarpı
                                                                break;
                                                            default:
                                                                echo "<i class='fas fa-question text-secondary'></i> Belirsiz"; // Belirli bir duruma uygun işlem yapılmadıysa soru işareti
                                                                break;
                                                        }
                                                        ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>


                                <div class="card mt-4">
                                    <div class="card-header">
                                        <h5 class="card-title">Telafi Derslerim</h5>
                                    </div>
                                    <div class="card-body">
                                        <table class="table">
                                            <thead>
                                            <tr>
                                                <th>Öğrenci</th>
                                                <th>Ders</th>
                                                <th>Sınıf</th>
                                                <th>Akademi</th>
                                                <th>Telafi Dersi Tarihi</th>
                                                <th>Telafi Dersine Katılım</th>
                                            </tr>
                                            </thead>
                                            <tbody>

                                            <?php foreach ($teacher_rescheduled_courses as $teacher_rescheduled_course): ?>
                                                <tr>
                                                    <td style="font-size: small;"><?php echo isset($teacher_rescheduled_course['student_first_name']) ? $teacher_rescheduled_course['student_first_name'] . ' ' . $teacher_rescheduled_course['student_last_name'] : ''; ?></td>
                                                    <td><?php echo isset($teacher_rescheduled_course['course_name']) ? $teacher_rescheduled_course['course_name'] : ''; ?></td>
                                                    <td><?php echo isset($teacher_rescheduled_course['class_name']) ? $teacher_rescheduled_course['class_name'] : ''; ?></td>
                                                    <td><?php echo isset($teacher_rescheduled_course['academy_name']) ? $teacher_rescheduled_course['academy_name'] : ''; ?></td>
                                                    <td><?php echo isset($teacher_rescheduled_course['course_date']) ? date("d.m.Y H:i", strtotime($teacher_rescheduled_course['course_date'])) : ''; ?></td>
                                                    <td>
                                                        <?php
                                                        $attendanceStatus = $teacher_rescheduled_course["course_attendance"];

                                                        switch ($attendanceStatus) {
                                                            case 0:
                                                                echo "<i class='fas fa-calendar-day text-primary'></i> Planlandı";
                                                                break;
                                                            case 1:
                                                                echo "<i class='fas fa-calendar-check text-success'></i> Katıldı";
                                                                break;
                                                            case 2:
                                                                echo "<i class='fas fa-calendar-times text-danger'></i> Katılmadı";
                                                                break;
                                                            case 3:
                                                                echo "<i class='fas fa-calendar-times text-warning'></i> Mazeretli";
                                                                break;
                                                            default:
                                                                echo "<i class='fas fa-question text-secondary'></i> Belirsiz";
                                                                break;
                                                        }
                                                        ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>

                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                </div>


                            <?php elseif ($user['user_type'] == '5'): ?>
                                <!-- Velinin Diğer Bilgileri -->
                                Veliye Özel Alan
                            <?php elseif ($user['user_type'] == '6'): ?>
                                <!-- Öğrencinin Diğer Bilgileri -->
                                <div class="card mt-4">
                                    <div class="card-header">
                                        <h5 class="card-title">Eğitim Planlarım</h5>
                                    </div>
                                    <div class="card-body">
                                        <table class="table">
                                            <thead>
                                            <tr>
                                                <th>Öğretmen</th>
                                                <th>Ders</th>
                                                <th>Sınıf</th>
                                                <th>Akademi</th>
                                                <th>1. Ders</th>
                                                <th>2. Ders</th>
                                                <th>3. Ders</th>
                                                <th>4. Ders</th>
                                                <th>1. <i class="fas fa-clipboard-check"></i></th>
                                                <th>2. <i class="fas fa-clipboard-check"></i></th>
                                                <th>3. <i class="fas fa-clipboard-check"></i></th>
                                                <th>4. <i class="fas fa-clipboard-check"></i></th>
                                                <th>Ders Ücreti</th>
                                                <th>Kalan Ücret</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <?php foreach ($student_courses as $course): ?>
                                                <tr>
                                                    <td style="font-size: small;"><?php echo isset($course['teacher_first_name']) ? $course['teacher_first_name'] . ' ' . $course['teacher_last_name'] : ''; ?></td>
                                                    <td style="font-size: small;"><?php echo isset($course['course_name']) ? $course['course_name'] : ''; ?></td>
                                                    <td style="font-size: small;"><?php echo isset($course['class_name']) ? $course['class_name'] : ''; ?></td>
                                                    <td style="font-size: small;"><?php echo isset($course['academy_name']) ? $course['academy_name'] : ''; ?></td>
                                                    <td style="font-size: small;"><?php echo isset($course['course_date_1']) ? date("d.m.Y H:i", strtotime($course['course_date_1'])) : ''; ?></td>
                                                    <td style="font-size: small;"><?php echo isset($course['course_date_2']) ? date("d.m.Y H:i", strtotime($course['course_date_2'])) : ''; ?></td>
                                                    <td style="font-size: small;"><?php echo isset($course['course_date_3']) ? date("d.m.Y H:i", strtotime($course['course_date_3'])) : ''; ?></td>
                                                    <td style="font-size: small;"><?php echo isset($course['course_date_4']) ? date("d.m.Y H:i", strtotime($course['course_date_4'])) : ''; ?></td>
                                                    <?php for ($i = 1; $i <= 4; $i++): ?>
                                                        <td>
                                                            <?php
                                                            $attendanceStatus = $course["course_attendance_$i"];

                                                            switch ($attendanceStatus) {
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
                                                            ?>
                                                        </td>
                                                    <?php endfor; ?>
                                                    <td style="font-size: small;"><?php echo isset($course['course_fee']) ? $course['course_fee'] : ''; ?> TL</td>

                                                    <td style="font-size: small;">
                                                        <?php
                                                        $remaining_payment = isset($course['debt_amount']) ? $course['debt_amount'] : 0;

                                                        // Kalan ödeme kontrolü
                                                        if ($remaining_payment == 0) {
                                                            echo '<i class="fas fa-check-circle text-success"></i> Yok'; // FontAwesome checkmark simgesi
                                                        } elseif ($remaining_payment > 0) {
                                                            echo $remaining_payment . ' TL'; // Kalan ödeme miktarını yazdır
                                                        } else {
                                                            echo 'Belirsiz'; // Hiç veri yoksa veya negatif bir değer varsa "Belirsiz" yazısı
                                                        }
                                                        ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>

                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Öğrencinin Diğer Bilgileri -->
                                <div class="card mt-4">
                                    <div class="card-header">
                                        <h5 class="card-title">Tanışma Derslerim</h5>
                                    </div>
                                    <div class="card-body">
                                        <table class="table">
                                            <thead>
                                            <tr>
                                                <th>Öğretmen</th>
                                                <th>Ders</th>
                                                <th>Sınıf</th>
                                                <th>Akademi</th>
                                                <th>Ders Tarihi</th>
                                                <th>Katılım</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <?php foreach ($student_introductory_courses as $introductory_course): ?>
                                                <tr>
                                                    <td style="font-size: small;"><?php echo isset($introductory_course['teacher_first_name']) ? $introductory_course['teacher_first_name'] . ' ' . $introductory_course['teacher_last_name'] : ''; ?></td>
                                                    <td style="font-size: small;"><?php echo isset($introductory_course['course_name']) ? $introductory_course['course_name'] : ''; ?></td>
                                                    <td style="font-size: small;"><?php echo isset($introductory_course['class_name']) ? $introductory_course['class_name'] : ''; ?></td>
                                                    <td style="font-size: small;"><?php echo isset($introductory_course['academy_name']) ? $introductory_course['academy_name'] : ''; ?></td>
                                                    <td style="font-size: small;"><?php echo isset($introductory_course['course_date']) ? date("d.m.Y H:i", strtotime($introductory_course['course_date'])) : ''; ?></td>
                                                    <td>
                                                        <?php
                                                        $attendanceStatus = $introductory_course["course_attendance"];

                                                        switch ($attendanceStatus) {
                                                            case 0:
                                                                echo "<i class='fas fa-calendar-day text-primary'></i> Planlandı"; // Henüz katılmadı ve planlandı durumu için takvim simgesi
                                                                break;
                                                            case 1:
                                                                echo "<i class='fas fa-calendar-check text-success'></i> Katıldı"; // Katılım varsa yeşil tik
                                                                break;
                                                            case 2:
                                                                echo "<i class='fas fa-calendar-times text-danger'></i> Katılmadı"; // Katılmadı durumu için kırmızı çarpı
                                                                break;
                                                            case 3:
                                                                echo "<i class='fas fa-calendar-times text-warning'></i></a> Mazeretli"; // Mazeretli durumu için sarı çarpı
                                                                break;
                                                            default:
                                                                echo "<i class='fas fa-question text-secondary'></i> Belirsiz"; // Belirli bir duruma uygun işlem yapılmadıysa soru işareti
                                                                break;
                                                        }
                                                        ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>

                                            </tbody>
                                        </table>
                                    </div>
                                </div>


                                <!-- Öğrencinin Diğer Bilgileri -->
                                <div class="card mt-4">
                                    <div class="card-header">
                                        <h5 class="card-title">Telafi Derslerim</h5>
                                    </div>
                                    <div class="card-body">
                                        <table class="table">
                                            <thead>
                                            <tr>
                                                <th>Öğretmen</th>
                                                <th>Ders</th>
                                                <th>Sınıf</th>
                                                <th>Akademi</th>
                                                <th>Telafi Dersi Tarihi</th>
                                                <th>Telafi Dersine Katılım</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <?php foreach ($student_rescheduled_courses as $student_rescheduled_course): ?>
                                                <tr>
                                                    <td style="font-size: small;"><?php echo isset($student_rescheduled_course['teacher_first_name']) ? $student_rescheduled_course['teacher_first_name'] . ' ' . $student_rescheduled_course['teacher_last_name'] : ''; ?></td>
                                                    <td style="font-size: small;"><?php echo isset($student_rescheduled_course['course_name']) ? $student_rescheduled_course['course_name'] : ''; ?></td>
                                                    <td style="font-size: small;"><?php echo isset($student_rescheduled_course['class_name']) ? $student_rescheduled_course['class_name'] : ''; ?></td>
                                                    <td style="font-size: small;"><?php echo isset($student_rescheduled_course['academy_name']) ? $student_rescheduled_course['academy_name'] : ''; ?></td>
                                                    <td style="font-size: small;"><?php echo isset($student_rescheduled_course['course_date']) ? date("d.m.Y H:i", strtotime($student_rescheduled_course['course_date'])) : ''; ?></td>
                                                    <td>
                                                        <?php
                                                        $attendanceStatus = $student_rescheduled_course["course_attendance"];

                                                        switch ($attendanceStatus) {
                                                            case 0:
                                                                echo "<i class='fas fa-calendar-day text-primary'></i> Planlandı"; // Henüz katılmadı ve planlandı durumu için takvim simgesi
                                                                break;
                                                            case 1:
                                                                echo "<i class='fas fa-calendar-check text-success'></i> Katıldı"; // Katılım varsa yeşil tik
                                                                break;
                                                            case 2:
                                                                echo "<i class='fas fa-calendar-times text-danger'></i> Katılmadı"; // Katılmadı durumu için kırmızı çarpı
                                                                break;
                                                            case 3:
                                                                echo "<i class='fas fa-calendar-times text-warning'></i></a> Mazeretli"; // Mazeretli durumu için sarı çarpı
                                                                break;
                                                            default:
                                                                echo "<i class='fas fa-question text-secondary'></i> Belirsiz"; // Belirli bir duruma uygun işlem yapılmadıysa soru işareti
                                                                break;
                                                        }
                                                        ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>

                                            </tbody>
                                        </table>
                                    </div>
                                </div>



                                <div class="card mt-4">
                                    <div class="card-header">
                                        <h5 class="card-title">Ödemelerim</h5>
                                    </div>
                                    <div class="card-body">
                                        <table class="table">
                                                        <thead>
                                                        <tr>
                                                            <!--<th>#</th>-->
                                                            <th>Akademi</th>
                                                            <th>Ders</th>
                                                            <th>Öğretmen</th>
                                                            <!-- <th>Ders Tarihi 1</th>
                                                            <th>Ders Tarihi 2</th>
                                                            <th>Ders Tarihi 3</th>
                                                            <th>Ders Tarihi 4</th>
                                                            <th>Katılım Durumu 1</th>
                                                            <th>Katılım Durumu 2</th>
                                                            <th>Katılım Durumu 3</th>
                                                            <th>Katılım Durumu 4</th>-->
                                                            <th>Ödenen Miktar</th>
                                                            <th>Ödeme Yöntem</th>
                                                            <th>Ödeme Tarihi</th>
                                                            <!--<th>Notlar</th>-->
                                                        </tr>
                                                        </thead>
                                                        <tbody>
                                                        <?php foreach ($student_payments as $payment): ?>
                                                            <tr>
                                                                <!--<td><?php echo $payment['id']; ?></td>-->
                                                                <td><?php echo $payment['academy_name']; ?></td>
                                                                <td><?php echo $payment['course_name']; ?></td>
                                                                <td><?php echo isset($course['teacher_first_name']) ? $course['teacher_first_name'] . ' ' . $course['teacher_last_name'] : ''; ?></td>

                                                                <!--<td><?php echo date("d.m.Y H:i", strtotime($payment['course_date_1'])); ?></td>
                                                                <td><?php echo date("d.m.Y H:i", strtotime($payment['course_date_2'])); ?></td>
                                                                <td><?php echo date("d.m.Y H:i", strtotime($payment['course_date_3'])); ?></td>
                                                                <td><?php echo date("d.m.Y H:i", strtotime($payment['course_date_4'])); ?></td>
                                                                <td><?php echo $payment['course_attendance_1'] ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>'; ?></td>
                                                                <td><?php echo $payment['course_attendance_2'] ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>'; ?></td>
                                                                <td><?php echo $payment['course_attendance_3'] ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>'; ?></td>
                                                                <td><?php echo $payment['course_attendance_4'] ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>'; ?></td>-->
                                                                <td><?php echo $payment['amount']; ?> TL</td>
                                                                <td>
                                                                    <?php
                                                                    // Ödeme yöntemini ismiyle gösterme
                                                                    $payment_method_id = $payment['payment_method'];
                                                                    $payment_method_name = array_filter($payment_methods, function ($method) use ($payment_method_id) {
                                                                        return $method['id'] == $payment_method_id;
                                                                    });

                                                                    // Anahtarın tanımlı olup olmadığını kontrol et
                                                                    if (!empty($payment_method_name)) {
                                                                        // Tanımlı ise ismi göster
                                                                        echo reset($payment_method_name)['name'];
                                                                    } else {
                                                                        // Tanımlı değilse hata kontrolü yapmadan boş bırak
                                                                        echo 'Belirsiz';
                                                                    }
                                                                    ?>
                                                                </td>
                                                                <td>
                                                                    <?php
                                                                    // Ödeme tarihini kontrol et ve eğer tanımlı ise göster
                                                                    echo isset($payment['payment_date']) ? date("d.m.Y H:i", strtotime($payment['payment_date'])) : 'Belirsiz';
                                                                    ?>
                                                                </td>
                                                                <!--<td><?php echo $payment['payment_notes']; ?></td>-->
                                                            </tr>


                                            <?php endforeach; ?>

                                            </tbody>
                                        </table>
                                    </div>
                                </div>



                            <?php endif; ?>
                        </ul>
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="card-title">Simgeler ve açıklamaları</h5>
                        </div>
                        <div class="card-body">
                            <table class="table">
                                <thead>
                                <tr>
                                    <th>Simge</th>
                                    <th>Durum Açıklaması</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr>
                                    <td><i class='fas fa-calendar-day text-primary'></i></td>
                                    <td>Planlandı - Ders planlandı ancak henüz katılım sağlanmadı için takvim simgesi</td>
                                </tr>
                                <tr>
                                    <td><i class='fas fa-calendar-check text-success'></i></td>
                                    <td>Katıldı - Derse katılım sağlandı için yeşil tikli takvim simgesi</td>
                                </tr>
                                <tr>
                                    <td><i class='fas fa-calendar-times text-danger'></i></td>
                                    <td>Katılmadı - Derse mazaretsiz katılım sağlanmadı için kırmızı çarpılı takvim simgesi</td>
                                </tr>
                                <tr>
                                    <td><i class='fas fa-calendar-times text-warning'></i></td>
                                    <td>Mazeretli - Derse mazaretli katılım sağlanmadı için turuncu çarpılı takvim simgesi</td>
                                </tr>
                                <tr>
                                    <td><i class='fas fa-question text-secondary'></i></td>
                                    <td>Belirsiz - Belirli bir duruma uygun işlem yapılmadıysa soru işareti simgesi</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <?php
} else {
    echo "Kullanıcı bilgileri alınamadı.";
}
require_once('../user/partials/footer.php');

?>
