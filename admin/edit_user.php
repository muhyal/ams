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
// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);
require_once('../config/config.php');

// Oturum kontrolü
session_start();
session_regenerate_id(true);

if (!isset($_SESSION["admin_id"])) {
    header("Location: index.php"); // Giriş sayfasına yönlendir
    exit();
}

require_once(__DIR__ . '/../config/db_connection.php');
require_once(__DIR__ . '/../vendor/autoload.php');

use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberFormat;
use League\ISO3166\ISO3166;

// Ülkeleri al
$phoneNumberUtil = PhoneNumberUtil::getInstance();
$iso3166 = new ISO3166();

// Kullanıcı bilgilerini kullanabilirsiniz
$admin_id = $_SESSION["admin_id"];
$admin_username = $_SESSION["admin_username"];

// Düzenleyen kullanıcının id ve tipini al
$editorUserId = $_SESSION['admin_id'];
$editorUserType = $_SESSION['admin_type'];

// Kullanıcı ID'sini ve düzenlenen kullanıcının tipini alın
if (isset($_GET["id"])) {
    $userId = $_GET["id"];

    // Kullanıcı tipini ve düzenleyen kullanıcının tipini al
    $queryUserType = "SELECT user_type FROM users WHERE id = :user_id";
    $stmtUserType = $db->prepare($queryUserType);
    $stmtUserType->bindParam(":user_id", $userId, PDO::PARAM_INT);
    $stmtUserType->execute();
    $userTypeResult = $stmtUserType->fetch(PDO::FETCH_ASSOC);

    // Eğer düzenleyen kullanıcı admin ise veya (düzenleyen kullanıcı tipi 2, 3, 4, 5, 6 ise ve düzenlenen kullanıcı tipi 2, 3, 4, 5, 6 ise)
    if ($editorUserType == 1 || ($editorUserType >= 2 && $editorUserType <= 6 && $userTypeResult['user_type'] >= 2 && $userTypeResult['user_type'] <= 6)) {
        // Diğer kodlar devam eder...
        $getUserQuery = "SELECT * FROM users LEFT JOIN user_types ON users.user_type = user_types.type_name WHERE users.id = ?";
        $stmt = $db->prepare($getUserQuery);
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Kullanıcı tiplerini almak için bir SELECT sorgusu
        $getUserTypesQuery = "SELECT id, type_name FROM user_types";
        $stmtUserTypes = $db->query($getUserTypesQuery);
        $userTypes = $stmtUserTypes->fetchAll(PDO::FETCH_ASSOC);
    } else {
        echo "<p>Bu kullanıcıyı düzenleme izniniz yok.</p>";
        exit();
    }
} else {
    echo "<p>Geçersiz kullanıcı ID'si.</p>";
    exit();



    $getUserQuery = "SELECT * FROM users LEFT JOIN user_types ON users.user_type = user_types.type_name WHERE users.id = ?";
    $stmt = $db->prepare($getUserQuery);
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Kullanıcı tiplerini almak için bir SELECT sorgusu
    $getUserTypesQuery = "SELECT id, type_name FROM user_types";
    $stmtUserTypes = $db->query($getUserTypesQuery);
    $userTypes = $stmtUserTypes->fetchAll(PDO::FETCH_ASSOC);
}

// Form gönderildiğinde güncelleme işlemini gerçekleştirin
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Formdan gelen verileri güvenli bir şekilde alın
    $username = htmlspecialchars($_POST["username"], ENT_QUOTES, 'UTF-8');
    $tc_identity = htmlspecialchars($_POST["tc_identity"], ENT_QUOTES, 'UTF-8');
    $first_name = isset($_POST["first_name"]) ? htmlspecialchars($_POST["first_name"], ENT_QUOTES, 'UTF-8') : "";
    $last_name = isset($_POST["last_name"]) ? htmlspecialchars($_POST["last_name"], ENT_QUOTES, 'UTF-8') : "";
    $email = htmlspecialchars($_POST["email"], ENT_QUOTES, 'UTF-8');
    $phone = htmlspecialchars($_POST["phone"], ENT_QUOTES, 'UTF-8');
    $birth_date = htmlspecialchars($_POST["birth_date"], ENT_QUOTES, 'UTF-8');
    $city = htmlspecialchars($_POST["city"], ENT_QUOTES, 'UTF-8');
    $district = htmlspecialchars($_POST["district"], ENT_QUOTES, 'UTF-8');
    $blood_type = htmlspecialchars($_POST["blood_type"], ENT_QUOTES, 'UTF-8');
    $health_issue = htmlspecialchars($_POST["health_issue"], ENT_QUOTES, 'UTF-8');
    $emergency_contact = htmlspecialchars($_POST["emergency_contact"], ENT_QUOTES, 'UTF-8');
    $emergency_phone = htmlspecialchars($_POST["emergency_phone"], ENT_QUOTES, 'UTF-8');
    $new_password = htmlspecialchars($_POST["new_password"], ENT_QUOTES, 'UTF-8');
    $user_type = htmlspecialchars($_POST["user_type"], ENT_QUOTES, 'UTF-8');
    $notes = htmlspecialchars($_POST["notes"], ENT_QUOTES, 'UTF-8');
    $is_active = htmlspecialchars($_POST["is_active"], ENT_QUOTES, 'UTF-8');

// Kurumsal bilgileri güvenli bir şekilde alın
    $invoice_type = isset($_POST["invoice_type"]) ? htmlspecialchars($_POST["invoice_type"], ENT_QUOTES, 'UTF-8') : "";
    $tax_company_name = isset($_POST["tax_company_name"]) ? htmlspecialchars($_POST["tax_company_name"], ENT_QUOTES, 'UTF-8') : "";
    $tax_office = isset($_POST["tax_office"]) ? htmlspecialchars($_POST["tax_office"], ENT_QUOTES, 'UTF-8') : "";
    $tax_number = isset($_POST["tax_number"]) ? htmlspecialchars($_POST["tax_number"], ENT_QUOTES, 'UTF-8') : "";
    $country = isset($_POST["country"]) ? htmlspecialchars($_POST["country"], ENT_QUOTES, 'UTF-8') : "";


    // Güncelleme sorgusunu oluşturun
    $updateQuery = "UPDATE users SET 
        username = ?, 
        tc_identity = ?, 
        first_name = ?, 
        last_name = ?, 
        email = ?, 
        phone = ?, 
        user_type = ?, 
        notes = ?, 
        is_active = ?, 
        invoice_type = ?, 
        tax_company_name = ?, 
        tax_office = ?, 
        tax_number = ?, 
        birth_date = ?, 
        city = ?, 
        district = ?, 
        country = ?, 
        blood_type = ?, 
        health_issue = ?, 
        emergency_contact = ?, 
        emergency_phone = ?, 
        updated_at = ?,
        updated_by_user_id = ?"; // Include the updated_by_user_id field


    // Şifre değişikliği yapılacak mı kontrolü
    $params = [
        $username, $tc_identity, $first_name, $last_name, $email, $phone,
        $user_type, $notes, $is_active,
        $invoice_type, $tax_company_name, $tax_office, $tax_number,
        $birth_date, $city, $district, $country,
        $blood_type, $health_issue, $emergency_contact,
        $emergency_phone, date("Y-m-d H:i:s"),
        $admin_id // Assuming $admin_id is the ID of the admin user making the update
    ];

    if (!empty($new_password)) {
        // Şifre değişikliği yapılacaksa
        $updateQuery .= ", password = ?";
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $params[] = $hashed_password;
    }

    $updateQuery .= " WHERE id = ?";
    $params[] = $userId;

    // Şimdi güncelleme sorgusunu çalıştırın
    $stmt = $db->prepare($updateQuery);
    $stmt->execute($params);

    // Kullanıcıyı güncelledikten sonra yönlendirme yapabilirsiniz
    header("Location: users.php");
    exit();
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
                <h2>Kullanıcı Düzenle</h2>
            </div>

            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <div class="btn-group mr-2">
                                <button onclick="history.back()" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-arrow-left"></i> Geri dön
                                </button>
                                <a href="users.php" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-list"></i> Kullanıcı Listesi
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
                        <?php if (isset($user)): ?>
                            <form method="post" action="" class="mb-4">

                                <label class="form-label mt-3" for="is_active">Durum:</label>
                                <select class="form-select" name="is_active" required>
                                    <option value="1" <?php echo $user["is_active"] == 1 ? 'selected' : ''; ?>>Aktif</option>
                                    <option value="0" <?php echo $user["is_active"] == 0 ? 'selected' : ''; ?>>Pasif</option>
                                </select>

                                <label class="form-label mt-3" for="user_type">Kullanıcı Tipi:</label>
                                <select class="form-select" name="user_type" required>
                                    <?php foreach ($userTypes as $type): ?>
                                        <option value="<?php echo $type['id']; ?>" <?php if ($user["user_type"] === $type['id']) echo "selected"; ?>><?php echo $type['type_name']; ?></option>
                                    <?php endforeach; ?>
                                </select>

                                <label class="form-label mt-3" for="username">Kullanıcı adı:</label>
                                <input class="form-control" type="text" name="username" value="<?php echo $user["username"]; ?>" required>

                                <label class="form-label mt-3" for="tc_identity">T.C. Kimlik No:</label>
                                <input class="form-control" type="text" name="tc_identity" value="<?php echo $user["tc_identity"]; ?>" required>

                                <label class="form-label mt-3" for="first_name">Ad:</label>
                                <input class="form-control" type="text" name="first_name" value="<?php echo $user["first_name"]; ?>" required>

                                <label class="form-label mt-3" for="last_name">Soyad:</label>
                                <input class="form-control" type="text" name="last_name" value="<?php echo $user["last_name"]; ?>" required>

                                <label for="email" class="form-label mt-3">E-posta:</label>
                                <input class="form-control" type="email" name="email" aria-describedby="emailHelp" value="<?php echo $user["email"]; ?>" required>
                                <div id="emailHelp" class="form-text">Geçerli bir e-posta adresi olmalıdır.</div>


                                <div class="mt-3">
                                    <label for="country" class="form-label">Ülke:</label>
                                    <div class="input-group">
                                        <select class="form-select" name="country" id="country" required>
                                            <?php
                                            foreach ($iso3166->all() as $country) {
                                                $countryCode = $phoneNumberUtil->getCountryCodeForRegion($country['alpha2']);
                                                $countryName = ($country['alpha2'] == 'TR') ? 'Türkiye' : $country['name'];

                                                // Kullanıcının veritabanında kayıtlı ülkesi ile mevcut ülke eşleşiyorsa, seçili hale getir
                                                $selected = ($user["country"] == $country['alpha2']) ? 'selected' : '';

                                                echo "<option value=\"" . $country['alpha2'] . "\" data-country-code=\"+$countryCode\" $selected>{$countryName}</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>



                                <label class="form-label mt-3" for="phone">Telefon:</label>
                                <input class="form-control" type="text" name="phone" value="<?php echo $user["phone"]; ?>" required>


                                <!-- Doğum Tarihi -->
                                <label class="form-label mt-3" for="birth_date">Doğum Tarihi:</label>
                                <input class="form-control" type="date" name="birth_date" value="<?php echo $user["birth_date"]; ?>" required>


                                <!-- Şehir -->
                                <label class="form-label mt-3" for="city">Şehir:</label>
                                <input class="form-control" type="text" name="city" value="<?php echo $user["city"]; ?>" required>

                                <!-- İlçe -->
                                <label class="form-label mt-3" for="district">İlçe:</label>
                                <input class="form-control" type="text" name="district" value="<?php echo $user["district"]; ?>" required>

                                <!-- Kan Grubu -->
                                <label class="form-label mt-3" for="blood_type">Kan Grubu:</label>
                                <input class="form-control" type="text" name="blood_type" value="<?php echo $user["blood_type"]; ?>" required>

                                <!-- Sağlık Sorunu -->
                                <label class="form-label mt-3" for="health_issue">Sağlık Sorunu:</label>
                                <input class="form-control" type="text" name="health_issue" value="<?php echo $user["health_issue"]; ?>" required>

                                <!-- Acil Durum Kişisi -->
                                <label class="form-label mt-3" for="emergency_contact">Acil Durum Kişisi:</label>
                                <input class="form-control" type="text" name="emergency_contact" value="<?php echo $user["emergency_contact"]; ?>" required>

                                <!-- Acil Durum Telefonu -->
                                <label class="form-label mt-3" for="emergency_phone">Acil Durum Telefonu:</label>
                                <input class="form-control" type="text" name="emergency_phone" value="<?php echo $user["emergency_phone"]; ?>" required>


                                <!-- Bireysel ve Kurumsal alanlarına ID eklendi -->
                                <label class="form-label mt-3" for="invoice_type">Fatura Tipi Seçin:</label>
                                <select class="form-select" name="invoice_type" id="invoice_type" onchange="toggleInvoiceFields()" required>
                                    <option value="individual" <?php echo $user["invoice_type"] === 'individual' ? 'selected' : ''; ?>>Bireysel</option>
                                    <option value="corporate" <?php echo $user["invoice_type"] === 'corporate' ? 'selected' : ''; ?>>Kurumsal</option>
                                </select>

                                <!-- Kurumsal Alanları -->
                                <div id="corporate_fields">
                                    <label class="form-label mt-3" for="tax_company_name">Şirket Ünvanı:</label>
                                    <input class="form-control" type="text" name="tax_company_name" value="<?php echo $user["tax_company_name"]; ?>" required>

                                    <label class="form-label mt-3" for="tax_office">Vergi Dairesi:</label>
                                    <input class="form-control" type="text" name="tax_office" value="<?php echo $user["tax_office"]; ?>" required>

                                    <label class="form-label mt-3" for="tax_number">Vergi Numarası:</label>
                                    <input class="form-control" type="text" name="tax_number" value="<?php echo $user["tax_number"]; ?>" required>

                                </div>


                                <div class="form-group mt-3">
                                    <label class="form-label" for="notes">Notlar (Güncelleme olmayacak ise boş bırakabilirsiniz):</label>
                                    <textarea class="form-control" name="notes"><?php echo $user["notes"]; ?></textarea>
                                </div>

                                <div class="form-group mt-3">
                                    <label class="form-label" for="new_password">Yeni Şifre (Güncelleme olmayacak ise boş bırakabilirsiniz):</label>
                                    <div class="input-group">
                                        <input class="form-control" type="password" name="new_password" id="new_password">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('new_password')">Şifreyi Göster</button>
                                        </div>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-outline-secondary" onclick="copyPassword('new_password')">Kopyala</button>
                                        </div>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-outline-secondary" onclick="generatePassword('new_password')">Şifre Üret</button>
                                        </div>
                                    </div>
                                </div>

             <script>
                 function togglePassword(passwordId) {
                     var passwordInput = document.getElementById(passwordId);
                     if (passwordInput.type === "new_password") {
                         passwordInput.type = "text";
                     } else {
                         passwordInput.type = "new_password";
                     }
                 }

                 function copyPassword(passwordId) {
                     var passwordInput = document.getElementById(passwordId);
                     passwordInput.select();
                     document.execCommand("copy");
                     alert("Şifre kopyalandı: " + passwordInput.value);
                 }

                 function generatePassword(passwordId) {
                     var generatedPasswordInput = document.getElementById(passwordId);
                     var xhr = new XMLHttpRequest();
                     xhr.onreadystatechange = function () {
                         if (xhr.readyState === 4 && xhr.status === 200) {
                             generatedPasswordInput.value = xhr.responseText;
                         }
                     };
                     xhr.open("GET", "/src/generate_password.php", true);
                     xhr.send();
                 }
             </script>
                                <script>
                                    // Fatura tipi seçildiğinde tetiklenecek fonksiyon
                                    function toggleInvoiceFields() {
                                        var invoiceType = document.getElementById('invoice_type').value;
                                        var corporateFields = document.getElementById('corporate_fields');

                                        // Kurumsal alanları göster veya gizle
                                        corporateFields.style.display = (invoiceType === 'corporate') ? 'block' : 'none';

                                        // Gerekli alanları kontrol et ve ayarla
                                        var taxCompanyInput = document.getElementsByName('tax_company_name')[0];
                                        var taxOfficeInput = document.getElementsByName('tax_office')[0];
                                        var taxNumberInput = document.getElementsByName('tax_number')[0];
                                        var eInvoiceSelect = document.getElementsByName('e_invoice')[0];

                                        taxCompanyInput.required = (invoiceType === 'corporate');
                                        taxOfficeInput.required = (invoiceType === 'corporate');
                                        taxNumberInput.required = (invoiceType === 'corporate');
                                        eInvoiceSelect.required = (invoiceType === 'corporate');
                                    }

                                    // Sayfa yüklendiğinde varsayılan olarak çalıştır
                                    window.onload = toggleInvoiceFields;
                                </script>
                                <div class="form-group mt-3">
             <button type="submit" class="btn btn-primary">Güncelle</button>
         </div>
     </form>
<?php else: ?>
    <p>Kullanıcı bulunamadı.</p>
<?php endif; ?>



</div>
</div>

<?php require_once('../admin/partials/footer.php'); ?>

