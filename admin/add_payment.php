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
    $selectCoursePlan = isset($_POST["selectCoursePlan"]) ? htmlspecialchars($_POST["selectCoursePlan"], ENT_QUOTES, 'UTF-8') : null;
    $paymentAmount = isset($_POST["paymentAmount"]) ? htmlspecialchars($_POST["paymentAmount"], ENT_QUOTES, 'UTF-8') : null;
    $paymentMethod = isset($_POST["paymentMethod"]) ? htmlspecialchars($_POST["paymentMethod"], ENT_QUOTES, 'UTF-8') : null;
    $paymentNotes = isset($_POST["paymentNotes"]) ? htmlspecialchars($_POST["paymentNotes"], ENT_QUOTES, 'UTF-8') : null;
    $firstName = isset($_POST["firstname"]) ? htmlspecialchars($_POST["firstname"], ENT_QUOTES, 'UTF-8') : null;
    $lastName = isset($_POST["lastname"]) ? htmlspecialchars($_POST["lastname"], ENT_QUOTES, 'UTF-8') : null;
    $courseName = isset($_POST["coursename"]) ? htmlspecialchars($_POST["coursename"], ENT_QUOTES, 'UTF-8') : null;
    $academyName = isset($_POST["academyname"]) ? htmlspecialchars($_POST["academyname"], ENT_QUOTES, 'UTF-8') : null;
    $courseFee = isset($_POST["coursefee"]) ? htmlspecialchars($_POST["coursefee"], ENT_QUOTES, 'UTF-8') : null;
    $debtAmount = isset($_POST["debtamount"]) ? htmlspecialchars($_POST["debtamount"], ENT_QUOTES, 'UTF-8') : null;
    $courseDates = isset($_POST["coursedates"]) ? htmlspecialchars($_POST["coursedates"], ENT_QUOTES, 'UTF-8') : null;

    try {
        // Ödeme yapılan ders planını alın
        $selectQuery = "SELECT * FROM course_plans WHERE id = :selectCoursePlan";
        $selectStatement = $db->prepare($selectQuery);
        $selectStatement->bindParam(':selectCoursePlan', $selectCoursePlan, PDO::PARAM_INT);
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

        // Eğer ödeme miktarı kalan borcu aşıyorsa işlem yapma
        if ($paymentAmount > $coursePlan["debt_amount"]) {
            throw new Exception("Ödeme miktarı, kalan borçtan fazla olamaz.");
        }

        // Ödeme yapılan öğrencinin toplam borcunu güncelle
        $newDebtAmount = $coursePlan["debt_amount"] - $paymentAmount;

        $updateDebtQuery = "UPDATE course_plans SET debt_amount = :newDebtAmount WHERE id = :selectCoursePlan";
        $updateDebtStatement = $db->prepare($updateDebtQuery);
        $updateDebtStatement->bindParam(':newDebtAmount', $newDebtAmount, PDO::PARAM_INT);
        $updateDebtStatement->bindParam(':selectCoursePlan', $selectCoursePlan, PDO::PARAM_INT);
        $updateDebtStatement->execute();

        // Accounting tablosuna ödeme kaydını ekle
        $insertPaymentQuery = "INSERT INTO accounting (course_plan_id, amount, payment_method, payment_notes, bank_name, received_by_id)
                      VALUES (:coursePlanId, :paymentAmount, :paymentMethod, :paymentNotes, :bankId, :receivedById)";
        $insertPaymentStatement = $db->prepare($insertPaymentQuery);
        $insertPaymentStatement->bindParam(':coursePlanId', $selectCoursePlan, PDO::PARAM_INT);
        $insertPaymentStatement->bindParam(':paymentAmount', $paymentAmount, PDO::PARAM_INT);
        $insertPaymentStatement->bindParam(':paymentMethod', $paymentMethod, PDO::PARAM_STR);
        $insertPaymentStatement->bindParam(':paymentNotes', $paymentNotes, PDO::PARAM_STR);

        // Eğer ödeme yöntemi "Banka Transferi" ise banka ID'sini kaydet
        if ($paymentMethod == "bank_transfer" || $paymentMethod == "2" || $paymentMethod == "3" || $paymentMethod == "5") {
            $bankId = isset($_POST["bankId"]) ? $_POST["bankId"] : null;
            $insertPaymentStatement->bindParam(':bankId', $bankId, PDO::PARAM_INT);
        } else {
            $bankId = null;
            $insertPaymentStatement->bindParam(':bankId', $bankId, PDO::PARAM_NULL);
        }

        // Ödemeyi alan kullanıcının ID'sini al
        $receivedById = $_SESSION["admin_id"];
        $insertPaymentStatement->bindParam(':receivedById', $receivedById, PDO::PARAM_INT);

        $insertPaymentStatement->execute();

        // Başarı mesajını ayarla
        $successMessage = "Ödeme ilgili ders planına başarıyla eklendi.";

    } catch (Exception $e) {
        // Hata durumunda işlemleri burada ele alın (hata mesajını gösterme, kayıt, vb.)
        $errorMessage = "Hata: " . $e->getMessage();
    }
}
// Header ve sidebar dosyalarını dahil et
require_once(__DIR__ . '/partials/header.php');
require_once(__DIR__ . '/partials/sidebar.php');
?>


<main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
        <h2>Ödeme Ekle</h2>
        <div class="btn-group mr-2">
            <button onclick="history.back()" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Geri dön
            </button>
            <a href="payments.php" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-list"></i> Ödeme listesi
            </a>
        </div>
    </div>

    <script>
        $(document).ready(function () {
            var planSelect = $('[name="selectCoursePlan"]');
            var paymentAmountInput = $('[name="paymentAmount"]');

            // Select2'yi başlat
            planSelect.select2();

            // Select2 olaylarını dinle
            planSelect.on('select2:select', function (e) {
                var selectedOption = $(this).find(':selected');

                // Seçilen ders planının kalan borç miktarını al
                var debtAmount = parseFloat(selectedOption.data('debtamount'));

                // Sadece kalan borç miktarını ödeme miktarına kopyala
                paymentAmountInput.val(debtAmount);

                // Update the "data-debtamount" attribute for reference
                paymentAmountInput.data('debtamount', debtAmount);
            });
        });

    </script>




    <?php if ($successMessage) : ?>
        <div class="alert alert-success" id="successMessage"><?php echo $successMessage; ?></div>

        <script>
            var countdown = 5;
            var successMessage = document.getElementById("successMessage");
            successMessage.classList.remove("alert-info");  // Remove this line
            successMessage.classList.add("alert-success");

            function updateCountdown() {
                successMessage.innerHTML = "<?php echo $successMessage; ?><br>(" + countdown + ") saniye içerisinde ödemeler listesine yönlendirileceksiniz...";
            }

            function redirect() {
                window.location.href = "payments.php";
            }

            updateCountdown();

            var countdownInterval = setInterval(function() {
                countdown--;
                updateCountdown();

                if (countdown <= 0) {
                    clearInterval(countdownInterval);
                    redirect();
                }
            }, 1000);
        </script>
    <?php endif; ?>

    <?php if ($errorMessage) : ?>
        <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
    <?php endif; ?>

    <form action="" method="post">
        <div class="form-group">
            <label for="selectCoursePlan">Ders Planı Seç:</label>

            <select class="form-control" name="selectCoursePlan" required>
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

                    // Ders adını ve akademi adını ayrı ayrı seçenek listesine ekle
                    $courseName = "{$plan["course_name"]}";
                    $academyName = "{$plan["academy_name"]}";

                    // Renk stilini doğrudan yazdırarak belirt
                    if ($plan["debt_amount"] > 0) {
                        echo "<option value='{$plan["id"]}' data-studentfirstname='{$plan["student_first_name"]}' data-studentlastname='{$plan["student_last_name"]}' 
        data-teacherfirstname='{$plan["teacher_first_name"]}' data-teacherlastname='{$plan["teacher_last_name"]}' 
        data-coursename='{$courseName}' data-academyname='{$academyName}' 
        data-coursefee='{$plan["course_fee"]}' data-debtamount='{$plan["debt_amount"]}' data-coursedates='{$courseDates}' 
        {$isSelected}>
        {$plan["student_first_name"]} {$plan["student_last_name"]} - {$plan["teacher_first_name"]} {$plan["teacher_last_name"]} 
        - {$courseName} - {$academyName} - Ders ücreti: {$plan["course_fee"]} TL - Kalan ödeme: {$plan["debt_amount"]} TL
        </option>";
                    }
                }
                ?>
            </select>
            <div id="paymentSelectHelp" class="form-text">Kalan ödemesi 0 TL olan ders planları listede gözükmemektedir.</div>


        </div>

        <div class="form-group mt-3 mb-3">
            <label for="paymentAmount">Ödeme Miktarı:</label>
            <input type="text" class="form-control" name="paymentAmount" required>
        </div>

        <div class="form-group mt-3 mb-3">
            <label for="paymentMethod">Ödeme Yöntemi:</label>
            <select class="form-control" name="paymentMethod" id="paymentMethod" required>
                <?php
                // Fetch payment methods from the database
                $paymentMethodsQuery = "SELECT * FROM payment_methods";
                $paymentMethodsStatement = $db->prepare($paymentMethodsQuery);
                $paymentMethodsStatement->execute();
                $paymentMethods = $paymentMethodsStatement->fetchAll(PDO::FETCH_ASSOC);

                // Display payment methods in the dropdown
                foreach ($paymentMethods as $method) {
                    // ID'si 2 veya 3 ise banka seçeneklerini göster
                    if ($method["id"] === "2" || $method["id"] === "3" || $method["id"] === "5") {
                        echo "<option value='{$method["id"]}'>{$method["name"]}</option>";
                    } else {
                        echo "<option value='{$method["id"]}'>{$method["name"]}</option>";
                    }
                }
                ?>
            </select>
            <div id="paymentTypeHelp" class="form-text">Banka seçenekleri Kredi Kartı & Havale / EFT seçeneklerinde gözükecektir.</div>
        </div>

        <!-- Banka seçimi sadece "Banka Transferi" seçildiğinde görüntülenecek -->
        <div class="form-group" id="bankSelection" style="display: none;">
            <label for="bankId">Banka Seçimi:</label>
            <select class="form-control" name="bankId">
                <option value="1">Ziraat Bankası</option>
                <option value="2">VakıfBank</option>
                <option value="3">İş Bankası</option>
                <option value="4">Halkbank</option>
                <option value="5">Garanti BBVA</option>
                <option value="6">Yapı Kredi</option>
                <option value="7">Akbank</option>
                <option value="8">QNB Finansbank</option>
                <option value="9">DenizBank</option>
                <option value="10">TEB</option>
            </select>
        </div>

        <div class="form-group">
            <label for="paymentNotes">Ödeme Notları (Opsiyonel):</label>
            <textarea class="form-control" name="paymentNotes"></textarea>
        </div>

        <button type="submit" class="btn btn-primary mt-3 mb-3">
            <i class="fas fa-money-check"></i> Ödemeyi İşle
        </button>
    </form>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            var paymentMethodSelect = document.getElementById("paymentMethod");
            var bankSelection = document.getElementById("bankSelection");

            // Sayfa yüklendiğinde kontrol et
            if (paymentMethodSelect.value === "bank_transfer" || paymentMethodSelect.value === "2" || paymentMethodSelect.value === "3" || paymentMethodSelect.value === "5") {
                bankSelection.style.display = "block";
            } else {
                bankSelection.style.display = "none";
            }

            // Ödeme yöntemi değiştiğinde kontrol et
            paymentMethodSelect.addEventListener("change", function () {
                var selectedPaymentMethod = this.value;

                // Ödeme yöntemi "bank_transfer" ise veya seçilen ödeme yöntemi bir banka ID'si ise banka seçimini göster
                if (selectedPaymentMethod === "bank_transfer" || selectedPaymentMethod === "2" || selectedPaymentMethod === "3" || selectedPaymentMethod === "5") {
                    bankSelection.style.display = "block";
                } else {
                    bankSelection.style.display = "none";
                }
            });
        });
    </script>
</main>

<?php require_once('../admin/partials/footer.php'); ?>
