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

// Bugünün tarihini al
$currentDate = date('d-m-Y');

// Rapor alma işlemi
if (isset($_GET["generate_report"])) {
    $start_date = date('Y-m-01');
    $end_date = date('Y-m-d');

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
    INNER JOIN course_plans ON accounting.course_plan_id = course_plans.id
    INNER JOIN academies ON course_plans.academy_id = academies.id
    WHERE academies.id = :academy_id
    AND accounting.payment_date BETWEEN :start_date AND :end_date
    GROUP BY academies.id
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
        CONCAT(users.first_name, ' ', users.last_name) AS student_name,
        courses.course_name,
        accounting.payment_date AS payment_date,
        accounting.amount AS payment_amount,
        accounting.payment_method
    FROM accounting
    INNER JOIN course_plans ON accounting.course_plan_id = course_plans.id
    INNER JOIN academies ON course_plans.academy_id = academies.id
    INNER JOIN users ON course_plans.student_id = users.id
    INNER JOIN courses ON course_plans.course_id = courses.id
    WHERE academies.id = :academy_id
    AND accounting.payment_date BETWEEN :start_date AND :end_date
    AND users.user_type = 6
";



        $stmtStudentDetail = $db->prepare($studentDetailSql);
        $stmtStudentDetail->bindParam(":academy_id", $academy['id'], PDO::PARAM_INT);
        $stmtStudentDetail->bindParam(":start_date", $start_date, PDO::PARAM_STR);
        $stmtStudentDetail->bindParam(":end_date", $end_date, PDO::PARAM_STR);
        $stmtStudentDetail->execute();
        $studentDetailData = $stmtStudentDetail->fetchAll(PDO::FETCH_ASSOC);

        $row = 2;
        foreach ($studentDetailData as $studentDetail) {
            $sheet->setCellValue('D' . $row, $studentDetail['student_name']);
            $sheet->setCellValue('E' . $row, $studentDetail['course_name']);
            $sheet->setCellValue('F' . $row, $studentDetail['payment_date']);
            $sheet->setCellValue('G' . $row, $studentDetail['payment_amount']);

            $paymentMethodNames = [
                1 => 'Nakit',
                2 => 'Kredi Kartı',
                3 => 'Havale / EFT',
                4 => 'Hediye Çeki',
            ];

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
    header('Content-Disposition: attachment;filename="bu_ayin_genel_akademiler_raporu.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

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

            <!-- Rapor Alma Formu -->
            <h5>Bu Ayın Genel Raporunu Al</h5>
            <a href="general_accounting_report_for_the_current_month.php?generate_report" class="btn btn-primary" target="_blank">Raporu İndir</a>

            <?php require_once "footer.php"; ?>
        </main>
    </div>
</div>
