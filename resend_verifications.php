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

// Oturum kontrolü
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php"); // Giriş sayfasına yönlendir
    exit();
}

require_once "db_connection.php";

//Import PHPMailer classes into the global namespace
//These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

//Load Composer's autoloader
require 'vendor/autoload.php';

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

// Doğrulama bağlantısı oluşturma
function getVerificationLink($emailOrPhone, $code, $type = "email") {
    global $siteUrl;
    if ($type == "phone") {
        return "$siteUrl/verify.php?phone=$emailOrPhone&code=$code";
    } else {
        return "$siteUrl/verify.php?email=$emailOrPhone&code=$code";
    }
}

// Kullanıcıya doğrulama kodlarını yeniden gönderme işlemi
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Kullanıcıyı veritabanından bul
    $query = "SELECT * FROM users WHERE email = ? AND phone = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$email, $phone]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Yeniden doğrulama kodları oluştur
        $verificationCodeEmail = generateVerificationCode();
        $verificationCodeSms = generateVerificationCode();

        // Yeniden doğrulama zamanlarını güncelle
        $verificationTimeEmail = date("Y-m-d H:i:s", time());
        $verificationTimeSms = date("Y-m-d H:i:s", time());

        // Veritabanında güncelle
        $updateQuery = "UPDATE users SET verification_code_email = ?, verification_code_sms = ?, verification_time_email_sent = ?, verification_time_sms_sent = ? WHERE id = ?";
        $stmtUpdate = $db->prepare($updateQuery);
        $stmtUpdate->execute([$verificationCodeEmail, $verificationCodeSms, $verificationTimeEmail, $verificationTimeSms, $user["id"]]);

        // E-posta ve SMS gönderme işlemleri
        sendVerificationEmail($email, $verificationCodeEmail, $user["first_name"], $user["last_name"]);
        sendVerificationSms($phone, $verificationCodeSms, $user["first_name"], $user["last_name"]);

        $message = "Doğrulama kodları yeniden gönderildi.";
    } else {
        $message = "Kullanıcı bulunamadı. Lütfen doğru e-posta ve telefon numarasını girin.";
    }
}

// Rastgele doğrulama kodu oluşturma fonksiyonu
function generateVerificationCode() {
    return mt_rand(100000, 999999); // Örnek: 6 haneli rastgele kod
}

// E-posta gönderme fonksiyonu
function sendVerificationEmail($to, $verificationCode, $first_name, $last_name) {
    global $config, $siteName, $agreementLink;

    $mail = new PHPMailer(true);

    try {
        // SMTP ayarları
        $mail->isSMTP();
        $mail->Host = $config['smtp']['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['smtp']['username'];
        $mail->Password = $config['smtp']['password'];
        $mail->SMTPSecure = $config['smtp']['encryption'];
        $mail->Port = $config['smtp']['port'];
        $mail->CharSet = $config['smtp']['mailCharset'];
        $mail->ContentType = $config['smtp']['mailContentType'];

        // E-posta ayarları
        $mail->setFrom($config['smtp']['username'], 'OİM');
        $mail->addAddress($to);

        $mail->Subject = 'Kayıt Doğrulama';

        // Parametreleri şifrele
        $encryptedEmail = $to;
        $encryptedCode = $verificationCode;

        // Gizli bağlantı oluştur
        $verificationLink = getVerificationLink($encryptedEmail, $encryptedCode);
        $mail->isHTML(true);
        $mail->Body = "
    <html>
    <body>
       <p>👋 Selam $first_name,</p>
        <p>$siteName 'e hoş geldin 🤗 Kaydının tamamlanabilmesi için sözleşmeleri okuyup onaylaman gerekiyor:</p>
        <p>Sözleşmeleri okumak için 🤓 <a href='$agreementLink'>buraya tıklayabilirsin</a>.</p>
        <p>Sözleşmeleri onaylamak için ✅ <a href='$verificationLink'>buraya tıklayabilirsin</a>.</p>
        <p>Müzik dolu günler dileriz 🎸🎹</p>
    </body>
    </html>
";

        // E-postayı gönder
        $mail->send();
    } catch (Exception $e) {
        // E-posta gönderimi hatası
        echo "E-posta gönderimi başarısız oldu. Hata: {$mail->ErrorInfo}";
    }
}

// SMS gönderme fonksiyonu
function sendVerificationSms($to, $verificationCode, $first_name, $last_name) {
    global $config, $BASE_URL, $API_KEY, $SENDER, $MESSAGE_TEXT, $siteName, $agreementLink;

    $smsConfiguration = new Configuration(host: $BASE_URL, apiKey: $API_KEY);

    $sendSmsApi = new SmsApi(config: $smsConfiguration);

    $destination = new SmsDestination(
        to: $to
    );

    // Parametreleri şifrele
    $encryptedPhone = $to;
    $encryptedCode = $verificationCode;

    // Gizli bağlantı oluştur
    $verificationLink = getVerificationLink($encryptedPhone, $encryptedCode, "phone");

    $message = new SmsTextualMessage(destinations: [$destination], from: $SENDER, text: "Selam $first_name, $siteName 'e hoş geldin 🤗 Kaydının tamamlanabilmesi için sözleşmeleri okuyup onaylaman gerekiyor: $agreementLink - Sözleşmeleri onaylamak için ise şu bağlantıya tıklayabilirsin (Bağlantı açıldığında sözleşmeler otomatik onaylanacaktır): $verificationLink.");

    $request = new SmsAdvancedTextualRequest(messages: [$message]);

    try {
        $smsResponse = $sendSmsApi->sendSmsMessage($request);

        // Mesajları gönderim sonuçları ile ilgili bilgileri saklayacak değişkenler
        $smsStatusMessages = [];
        $smsBulkId = $smsResponse->getBulkId();

        foreach ($smsResponse->getMessages() ?? [] as $message) {
            $smsStatusMessages[] = sprintf('SMS Gönderim No: %s, Durum: %s', $message->getMessageId(), $message->getStatus()?->getName());
        }

        // Başarılı mesajları gösteren bir mesaj oluşturuyoruz
        $smsSuccessMessage = "SMS gönderimi başarılı, Gönderim No: $smsBulkId";

        // Hata mesajını temsil edecek değişkeni boş olarak başlatıyoruz
        $smsErrorMessage = "";

    } catch (Throwable $apiException) {
        // Hata durumunda hata mesajını saklayan değişkeni ayarlıyoruz
        $smsErrorMessage = "SMS gönderimi sırasında bir hata oluştu: " . $apiException->getMessage();

        // Başarılı ve hata mesajlarını boş olarak başlatıyoruz
        $smsSuccessMessage = "";
        $smsStatusMessages = [];
    }
}
?>
<?php
require_once "admin_panel_header.php";
?>
<div class="container-fluid">
    <div class="row">
        <?php
        require_once "admin_panel_sidebar.php";
        ?>
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                <h2>Doğrulamayı Tekrar Gönder</h2>
            </div>
            <!-- Yeniden doğrulama isteği formu -->
    <form method="post" action="">
        <label class="form-label" for="email">E-posta:</label>
        <input class="form-control" type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required><br>

        <label class="form-label" for="phone">Telefon:</label>
        <input class="form-control" type="text" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required><br>

        <button type="submit" class="btn btn-primary">Doğrulama Kodunu Yeniden Gönder</button>
    </form>

    <!-- Mesajı burada gösteriyoruz -->
    <?php if (isset($message) && $message !== ""): ?>
        <div class="alert alert-danger" role="alert">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <!-- SMS gönderim başarılı mesajı -->
    <?php if (isset($smsSuccessMessage) && $smsSuccessMessage !== ""): ?>
        <div class="alert alert-success" role="alert">
            <?= $smsSuccessMessage ?>
        </div>
    <?php endif; ?>

    <!-- SMS gönderim hata mesajı -->
    <?php if (isset($smsErrorMessage) && $smsErrorMessage !== ""): ?>
        <div class="alert alert-danger" role="alert">
            <?= $smsErrorMessage ?>
        </div>
    <?php endif; ?>

</div>

<?php
require_once "footer.php";
?>
