<?php
global $db, $siteUrl, $siteName, $siteShortName;
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
        $message = "Bu e-posta, T.C. kimlik numarası veya telefon numarası zaten kayıtlı!";
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
    sendVerificationEmail($email, $verificationCodeEmail, $firstname, $lastname);
    sendVerificationSms($phone, $verificationCodeSms, $firstname, $lastname);

    // Kullanıcı kaydedildiğini bildiren mesajı $message değişkenine atıyoruz
    $message = "Kullanıcı kaydedildi, doğrulama e-postası ve SMS gönderildi.";
} catch (PDOException $e) {
    // Hata durumunda hata mesajını $message değişkenine atıyoruz
    $message = "Hata: " . $e->getMessage();
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
        $mail->CharSet = $config['smtp']['mailCharset'];
        $mail->ContentType = $config['smtp']['mailContentType'];

        // E-posta ayarları
        $mail->setFrom($config['smtp']['username'], 'OİM');
        $mail->addAddress($to);

        $mail->Subject = 'Hesap Doğrulama';

        // Parametreleri şifrele
        $encryptedEmail = $to;
        $encryptedCode = $verificationCode;

        // Gizli bağlantı oluştur
        $verificationLink = getVerificationLink($encryptedEmail, $encryptedCode);

        $mail->Body = "Sayın $firstname $lastname, $siteName kaydınızı doğrulamanız ve sözleşmeleri okuyup onaylamanız gerekmektedir. Sözleşmeleri görüntüleyin: $agreementLink Sözleşmeleri onaylayın (Bağlantı açıldığında sözleşmeler otomatik onaylanacaktır): $verificationLink";

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
    $encryptedPhone = $to;
    $encryptedCode = $verificationCode;
	

    // Gizli bağlantı oluştur
    $verificationLink = getVerificationLink($encryptedPhone, $encryptedCode,"phone");

    $message = new SmsTextualMessage(destinations: [$destination], from: $SENDER, text: "Sayın $firstname $lastname, $siteName kaydınızı doğrulamanız ve sözleşmeleri okuyup onaylamanız gerekmektedir. Sözleşmeleri görüntüleyin: $agreementLink Sözleşmeleri onaylayın (Bağlantı açıldığında sözleşmeler otomatik onaylanacaktır): $verificationLink");

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

// Doğrulama bağlantısı oluşturma
function getVerificationLink($emailOrPhone, $code, $type="email") {
    global $siteUrl;
	if($type == "phone"){
	 return "$siteUrl/verify.php?phone=$emailOrPhone&code=$code";
	}else{
		 return "$siteUrl/verify.php?email=$emailOrPhone&code=$code";
	}
   
}

require_once "admin_panel_header.php";

?>

    <div class="container-fluid">
      <div class="row">

          <?php
          require_once "admin_panel_sidebar.php";
          ?>

        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
          <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
              <h2>Kullanıcı Kaydı</h2>
          </div>

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

  <!-- SMS gönderim sonuçları -->
    <?php if (isset($smsStatusMessage) && $smsStatusMessage !== ""): ?>
    <div class="alert alert-info" role="alert">
      <?= $smsStatusMessage ?>
    </div>
    <?php endif; ?>

  <form method="post" action="">
    <label class="form-label" for="tc">TC Kimlik No:</label>
    <input class="form-control" type="text" name="tc" required><br>
    <label class="form-label" for="firstname">Ad:</label>
    <input class="form-control"type="text" name="firstname" required><br>
    <label class="form-label" for="lastname">Soyad:</label>
    <input class="form-control"type="text" name="lastname" required><br>
    <label for="email" class="form-label">E-posta:</label>
    <input class="form-control"type="email" name="email" class="form-control" aria-describedby="emailHelp" required>
      <div id="emailHelp" class="form-text">Geçerli bir e-posta adresi olmalıdır.</div>
      <br>
    <label class="form-label" for="phone">Telefon:</label>
    <input class="form-control" type="text" name="phone" required><br>
    <label  class="form-label"for="password">Şifre:</label>
    <input class="form-control" type="password" name="password" required><br>
      <button type="submit" class="btn btn-primary">Kaydet</button>
        <button onclick="history.back()" class="btn btn-primary">Geri dön</button>
    </form>
</main>
</div>
</div>

<?php
require_once "footer.php";
?>
