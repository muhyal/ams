<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Infobip\Api\SmsApi;
use Infobip\Configuration;
use Infobip\Model\SmsAdvancedTextualRequest;
use Infobip\Model\SmsDestination;
use Infobip\Model\SmsTextualMessage;


// Dil seçimini tarayıcı dilinden al, eğer belirtilmemişse varsayılan olarak "tr" kullan
$selectedLanguage = isset($_GET['lang']) ? $_GET['lang'] : (isset($_SESSION['selectedLanguage']) ? $_SESSION['selectedLanguage'] : substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2));

// Dil seçimini sakla, böylece diğer sayfalarda kullanabilirsiniz
$_SESSION['selectedLanguage'] = $selectedLanguage;

// Çeviri fonksiyonunu tanımla
function translate($key, $language) {
    // Dil seçimine göre çeviri dosyasını yükle
    $translationsPath = __DIR__ . "/../translations/$language.php";
    if (file_exists($translationsPath)) {
        $translations = include $translationsPath;
    } else {
        die("Hata: Dil dosyası bulunamadı.");
    }
    return $translations[$key] ?? $key;
}

function getCourseName($courseId)
{
    global $courses;
    foreach ($courses as $course) {
        if ($course['id'] == $courseId) {
            return $course['course_name'];
        }
    }
    return "";
}

function getPaymentMethodName($paymentMethodId)
{
    global $payment_methods;
    foreach ($payment_methods as $payment_method) {
        if ($payment_method['id'] == $paymentMethodId && isset($payment_method['name'])) {
            return $payment_method['name'];
        }
    }
    return "";
}


function getBankName($bankId)
{
    global $db;
    try {
        // Banka ismini çek
        $query = "SELECT bank_name FROM banks WHERE id = :bank_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':bank_id', $bankId, PDO::PARAM_INT);
        $stmt->execute();

        // Eğer belirtilen banka ID'si bulunursa banka ismini döndür; yoksa 'Belirsiz' döndür.
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['bank_name'] : 'Belirsiz';
    } catch (PDOException $e) {
        die("Veritabanından banka ismi çekerken bir hata oluştu: " . $e->getMessage());
    }
}

// Profil fotoğrafını yükleme fonksiyonu
function uploadProfilePhoto($user_id, $extension, $target_width = 300, $target_height = 300, $compression_quality = 50) {
    $hashed_filename = md5($user_id) . "." . $extension;
    $photo_path = "../uploads/profile_photos/{$hashed_filename}"; // "../" ekleyerek bir üst dizine çıkıyoruz

    // Resmi yükle
    $original_image = imagecreatefromstring(file_get_contents($_FILES["profile_photo"]["tmp_name"]));

    // Orijinal resmin genişliği ve yüksekliği
    $original_width = imagesx($original_image);
    $original_height = imagesy($original_image);

    // Yeniden boyutlandırma oranlarını hesapla
    $resize_ratio = min($target_width / $original_width, $target_height / $original_height);

    // Yeni genişlik ve yüksekliği hesapla
    $new_width = $original_width * $resize_ratio;
    $new_height = $original_height * $resize_ratio;

    // Yeni resmi oluştur
    $resized_image = imagecreatetruecolor($new_width, $new_height);
    imagecopyresampled($resized_image, $original_image, 0, 0, 0, 0, $new_width, $new_height, $original_width, $original_height);

    // Resmi sıkıştır ve kaydet
    imagejpeg($resized_image, $photo_path, $compression_quality);

    // Bellekten temizle
    imagedestroy($original_image);
    imagedestroy($resized_image);

    return $photo_path;
}


// Profil fotoğrafını silme fonksiyonu
function deleteProfilePhoto($user_id) {
    $photo_path = getProfilePhotoPath($user_id);

    if (file_exists($photo_path)) {
        unlink($photo_path);
        updateProfilePhotoPath($user_id, null);
    }
}

// Profil fotoğrafı yolunu güncelleme fonksiyonu
function updateProfilePhotoPath($user_id, $photo_path) {
    global $db;
    $updatePhotoQuery = "UPDATE users SET profile_photo = :photo_path WHERE id = :user_id";
    $stmtUpdatePhoto = $db->prepare($updatePhotoQuery);
    $stmtUpdatePhoto->bindParam(':photo_path', $photo_path, PDO::PARAM_STR);
    $stmtUpdatePhoto->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmtUpdatePhoto->execute();
}

// Profil fotoğrafı yolunu alma fonksiyonu
function getProfilePhotoPath($user_id) {
    global $db;
    $getPhotoPathQuery = "SELECT profile_photo FROM users WHERE id = :user_id";
    $stmtGetPhotoPath = $db->prepare($getPhotoPathQuery);
    $stmtGetPhotoPath->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmtGetPhotoPath->execute();
    $result = $stmtGetPhotoPath->fetch(PDO::FETCH_ASSOC);

    return $result['profile_photo'];
}

// Function to send SMS
function sendSMS($to, $userInputMessage, $first_name, $username, $email) {
    global $config, $siteName, $siteUrl;

    // Check if Infobip configuration is enabled and valid
    if (
        !empty($config['infobip']['BASE_URL'])
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

        $message = new SmsTextualMessage(destinations: [$destination], from: $SENDER, text: "Selam $first_name, Bir mesajın var 🤗. 🧐 $siteName dedi ki: $userInputMessage");

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

        echo json_encode(['success' => false, 'message' => $smsErrorMessage]);
        exit;
    }
}

// Function to send Email
function sendEmail($to, $userInputMessage, $first_name, $username, $email) {
    global $config, $siteName, $siteUrl;

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
        $mail->Subject = '=?UTF-8?B?' . base64_encode($siteName . ' - Bir Mesajın Var 👋') . '?='; // Encode subject in UTF-8

        $mail->Body = "
            <html>
                <body>
                    <p>👋 Selam $first_name, bir mesajın var 🤗</p>
                    <p>🧐 $siteName dedi ki:</p>
                    <p> $userInputMessage</p>
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

// Function to save the message to the database
function saveMessageToDatabase($receiverId, $messageText, $sendAsSMS, $sendAsEmail) {
    global $db;

    // Oturum açmış adminin ID'sini al
    $senderId = $_SESSION['admin_id'];
    $currentDateTime = date('Y-m-d H:i:s');

    try {
        // Insert the message into the database with the current date and time
        $query = "INSERT INTO sent_messages (sender_id, receiver_id, sent_at, message_text, send_as_sms, send_as_email) VALUES (:sender_id, :receiver_id, :sent_at, :message_text, :send_as_sms, :send_as_email)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':sender_id', $senderId, PDO::PARAM_INT);
        $stmt->bindParam(':receiver_id', $receiverId, PDO::PARAM_INT);
        $stmt->bindParam(':sent_at', $currentDateTime, PDO::PARAM_STR);
        $stmt->bindParam(':message_text', $messageText, PDO::PARAM_STR);

        // Kullanıcı hem SMS'i hem de e-postayı seçtiyse, her ikisini de kaydet
        if ($sendAsSMS && $sendAsEmail) {
            $sendAsSMS = 1;
            $sendAsEmail = 1;
        } else {
            // Tek seçenek seçiliyse diğerini 0 yap
            $sendAsSMS = $sendAsSMS ? 1 : 0;
            $sendAsEmail = $sendAsEmail ? 1 : 0;
        }

        $stmt->bindParam(':send_as_sms', $sendAsSMS, PDO::PARAM_INT);
        $stmt->bindParam(':send_as_email', $sendAsEmail, PDO::PARAM_INT);

        $stmt->execute();
    } catch (PDOException $e) {
        echo 'Veritabanına kayıt hatası: ' . $e->getMessage();
        exit;
    }
}

// Rastgele doğrulama kodu oluşturma fonksiyonu
function generateVerificationCode() {
    return mt_rand(100000, 999999); // Örnek: 6 haneli rastgele kod
}


// E-posta gönderme fonksiyonu
function sendWelcomeEmail($to, $verificationCode, $first_name, $plainPassword, $username, $email) {
    global $config, $siteName, $siteUrl;

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
        <p>$siteName 'e hoş geldin 🤗.</p>
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
function sendWelcomeSms($to, $verificationCode, $first_name, $plainPassword, $username, $email) {
    global $siteName, $siteUrl, $config, $first_name, $plainPassword, $username, $email;

    // Check if Infobip configuration is enabled and valid
    if (
        !empty($config['infobip']['BASE_URL'])
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

        $message = new SmsTextualMessage(destinations: [$destination], from: $SENDER, text: "Selam $first_name, $siteName 'e hoş geldin 🤗. $siteUrl üzerinden $email e-posta adresin ya da $username kullanıcı adın ve şifren $plainPassword ile $siteName panelinde oturum açabilirsin.");

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

// Rastgele 3 karakter oluşturan fonksiyon
function generateRandomChars() {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
    $length = 3;
    return substr(str_shuffle($characters), 0, $length);
}

// Yeni şifre oluşturma fonksiyonu
function generateRandomPassword($length = 8) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $password;
}

// Kullanıcıya şifre sıfırlama mesajı gönderme fonksiyonu
function sendPasswordResetMessage($email, $phone, $new_password) {
    global $config, $siteName, $siteShortName, $first_name;

    // E-posta gönderme fonksiyonu
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
        $mail->addAddress($email);

        $mail->Subject = 'Talebiniz Üzerine Şifreniz Sıfırlandı';

        // Bağlantı oluştur
        $mail->Body = "
            <html>
            <body>
                <p>👋 Selam $first_name,</p>
                <p>Talebiniz üzerine şifreniz sıfırlandı!</p>
                <p>Yeni şifreniz: $new_password</p>
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

    // SMS gönderme fonksiyonu
    $smsConfiguration = new Configuration(
        host: $config['infobip']['BASE_URL'],
        apiKey: $config['infobip']['API_KEY']
    );

    $sendSmsApi = new SmsApi(config: $smsConfiguration);

    $destination = new SmsDestination(
        to: $phone
    );

    $text = "👋 Selam $first_name, $siteName - $siteShortName şifreniz talebiniz üzerine sıfırlandı! Yeni şifreniz: $new_password";
    $message = new SmsTextualMessage(destinations: [$destination], from: $config['infobip']['SENDER'], text: $text);

    $request = new SmsAdvancedTextualRequest(messages: [$message]);

    try {
        $smsResponse = $sendSmsApi->sendSmsMessage($request);

        if ($smsResponse->getMessages()[0]->getStatus()->getGroupName() === 'PENDING') {
            echo 'SMS başarıyla gönderildi.';
        } else {
            echo 'SMS gönderimi başarısız.';
        }
    } catch (\Throwable $exception) {
        echo 'SMS gönderimi sırasında bir hata oluştu. Hata: ' . $exception->getMessage();
    }
}

// E-posta gönderme fonksiyonu
function sendVerificationEmail($to, $verificationCode, $first_name, $verificationId) {
    global $config, $siteName;

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

        $mail->Subject = 'Kayıt Doğrulama';

        // Parametreleri şifrele
        $encryptedEmail = $to;
        $encryptedCode = $verificationCode;

        // Bağlantı oluştur
        $verificationLink = getVerificationLink($to, $verificationCode, $verificationId);
        $mail->Body = "
    <html>
    <body>
       <p>👋 Selam $first_name,</p>
        <p>$siteName 'e hoş geldin 🤗.</p>
        <p>Kaydının tamamlanabilmesi için aşağıdaki bağlantıdan sözleşmeleri okuyup, onaylaman gerekiyor.</p>
        <p>Sözleşmeleri okuyup, onaylamak için 🤓 <a href='$verificationLink'>buraya tıklayabilirsin</a>.</p>
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
function sendVerificationSms($to, $verificationCode, $first_name, $verificationId) {
    global $config, $SENDER, $siteName;

    $infobipConfig = $config['infobip'];
    $smsConfiguration = new Configuration(host: $infobipConfig['BASE_URL'], apiKey: $infobipConfig['API_KEY']);


    $sendSmsApi = new SmsApi(config: $smsConfiguration);

    $destination = new SmsDestination(
        to: $to
    );

    // Parametreleri şifrele
    $encryptedPhone = $to;
    $encryptedCode = $verificationCode;

    // Bağlantı oluştur
    $verificationLink = getVerificationLink($to, $verificationCode, $verificationId, "phone");

    $message = new SmsTextualMessage(destinations: [$destination], from: $SENDER, text: "Selam $first_name, $siteName platformuna hoş geldin 🤗 Kaydının tamamlanabilmesi için sözleşmeleri okuyup onaylaman gerekiyor: $verificationLink.");

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

function getConfigurationFromDatabase($db)
{
    try {
        $query = "SELECT * FROM options";
        $stmt = $db->prepare($query);
        $stmt->execute();

        $options = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Check if any options are found
        if (!$options) {
            throw new Exception("Options not found");
        }

        // Transform options into an associative array
        $result = [];
        foreach ($options as $option) {
            $result[$option['option_name']] = $option['option_value'];
        }

        return $result;
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}

function logoutUser() {
    // Eğer "action" parametresi "logout" ise oturumu kapat
    if (isset($_GET['action']) && $_GET['action'] == 'logout') {
        // Önceki sayfanın URL'sini al
        $previousPage = $_SERVER["HTTP_REFERER"];

        // Oturumu temizle ve sonlandır
        session_unset();
        session_destroy();

        // Eğer önceki sayfa bilgisi varsa, kullanıcıyı o sayfaya yönlendir
        if (!empty($previousPage)) {
            header("Location: $previousPage");
        } else {
            header("Location: /admin/panel.php"); // Varsayılan sayfaya yönlendirme
        }

        exit(); // İşlemi sonlandır
    }
}

?>