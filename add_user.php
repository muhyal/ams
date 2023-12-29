<?php
global $db, $showErrors, $siteName, $siteShortName, $siteUrl;
// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);
require_once "config.php";

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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tc = $_POST["tc"];
    $firstname = $_POST["firstname"];
    $lastname = $_POST["lastname"];
    $email = $_POST["email"];
    $phone = $_POST["phone"];
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);

    // Eklenen satır - Kullanıcı tipi bilgisini al
    $userType = $_POST["user_type"];

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
        $verificationTimeEmail = date("Y-m-d H:i:s", time());
        $verificationTimeSms = date("Y-m-d H:i:s", time());

        $insertQuery = "INSERT INTO users (tc, firstname, lastname, email, phone, password, verification_code_email, verification_code_sms, verification_time_email_sent, verification_time_sms_sent, user_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        try {
            $stmt = $db->prepare($insertQuery);
            $stmt->execute([$tc, $firstname, $lastname, $email, $phone, $password, $verificationCodeEmail, $verificationCodeSms, $verificationTimeEmail, $verificationTimeSms, $userType]);

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
        $mail->setFrom($config['smtp']['username'], $siteName);
        $mail->addAddress($to);

        $mail->Subject = '=?UTF-8?B?' . base64_encode('Sözleşme Onayı') . '?='; // Encode subject in UTF-8

        // Parametreleri şifrele
        $encryptedEmail = $to;
        $encryptedCode = $verificationCode;

        // Gizli bağlantı oluştur
        $verificationLink = getVerificationLink($encryptedEmail, $encryptedCode);

        $mail->Body = "Sayın $firstname $lastname, $siteName kaydınızı doğrulamanız ve sözleşmeleri okuyup onaylamanız gerekmektedir. Sözleşmeleri okuyun: $agreementLink - Sözleşmeleri onaylayın (Bağlantı açıldığında sözleşmeler otomatik onaylanacaktır): $verificationLink";

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

    $message = new SmsTextualMessage(destinations: [$destination], from: $SENDER, text: "Sayın $firstname $lastname, $siteName kaydınızı doğrulamanız ve sözleşmeleri okuyup onaylamanız gerekmektedir. Sözleşmeleri okumak için: $agreementLink - Sözleşmeleri onaylamak için (Bağlantı açıldığında sözleşmeler otomatik onaylanacaktır): $verificationLink");

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
              <h2>Kullanıcı Kaydı</h2>
          </div>

  <!-- Mesajı burada gösteriyoruz -->
<?php if (isset($message) && $message !== ""): ?>
    <div class="alert alert-primary" role="alert">
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
      <label class="form-label" for="tc">Kullanıcı tipi:</label>
      <select class="form-control" name="user_type" required>
          <option value="6">Öğrenci</option>
          <option value="5">Öğretmen</option>
          <option value="4">Veli</option>
          <option value="3">Koordinatör</option>
          <option value="2">Eğitim Danışmanı</option>
          <option value="1">Yönetici</option>
      </select><br>

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
      <input class="form-control" type="text" name="phone" value="90" required><br>

      <div class="form-group">
          <label class="form-label" for="password">Şifre:</label>
          <div class="input-group">
              <input class="form-control" type="password" name="password" id="password" required>
              <div class="input-group-append">
                  <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('password')">Şifreyi Göster</button>
              </div>
              <div class="input-group-append">
                  <button type="button" class="btn btn-outline-secondary" onclick="copyPassword('password')">Kopyala</button>
              </div>
              <div class="input-group-append">
                  <button type="button" class="btn btn-outline-secondary" onclick="generatePassword('password')">Şifre Üret</button>
              </div>
          </div>
      </div>

      <script>
          function togglePassword(passwordId) {
              var passwordInput = document.getElementById(passwordId);
              if (passwordInput.type === "password") {
                  passwordInput.type = "text";
              } else {
                  passwordInput.type = "password";
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


      <button type="submit" class="btn btn-primary">Kaydet</button>
      <button onclick="history.back()" class="btn btn-primary">Geri dön</button>
      <button onclick="window.location.href='user_list.php'" class="btn btn-secondary">Kullanıcı listesi</button>
  </form>
</main>
</div>
</div>
<?php
require_once "footer.php";
?>
