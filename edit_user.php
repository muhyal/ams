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
require_once "config.php";

// Oturum kontrolü
session_start();
session_regenerate_id(true);

if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php"); // Giriş sayfasına yönlendir
    exit();
}

require_once "db_connection.php"; // Veritabanı bağlantısı

// Kullanıcı bilgilerini kullanabilirsiniz
$admin_id = $_SESSION["admin_id"];
$admin_username = $_SESSION["admin_username"];

// Kullanıcı ID'sini alın
if (isset($_GET["id"])) {
    $userId = $_GET["id"];
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
    // Formdan gelen verileri alın
    $username = $_POST["username"];
    $tc_identity = $_POST["tc_identity"];
    $first_name = isset($_POST["first_name"]) ? $_POST["first_name"] : "";
    $last_name = isset($_POST["last_name"]) ? $_POST["last_name"] : "";
    $email = $_POST["email"];
    $phone = $_POST["phone"];
    $new_password = $_POST["new_password"];
    $user_type = $_POST["user_type"];
    $notes = $_POST["notes"];

    // Kurumsal bilgileri alın
    $invoice_type = isset($_POST["invoice_type"]) ? $_POST["invoice_type"] : "";

    if ($invoice_type === "individual" || $invoice_type === "corporate") {
        // Geçerli bir invoice_type değeri var, bu değeri kullanabilirsiniz.
        // İşlemlerinizi burada devam ettirin.
    } else {
        // Geçersiz bir invoice_type değeri varsa, hata işlemlerini yapabilir veya kullanıcıya bir hata mesajı gösterebilirsiniz.
        echo "Geçersiz invoice_type değeri!";
    }
    $tax_company_name = isset($_POST["tax_company_name"]) ? $_POST["tax_company_name"] : "";
    $tax_office = isset($_POST["tax_office"]) ? $_POST["tax_office"] : "";
    $tax_number = isset($_POST["tax_number"]) ? $_POST["tax_number"] : "";

    // Şifre değişikliği yapılacak mı kontrolü
    if (!empty($new_password)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $updateQuery = "UPDATE users SET username = ?, tc_identity = ?, first_name = ?, last_name = ?, email = ?, phone = ?, password = ?, user_type = ?, notes = ?, invoice_type = ?, tax_company_name = ?, tax_office = ?, tax_number = ?, updated_at = ? WHERE id = ?";
        $stmt = $db->prepare($updateQuery);
        $stmt->execute([$username, $tc_identity, $first_name, $last_name, $email, $phone, $hashed_password, $user_type, $notes, $invoice_type, $tax_company_name, $tax_office, $tax_number, date("Y-m-d H:i:s"), $userId]);
    } else {
        // Eğer kurumsal kullanıcı ise ilgili alanları güncelle
        if ($user_type == "corporate") {
            $updateCorporateQuery = "UPDATE users SET username = ?, tc_identity = ?, first_name = ?, last_name = ?, email = ?, phone = ?, user_type = ?, notes = ?, invoice_type = ?, tax_company_name = ?, tax_office = ?, tax_number = ?, updated_at = ? WHERE id = ?";
            $stmtCorporate = $db->prepare($updateCorporateQuery);
            $stmtCorporate->execute([$username, $tc_identity, $first_name, $last_name, $email, $phone, $user_type, $notes, $invoice_type, $tax_company_name, $tax_office, $tax_number, date("Y-m-d H:i:s"), $userId]);
        } else {
            // Eğer bireysel kullanıcı ise ilgili alanları güncelle
            $updateQuery = "UPDATE users SET username = ?, tc_identity = ?, first_name = ?, last_name = ?, email = ?, phone = ?, user_type = ?, notes = ?, invoice_type = ?, tax_company_name = ?, tax_office = ?, tax_number  = ?, updated_at = ? WHERE id = ?";
            $stmt = $db->prepare($updateQuery);
            $stmt->execute([$username, $tc_identity, $first_name, $last_name, $email, $phone, $user_type, $notes, $invoice_type, $tax_company_name, $tax_office, $tax_number, date("Y-m-d H:i:s"), $userId]);
        }
    }


    // Kullanıcıyı güncelledikten sonra yönlendirme yapabilirsiniz
    header("Location: users.php");
    exit();
}

?>
<?php
require_once "admin_panel_header.php";
?>
<div class="container-fluid">
    <div class="row">
        <?php require_once "admin_panel_sidebar.php"; ?>
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

                                <label class="form-label mt-3" for="phone">Telefon:</label>
                                <input class="form-control" type="text" name="phone" value="<?php echo $user["phone"]; ?>" required>


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
                     xhr.open("GET", "generate_password.php", true);
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

<?php
require_once "footer.php";
?>
