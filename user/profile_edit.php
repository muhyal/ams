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

global $db;
session_start();
session_regenerate_id(true);

require_once(__DIR__ . '/../config/db_connection.php');

// Kullanıcı oturum kontrolü yapılır
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

// Formdan gelen veriler alınır ve güncelleme işlemi yapılır
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user_id = htmlspecialchars($_SESSION["user_id"], ENT_QUOTES, 'UTF-8');
    $first_name = htmlspecialchars($_POST["first_name"], ENT_QUOTES, 'UTF-8');
    $last_name = htmlspecialchars($_POST["last_name"], ENT_QUOTES, 'UTF-8');
    $email = htmlspecialchars($_POST["email"], ENT_QUOTES, 'UTF-8');
    $tc_identity = htmlspecialchars($_POST["tc_identity"], ENT_QUOTES, 'UTF-8');
    $phone = htmlspecialchars($_POST["phone"], ENT_QUOTES, 'UTF-8');
    $invoice_type = isset($_POST["invoice_type"]) ? htmlspecialchars($_POST["invoice_type"], ENT_QUOTES, 'UTF-8') : "";
    $tax_company_name = isset($_POST["tax_company_name"]) ? htmlspecialchars($_POST["tax_company_name"], ENT_QUOTES, 'UTF-8') : "";
    $tax_office = isset($_POST["tax_office"]) ? htmlspecialchars($_POST["tax_office"], ENT_QUOTES, 'UTF-8') : "";
    $tax_number = isset($_POST["tax_number"]) ? htmlspecialchars($_POST["tax_number"], ENT_QUOTES, 'UTF-8') : "";
    $tc_identity_for_individual_invoice = isset($_POST["tc_identity_for_individual_invoice"]) ? htmlspecialchars($_POST["tc_identity_for_individual_invoice"], ENT_QUOTES, 'UTF-8') : "";


    // Güncelleme sorgusu hazırlanır
    $query = "UPDATE users SET 
            first_name = ?, 
            last_name = ?, 
            email = ?, 
            tc_identity = ?, 
            phone = ?,  
            invoice_type = ?, 
            tax_company_name = ?, 
            tax_office = ?, 
            tax_number = ?, 
            tc_identity_for_individual_invoice = ? 
            WHERE id = ?";
    $stmt = $db->prepare($query);

    // Güncelleme sorgusu çalıştırılır
    $result = $stmt->execute([$first_name, $last_name, $email, $tc_identity, $phone, $invoice_type, $tax_company_name, $tax_office, $tax_number,
        $tc_identity_for_individual_invoice, $user_id]);


    if ($result) {
        // Başarılı güncelleme durumunda kullanıcıyı bilgilendir
        $_SESSION["success_message"] = "Profil bilgileriniz güncellendi.";
    } else {
        // Hata durumunda kullanıcıyı bilgilendir
        $_SESSION["error_message"] = "Profil bilgileriniz güncellenirken bir hata oluştu.";
    }

 // Profil sayfasına yönlendirme yapılır
    header("Location: /user/panel.php");
    exit();

}

// Veritabanından kullanıcı detaylarını çek
$user_id = $_SESSION["user_id"];
$query = "SELECT * FROM users WHERE id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

require_once(__DIR__ . '/partials/header.php');

?>

<!-- Ana içerik -->
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-4 mt-3">
            <div class="card">
                <!-- Form başlangıcı -->
                <form action="" method="post">
                    <!-- Form alanları buraya eklenir -->
                    <div class="form-group mt-3 mx-3">
                        <label for="tc_identity">T.C. Kimlik No:</label>
                        <input type="text" class="form-control" name="tc_identity" value="<?= $user['tc_identity'] ?>" readonly style="background-color: #f8f9fa; cursor: not-allowed;">
                    </div>

                    <div class="form-group mt-3 mx-3">
                        <label for="first_name">Ad:</label>
                        <input type="text" class="form-control" name="first_name" value="<?= $user['first_name'] ?>" required>
                    </div>

                    <div class="form-group mt-3 mx-3">
                        <label for="last_name">Soyad:</label>
                        <input type="text" class="form-control" name="last_name" value="<?= $user['last_name'] ?>" required>
                    </div>

                    <div class="form-group mt-3 mx-3">
                        <label for="email">E-posta:</label>
                        <input type="email" class="form-control" name="email" value="<?= $user['email'] ?>" required>
                    </div>

                    <div class="form-group mt-3 mx-3">
                        <label for="phone">Telefon:</label>
                        <input type="text" class="form-control" name="phone" value="<?= $user['phone'] ?>" required>
                    </div>

                    <?php if ($user['user_type'] == 6): ?>
                        <!-- Fatura Tipi Seçimi -->
                    <div class="form-group mt-3 mx-3">
                    <label for="invoice_type">Fatura Tipi Seçin:</label>
                        <select class="form-select" name="invoice_type" id="invoice_type" onchange="toggleInvoiceFields()" required>
                            <option value="individual" <?php echo $user["invoice_type"] === 'individual' ? 'selected' : ''; ?>>Bireysel</option>
                            <option value="corporate" <?php echo $user["invoice_type"] === 'corporate' ? 'selected' : ''; ?>>Kurumsal</option>
                        </select>

                    <!-- Kurumsal Alanları -->
                        <div id="corporate_fields" style="display: <?php echo $user["invoice_type"] === 'corporate' ? 'block' : 'none'; ?>">
                            <label class="form-label mt-3" for="tax_company_name">Şirket Ünvanı:</label>
                            <input class="form-control" type="text" name="tax_company_name" value="<?php echo $user["tax_company_name"]; ?>" required>

                            <label class="form-label mt-3" for="tax_office">Vergi Dairesi:</label>
                            <input class="form-control" type="text" name="tax_office" value="<?php echo $user["tax_office"]; ?>" required>

                            <label class="form-label mt-3" for="tax_number">Vergi Numarası:</label>
                            <input class="form-control" type="text" name="tax_number" value="<?php echo $user["tax_number"]; ?>" required>
                        </div>

                        <!-- Bireysel Alanları -->
                        <div id="individual_fields" style="display: <?php echo $user["invoice_type"] === 'individual' ? 'block' : 'none'; ?>">
                            <label class="form-label mt-3" for="tc_identity_for_individual_invoice">Fatura T.C. Kimlik No:</label>
                            <input class="form-control" type="text" name="tc_identity_for_individual_invoice" value="<?php echo $user["tc_identity_for_individual_invoice"]; ?>" required>
                        </div>
                    <?php endif; ?>
                    </div>


                    <div class="form-group mt-3 mx-3 mb-3">
                        <button type="submit" class="btn btn-primary">Bilgileri Güncelle</button>
                    </div>
                </form>
                <!-- Form bitişi -->
            </div>
        </div>
    </div>
</div>

<script>
    // Fatura tipi seçildiğinde tetiklenecek fonksiyon
    function toggleInvoiceFields() {
        var invoiceType = document.getElementById('invoice_type').value;
        var corporateFields = document.getElementById('corporate_fields');
        var individualFields = document.getElementById('individual_fields');

        // Kurumsal ve bireysel alanları göster veya gizle
        corporateFields.style.display = (invoiceType === 'corporate') ? 'block' : 'none';
        individualFields.style.display = (invoiceType === 'individual') ? 'block' : 'none';

        // Gerekli alanları kontrol et ve ayarla
        var taxCompanyInput = document.getElementsByName('tax_company_name')[0];
        var taxOfficeInput = document.getElementsByName('tax_office')[0];
        var taxNumberInput = document.getElementsByName('tax_number')[0];
        var tcIdentityInput = document.getElementsByName('tc_identity_for_individual_invoice')[0];

        taxCompanyInput.required = (invoiceType === 'corporate');
        taxOfficeInput.required = (invoiceType === 'corporate');
        taxNumberInput.required = (invoiceType === 'corporate');
        tcIdentityInput.required = (invoiceType === 'individual');
    }

    // Sayfa yüklendiğinde varsayılan olarak çalıştır
    window.onload = toggleInvoiceFields;
</script>

<?php require_once('../user/partials/footer.php'); ?>
