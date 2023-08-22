<?php
global $db;
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Oturum kontrolü
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php"); // Giriş sayfasına yönlendir
    exit();
}

require_once "db_connection.php";

//Import PHPMailer classes into the global namespace
//These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

//Load Composer's autoloader
require 'vendor/autoload.php';

require 'config.php';

use Infobip\Api\SmsApi;
use Infobip\Configuration;
use Infobip\Model\SmsAdvancedTextualRequest;
use Infobip\Model\SmsDestination;
use Infobip\Model\SmsTextualMessage;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tc = $_POST["tc"];
    $firstname = $_POST["firstname"];
    $lastname = $_POST["lastname"];
    $email = $_POST["email"];
    $phone = $_POST["phone"];
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);

    // Kullanıcının daha önce kayıtlı olup olmadığını kontrol et
    $queryCheck = "SELECT * FROM users WHERE email = ? OR tc = ? OR phone = ?";
    $stmtCheck = $db->prepare($queryCheck);
    $stmtCheck->execute([$email, $tc, $phone]);
    $existingUser = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($existingUser) {
        echo "Bu e-posta, T.C. kimlik numarası veya telefon numarası zaten kayıtlı!";
    } else {
        // Yeni kayıt işlemi
        $verificationCodeEmail = generateVerificationCode();
        $verificationCodeSms = generateVerificationCode();
        $verificationTimeEmail = date("Y-m-d H:i:s", time()); // E-posta doğrulama zamanı
        $verificationTimeSms = date("Y-m-d H:i:s", time()); // SMS doğrulama zamanı

        $insertQuery = "INSERT INTO users (tc, firstname, lastname, email, phone, password, verification_code_email, verification_code_sms, verification_time_email_sent, verification_time_sms_sent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        try {
            $stmt = $db->prepare($insertQuery);
            $stmt->execute([$tc, $firstname, $lastname, $email, $phone, $password, $verificationCodeEmail, $verificationCodeSms, $verificationTimeEmail, $verificationTimeSms]);

            // E-posta ve SMS gönderme işlemleri
            sendVerificationEmail($email, $verificationCodeEmail, $firstname, $lastname); // Doğru şekilde çağrıldı
            sendVerificationSms($phone, $verificationCodeSms, $firstname, $lastname); // Doğru şekilde çağrıldı


            echo "Kullanıcı kaydedildi, doğrulama e-postası ve SMS gönderildi.";
        } catch (PDOException $e) {
            echo "Hata: " . $e->getMessage();
        }
    }
}

// Rastgele doğrulama kodu oluşturma fonksiyonu
function generateVerificationCode() {
    return mt_rand(100000, 999999); // Örnek: 6 haneli rastgele kod
}

// E-posta gönderme fonksiyonu
function sendVerificationEmail($to, $verificationCode, $firstname, $lastname) {
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

        // E-posta ayarları
        $mail->setFrom($config['smtp']['username'], 'OİM');
        $mail->addAddress($to);

        $mail->Subject = 'Hesap Doğrulama';

        // Parametreleri şifrele
        $encryptedEmail = urlencode(base64_encode($to));
        $encryptedCode = urlencode(base64_encode($verificationCode));

        // Gizli bağlantı oluştur
        $verificationLink = getVerificationLink($encryptedEmail, $encryptedCode);

        $mail->Body = "Sayın $firstname $lastname, $siteName kaydınızı doğrulamanız ve sözleşmeleri okuyup onaylamanız gerekmektedir. Sözleşmeleri okumak için tıklayın: $agreementLink Sözleşmeleri onaylamak için (Onay bağlantı açıldığında otomatik onaylanmış olacaktır): $verificationLink";

        // E-postayı gönder
        $mail->send();
    } catch (Exception $e) {
        // E-posta gönderimi hatası
        echo "E-posta gönderimi başarısız oldu. Hata: {$mail->ErrorInfo}";
    }
}


// SMS gönderme fonksiyonu
function sendVerificationSms($to, $verificationCode, $firstname, $lastname) {
    global $config, $BASE_URL, $API_KEY, $SENDER, $MESSAGE_TEXT, $siteName, $agreementLink;

    $smsConfiguration = new Configuration(host: $BASE_URL, apiKey: $API_KEY);

    $sendSmsApi = new SmsApi(config: $smsConfiguration);

    $destination = new SmsDestination(
        to: $to
    );

    // Parametreleri şifrele
    $encryptedPhone = urlencode(base64_encode($to));
    $encryptedCode = urlencode(base64_encode($verificationCode));

    // Gizli bağlantı oluştur
    $verificationLink = getVerificationLink($encryptedPhone, $encryptedCode);

    $message = new SmsTextualMessage(destinations: [$destination], from: $SENDER, text: "Sayın $firstname $lastname, $siteName kaydınızı doğrulamanız ve sözleşmeleri okuyup onaylamanız gerekmektedir. Sözleşmeleri okumak için: $agreementLink Sözleşmeleri onaylamak için (Onay bağlantı açıldığında otomatik onaylanmış olacaktır): $verificationLink");

    $request = new SmsAdvancedTextualRequest(messages: [$message]);

    try {
        $smsResponse = $sendSmsApi->sendSmsMessage($request);

        echo $smsResponse->getBulkId() . PHP_EOL;

        foreach ($smsResponse->getMessages() ?? [] as $message) {
            echo sprintf('SMS Gönderim No: %s, Durum: %s', $message->getMessageId(), $message->getStatus()?->getName()) . PHP_EOL;
        }
    } catch (Throwable $apiException) {
        echo("SMS gönderimi sırasında bir hata oluştu: " . $apiException->getMessage() . "\n");
    }
}


// Doğrulama bağlantısı oluşturma
function getVerificationLink($email, $code) {
    global $siteUrl;
    return "$siteUrl/verify.php?email=$email&code=$code";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Kayıt Formu</title>
</head>
<body>
<h2>Kayıt Formu</h2>
<form method="post" action="">
    <label for="tc">TC Kimlik No:</label>
    <input type="text" name="tc" required><br>
    <label for="firstname">Ad:</label>
    <input type="text" name="firstname" required><br>
    <label for="lastname">Soyad:</label>
    <input type="text" name="lastname" required><br>
    <label for="email">E-posta:</label>
    <input type="email" name="email" required><br>
    <label for="phone">Telefon:</label>
    <input type="text" name="phone" required><br>
    <label for="password">Şifre:</label>
    <input type="password" name="password" required><br>
    <p><input type="submit" value="Kaydet"></p>
    <p><button onclick="history.back()">Geri Dön</button></p>
</form>
</body>
</html>
