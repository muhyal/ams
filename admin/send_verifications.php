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
require_once('../src/functions.php');


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
    $query = "SELECT email, phone FROM users WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Kullanıcı bilgilerini alın
        $email = $user["email"];
        $phone = $user["phone"];
    } else {
        // Kullanıcı bulunamadıysa hata mesajı ayarla
        $message = "Kullanıcı bulunamadı.";
    }
} else {
    // "id" parametresi belirtilmemişse veya kullanıcı sayfaya doğrudan eriştiyse, varsayılan değerleri ata
    $email = ""; // veya varsayılan e-posta adresi
    $phone = ""; // veya varsayılan telefon numarası
}

// Kullanıcı bilgilerini veritabanından çekin
$query = "SELECT email, phone, user_type FROM users WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    // Kullanıcı bilgilerini alın
    $email = $user["email"];
    $phone = $user["phone"];
    $user_type = $user["user_type"];
} else {
    // Kullanıcı bulunamadıysa hata mesajı ayarla
    $message = "Kullanıcı bulunamadı.";
}



// Kullanıcıya doğrulama kodlarını yeniden gönderme işlemi
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Oturumda yer alan kullanıcının ID'sini alın
    $sent_by_user_id = $_SESSION["admin_id"];
    // Kullanıcıyı veritabanından bul
    $query = "SELECT * FROM users WHERE email = ? AND phone = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$email, $phone]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Yeniden doğrulama kodları oluştur
        // Kullanıcının veritabanındaki id değerini alın
        $user_id = $user["id"];

// Yeni doğrulama kodlarını oluşturun
        $verificationCodeEmail = generateVerificationCode();
        $verificationCodeSms = generateVerificationCode();

// Yeniden doğrulama zamanlarını güncelle
        $verificationTimeEmail = date("Y-m-d H:i:s", time());
        $verificationTimeSms = date("Y-m-d H:i:s", time());

        $sentAt = date("Y-m-d H:i:s", time()); // Şu anki zaman

        // Veritabanında ekle (insert)
        $insertQuery = "INSERT INTO verifications (user_id, email, phone, verification_code_email, verification_code_sms, verification_time_email_sent, verification_time_sms_sent, sent_by_user_id, sent_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmtInsert = $db->prepare($insertQuery);

        if ($stmtInsert->execute([$user_id, $email, $phone, $verificationCodeEmail, $verificationCodeSms, $verificationTimeEmail, $verificationTimeSms, $sent_by_user_id, $sentAt])) {
            $verificationId = $db->lastInsertId();
        }

// E-posta ve SMS gönderme işlemleri
        sendVerificationEmail($email, $verificationCodeEmail, $user["first_name"], $verificationId);
        sendVerificationSms($phone, $verificationCodeSms, $user["first_name"], $verificationId);


        $message = "Doğrulama kodları yeniden gönderildi.";
    } else {
        $message = "Kullanıcı bulunamadı. Lütfen doğru e-posta ve telefon numarasını girin.";
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
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                <h2>Doğrulamaları Gönder</h2>
            </div>

            <!-- Mesajı burada gösteriyoruz -->
            <?php if (isset($message) && $message !== ""): ?>
                <div class="mt-3 mb-3 alert alert-info" role="alert">
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <!-- SMS gönderim başarılı mesajı -->
            <?php if (isset($smsSuccessMessage) && $smsSuccessMessage !== ""): ?>
                <div class="mt-3 mb-3 alert alert-success" role="alert">
                    <?= $smsSuccessMessage ?>
                </div>
            <?php endif; ?>

            <!-- SMS gönderim hata mesajı -->
            <?php if (isset($smsErrorMessage) && $smsErrorMessage !== ""): ?>
                <div class="mt-3 mb-3 alert alert-danger" role="alert">
                    <?= $smsErrorMessage ?>
                </div>
            <?php endif; ?>

            <!-- Yeniden doğrulama isteği formu -->
    <form method="post" action="">
        <label class="form-label" for="email">E-posta:</label>
        <input class="form-control" type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required><br>

        <label class="form-label" for="phone">Telefon:</label>
        <input class="form-control" type="text" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required><br>

        <button type="submit" class="btn btn-primary">Doğrulama Kodunu Yeniden Gönder</button>
    </form>

</div>

<?php require_once('../admin/partials/footer.php'); ?>

