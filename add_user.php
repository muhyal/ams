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

session_start();
session_regenerate_id(true);

// Oturum kontrolü
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php"); // Giriş sayfasına yönlendir
    exit();
}

require_once "db_connection.php";
require 'vendor/autoload.php';


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Infobip\Api\SmsApi;
use Infobip\Configuration;
use Infobip\Model\SmsAdvancedTextualRequest;
use Infobip\Model\SmsDestination;
use Infobip\Model\SmsTextualMessage;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberFormat;
use League\ISO3166\ISO3166;

// Ülkeleri al
$phoneNumberUtil = PhoneNumberUtil::getInstance();
$iso3166 = new ISO3166();

// Rastgele doğrulama kodu oluşturma fonksiyonu
function generateVerificationCode() {
    return mt_rand(100000, 999999); // Örnek: 6 haneli rastgele kod
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = isset($_POST["username"]) ? $_POST["username"] : "";
    $tc_identity = isset($_POST["tc_identity"]) ? $_POST["tc_identity"] : "";
    $first_name = isset($_POST["first_name"]) ? $_POST["first_name"] : "";
    $last_name = isset($_POST["last_name"]) ? $_POST["last_name"] : "";
    $email = isset($_POST["email"]) ? $_POST["email"] : "";
    $phone = isset($_POST["phone"]) ? $_POST["phone"] : "";
    $birth_date = isset($_POST["birth_date"]) ? $_POST["birth_date"] : "";
    $city = isset($_POST["city"]) ? $_POST["city"] : "";
    $district = isset($_POST["district"]) ? $_POST["district"] : "";
    $blood_type = isset($_POST["blood_type"]) ? $_POST["blood_type"] : "";
    $health_issue = isset($_POST["health_issue"]) ? $_POST["health_issue"] : "";
    $emergency_contact = isset($_POST["emergency_contact"]) ? $_POST["emergency_contact"] : "";
    $emergency_phone = isset($_POST["emergency_phone"]) ? $_POST["emergency_phone"] : "";
    $countryCode = isset($_POST["country"]) ? $_POST["country"] : "";
    $phoneNumber = isset($_POST["phone"]) ? $_POST["phone"] : "";
    $country = $_POST["country"];
    // Ülke kodunu ve telefon numarasını birleştir
    $fullPhoneNumber = "+" . $phoneNumberUtil->getCountryCodeForRegion($countryCode) . $phoneNumber;
    // $phone değişkenini güncelle
    $phone = $fullPhoneNumber;
    // Hash'lenmemiş şifreyi al
    $plainPassword = $_POST["password"];

    // Şifreyi hash'leyerek bir değişkene atayalım
    $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

    // Kullanıcı tipi bilgisini al
    $userType = $_POST["user_type"];

    // Kullanıcının daha önce kayıtlı olup olmadığını kontrol et
    $queryCheck = "SELECT * FROM users WHERE email = ? OR tc_identity = ? OR phone = ? OR username = ?";
    $stmtCheck = $db->prepare($queryCheck);
    $stmtCheck->execute([$email, $tc_identity, $phone, $username]);
    $existingUser = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($existingUser) {
        $message = "Bu e-posta, T.C. kimlik numarası veya telefon numarası zaten kayıtlı!";
    } else {
        // Yeni kayıt işlemi
        $verificationCodeEmail = generateVerificationCode();
        $verificationCodeSms = generateVerificationCode();
        $verificationTimeEmail = date("Y-m-d H:i:s", time());
        $verificationTimeSms = date("Y-m-d H:i:s", time());

        $insertQuery = "INSERT INTO users (
     username, 
        tc_identity, 
        first_name, 
        last_name, 
        email, 
        phone, 
        password, 
        verification_code_email, 
        verification_code_sms, 
        verification_time_email_sent, 
        verification_time_sms_sent, 
        user_type, 
        birth_date,
        city,
        district,
        blood_type,
        health_issue,
        emergency_contact,
        emergency_phone,
        country,
        is_active,
        created_at,
        created_by_user_id,
        updated_at,
        updated_by_user_id 
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        try {
            $stmt = $db->prepare($insertQuery);
            $stmt->execute([
                $username,
                $tc_identity,
                $first_name,
                $last_name,
                $email,
                $phone,
                $hashedPassword,
                $verificationCodeEmail,
                $verificationCodeSms,
                $verificationTimeEmail,
                $verificationTimeSms,
                $userType,
                $birth_date,
                $city,
                $district,
                $blood_type,
                $health_issue,
                $emergency_contact,
                $emergency_phone,
                $country,
                1,  // Set the default value for 'is_active' to 1 (active)
                date("Y-m-d H:i:s"),
                $_SESSION["admin_id"],  // Varsayılan olarak admin kullanıcısının ID'sini ekledim, sizin kullanıcı kimliğinize göre düzenlemeniz gerekebilir
                date("Y-m-d H:i:s"),
                $_SESSION["admin_id"]   // Varsayılan olarak admin kullanıcısının ID'sini ekledim, sizin kullanıcı kimliğinize göre düzenlemeniz gerekebilir
            ]);

            // E-posta ve SMS gönderme işlemleri
            sendVerificationEmail($email, $verificationCodeEmail, $first_name, $last_name, $plainPassword, $username, $email);
            sendVerificationSms($phone, $verificationCodeSms, $first_name, $last_name, $plainPassword, $username, $email);

            // Kullanıcı kaydedildiğini bildiren mesajı $message değişkenine atıyoruz
            $message = "Kullanıcı kaydedildi, doğrulama e-postası ve SMS gönderildi.";
        } catch (PDOException $e) {
            // Hata durumunda hata mesajını $message değişkenine atıyoruz
            $message = "Hata: " . $e->getMessage();
        }
    }

}

// E-posta gönderme fonksiyonu
function sendVerificationEmail($to, $verificationCode, $first_name, $last_name, $plainPassword, $username, $email) {
    global $config, $siteName, $agreementLink, $siteUrl;

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

        $mail->isHTML(true);
        $mail->Subject = '=?UTF-8?B?' . base64_encode($siteName . ' - Hoş Geldiniz 👋') . '?='; // Encode subject in UTF-8

        // Parametreleri şifrele
        $encryptedEmail = $to;
        $encryptedCode = $verificationCode;

        // Gizli bağlantı oluştur
        $verificationLink = getVerificationLink($encryptedEmail, $encryptedCode);

        $mail->Body = "
    <html>
    <body>
        <p>👋 Selam $first_name,</p>
        <p>$siteName 'e hoş geldin 🤗 Kaydının tamamlanabilmesi için sözleşmeleri okuyup onaylaman gerekiyor:</p>
        <p>Sözleşmeleri okumak için 🤓 <a href='$agreementLink'>buraya tıklayabilirsin</a>.</p>
        <p>Sözleşmeleri onaylamak için ✅ <a href='$verificationLink'>buraya tıklayabilirsin</a>.</p>
        <p>🧐 $siteName paneline $siteUrl adresinden $username kullanıcı adın ya da $email e-postan ve şifren $plainPassword ile oturum açabilirsin.</p>
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
function sendVerificationSms($to, $verificationCode, $first_name, $last_name, $plainPassword, $username, $email) {
    global $siteName, $agreementLink, $siteUrl, $config, $first_name, $last_name, $plainPassword, $username, $email;

    // Check if Infobip configuration is enabled and valid
    if (
        $config['infobip']['enabled']
        && !empty($config['infobip']['BASE_URL'])
        && !empty($config['infobip']['API_KEY'])
        && !empty($config['infobip']['SENDER'])
    ) {
        $BASE_URL = $config['infobip']['BASE_URL'];
        $API_KEY = $config['infobip']['API_KEY'];
        $SENDER = $config['infobip']['SENDER'];

        // Infobip Configuration sınıfını oluştur
        $infobipConfig = new \Infobip\Configuration($BASE_URL, $API_KEY, $SENDER);

        // Infobip SmsApi sınıfını başlat
        $sendSmsApi = new \Infobip\Api\SmsApi(config: $infobipConfig);

        $destination = new SmsDestination(
            to: $to
        );

        // Parametreleri şifrele
        $encryptedPhone = $to;
        $encryptedCode = $verificationCode;

        // Gizli bağlantı oluştur
        $verificationLink = getVerificationLink($encryptedPhone, $encryptedCode, "phone");

        $message = new SmsTextualMessage(destinations: [$destination], from: $SENDER, text: "Selam $first_name, $siteName 'e hoş geldin 🤗 Kaydının tamamlanabilmesi için sözleşmeleri okuyup onaylaman gerekiyor: $agreementLink - Sözleşmeleri onaylamak için ise şu bağlantıya tıklayabilirsin (Bağlantı açıldığında sözleşmeler otomatik onaylanacaktır): $verificationLink.  $siteUrl üzerinden $email e-posta adresin ya da $username ve şifren $plainPassword ile $siteName panelinde oturum açabilirsin.");

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
    } else {
        // Log or handle the case where Infobip configuration is not valid
        $smsErrorMessage = "Infobip configuration is not valid.";
        // You may want to log this information or handle it appropriately.
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

            <form method="post" action="" class="needs-validation" onsubmit="return validateForm()" name="addUserForm">
                <div class="row">
                    <div class="col-md-6">
                        <!-- Sol sütun form alanları -->
                        <div class="mb-3">
                            <label class="form-label" for="user_type">Kullanıcı tipi:</label>
                            <select class="form-select" name="user_type" required>
                                <?php
                                // Kullanıcı oturumunu kontrol et
                                session_start();

                                // Eğer kullanıcı oturum açmışsa ve user_type değeri varsa, onu kullan
                                $currentUserType = isset($_SESSION['admin_type']) ? $_SESSION['admin_type'] : null;

                                // Kullanıcı rollerine bağlı olarak mevcut seçenekleri tanımla
                                $options = [
                                    1 => ["Yönetici"],
                                    2 => ["Koordinatör"],
                                    3 => ["Eğitim Danışmanı"],
                                    4 => ["Öğretmen"],
                                    5 => ["Veli"],
                                    6 => ["Öğrenci"],
                                ];

                                // Kullanıcı tipine bağlı olarak seçenekleri göster
                                foreach ($options as $type => $labels) {
                                    if ($currentUserType == 1) {
                                        // Yönetici, tüm seçenekleri görebilir
                                        echo "<option value=\"$type\">" . $labels[0] . "</option>";
                                    } elseif ($currentUserType == 2) {
                                        // Koordinatör, sadece belirli seçenekleri görebilir
                                        if ($type >= 3 && $type <= 6) {
                                            echo "<option value=\"$type\">" . $labels[0] . "</option>";
                                        }
                                    } elseif ($currentUserType == 3) {
                                        // Eğitim Danışmanı sadece Öğrenci ve Veli'yi görebilir
                                        if ($type == 6 || $type == 5) {
                                            echo "<option value=\"$type\">" . $labels[0] . "</option>";
                                        }
                                    }
                                }
                                ?>
                            </select>
                            <div class="invalid-feedback">Kullanıcı tipini seçin.</div>
                        </div>


                        <?php
                        // Rastgele 3 karakter oluşturan fonksiyon
                        function generateRandomChars() {
                            $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
                            $length = 3;
                            return substr(str_shuffle($characters), 0, $length);
                        }

                        // Veritabanı bağlantısı
                        require_once "db_connection.php";

                        // Benzersiz bir kullanıcı adı oluşturana kadar dönen fonksiyon
                        function getUniqueRandomUsername($db) {
                            $isUnique = false;
                            $maxAttempts = 10; // Maksimum deneme sayısı
                            $attempts = 0;

                            while (!$isUnique && $attempts < $maxAttempts) {
                                $generatedChars = generateRandomChars();
                                $currentDate = date('dmy'); // Bugünün gün, ay ve yıl bilgisi (2 haneli yıl)

                                $generatedUsername = "d" . $generatedChars . $currentDate;

                                $checkQuery = "SELECT COUNT(*) as count FROM users WHERE username = ?";
                                $stmt = $db->prepare($checkQuery);
                                $stmt->execute([$generatedUsername]);
                                $result = $stmt->fetch(PDO::FETCH_ASSOC);

                                if ($result['count'] == 0) {
                                    $isUnique = true;
                                }

                                $attempts++;
                            }

                            return $isUnique ? $generatedUsername : null;
                        }

                        // Oluşturulan benzersiz kullanıcı adını alın
                        $generatedUsername = getUniqueRandomUsername($db);
                        ?>

                        <div class="mb-3">
                            <label class="form-label" for="username">Kullanıcı adı:</label>
                            <input class="form-control" type="text" name="username" value="<?php echo strtolower($generatedUsername); ?>" required>
                            <div class="invalid-feedback">Bu alan gereklidir.</div>
                        </div>


                        <div class="mb-3">
                            <label class="form-label" for="tc_identity">TC Kimlik No:</label>
                            <input class="form-control" type="text" name="tc_identity" id="tc_identity" required>
                            <div class="invalid-feedback">Bu alan gereklidir ve maksimum 11 haneli sayı olmalıdır.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="first_name">Ad:</label>
                            <input class="form-control" type="text" name="first_name" required>
                            <div class="invalid-feedback">Bu alan gereklidir.</div>
                        </div>

                        <div class="mb-3">
                            <label for="last_name" class="form-label">Soyad:</label>
                            <input type="text" name="last_name" class="form-control" required>
                            <div class="invalid-feedback">Bu alan gereklidir.</div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">E-posta:</label>
                            <input type="email" name="email" class="form-control" aria-describedby="emailHelp" required>
                            <div id="emailHelp" class="form-text">Geçerli bir e-posta adresi olmalıdır.</div>
                            <div class="invalid-feedback">Bu alan gereklidir.</div>
                        </div>

                        <div class="mb-3">
                            <label for="country" class="form-label">Ülke:</label>
                            <div class="input-group">
                                <select class="form-select" name="country" id="country" required>
                                    <?php
                                    foreach ($iso3166->all() as $country) {
                                        $selected = ($country['alpha2'] == 'TR') ? 'selected' : '';
                                        $countryCode = $phoneNumberUtil->getCountryCodeForRegion($country['alpha2']);
                                        $countryName = ($country['alpha2'] == 'TR') ? 'Türkiye' : $country['name'];
                                        echo "<option value=\"" . $country['alpha2'] . "\" data-country-code=\"+$countryCode\" $selected>{$countryName}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Telefon:</label>
                            <div class="input-group">
                                <span class="input-group-text" id="phone-addon">+90</span>
                                <input type="text" name="phone" class="form-control" aria-describedby="phone-addon" required>
                                <div class="invalid-feedback">Geçerli bir telefon numarası girin.</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="birth_date" class="form-label">Doğum Tarihi:</label>
                            <input type="date" name="birth_date" class="form-control" required>
                            <div class="invalid-feedback">Bu alan gereklidir.</div>
                        </div>

                        <script>
                            // JavaScript ile ülke seçimi değiştiğinde telefon kodunu güncelle
                            var countrySelect = document.getElementById("country");
                            var phoneAddon = document.getElementById("phone-addon");

                            countrySelect.addEventListener("change", function () {
                                var selectedOption = this.options[this.selectedIndex];
                                var countryCode = (selectedOption && selectedOption.getAttribute("data-country-code")) || "+90";

                                phoneAddon.innerText = countryCode;
                            });

                            // Sayfa yüklendiğinde de ilk değeri al
                            var defaultCountryOption = countrySelect.options[countrySelect.selectedIndex];
                            var defaultCountryCode = (defaultCountryOption && defaultCountryOption.getAttribute("data-country-code")) || "+90";
                            phoneAddon.innerText = defaultCountryCode;
                        </script>


                    </div>

                    <div class="col-md-6">
                        <!-- Sağ sütun form alanları -->

                        <div class="mb-3">
                            <label for="city" class="form-label">Şehir:</label>
                            <input type="text" name="city" class="form-control" required>
                            <div class="invalid-feedback">Bu alan gereklidir.</div>
                        </div>

                        <div class="mb-3">
                            <label for="district" class="form-label">İlçe:</label>
                            <input type="text" name="district" class="form-control" required>
                            <div class="invalid-feedback">Bu alan gereklidir.</div>
                        </div>

                        <div class="mb-3">
                            <label for="blood_type" class="form-label">Kan Grubu:</label>
                            <input type="text" name="blood_type" class="form-control" required>
                            <div class="invalid-feedback">Bu alan gereklidir.</div>
                        </div>

                        <div class="mb-3">
                            <label for="health_issue" class="form-label">Sağlık Sorunu:</label>
                            <input type="text" name="health_issue" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label for="emergency_contact" class="form-label">Acil Durum Kişisi:</label>
                            <input type="text" name="emergency_contact" class="form-control" required>
                            <div class="invalid-feedback">Bu alan gereklidir.</div>
                        </div>

                        <div class="mb-3">
                            <label for="emergency_phone" class="form-label">Acil Durum Kişisi Telefon:</label>
                            <input type="tel" name="emergency_phone" class="form-control" required>
                            <div class="invalid-feedback">Geçerli bir telefon numarası girin.</div>
                        </div>
                    </div>
                </div>


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
                            <button type="button" class="btn btn-outline-secondary" onclick="generateAndSetPassword('password')">Şifre Üret</button>
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

                    function generateAndSetPassword(passwordId) {
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

                    // Sayfa yüklendiğinde otomatik olarak şifre üretme fonksiyonunu çağırabilirsiniz
                    window.onload = function () {
                        generateAndSetPassword('password');
                    };
                </script>

                <div class="form-group mt-3">
      <button type="submit" class="btn btn-primary">Kaydet</button>
      </div>
  </form>
</main>
</div>
</div>

<?php
require_once "footer.php";
?>
