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
require_once(__DIR__ . '/../vendor/autoload.php');
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Oturum kontrolü
session_start();
session_regenerate_id(true);

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

// Form gönderildi mi kontrolü
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Rapor alma işlemi
    if (isset($_POST["generate_report"])) {
        $start_date = $_POST["start_date"];
        $end_date = $_POST["end_date"];

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
            $sheet->setCellValue('B1', 'Toplam Tutar');

            // Bu tarih aralığındaki alınan ödemeleri getir
            $sql = "
        SELECT
            academies.name AS academy_name,
            SUM(accounting.amount) AS total_amount
        FROM accounting
        INNER JOIN academies ON accounting.academy_id = academies.id
        WHERE academies.id = :academy_id
        AND accounting.payment_date BETWEEN :start_date AND :end_date
        GROUP BY accounting.academy_id
    ";

            $stmt = $db->prepare($sql);
            $stmt->bindParam(":academy_id", $academy['id'], PDO::PARAM_INT);
            $stmt->bindParam(":start_date", $start_date, PDO::PARAM_STR);
            $stmt->bindParam(":end_date", $end_date, PDO::PARAM_STR);
            $stmt->execute();
            $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $row = 2;
            foreach ($reportData as $data) {
                $sheet->setCellValue('A' . $row, $data['academy_name']);
                $sheet->setCellValue('B' . $row, $data['total_amount']);
                $row++;
            }

            // Öğrenci Detayları
            $sheet->setCellValue('D1', 'Öğrenci Adı');
            $sheet->setCellValue('E1', 'Ders Adı');
            $sheet->setCellValue('F1', 'Ödeme Tarihi');
            $sheet->setCellValue('G1', 'Ödeme Tutarı');
            $sheet->setCellValue('H1', 'Ödeme Yöntemi');

            // Öğrenci Detayları için sorgu
            $studentDetailSql = "
    SELECT
        students.first_name AS student_name,
        students.last_name AS student_lastname,
        courses.course_name,
        accounting.payment_date AS payment_date,
        accounting.amount AS payment_amount,
        accounting.payment_method
    FROM accounting
    INNER JOIN students ON accounting.student_id = students.id
    INNER JOIN courses ON accounting.course_id = courses.id
    WHERE accounting.academy_id = :academy_id
    AND accounting.payment_date BETWEEN :start_date AND :end_date
";

            // Öğrenci Detayları için sorgu
            $stmtStudentDetail = $db->prepare($studentDetailSql);
            $stmtStudentDetail->bindParam(":academy_id", $academy['id'], PDO::PARAM_INT);
            $stmtStudentDetail->bindParam(":start_date", $start_date, PDO::PARAM_STR);
            $stmtStudentDetail->bindParam(":end_date", $end_date, PDO::PARAM_STR);
            $stmtStudentDetail->execute();
            $studentDetailData = $stmtStudentDetail->fetchAll(PDO::FETCH_ASSOC);

            $row = 2;
            foreach ($studentDetailData as $studentDetail) {
                $sheet->setCellValue('D' . $row, $studentDetail['student_name'] . ' ' . $studentDetail['student_lastname']);
                $sheet->setCellValue('E' . $row, $studentDetail['course_name']);
                $sheet->setCellValue('F' . $row, $studentDetail['payment_date']);
                $sheet->setCellValue('G' . $row, $studentDetail['payment_amount']);

                // Map integer payment_method to its corresponding name
                $paymentMethodNames = [
                    1 => 'Nakit',
                    2 => 'Kredi Kartı',
                    3 => 'Havale / EFT',
                    4 => 'Hediye Çeki',
                    5 => 'Mail Order',
                    // Add more mappings as needed
                ];

                // Check if 'payment_method' key exists before using it
                if (array_key_exists('payment_method', $studentDetail)) {
                    $paymentMethod = isset($paymentMethodNames[$studentDetail['payment_method']])
                        ? $paymentMethodNames[$studentDetail['payment_method']]
                        : 'Bilinmeyen Yöntem';

                    $sheet->setCellValue('H' . $row, $paymentMethod);
                } else {
                    $sheet->setCellValue('H' . $row, 'Kullanılamıyor');
                }

                $row++;
            }
        }
            // Hata ayıklama
        error_reporting(E_ALL);
        ini_set('display_errors', TRUE);
        ini_set('display_startup_errors', TRUE);

        // Excel dosyasını indir
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="ozel_tarih_araliginda_alinan_genel_rapor.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
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
                <h2>Muhasebe Raporları</h2>
            </div>

            <!-- Uyarı Mesajları -->
            <?php if (!empty($alertMessage)): ?>
                <div class="alert alert-<?= $alertColor ?>" role="alert">
                    <?= $alertMessage ?>
                </div>
            <?php endif; ?>


            <div class="card mt-4">
                <div class="card-header">
                    <h5>Özel Tarih Aralığında Rapor Al</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mt-3">
                                    <label for="start_date">Başlangıç Tarihi:</label>
                                    <input type="date" name="start_date" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mt-3">
                                    <label for="end_date">Bitiş Tarihi:</label>
                                    <input type="date" name="end_date" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        <div class="form-group mt-3">
                            <button type="submit" name="generate_report" class="btn btn-primary">Rapor Al</button>
                        </div>
                    </form>                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5>Bu Ayın Genel Raporunu Al</h5>
                </div>
                <div class="card-body">
                    <a href="../reports/general_accounting_report_for_the_current_month.php?generate_report" class="btn btn-primary" target="_blank">Raporu İndir</a>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5>Geçen Ayın Genel Raporunu Al</h5>
                </div>
                <div class="card-body">
                    <a href="../reports/general_accounting_report_for_last_month.php?generate_report" class="btn btn-primary" target="_blank">Raporu İndir</a>
                </div>
            </div>

            <?php require_once('../admin/partials/footer.php'); ?>
        </main>
    </div>
</div>
