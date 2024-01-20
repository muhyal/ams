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

$firstDayOfLastMonth = date('Y-m-01', strtotime('-1 month'));
$lastDayOfLastMonth = date('Y-m-t', strtotime('-1 month'));

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
    $sheet->setCellValue('B1', 'Öğrenci Adı');
    $sheet->setCellValue('C1', 'Ders Adı');
    $sheet->setCellValue('D1', 'Ders Tarihi');
    $sheet->setCellValue('E1', 'Katılım Tarihi');

    // Bu tarih aralığındaki ders katılım bilgilerini getir
    $sql = "
    SELECT
        academies.name AS academy_name,
        CONCAT(users.first_name, ' ', users.last_name) AS student_name,
        courses.course_name,
        -- Aşağıdaki sütunları derslere göre düzenleyin
        -- Sizin durumunuzda course_date_1, course_date_2, vs. gibi sütunları kullanın
        course_plans.course_date_1 AS course_date_1,
        course_plans.course_attendance_1 AS attendance_1,
        course_plans.course_date_2 AS course_date_2,
        course_plans.course_attendance_2 AS attendance_2,
        course_plans.course_date_3 AS course_date_3,
        course_plans.course_attendance_3 AS attendance_3,
        course_plans.course_date_4 AS course_date_4,
        course_plans.course_attendance_4 AS attendance_4
    FROM course_plans
    INNER JOIN academies ON course_plans.academy_id = academies.id
    INNER JOIN users ON course_plans.student_id = users.id
    INNER JOIN courses ON course_plans.course_id = courses.id
    WHERE academies.id = :academy_id
    AND (course_plans.course_date_1 BETWEEN :start_date AND :end_date
        OR course_plans.course_date_2 BETWEEN :start_date AND :end_date
        OR course_plans.course_date_3 BETWEEN :start_date AND :end_date
        OR course_plans.course_date_4 BETWEEN :start_date AND :end_date)
    AND users.user_type = 6
";

    $stmtAttendance = $db->prepare($sql);
    $stmtAttendance->bindParam(":academy_id", $academy['id'], PDO::PARAM_INT);
    $stmtAttendance->bindParam(":start_date", $firstDayOfLastMonth, PDO::PARAM_STR);
    $stmtAttendance->bindParam(":end_date", $lastDayOfLastMonth, PDO::PARAM_STR);
    $stmtAttendance->execute();
    $attendanceData = $stmtAttendance->fetchAll(PDO::FETCH_ASSOC);

    $row = 2;
    foreach ($attendanceData as $attendance) {
        $sheet->setCellValue('A' . $row, $attendance['academy_name']);
        $sheet->setCellValue('B' . $row, $attendance['student_name']);
        $sheet->setCellValue('C' . $row, $attendance['course_name']);

        // Ders tarihleri ve katılım bilgilerini döngü ile kontrol et
        for ($i = 1; $i <= 4; $i++) {
            $courseDateColumn = "course_date_" . $i;
            $attendanceColumn = "attendance_" . $i;

            // Sadece dolu değerleri ekleyelim
            if (!empty($attendance[$courseDateColumn])) {
                $sheet->setCellValue('D' . $row, $attendance[$courseDateColumn]);
                $sheet->setCellValue('E' . $row, $attendance[$attendanceColumn]);
                $row++;
            }
        }
    }
}

// Hata ayıklama
error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);

// Excel dosyasını indir
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="genel_ders_planlari_raporu.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
