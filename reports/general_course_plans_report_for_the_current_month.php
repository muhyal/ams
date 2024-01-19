<?php
global $showErrors, $db;
require __DIR__ . '/../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Oturum kontrolü
session_start();
session_regenerate_id(true);

if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php"); // Giriş sayfasına yönlendir
    exit();
}

require __DIR__ . '/../db_connection.php';
require __DIR__ . '/../config.php';

// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);

$currentDate = date('Y-m-d');
$start_date = date('Y-m-01');
$end_date = $currentDate;

// Akademileri getir
$academiesSql = "SELECT * FROM academies";
$stmtAcademies = $db->prepare($academiesSql);
$stmtAcademies->execute();
$academies = $stmtAcademies->fetchAll(PDO::FETCH_ASSOC);

// Excel dosyasını oluştur
$spreadsheet = new Spreadsheet();

foreach ($academies as $academy) {
    $academyName = $academy['name'];

    // Akademi sayfasını oluştur
    $spreadsheet->createSheet()->setTitle($academyName);
    $spreadsheet->setActiveSheetIndexByName($academyName);
    $sheet = $spreadsheet->getActiveSheet();

    // Türkçe karakter sorunlarını önlemek için
    $sheet->setCellValue('A1', 'Akademi');
    $sheet->setCellValue('B1', 'Öğretmen Adı');
    $sheet->setCellValue('C1', 'Öğrenci Adı');
    $sheet->setCellValue('D1', 'Ders Adı');
    $sheet->setCellValue('E1', 'Sınıf');
    $sheet->setCellValue('F1', 'Ders Tarihi 1');
    $sheet->setCellValue('G1', 'Ders Tarihi 2');
    $sheet->setCellValue('H1', 'Ders Tarihi 3');
    $sheet->setCellValue('I1', 'Ders Tarihi 4');
    $sheet->setCellValue('J1', 'Katılım 1');
    $sheet->setCellValue('K1', 'Katılım 2');
    $sheet->setCellValue('L1', 'Katılım 3');
    $sheet->setCellValue('M1', 'Katılım 4');

    // Bu tarih aralığındaki ders katılım bilgilerini getir
    $sql = "
    SELECT
        academies.name AS academy_name,
        CONCAT(users.first_name, ' ', users.last_name) AS teacher_name,
        CONCAT(users_student.first_name, ' ', users_student.last_name) AS student_name,
        courses.course_name,
        academy_classes.class_name,
        course_plans.course_date_1 AS course_date_1,
        course_plans.course_date_2 AS course_date_2,
        course_plans.course_date_3 AS course_date_3,
        course_plans.course_date_4 AS course_date_4,
        course_plans.course_attendance_1 AS attendance_1,
        course_plans.course_attendance_2 AS attendance_2,
        course_plans.course_attendance_3 AS attendance_3,
        course_plans.course_attendance_4 AS attendance_4
    FROM course_plans
    INNER JOIN academies ON course_plans.academy_id = academies.id
    INNER JOIN users ON course_plans.teacher_id = users.id AND users.user_type = 4
    INNER JOIN users AS users_student ON course_plans.student_id = users_student.id
    INNER JOIN courses ON course_plans.course_id = courses.id
    INNER JOIN academy_classes ON course_plans.class_id = academy_classes.id
    WHERE academies.id = :academy_id
    AND (course_plans.course_date_1 BETWEEN :start_date AND :end_date
        OR course_plans.course_date_2 BETWEEN :start_date AND :end_date
        OR course_plans.course_date_3 BETWEEN :start_date AND :end_date
        OR course_plans.course_date_4 BETWEEN :start_date AND :end_date)
";

    $stmtAttendance = $db->prepare($sql);
    $stmtAttendance->bindParam(":academy_id", $academy['id'], PDO::PARAM_INT);
    $stmtAttendance->bindParam(":start_date", $start_date, PDO::PARAM_STR);
    $stmtAttendance->bindParam(":end_date", $end_date, PDO::PARAM_STR);
    $stmtAttendance->execute();
    $attendanceData = $stmtAttendance->fetchAll(PDO::FETCH_ASSOC);

    $row = 2;
    foreach ($attendanceData as $attendance) {
        $sheet->setCellValue('A' . $row, $attendance['academy_name']);
        $sheet->setCellValue('B' . $row, $attendance['teacher_name']);
        $sheet->setCellValue('C' . $row, $attendance['student_name']);
        $sheet->setCellValue('D' . $row, $attendance['course_name']);
        $sheet->setCellValue('E' . $row, $attendance['class_name']);
        $sheet->setCellValue('F' . $row, $attendance['course_date_1']);
        $sheet->setCellValue('G' . $row, $attendance['course_date_2']);
        $sheet->setCellValue('H' . $row, $attendance['course_date_3']);
        $sheet->setCellValue('I' . $row, $attendance['course_date_4']);
        $sheet->setCellValue('J' . $row, $attendance['attendance_1']);
        $sheet->setCellValue('K' . $row, $attendance['attendance_2']);
        $sheet->setCellValue('L' . $row, $attendance['attendance_3']);
        $sheet->setCellValue('M' . $row, $attendance['attendance_4']);
        $row++;
    }
}

// Hata ayıklama
error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);

// Excel dosyasını indir
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="bu_ayin_genel_ders_planlari_raporu.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
