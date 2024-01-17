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

require_once "db_connection.php";
require_once "config.php";

// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);

require 'vendor/autoload.php';
require_once('db_connection.php');

use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isPhpEnabled', true);
$options->set('defaultFont', 'Arial');

$dompdf = new Dompdf($options);

$coursePlanId = isset($_GET['course_plan_id']) ? intval($_GET['course_plan_id']) : 0;

if ($coursePlanId === 0) {
    echo json_encode(array('success' => false, 'message' => 'Geçersiz course_plan_id.'));
    exit();
}

$query = "
    SELECT
        u.first_name AS student_first_name,
        u.last_name AS student_last_name,
        u.phone AS student_phone,
        u.email AS student_email,
        u.invoice_type,
        a.name AS academy_name,
        u.city AS academy_city,
        u.district AS academy_district,
        u.tax_company_name,
        u.phone AS student_phone,
        u.email AS student_email,
        u.tc_identity,
        c.course_name AS lesson_name,
        u.district,
        u.tax_office,
        u.tax_number,
        cp.created_by_user_id,
        cp.created_at,
        created_by.first_name AS created_by_first_name,
        created_by.last_name AS created_by_last_name
    FROM
        course_plans cp
    INNER JOIN
        users u ON cp.student_id = u.id
    LEFT JOIN
        academies a ON cp.academy_id = a.id
    LEFT JOIN
        courses c ON cp.course_id = c.id
    LEFT JOIN
        users created_by ON cp.created_by_user_id = created_by.id
    WHERE
        cp.id = :course_plan_id
";

$stmt = $db->prepare($query);
$stmt->bindParam(':course_plan_id', $coursePlanId, PDO::PARAM_INT);
$stmt->execute();
$coursePlan = $stmt->fetch(PDO::FETCH_ASSOC);

$queryPayments = "
    SELECT
        a.amount,
        a.payment_date,
        a.payment_method,
        a.bank_name,
        a.payment_notes,
        a.received_by_id,
        u.first_name AS received_by_first_name,
        u.last_name AS received_by_last_name
    FROM
        accounting a
    LEFT JOIN
        users u ON a.received_by_id = u.id
    WHERE
        a.course_plan_id = :course_plan_id
";

$stmtPayments = $db->prepare($queryPayments);
$stmtPayments->bindParam(':course_plan_id', $coursePlanId, PDO::PARAM_INT);
$stmtPayments->execute();
$payments = $stmtPayments->fetchAll(PDO::FETCH_ASSOC);


$queryRemainingDebt = "
    SELECT
    course_fee,
    debt_amount,
    course_date_1,
    course_date_2,
    course_date_3,
    course_date_4,
    course_attendance_1,
    course_attendance_2,
    course_attendance_3,
    course_attendance_4
    FROM
        course_plans
    WHERE
        id = :course_plan_id
";

$stmtRemainingDebt = $db->prepare($queryRemainingDebt);
$stmtRemainingDebt->bindParam(':course_plan_id', $coursePlanId, PDO::PARAM_INT);
$stmtRemainingDebt->execute();
$remainingDebtInfo = $stmtRemainingDebt->fetch(PDO::FETCH_ASSOC);


// Banka adlarını ve ödeme yöntemlerini çek
$paymentMethodNames = array(
    1 => 'Ziraat Bankası',
    2 => 'VakıfBank',
    3 => 'İş Bankası',
    4 => 'Halkbank',
    5 => 'Garanti BBVA',
    6 => 'Yapı Kredi',
    7 => 'Akbank',
    8 => 'QNB Finansbank',
    9 => 'DenizBank',
    10 => 'TEB'
);

foreach ($payments as &$payment) {
    // Ödeme yöntemini ve banka adını id'ye göre değiştir
    $payment['payment_method'] = isset($paymentMethodNames[$payment['payment_method']]) ? $paymentMethodNames[$payment['payment_method']] : '-';
    $payment['bank_name'] = isset($paymentMethodNames[$payment['bank_name']]) ? $paymentMethodNames[$payment['bank_name']] : '-';
}

if (!$coursePlan) {
    echo json_encode(array('success' => false, 'message' => 'Ders planı bulunamadı.'));
    exit();
}

$html = "
    <html>
    <style>
    *{ font-family: DejaVu Sans !important;}
    body {
        font-family: Arial, sans-serif;
        font-size: x-small;
    }
    h2 {
        text-align: center;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 10px;
    }
    table, th, td {
        border: 1px solid black;
    }
    th, td {
        padding: 8px;
        text-align: left;
    }
    </style>
    <body>
    
        <h2>Fatura İstemi</h2>

        <!-- Öğrenci Bilgileri Tablosu -->
        <table>
            <tr>
                <td>Adı Soyadı</td>
                <td>{$coursePlan['student_first_name']} {$coursePlan['student_last_name']}</td>
            </tr>
            <tr>
                <td>Telefon No</td>
                <td>{$coursePlan['student_phone']}</td>
            </tr>
            <tr>
                <td>E-posta</td>
                <td>{$coursePlan['student_email']}</td>
            </tr>
            <tr>
                <td>T.C. Kimlik No</td>
                <td>{$coursePlan['tc_identity']}</td>
            </tr>
        </table>

        <!-- Akademi Bilgileri Tablosu -->
        <table>
            <tr>
                <td>Şehir</td>
                <td>{$coursePlan['academy_city']}</td>
            </tr>
            <tr>
                <td>İlçe</td>
                <td>{$coursePlan['academy_district']}</td>
            </tr>
              <tr>
                <td>Fatura Türü</td>
                <td>" . ($coursePlan['invoice_type'] == 'corporate' ? 'Kurumsal' : 'Bireysel') . "</td>
            </tr>
            <tr>
                <td>Şirket Ünvanı</td>
                <td>{$coursePlan['tax_company_name']}</td>
            </tr>
            <tr>
                <td>Vergi Dairesi</td>
                <td>{$coursePlan['tax_office']}</td>
            </tr>
            <tr>
                <td>Vergi Numarası</td>
                <td>{$coursePlan['tax_number']}</td>
            </tr>
        </table>
        
        <!-- Course Fee and Remaining Debt Tables -->
        <table>
            <tr>
                <th>Ders Ücreti</th>
                <th>Kalan Borç</th>
            </tr>
            <tr>
                <td>{$remainingDebtInfo['course_fee']} TL</td>
                <td>{$remainingDebtInfo['debt_amount']} TL</td>
            </tr>
        </table>

        
         <!-- Course Plan Dates -->
        <table>
            <tr>
                <th>Ders</th>
                <th>Tarihi</th>
            </tr>
            <tr>
              <td>1. Ders</td>
                <td>" . date(DATETIME_FORMAT, strtotime($remainingDebtInfo['course_date_1'])) . "</td>
            </tr>
            <tr>
                <td>2. Ders</td>
                <td>" . date(DATETIME_FORMAT, strtotime($remainingDebtInfo['course_date_2'])) . "</td>
            </tr>
            <tr>
                <td>3. Ders</td>
                <td>" . date(DATETIME_FORMAT, strtotime($remainingDebtInfo['course_date_3'])) . "</td>
            </tr>
            <tr>
                <td>4. Ders</td>
                <td>" . date(DATETIME_FORMAT, strtotime($remainingDebtInfo['course_date_4'])) . "</td>
             </tr>
        </table>

        <!-- Ders Bilgileri Tablosu -->
        <table>
            <tr>
                <td>Akademi</td>
                <td>{$coursePlan['academy_name']}</td>
            </tr>
            <tr>
                <td>Ders Adı</td>
                <td>{$coursePlan['lesson_name']}</td>
            </tr>
            <tr>
                <td>Eğitim Danışmanı</td>
                <td>{$coursePlan['created_by_first_name']} {$coursePlan['created_by_last_name']}</td>
            </tr>
            <tr>
                <td>Oluşturulma Tarihi</td>
                <td>" . date(DATETIME_FORMAT, strtotime($coursePlan['created_at'])) . "</td>
            </tr>
        </table>

        <!-- Ödeme Bilgileri Tablosu -->

        <table>
            <tr>
                <td>Ödeme Tutarı</td>
                <td>Ödeme Tarihi</td>
                <td>Ödeme Yöntemi</td>
                <td>Banka Adı</td>
                <td>Ödeme Notları</td>
                <td>Ödemeyi İşleyen</td>
            </tr>";

foreach ($payments as $payment) {
    $html .= "
            <tr>
                <td>{$payment['amount']} TL</td>
                <td>" . date(DATETIME_FORMAT, strtotime($payment['payment_date'])) . "</td>
                <td>{$payment['payment_method']}</td>
                <td>{$payment['bank_name']}</td>
                <td>{$payment['payment_notes']}</td>
                <td>{$payment['received_by_first_name']} {$payment['received_by_last_name']}</td>
            </tr>";
}

$html .= "
        </table>
    </body>
    </table>
        
    </body>
    </html>
";

$dompdf->loadHtml($html);

// Ayarlamalar
$dompdf->setPaper('A4', 'portrait');

// PDF oluştur
$dompdf->render();

// PDF'i tarayıcıya gönder
$dompdf->stream('fatura_istemi_' . $coursePlanId . '.pdf', array('Attachment' => 1));
exit();
?>
