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

require __DIR__ . '/../vendor/autoload.php';

if (!isset($_SESSION["admin_id"])) {
    header("Location: index.php"); // Giriş sayfasına yönlendir
    exit();
}

require_once(__DIR__ . '/../config/db_connection.php');
require_once('../config/config.php');

// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);

use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isPhpEnabled', true);
$options->set('defaultFont', 'Arial');
$options->set('dpi', '1200');

// Bugünün tarihini al
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Akademi ID'sini linkten al
$academyId = isset($_GET['academy_id']) ? $_GET['academy_id'] : null;

// Akademi ID'si belirtilmemişse veya geçerli bir sayı değilse varsayılan bir değer kullanabilirsiniz
if ($academyId === null || !is_numeric($academyId)) {
    $academyId = 0; // Varsayılan bir akademi ID'si, kendi veritabanınıza göre güncelleyin
}

$queryDailyPlans = "
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
        u.tc_identity,
        c.course_name AS lesson_name,
        u.district,
        u.tax_office,
        u.tax_number,
        cp.created_by_user_id,
        cp.created_at,
        created_by.first_name AS created_by_first_name,
        created_by.last_name AS created_by_last_name,
        cp.id AS course_plan_id
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
        a.id = :academy_id
        AND DATE(cp.created_at) = :date
";

try {
    $stmtDailyPlans = $db->prepare($queryDailyPlans);
    $stmtDailyPlans->bindParam(':academy_id', $academyId, PDO::PARAM_INT);
    $stmtDailyPlans->bindParam(':date', $date, PDO::PARAM_STR);
    $stmtDailyPlans->execute();
    $dailyPlans = $stmtDailyPlans->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error in query: " . $e->getMessage());
}

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
$queryBankNames = "SELECT id, bank_name FROM banks";
$stmtBankNames = $db->prepare($queryBankNames);
$stmtBankNames->execute();
$paymentBankNames = $stmtBankNames->fetchAll(PDO::FETCH_KEY_PAIR);


// Ödeme yöntemlerini çek
$queryPaymentMethods = "SELECT id, name FROM payment_methods";
$stmtPaymentMethods = $db->prepare($queryPaymentMethods);
$stmtPaymentMethods->execute();
$paymentMethodNames = $stmtPaymentMethods->fetchAll(PDO::FETCH_KEY_PAIR);



foreach ($payments as &$payment) {
    // Ödeme yöntemini ve banka adını id'ye göre değiştir
    $paymentMethodId = $payment['payment_method'];
    $bankNameId = $payment['bank_name'];

    $payment['payment_method'] = isset($paymentMethodNames[$paymentMethodId]) ? $paymentMethodNames[$paymentMethodId] : 'Bilinmeyen Ödeme Yöntemi';
    $payment['bank_name'] = isset($paymentBankNames[$bankNameId]) ? $paymentBankNames[$bankNameId] : 'Bilinmeyen Banka';
}



// Function to get admin information including academy name
function getAdminInfo($adminId) {
    global $db;

    $query = "
    SELECT
        u.id,
        u.first_name,
        u.last_name
    FROM
        users u
    WHERE
        u.id = :admin_id
";


    $stmt = $db->prepare($query);
    $stmt->bindParam(':admin_id', $adminId, PDO::PARAM_INT);
    $stmt->execute();
    $adminInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    return $adminInfo;
}

// Akademi adını al
$queryAcademyName = "SELECT name FROM academies WHERE id = :academy_id";
$stmtAcademyName = $db->prepare($queryAcademyName);
$stmtAcademyName->bindParam(':academy_id', $academyId, PDO::PARAM_INT);
$stmtAcademyName->execute();
$academyName = $stmtAcademyName->fetchColumn();



// Çıktıyı alan admin bilgilerini al
$adminId = isset($_SESSION["admin_id"]) ? $_SESSION["admin_id"] : null;
$adminInfo = getAdminInfo($adminId);

// PDF oluşturmak için HTML oluşturun
$html = "
<html>
<head>
<style>
        * { font-family: DejaVu Sans !important; }
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
            font-size: smaller; /* veya istediğiniz bir font boyutu */
        }
    </style>
</head>
<body>
    <h2>Günlük Ciro Listesi</h2>
   <table>
        <tr>
            <th>T.C. Kimlik No</th>
            <th>Öğrenci</th>
            <th>Telefon No</th>
            <th>Fatura Türü</th>
            <th>Akademi</th>
            <th>Ders</th>
            <th>Eğitim Danışmanı</th>
            <th>Oluşturuldu</th>
            <th>Ders Ücreti</th>
            <th>Kalan Borç</th>
            <th>Ödeme Tutarı</th>
            <th>Ödeme Tarihi</th>
            <th>Ödeme Yöntemi</th>
            <th>Banka</th>
            <th>Ödeme Notları</th>
            <th>Ödemeyi İşleyen</th>
        </tr>";

foreach ($dailyPlans as $plan) {

    // Ders ücreti ve kalan borç bilgilerini çek
    $queryRemainingDebt = "
        SELECT
            course_fee,
            debt_amount
        FROM
            course_plans
        WHERE
            id = :course_plan_id
    ";

    $stmtRemainingDebt = $db->prepare($queryRemainingDebt);
    $stmtRemainingDebt->bindParam(':course_plan_id', $plan['course_plan_id'], PDO::PARAM_INT);
    $stmtRemainingDebt->execute();
    $remainingDebtInfo = $stmtRemainingDebt->fetch(PDO::FETCH_ASSOC);


    // Ödeme bilgilerini çek
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
    $stmtPayments->bindParam(':course_plan_id', $plan['course_plan_id'], PDO::PARAM_INT);
    $stmtPayments->execute();
    $payments = $stmtPayments->fetchAll(PDO::FETCH_ASSOC);

$html .= "
    <tr>
        <td>{$plan['tc_identity']}</td>
        <td>{$plan['student_first_name']} {$plan['student_last_name']}</td>
        <td>{$plan['student_phone']}</td>
        <td>" . ($plan['invoice_type'] == 'corporate' ? 'Kurumsal' : 'Bireysel') . "</td>
        <td>{$plan['academy_name']}</td>
        <td>{$plan['lesson_name']}</td>
        <td>{$plan['created_by_first_name']} {$plan['created_by_last_name']}</td>
        <td>" . date('d.m.Y H:i', strtotime($plan['created_at'])) . "</td>
        <td>{$remainingDebtInfo['course_fee']} TL</td>
        <td>{$remainingDebtInfo['debt_amount']} TL</td>";

// Ödeme bilgilerini tabloya ekle
    if (count($payments) > 0) {
        $payment = $payments[0]; // Sadece ilk ödeme bilgisini alıyoruz, isteğe bağlı olarak diğer ödemeleri de ekleyebilirsiniz

        // Ödeme yöntemini ve banka adını id'ye göre değiştir
        $paymentMethod = isset($paymentMethodNames[$payment['payment_method']]) ? $paymentMethodNames[$payment['payment_method']] : 'Yok';
        $bankName = isset($paymentBankNames[$payment['bank_name']]) ? $paymentBankNames[$payment['bank_name']] : 'Yok';

        $html .= "
        <td>{$payment['amount']} TL</td>
        <td>" . date('d.m.Y H:i', strtotime($payment['payment_date'])) . "</td>
        <td>{$paymentMethod}</td>
        <td>{$bankName}</td>
        <td>{$payment['payment_notes']}</td>
        <td>{$payment['received_by_first_name']} {$payment['received_by_last_name']}</td>";
    } else {
        // Eğer ödeme bilgisi yoksa boş hücreler ekleyebilirsiniz
        $html .= "
        <td>Yok</td>
        <td>Yok</td>
        <td>Yok</td>
        <td>Yok</td>
        <td>Yok</td>
        <td>Yok</td>";
    }

    $html .= "</tr>";

}

// Toplam ödeme tutarını hesapla
$totalPaymentAmount = 0;
foreach ($dailyPlans as $plan) {
    $totalPaymentAmount += isset($payments[0]['amount']) ? $payments[0]['amount'] : 0;
}

$html .= "
    </table>
    <div class='total-payment-info'>
        <p><strong>Toplam Ödeme Tutarı:</strong> {$totalPaymentAmount} TL</p>
        <p><strong>Çıktıyı Alan:</strong> {$adminInfo['first_name']} {$adminInfo['last_name']}</p>
        <p><strong>Akademi:</strong> {$academyName}</p>
        <p><strong>Çıktı Alınma Tarihi:</strong> " . date('d.m.Y H:i') . "</p>
    </div>
</body>
</html>";

// PDF oluştur

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

// PDF'i tarayıcıya gönder
$dompdf->stream('gunluk_ders_planlari.pdf', array('Attachment' => 1));
exit();
?>
