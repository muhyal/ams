<?php
global $db, $showErrors, $siteName, $siteShortName, $siteUrl;
session_start();
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

// Kullanıcı bilgilerini kullanabilirsiniz
$admin_id = $_SESSION["admin_id"];
$admin_username = $_SESSION["admin_username"];

// Akademileri çek
$sql = "SELECT * FROM academies";
$stmt = $db->query($sql);
$academies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Öğrencileri çek
$sql = "SELECT * FROM students";
$stmt = $db->query($sql);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Öğretmenleri çek
$sql = "SELECT * FROM teachers";
$stmt = $db->query($sql);
$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Dersleri çek
$sql = "SELECT * FROM courses";
$stmt = $db->query($sql);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ödeme yöntemlerini çek
$sql = "SELECT * FROM payment_methods";
$stmt = $db->query($sql);
$payment_methods = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Muhasebe girdilerini çek
$sql_entries = "
    SELECT 
        accounting_entries.id,
        accounting_entries.academy_id,
        accounting_entries.student_id,
        accounting_entries.course_id,
        accounting_entries.amount,
        accounting_entries.entry_date,
        accounting_entries.payment_method
    FROM accounting_entries
";
$stmt_entries = $db->query($sql_entries);
$entries = $stmt_entries->fetchAll(PDO::FETCH_ASSOC) ?? [];

// Yardımcı fonksiyonlar
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
            return $student['firstname'] . ' ' . $student['lastname'];
        }
    }
    return "";
}

function getCourseName($courseId)
{
    global $courses;
    foreach ($courses as $course) {
        if ($course['id'] == $courseId) {
            return $course['course_name'];
        }
    }
    return "";
}

function getPaymentMethodName($paymentMethodId)
{
    global $payment_methods;
    foreach ($payment_methods as $payment_method) {
        if ($payment_method['id'] == $paymentMethodId && isset($payment_method['name'])) {
            return $payment_method['name'];
        }
    }
    return "";
}
?>
<?php
require_once "admin_panel_header.php";
?>
    <div class="container-fluid">
    <div class="row">
<?php
require_once "admin_panel_sidebar.php";
?>
    <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
        <h2>Muhasebe Kayıtları</h2>
    </div>
<div class="table-responsive">
    <table class="table table-bordered">
        <thead>
        <tr>
            <th>ID</th>
            <th>Akademi</th>
            <th>Öğrenci</th>
            <th>Ders</th>
            <th>Tutar</th>
            <th>Tarih</th>
            <th>Ödeme Yöntemi</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!empty($entries) && is_array($entries)): ?>
            <?php foreach ($entries as $entry): ?>
                <tr>
                    <td><?= $entry['id'] ?></td>
                    <td><?= isset($entry['academy_id']) ? getAcademyName($entry['academy_id']) : 'Belirsiz' ?></td>
                    <td><?= isset($entry['student_id']) ? getStudentName($entry['student_id']) : 'Belirsiz' ?></td>
                    <td><?= isset($entry['course_id']) ? getCourseName($entry['course_id']) : 'Belirsiz' ?></td>
                    <td><?= isset($entry['amount']) ? $entry['amount'] : 'Belirsiz' ?></td>
                    <td><?php
                    if (isset($entry['entry_date'])) {
                        $timestamp = strtotime($entry['entry_date']);
                        echo date('d.m.Y H:i', $timestamp);
                    } else {
                        echo 'Belirsiz';
                    }
                    ?></td>
                    <td><?= isset($entry['payment_method']) ? getPaymentMethodName($entry['payment_method']) : 'Belirsiz' ?></td>
                </tr>
            <?php endforeach; ?>


        <?php else: ?>
            <tr>
                <td colspan="7">Kayıt bulunamadı.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?php
require_once "footer.php";
?>