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

// Kullanıcı bilgilerini kullanabilirsiniz
$admin_id = $_SESSION["admin_id"];
$admin_username = $_SESSION["admin_username"];

// Akademileri çek
$sql_academies = "SELECT * FROM academies";
$stmt_academies = $db->query($sql_academies);
$academies = $stmt_academies->fetchAll(PDO::FETCH_ASSOC);

// Kullanıcıları ve rollerini çek
$sql_users_roles = "SELECT users.*, user_types.type_name FROM users 
                    LEFT JOIN user_types ON users.id = user_types.id";
$stmt_users_roles = $db->query($sql_users_roles);
$users_roles = $stmt_users_roles->fetchAll(PDO::FETCH_ASSOC);

// Öğrencileri ve öğretmenleri ayır
$students = [];
$teachers = [];

foreach ($users_roles as $user) {
    if ($user['user_type'] == 6) {
        $students[] = $user;
    } elseif ($user['user_type'] == 4) {
        $teachers[] = $user;
    }
}

$sql = "SELECT * FROM courses";
$stmt = $db->query($sql);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ders planlarını çek
$sql_course_plans = "
    SELECT 
        course_plans.*,
        academies.name AS academy_name,
        courses.course_name,
        academy_classes.class_name,
        teachers.first_name AS teacher_first_name,
        teachers.last_name AS teacher_last_name,
        students.first_name AS student_first_name,
        students.last_name AS student_last_name
    FROM course_plans
    LEFT JOIN academies ON course_plans.academy_id = academies.id
    LEFT JOIN courses ON course_plans.course_id = courses.id
    LEFT JOIN academy_classes ON course_plans.class_id = academy_classes.id
    LEFT JOIN users AS teachers ON course_plans.teacher_id = teachers.id
    LEFT JOIN users AS students ON course_plans.student_id = students.id
    WHERE teachers.user_type = 4 AND students.user_type = 5"
;

$stmt_course_plans = $db->query($sql_course_plans);
$course_plans = $stmt_course_plans->fetchAll(PDO::FETCH_ASSOC);

// Ödeme yöntemlerini çek
$sql_payment_methods = "SELECT * FROM payment_methods";
$stmt_payment_methods = $db->query($sql_payment_methods);
$payment_methods = $stmt_payment_methods->fetchAll(PDO::FETCH_ASSOC);

$sql_entries = "
    SELECT 
        accounting.id,
        accounting.course_plan_id,
        accounting.amount,
        accounting.payment_date,
        accounting.payment_method,
        accounting.payment_notes,
        accounting.bank_name,
        users.first_name AS received_by_first_name,
        users.last_name AS received_by_last_name
    FROM accounting
    LEFT JOIN course_plans ON accounting.course_plan_id = course_plans.id
    LEFT JOIN users ON accounting.received_by_id = users.id
    WHERE course_plans.academy_id IN (" . implode(",", $allowedAcademies) . ")
";
$stmt_entries = $db->query($sql_entries);
$entries = $stmt_entries->fetchAll(PDO::FETCH_ASSOC) ?? [];

// Yardımcı fonksiyonlar
function getCoursePlanDetails($coursePlanId)
{
    global $db;

    $sql = "
        SELECT 
            academy_id,
            course_id,
            class_id,
            teacher_id,
            student_id
        FROM course_plans
        WHERE id = ?
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute([$coursePlanId]);
    $details = $stmt->fetch(PDO::FETCH_ASSOC);

    return $details;
}

function getAcademyName($academyId)
{
    global $academies;
    foreach ($academies as $academy) {
        if ($academy['id'] == $academyId) {
            return $academy['name'];
        }
    }
    return "";
}

function getStudentName($studentId)
{
    global $students;
    foreach ($students as $student) {
        if ($student['id'] == $studentId) {
            return $student['first_name'] . ' ' . $student['last_name'];
        }
    }
    return "";
}

function getTeacherName($teacherId)
{
    global $teachers; // $teachers dizisi, öğretmen bilgilerini içeren bir dizi olarak varsayılmış.

    foreach ($teachers as $teacher) {
        if ($teacher['id'] == $teacherId) {
            return $teacher['first_name'] . ' ' . $teacher['last_name'];
        }
    }

    // Eğer belirtilen öğretmen kimliği ile eşleşen bir öğretmen bulunamazsa boş bir dize döndürülür.
    return "";
}

?>
<?php require_once('../admin/partials/header.php'); ?>

<div class="container-fluid">
    <div class="row">
       <?php
        require_once(__DIR__ . '/partials/sidebar.php');
?>
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
                <h2>Ödemeler</h2>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group mr-2">
                        <a href="add_payment.php" class="btn btn-sm btn-outline-secondary">Ödeme Ekle</a>
                        <a href="reports.php" class="btn btn-sm btn-outline-secondary">Raporlar</a>
                    </div>
                </div>
            </div>
    <div class="table-responsive">
        <table id="accountingListTable" class="table table-striped table-sm" style="border: 1px solid #ddd;">
            <thead class="thead-dark">
            <tr>
                <th>Plan</th>
                <th>Akademi</th>
                <th>Öğrenci</th>
                <th>Öğretmen</th>
                <th>Ders</th>
                <th>Tutar</th>
                <th>Tarih</th>
                <th>Ödeme Yöntemi</th>
                <th>Banka</th>
                <th>Not</th>
                <th>Ödemeyi İşleyen</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!empty($entries) && is_array($entries)): ?>
                <?php foreach ($entries as $entry): ?>
                    <tr>
                        <td>
                            <?php
                            $coursePlanId = isset($entry['course_plan_id']) ? $entry['course_plan_id'] : 'Belirsiz';
                            echo "<a href='course_plans.php?id=$coursePlanId' class='btn btn-success btn-sm'><i class='bi bi-eye-fill'></i></a>";
                            ?>
                        </td>
                        <?php
                        $coursePlanDetails = getCoursePlanDetails($entry['course_plan_id']);
                        $academyName = isset($coursePlanDetails['academy_id']) ? getAcademyName($coursePlanDetails['academy_id']) : 'Belirsiz';
                        $studentName = isset($coursePlanDetails['student_id']) ? getStudentName($coursePlanDetails['student_id']) : 'Belirsiz';
                        $teacherName = isset($coursePlanDetails['teacher_id']) ? getTeacherName($coursePlanDetails['teacher_id']) : 'Belirsiz';
                        $courseName = isset($coursePlanDetails['course_id']) ? getCourseName($coursePlanDetails['course_id']) : 'Belirsiz';
                        ?>
                        <td><?= $academyName ?></td>
                        <td><?= $studentName ?></td>
                        <td><?= $teacherName ?></td>
                        <td><?= $courseName ?></td>
                        <td><?= isset($entry['amount']) ? $entry['amount'] : 'Belirsiz' ?></td>
                        <td>
                            <?php
                            if (isset($entry['payment_date'])) {
                                $timestamp = strtotime($entry['payment_date']);
                                echo date('d.m.Y H:i', $timestamp);
                            } else {
                                echo 'Belirsiz';
                            }
                            ?>
                        </td>
                        <td><?= isset($entry['payment_method']) ? getPaymentMethodName($entry['payment_method']) : 'Belirsiz' ?></td>
                        <td><?= isset($entry['bank_name']) ? getBankName($entry['bank_name']) : 'Belirsiz' ?></td>
                        <td><?= isset($entry['payment_notes']) ? $entry['payment_notes'] : 'Belirsiz' ?></td>
                        <td><?= isset($entry['received_by_first_name']) ? $entry['received_by_first_name'] . ' ' . $entry['received_by_last_name'] : 'Belirsiz' ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="10">Kayıt bulunamadı.</td>
                </tr>
            <?php endif; ?>
            </tbody>
                </table>
            </div>
<?php require_once('../admin/partials/footer.php'); ?>