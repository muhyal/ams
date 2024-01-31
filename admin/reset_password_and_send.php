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
require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/../src/functions.php');

// Oturum kontrolü
session_start();
session_regenerate_id(true);

// Oturum kontrolü
if (!isset($_SESSION["admin_id"])) {
    header("Location: index.php"); // Giriş sayfasına yönlendir
    exit();
}

require_once(__DIR__ . '/../config/db_connection.php');
require_once(__DIR__ . '/../vendor/autoload.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Infobip\Api\SmsApi;
use Infobip\Configuration;
use Infobip\Model\SmsAdvancedTextualRequest;
use Infobip\Model\SmsDestination;
use Infobip\Model\SmsTextualMessage;

// "id" parametresini kontrol et
if (isset($_GET["id"])) {
    $user_id = $_GET["id"];

    // Kullanıcı bilgilerini veritabanından çekin
    $query = "SELECT email, phone, first_name FROM users WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Kullanıcıya şifre sıfırlama mesajı gönderme işlemi
    if ($user) {
        // Kullanıcı bilgilerini alın
        $email = $user["email"];
        $phone = $user["phone"];
        $first_name = $user["first_name"];


        // Onay butonu tıklandığında
        if (isset($_POST['confirm_reset'])) {
            // Yeni şifre oluştur
            $new_password = generateRandomPassword();

            // Şifreyi sıfırla
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET password = ? WHERE id = ?";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->execute([$hashed_password, $user_id]);

            // Kullanıcıya e-posta ve telefon numarasına mesaj gönder
            sendPasswordResetMessage($email, $phone, $new_password);

            $message = "Kullanıcının şifresi sıfırlandı ve yeni şifre kullanıcıya gönderildi.";
        }
    } else {
        // Kullanıcı bulunamadıysa hata mesajı ayarla
        $message = "Kullanıcı bulunamadı.";
    }
} else {
    // "id" parametresi belirtilmemişse veya kullanıcı sayfaya doğrudan eriştiyse, varsayılan değerleri ata
    $email = ""; // veya varsayılan e-posta adresi
    $phone = ""; // veya varsayılan telefon numarası
}



?>
<?php require_once('../admin/partials/header.php'); ?>
<div class="container-fluid">
    <div class="row">
        <?php
        require_once(__DIR__ . '/partials/sidebar.php');
        ?>
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
                <h2>Şifre Sıfırla & Gönder</h2>
            </div>
<!-- Onay formunu göster -->
<div class='container mt-4'>
    <h5 class='mb-4'>Kullanıcının şifresini sıfırlayıp göndermek istediğinizden emin misiniz?</h5>
    <form method='post' action='reset_password_and_send.php?id=<?php echo $user_id; ?>' class='needs-validation' novalidate>
        <div class='mb-3'>
            <button type='submit' name='confirm_reset' class='btn btn-warning'>Evet, Şifreyi Sıfırla</button>
        </div>
    </form>
</div>
<?php require_once('../admin/partials/footer.php'); ?>