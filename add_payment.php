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
    header("Location: admin_login.php"); // Giriş sayfasına yönlendir
    exit();
}

require_once "db_connection.php";
require_once "config.php";

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

$selectedPlanId = isset($_GET['id']) ? $_GET['id'] : null;

// Başarı mesajı için değişken
$successMessage = "";
$errorMessage = "";


// Eğer form gönderilmişse
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Diğer form alanlarından gelen verileri alın
    $rowId = isset($_POST["rowId"]) ? $_POST["rowId"] : null;
    $paymentAmount = isset($_POST["paymentAmount"]) ? $_POST["paymentAmount"] : null;
    $paymentMethod = isset($_POST["paymentMethod"]) ? $_POST["paymentMethod"] : null;
    $paymentNotes = isset($_POST["paymentNotes"]) ? $_POST["paymentNotes"] : null;

// Ek bilgileri alın
    $firstName = isset($_POST["firstname"]) ? $_POST["firstname"] : null;
    $lastName = isset($_POST["lastname"]) ? $_POST["lastname"] : null;
    $courseName = isset($_POST["coursename"]) ? $_POST["coursename"] : null;
    $academyName = isset($_POST["academyname"]) ? $_POST["academyname"] : null;
    $courseFee = isset($_POST["coursefee"]) ? $_POST["coursefee"] : null;
    $debtAmount = isset($_POST["debtamount"]) ? $_POST["debtamount"] : null;
    $courseDates = isset($_POST["coursedates"]) ? $_POST["coursedates"] : null;


    try {
        // Ödeme yapılan ders planını alın
        $selectQuery = "SELECT * FROM course_plans WHERE id = :rowId";
        $selectStatement = $db->prepare($selectQuery);
        $selectStatement->bindParam(':rowId', $rowId, PDO::PARAM_INT);
        $selectStatement->execute();
        $coursePlan = $selectStatement->fetch(PDO::FETCH_ASSOC);

        // Eğer ders planı bulunamazsa hata mesajı göster
        if (!$coursePlan) {
            throw new Exception("Ders planı bulunamadı.");
        }

        // Eğer borç miktarı sıfırsa "Borç bulunamadı" mesajı göster
        if ($coursePlan["debt_amount"] == 0) {
            throw new Exception("Bu ders planına ait bir borç bulunmuyor.");
        }

        // Ödeme yapılan öğrencinin toplam borcunu güncelle
        $newDebtAmount = $coursePlan["debt_amount"] - $paymentAmount;

        $updateDebtQuery = "UPDATE course_plans SET debt_amount = :newDebtAmount WHERE id = :rowId";
        $updateDebtStatement = $db->prepare($updateDebtQuery);
        $updateDebtStatement->bindParam(':newDebtAmount', $newDebtAmount, PDO::PARAM_INT);
        $updateDebtStatement->bindParam(':rowId', $rowId, PDO::PARAM_INT);
        $updateDebtStatement->execute();

        // Accounting tablosuna ödeme kaydını ekle
        $insertPaymentQuery = "INSERT INTO accounting (course_plan_id, amount, payment_method, payment_notes)
                      VALUES (:coursePlanId, :paymentAmount, :paymentMethod, :paymentNotes)";
        $insertPaymentStatement = $db->prepare($insertPaymentQuery);
        $insertPaymentStatement->bindParam(':coursePlanId', $rowId, PDO::PARAM_INT);
        $insertPaymentStatement->bindParam(':paymentAmount', $paymentAmount, PDO::PARAM_INT);
        $insertPaymentStatement->bindParam(':paymentMethod', $paymentMethod, PDO::PARAM_STR);
        $insertPaymentStatement->bindParam(':paymentNotes', $paymentNotes, PDO::PARAM_STR);
        $insertPaymentStatement->execute();

        // Başarı mesajını ayarla
        $successMessage = "Ödeme ilgili ders planına başarıyla eklendi.";

    } catch (Exception $e) {
        // Hata durumunda işlemleri burada ele alın (hata mesajını gösterme, kayıt, vb.)
        $errorMessage = "Hata: " . $e->getMessage();
    }
}
// Header ve sidebar dosyalarını dahil et
require_once "admin_panel_header.php";
require_once "admin_panel_sidebar.php";
?>

<main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
        <h2>Ödeme Ekle</h2>
        <div class="btn-group mr-2">
            <button onclick="history.back()" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Geri dön
            </button>
            <a href="accounting_list.php" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-list"></i> Ödeme listesi
            </a>
        </div>
    </div>

    <?php if ($successMessage) : ?>
        <div class="alert alert-success"><?php echo $successMessage; ?></div>
    <?php endif; ?>

    <?php if ($errorMessage) : ?>
        <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
    <?php endif; ?>

    <form action="" method="post">
        <div class="form-group">
            <label for="rowId">Ders Planı Seç:</label>
            <select class="form-control" name="rowId" required>
                <?php
                // Ders planlarını ve öğrenci bilgilerini, öğretmen bilgilerini ve ders adlarını çek
                $selectPlansQuery = "SELECT cp.id, cp.course_fee, cp.debt_amount, cp.course_date_1, cp.course_date_2, cp.course_date_3, cp.course_date_4,
                    u_student.first_name AS student_first_name, u_student.last_name AS student_last_name,
                    u_teacher.first_name AS teacher_first_name, u_teacher.last_name AS teacher_last_name,
                    c.course_name, a.name AS academy_name
                 FROM course_plans cp
                 JOIN users u_student ON cp.student_id = u_student.id
                 JOIN users u_teacher ON cp.teacher_id = u_teacher.id
                 JOIN courses c ON cp.course_id = c.id
                 JOIN academies a ON cp.academy_id = a.id
                 WHERE (u_student.user_type = 6 OR u_teacher.user_type = 4)
                 AND cp.academy_id IN (" . implode(",", $allowedAcademies) . ")";
                $selectPlansStatement = $db->prepare($selectPlansQuery);
                $selectPlansStatement->execute();
                $coursePlans = $selectPlansStatement->fetchAll(PDO::FETCH_ASSOC);

                // Ders planlarını seçenek listesine ekle
                echo "<option value='' selected disabled>Seçim Yapın</option>";

                foreach ($coursePlans as $plan) {
                    $courseDates = implode(", ", array_filter([$plan["course_date_1"], $plan["course_date_2"], $plan["course_date_3"], $plan["course_date_4"]]));
                    $isSelected = ($plan["id"] == $selectedPlanId) ? 'selected' : '';

                    // Renk stilini doğrudan yazdırarak belirt
                    echo "<option value='{$plan["id"]}' data-studentfirstname='{$plan["student_first_name"]}' data-studentlastname='{$plan["student_last_name"]}' 
        data-teacherfirstname='{$plan["teacher_first_name"]}' data-teacherlastname='{$plan["teacher_last_name"]}' 
        data-coursename='{$plan["course_name"]}' data-academyname='{$plan["academy_name"]}' 
        data-coursefee='{$plan["course_fee"]}' data-debtamount='{$plan["debt_amount"]}' data-coursedates='{$courseDates}' 
        {$isSelected}>
        {$plan["student_first_name"]} {$plan["student_last_name"]} - {$plan["teacher_first_name"]} {$plan["teacher_last_name"]} 
        - {$plan["course_name"]} - Ders ücreti: {$plan["course_fee"]} TL - Kalan borç: {$plan["debt_amount"]} TL
    </option>";
                }
                ?>
            </select>

        </div>

        <div class="form-group">
            <label for="paymentAmount">Ödeme Miktarı:</label>
            <input type="text" class="form-control" name="paymentAmount" required>
        </div>

        <div class="form-group">
            <label for="paymentMethod">Ödeme Yöntemi:</label>
            <select class="form-control" name="paymentMethod" required>
                <?php
                // Fetch payment methods from the database
                $paymentMethodsQuery = "SELECT * FROM payment_methods";
                $paymentMethodsStatement = $db->prepare($paymentMethodsQuery);
                $paymentMethodsStatement->execute();
                $paymentMethods = $paymentMethodsStatement->fetchAll(PDO::FETCH_ASSOC);

                // Display payment methods in the dropdown
                foreach ($paymentMethods as $method) {
                    echo "<option value='{$method["id"]}'>{$method["name"]}</option>";
                }
                ?>
            </select>
        </div>

        <div class="form-group">
            <label for="paymentNotes">Ödeme Notları:</label>
            <textarea class="form-control" name="paymentNotes"></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Ödeme Yap</button>
    </form>
</main>

<?php require_once "footer.php"; ?>
