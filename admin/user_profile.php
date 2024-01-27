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

// Kullanıcı girişi kontrolü
if (!isset($_SESSION["admin_id"])) {
    header("Location: index.php");
    exit();
}

require_once(__DIR__ . '/../config/db_connection.php');
require_once('../config/config.php');
require_once "../src/functions.php";
require_once(__DIR__ . '/../vendor/autoload.php');

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

// Kullanıcı bilgilerini kullanabilirsiniz
$admin_id = $_SESSION["admin_id"];
$student_id = isset($_GET['student_id']) ? $_GET['student_id'] : null;
$admin_username = $_SESSION["admin_username"];

// Kullanıcı değişkenini tanımla
$user = null;

// URL parametrelerinden kullanıcı ID'sini al
if (isset($_GET["id"])) {
    $user_id = $_GET["id"];

    $query = "
SELECT 
    users.*,
    verifications.id AS verification_id,
    verifications.email AS verification_email,
    verifications.phone AS verification_phone,
    verifications.verification_code_email,
    verifications.verification_code_sms,
    verifications.verification_ip_email,
    verifications.verification_ip_sms,
    verifications.verification_time_email_sent,
    verifications.verification_time_sms_sent,
    verifications.verification_time_email_confirmed,
    verifications.verification_time_sms_confirmed,
    verifications.verification_signature_email,
    verifications.verification_signature_sms,
    CONCAT(u_created_by.first_name, ' ', u_created_by.last_name) AS created_by_name,
    CONCAT(u_updated_by.first_name, ' ', u_updated_by.last_name) AS updated_by_name,
    CONCAT(u_deleted_by.first_name, ' ', u_deleted_by.last_name) AS deleted_by_name
FROM users
LEFT JOIN users u_created_by ON users.created_by_user_id = u_created_by.id
LEFT JOIN users u_updated_by ON users.updated_by_user_id = u_updated_by.id
LEFT JOIN users u_deleted_by ON users.deleted_by_user_id = u_deleted_by.id
LEFT JOIN verifications ON users.id = verifications.user_id
WHERE users.id = :user_id
";

    $stmt = $db->prepare($query);
    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $query = "SELECT * FROM verifications WHERE user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $verifications = $stmt->fetchAll(PDO::FETCH_ASSOC);


    if (!$user) {
        // Belirtilen ID'ye sahip kullanıcı yoksa, kullanıcı listesine yönlendir
        header("Location: users.php");
        exit();
    }

    // Kullanıcı tipini al
    $user_type = $user['user_type'];
} else {
    // Kullanıcı ID sağlanmadıysa yönlendir
    header("Location: users.php");
    exit();
}

// Kullanıcı ve akademi ilişkisini çekmek için bir SQL sorgusu
$getUserAcademyQuery = "SELECT academy_id FROM user_academy_assignment WHERE user_id = :user_id";
$stmtUserAcademy = $db->prepare($getUserAcademyQuery);
$stmtUserAcademy->bindParam(':user_id', $_SESSION["admin_id"], PDO::PARAM_INT);
$stmtUserAcademy->execute();
$associatedAcademies = $stmtUserAcademy->fetchAll(PDO::FETCH_COLUMN);

// Eğer kullanıcı hiçbir akademide ilişkilendirilmemişse veya bu akademilerden hiçbiri yoksa, uygun bir işlemi gerçekleştirin
if (empty($associatedAcademies)) {
    echo "Kullanıcınız bu işlem için yetkili değil!";
    exit();
}

// Eğitim danışmanının erişebileceği akademilerin listesini güncelle
$allowedAcademies = $associatedAcademies;

$queryUserType = "SELECT type_name FROM user_types WHERE id = :user_type_id";
$stmtUserType = $db->prepare($queryUserType);
$stmtUserType->bindParam(":user_type_id", $user['user_type'], PDO::PARAM_INT);
$stmtUserType->execute();
$userType = $stmtUserType->fetchColumn();

// Profil fotoğrafını işle
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Profil fotoğrafını yükleme işlemi
    if (isset($_FILES["profile_photo"]) && $_FILES["profile_photo"]["error"] == 0) {
        $allowedExtensions = ["jpg", "jpeg", "png"];
        $maxFileSize = 5 * 1024 * 1024; // 5 MB

        $extension = strtolower(pathinfo($_FILES["profile_photo"]["name"], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions)) {
            echo json_encode(['success' => false, 'message' => 'Sadece JPG, JPEG ve PNG uzantılı dosyaları yükleyebilirsiniz.']);
            exit;
        }

        if ($_FILES["profile_photo"]["size"] > $maxFileSize) {
            echo json_encode(['success' => false, 'message' => 'Dosya boyutu 5 MB\'dan büyük olamaz.']);
            exit;
        }

        // Mevcut fotoğraf varsa sil
        if (!empty($user['profile_photo'])) {
            deleteProfilePhoto($user['id']);
        }

        // Yeni fotoğrafı yükle
        $photo_path = uploadProfilePhoto($user['id'], $extension);
        updateProfilePhotoPath($user['id'], $photo_path);

        // Güncellenen HTML içeriğini üret
        $profilePhotoPath = getProfilePhotoPath($user['id']);
        $htmlContent = '<img id="profilePhoto" src="' . $profilePhotoPath . '" alt="Profil Fotoğrafı" class="rounded-circle" style="width: 150px; height: 150px;">';
        echo json_encode(['success' => true, 'message' => 'Profil fotoğrafı güncellendi.', 'html' => $htmlContent]);
        exit;
    }

    // Profil fotoğrafını silme işlemi
    if (isset($_POST["delete_photo"])) {
        deleteProfilePhoto($user['id']);

        // Güncellenen HTML içeriğini üret
        $htmlContent = '<img id="profilePhoto" src="/assets/brand/default_pp.png" alt="Profil Fotoğrafı" class="rounded-circle" style="width: 150px; height: 150px;">';
        echo json_encode(['success' => true, 'message' => 'Profil fotoğrafı silindi.', 'html' => $htmlContent]);
        exit;
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

// Assuming you have a textarea with the id "messageText" in your HTML form
$userInputMessage = $_POST['messageText'] ?? ''; // Adjust this according to your form submission method (e.g., $_POST, $_GET)

// Function to send SMS
function sendSMS($to, $userInputMessage, $first_name, $username, $email) {
    global $config, $siteName, $siteUrl;

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

// AJAX endpoint for sending messages
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'sendMessage') {
    $messageText = $_POST['messageText'];
    $sendAsSMS = isset($_POST['sendAsSMS']) && $_POST['sendAsSMS'] === 'true';
    $sendAsEmail = isset($_POST['sendAsEmail']) && $_POST['sendAsEmail'] === 'true';

    // Call the functions to send messages
    if ($sendAsSMS) {
        $phoneNumber = $user['phone']; // Replace with the actual field that holds the phone number
        sendSMS($phoneNumber, $messageText, $user['first_name'], $user['username'], $user['email']);
    }

    if ($sendAsEmail) {
        $userEmail = $user['email']; // Replace with the actual field that holds the email address
        sendEmail($userEmail, $messageText, $user['first_name'], $user['username'], $user['email']);
    }

    echo json_encode(['success' => true, 'message' => 'Mesaj başarıyla gönderildi.']);
    exit;
}

?>
<?php
require_once(__DIR__ . '/partials/header.php');
?>
<script>
    var user = <?php echo json_encode($user); ?>;
</script>

<?php
require_once(__DIR__ . '/partials/sidebar.php');
?>


    <!-- Ana içerik -->
<main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
            <h2>Kullanıcı Profili</h2>
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
                        <a href="edit_user.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-edit"></i> Kullanıcı Düzenle
                        </a>
                        <a href="user_profile.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-user-lock"></i> Şifre Gönder
                        </a>
                        <a href="#" class="btn btn-sm btn-outline-secondary" onclick="openSendMessageModal()">
                            <i class="fas fa-envelopes-bulk"></i> SMS & E-posta Gönder
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

        <div class="row">
            <!-- İlk sütun -->
            <!-- Message Modal -->
            <div class="modal fade" id="sendMessageModal" tabindex="-1" aria-labelledby="sendMessageModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h1 class="modal-title fs-5" id="sendMessageModalLabel">Mesaj Gönder</h1>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <textarea id="messageText" class="form-control" rows="4" placeholder="İletilmek istenen mesaj"></textarea>

                            <div class="form-check mt-3">
                                <input class="form-check-input" type="checkbox" id="sendAsSMS" checked>
                                <label class="form-check-label" for="sendAsSMS">SMS olarak gönder</label>
                                <span id="smsCharacterCount" class="ms-2">(160 karakter = 1 SMS)</span>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="sendAsEmail" checked>
                                <label class="form-check-label" for="sendAsEmail">E-posta olarak gönder</label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Kapat</button>
                            <button type="button" class="btn btn-success btn-sm" onclick="sendMessage()">Mesajı Gönder</button>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                // Function to update character count and SMS message info
                function updateCharacterCount() {
                    var maxCharacters = 160; // Adjust this to your desired character limit
                    var messageText = document.getElementById('messageText').value;
                    var remainingCharacters = messageText.length;
                    var characterCountElement = document.getElementById('smsCharacterCount');

                    // Check if the message is not empty
                    if (remainingCharacters === 0) {
                        characterCountElement.textContent = ''; // Hide the message when characters are 0
                        return;
                    }

                    // Calculate SMS count and remaining characters for countdown
                    var smsCount = Math.ceil(remainingCharacters / maxCharacters);
                    var countdown = remainingCharacters % maxCharacters || maxCharacters;

                    // Change the color based on the remaining characters
                    if (remainingCharacters >= 0) {
                        characterCountElement.style.color = 'black'; // or your default color
                    } else {
                        characterCountElement.style.color = 'red'; // or any color you prefer for exceeding the limit
                    }

                    // Display a message until 160 characters are reached
                    if (remainingCharacters > 0) {
                        characterCountElement.textContent = `(${countdown} karakter = ${smsCount} SMS)`;
                    } else {
                        // Display the number of SMS messages needed
                        characterCountElement.textContent = `${smsCount} SMS gerekli`;
                    }
                }

                // Attach the updateCharacterCount function to the input event of the messageText textarea
                document.getElementById('messageText').addEventListener('input', updateCharacterCount);


                function openSendMessageModal() {
                    // Open the modal
                    $('#sendMessageModal').modal('show');
                }

                function sendMessage() {
                    var messageText = document.getElementById('messageText').value;
                    var sendAsSMS = document.getElementById('sendAsSMS').checked;
                    var sendAsEmail = document.getElementById('sendAsEmail').checked;

                    // Validate message text
                    if (messageText.trim() === '') {
                        alert('Mesaj metni girilmedi!');
                        return;
                    }

                    // Send the message via AJAX
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', window.location.href, true);
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                    xhr.onreadystatechange = function () {
                        if (xhr.readyState === XMLHttpRequest.DONE) {
                            if (xhr.status === 200) {
                                try {
                                    var response = JSON.parse(xhr.responseText);
                                    if (response.success) {
                                        alert(response.message);
                                        // Optionally, close the modal or perform other actions on success
                                        $('#sendMessageModal').modal('hide'); // Close the modal
                                    } else {
                                        alert('Mesaj gönderimi başarısız..');
                                    }
                                } catch (e) {
                                    console.error('Error parsing JSON:', e);
                                    alert('Mesaj gönderimi başarısız.');
                                }
                            } else {
                                alert('Mesaj gönderimi başarısız.');
                            }
                        }
                    };

                    var formData = new FormData();
                    formData.append('action', 'sendMessage');
                    formData.append('messageText', messageText);
                    formData.append('sendAsSMS', sendAsSMS);
                    formData.append('sendAsEmail', sendAsEmail);

                    xhr.send(new URLSearchParams(formData));
                }
            </script>


            <div class="col-md-4">
                <div class="card mt-3 mb-3">
                    <div class="card-header">
                        <h5 class="card-title">Kullanıcı Bilgileri</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item"><strong>Kayıt No:</strong> <?= $user['id'] ?></li>
                            <li class="list-group-item"><strong>Kullanıcı adı:</strong> <?= $user['username'] ?></li>
                            <li class="list-group-item"><strong>T.C. Kimlik No:</strong> <?= $user['tc_identity'] ?></li>
                            <li class="list-group-item"><strong>Ad:</strong> <?= $user['first_name'] ?></li>
                            <li class="list-group-item"><strong>Soyad:</strong> <?= $user['last_name'] ?></li>
                            <li class="list-group-item"><strong>E-posta:</strong> <?= $user['email'] ?></li>
                            <li class="list-group-item"><strong>Telefon:</strong> <?= $user['phone'] ?></li>
                            <li class="list-group-item"><strong>İl:</strong> <?= $user['city'] ? $user['city'] : 'Veri yok'; ?></li>
                            <li class="list-group-item"><strong>İlçe:</strong> <?= $user['district'] ? $user['district'] : 'Veri yok'; ?></li>
                            <?php
                            function getCountryName($countryCode) {
                                global $iso3166;

                                // Türkiye'nin alpha2 kodu TR ise "Türkiye" döndür, aksi takdirde ISO3166 kütüphanesinden al
                                return ($countryCode === 'TR') ? 'Türkiye' : $iso3166->alpha2($countryCode)['name'] ?? $countryCode;
                            }
                            ?>
                            <li class="list-group-item"><strong>Ülke:</strong> <?= ($user['country']) ? getCountryName($user['country']) : 'Veri yok'; ?></li>                <li class="list-group-item"><strong>Doğum Tarihi:</strong> <?php echo $user['birth_date'] ? date(DATE_FORMAT, strtotime($user['birth_date'])) : 'Belli değil'; ?></li>
                            <?php
                            // Kullanıcının doğum tarihi
                            $birthDate = $user['birth_date'];

                            if ($birthDate) {
                                // Bugünün tarihini al
                                $today = new DateTime();

                                // Doğum tarihini DateTime nesnesine dönüştür
                                $birthDateTime = new DateTime($birthDate);

                                // Yaşı hesapla
                                $age = $today->diff($birthDateTime)->y;

                                echo '<li class="list-group-item"><strong>Yaş:</strong> ' . $age . '</li>';
                            } else {
                                echo '<li class="list-group-item"><strong>Yaş:</strong> Bilgi bulunmuyor</li>';
                            }
                            ?>
                            <li class="list-group-item"><strong>Kan Grubu:</strong> <?= $user['blood_type'] ? $user['blood_type'] : 'Veri yok'; ?></li>
                            <li class="list-group-item"><strong>Bilinen Sağlık Sorunu:</strong> <?= $user['health_issue'] ? $user['health_issue'] : 'Veri yok'; ?></li>
                              <li class="list-group-item"><strong>Fatura Türü:</strong> <?= $user['invoice_type'] == 'individual' ? 'Bireysel' : 'Kurumsal' ?></li>
                            <?php if ($user['invoice_type'] == 'individual'): ?>
                                <li class="list-group-item"><strong>Fatura T.C. Kimlik No:</strong> <?= $user['tc_identity_for_individual_invoice'] ?></li>
                            <?php elseif ($user['invoice_type'] == 'corporate'): ?>
                                <li class="list-group-item"><strong>Şirket Ünvanı:</strong> <?= $user['tax_company_name'] ?></li>
                                <li class="list-group-item"><strong>Vergi Dairesi:</strong> <?= $user['tax_office'] ?></li>
                                <li class="list-group-item"><strong>Vergi Numarası:</strong> <?= $user['tax_number'] ?></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>       </div>

            <!-- İkinci sütun -->
            <div class="col-md-4">

                <div class="card mt-3 mb-3">
                    <div class="card-header">
                        <h5 class="card-title">Diğer Bilgiler</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item"><strong>SMS Onay Durumu:</strong> <?= $user['verification_time_sms_confirmed'] ? '<i class="fas fa-check text-success"></i> Doğrulandı' : '<i class="fas fa-times text-danger"></i> Doğrulanmadı' ?></li>
                            <li class="list-group-item"><strong>E-posta Onay Durumu:</strong> <?= $user['verification_time_email_confirmed'] ? '<i class="fas fa-check text-success"></i> Doğrulandı' : '<i class="fas fa-times text-danger"></i> Doğrulanmadı' ?></li>
                            <li class="list-group-item"><strong>Kullanıcı Türü:</strong> <?= $userType ?></li>
                            <li class="list-group-item"><strong>Oluşturan:</strong> <?= !empty($user['created_by_name']) ? $user['created_by_name'] : 'Veri yok'; ?></li>
                            <li class="list-group-item"><strong>Oluşturulma:</strong> <?= $user['created_at'] ? date(DATETIME_FORMAT, strtotime($user['created_at'])) : 'Veri yok'; ?></li>
                            <li class="list-group-item"><strong>Güncelleyen:</strong> <?= !empty($user['updated_by_name']) ? $user['updated_by_name'] : 'Veri yok'; ?></li>
                            <li class="list-group-item"><strong>Güncellenme:</strong> <?= $user['updated_at'] ? date(DATETIME_FORMAT, strtotime($user['updated_at'])) : 'Veri yok'; ?></li>
                            <li class="list-group-item"><strong>Silen:</strong> <?= !empty($user['deleted_by_name']) ? $user['deleted_by_name'] : 'Veri yok'; ?></li>
                            <li class="list-group-item"><strong>Silinme:</strong> <?= $user['deleted_at'] ? date(DATETIME_FORMAT, strtotime($user['deleted_at'])) : 'Veri yok'; ?></li>
                            <li class="list-group-item"><strong>SMS Gönderilme:</strong> <?php echo $user['verification_time_sms_sent'] ? date(DATETIME_FORMAT, strtotime($user['verification_time_sms_sent'])) : 'Veri yok'; ?></li>
                            <li class="list-group-item"><strong>SMS Onaylandı:</strong> <?php echo $user['verification_time_sms_confirmed'] ? date(DATETIME_FORMAT, strtotime($user['verification_time_sms_confirmed'])) : 'Veri yok'; ?></li>
                            <li class="list-group-item"><strong>SMS Onay IP:</strong> <?= $user['verification_ip_sms'] ? $user['verification_ip_sms'] : 'Veri yok'; ?></li>
                            <li class="list-group-item"><strong>E-posta Gönderilme:</strong> <?php echo $user['verification_time_email_sent'] ? date(DATETIME_FORMAT, strtotime($user['verification_time_email_sent'])) : 'Veri yok'; ?></li>
                            <li class="list-group-item"><strong>E-posta Onaylandı:</strong> <?php echo $user['verification_time_email_confirmed'] ? date(DATETIME_FORMAT, strtotime($user['verification_time_email_confirmed'])) : 'Veri yok'; ?></li>
                            <li class="list-group-item"><strong>E-posta Onay IP:</strong> <?= $user['verification_ip_email'] ? $user['verification_ip_email'] : 'Veri yok'; ?></li>
                        </ul>
                    </div>
                </div>



            </div>

            <!-- Üçüncü sütun -->
            <div class="col-md-4">

                <div class="card mt-3 mb-3">
                    <div class="card-header">
                        <h5 class="card-title">Profil Fotoğrafı</h5>
                    </div>
                    <div class="card-body">


                        <!-- Profil fotoğrafı gösterme alanı -->
                        <div id="profilePhotoContainer" class="mt-3 mb-3 text-center"> <!-- text-center sınıfı eklenerek içeriği ortalamış oluyoruz -->
                            <?php
                            $profilePhotoPath = !empty($user['profile_photo']) ? getProfilePhotoPath($user['id']) : "/assets/brand/default_pp.png";
                            ?>
                            <img id="profilePhoto" src="<?= $profilePhotoPath ?>" alt="Profil Fotoğrafı" class="rounded-circle" style="width: 150px; height: 150px;">
                        </div>



                        <!-- Profil fotoğrafı yükleme ve silme formu -->
                        <form id="photoForm" action="" method="post" enctype="multipart/form-data">
                            <div class="mt-3 mb-3">
                                <label for="profile_photo" class="form-label">Profil Fotoğrafı Yükle</label>
                                <div class="input-group">
                                    <input type="file" name="profile_photo" id="profile_photo" class="form-control" accept="image/*">
                                    <button type="submit" class="btn btn-primary">Yükle</button>

                                    <?php if (!empty($user['profile_photo'])): ?>
                                        <input type="hidden" name="delete_photo" value="1">
                                        <button type="button" id="deleteBtn" class="btn btn-danger">
                                            Sil <i class="bi bi-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>

                        <script>
                            // Profil fotoğrafını güncelleme işlemi
                            $(document).ready(function () {
                                $('#photoForm').submit(function (e) {
                                    e.preventDefault();

                                    var formData = new FormData(this);

                                    $.ajax({
                                        url: '', // Sayfanın URL'sini buraya ekleyin
                                        method: 'POST',
                                        data: formData,
                                        contentType: false,
                                        processData: false,
                                        success: function (data) {
                                            try {
                                                var response = JSON.parse(data);

                                                console.log(response); // Kontrol için konsol log'u

                                                alert(response.message);

                                                if (response.success) {
                                                    $('#profilePhotoContainer').html(response.html);
                                                }
                                            } catch (error) {
                                                console.error('Ajax isteği sırasında bir hata oluştu:', error);
                                            }
                                        },
                                        error: function (xhr, status, error) {
                                            console.error('Ajax isteği sırasında bir hata oluştu:', error);
                                        }
                                    });
                                });

                                // Profil fotoğrafını silme işlemi
                                $('#deleteBtn').click(function () {
                                    $.ajax({
                                        url: '', // Sayfanın URL'sini buraya ekleyin
                                        method: 'POST',
                                        data: 'delete_photo=1',
                                        success: function (data) {
                                            try {
                                                var response = JSON.parse(data);

                                                console.log(response); // Kontrol için konsol log'u

                                                alert(response.message);

                                                if (response.success) {
                                                    $('#profilePhotoContainer').html(response.html);
                                                }
                                            } catch (error) {
                                                console.error('Ajax isteği sırasında bir hata oluştu:', error);
                                            }
                                        },
                                        error: function (xhr, status, error) {
                                            console.error('Ajax isteği sırasında bir hata oluştu:', error);
                                        }
                                    });
                                });
                            });

                        </script>



                    </div>
                </div>

                <div class="card mt-3 mb-3">
                    <div class="card-header">
                        <h5 class="card-title">Doğrulama Bilgileri</h5>
                    </div>
                    <div class="card-body">

                        <div class="row mt-3 mb-3">
                            <!-- SMS İmza Gösterim Alanı -->
                            <div class="col-md-6">
                                <p style="font-weight: bold;">SMS İmza</p>
                                <?php
                                if ($user['verification_signature_sms']) {
                                    $signatureDataSMS = $user['verification_signature_sms']; // SMS İmza verisini al

                                    // İmzayı panelden göster
                                    echo '<img src="' . $signatureDataSMS . '" alt="User SMS Signature" style="border: 1px solid #ccc; max-width: 75%; max-height: 200px;">';
                                } else {
                                    echo "SMS imzası yok.";
                                }
                                ?>
                            </div>

                            <!-- E-posta İmza Gösterim Alanı -->
                            <div class="col-md-6">
                                <p style="font-weight: bold;">E-posta İmza</p>
                                <?php
                                if ($user['verification_signature_email']) {
                                    $signatureDataEmail = $user['verification_signature_email']; // E-posta İmza verisini al

                                    // İmzayı panelden göster
                                    echo '<img src="' . $signatureDataEmail . '" alt="User Email Signature" style="border: 1px solid #ccc; max-width: 75%; max-height: 200px;">';
                                } else {
                                    echo "E-posta imzası yok.";
                                }
                                ?>
                            </div>
                        </div>

                        <!-- Button trigger modal -->
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-toggle="modal" data-bs-target="#verificationsModal">
                            <i class="bi bi-clock"></i> Doğrulama Geçmişi
                        </button>
                    </div>
                </div>
                <!-- Verifications Modal -->
                <div class="modal fade" id="verificationsModal" tabindex="-1" aria-labelledby="verificationsModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" style="max-width: 90%;">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="verificationsModalLabel">Doğrulama Bilgileri</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <table class="table table-bordered">
                                    <thead>
                                    <tr>
                                        <th>E-posta</th>
                                        <th>Telefon</th>
                                        <th>E-posta Kod</th>
                                        <th>SMS Kod</th>
                                        <th>E-posta IP</th>
                                        <th>SMS IP</th>
                                        <th>E-posta Gönderim</th>
                                        <th>SMS Gönderim</th>
                                        <th>E-posta Onay</th>
                                        <th>SMS Onay</th>
                                        <th>E-posta İmza</th>
                                        <th>SMS İmza</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($verifications as $verification): ?>
                                        <tr>
                                            <td><?= !empty($verification['email']) ? $verification['email'] : 'Veri yok' ?></td>
                                            <td><?= !empty($verification['phone']) ? $verification['phone'] : 'Veri yok' ?></td>
                                            <td><?= !empty($verification['verification_code_email']) ? $verification['verification_code_email'] : 'Veri yok' ?></td>
                                            <td><?= !empty($verification['verification_code_sms']) ? $verification['verification_code_sms'] : 'Veri yok' ?></td>
                                            <td><?= !empty($verification['verification_ip_email']) ? $verification['verification_ip_email'] : 'Veri yok' ?></td>
                                            <td><?= !empty($verification['verification_ip_sms']) ? $verification['verification_ip_sms'] : 'Veri yok' ?></td>
                                            <td><?= isset($verification['verification_time_email_sent']) ? date(DATETIME_FORMAT, strtotime($verification['verification_time_email_sent'])) : 'Veri yok' ?></td>
                                            <td><?= isset($verification['verification_time_sms_sent']) ? date(DATETIME_FORMAT, strtotime($verification['verification_time_sms_sent'])) : 'Veri yok' ?></td>
                                            <td><?= isset($verification['verification_time_email_confirmed']) ? date(DATETIME_FORMAT, strtotime($verification['verification_time_email_confirmed'])) : 'Veri yok' ?></td>
                                            <td><?= isset($verification['verification_time_sms_confirmed']) ? date(DATETIME_FORMAT, strtotime($verification['verification_time_sms_confirmed'])) : 'Veri yok' ?></td>
                                            <td><?= $verification['verification_signature_email'] ? '<img src="' . $verification['verification_signature_email'] . '" alt="Verification Signature Email" style="max-width: 75px; max-height: 75px;">' : 'Veri yok' ?></td>
                                            <td><?= $verification['verification_signature_sms'] ? '<img src="' . $verification['verification_signature_sms'] . '" alt="Verification Signature SMS" style="max-width: 75px; max-height: 75px;">' : 'Veri yok' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                            </div>
                        </div>
                    </div>
                </div>

                <?php
                // Kullanıcının rolünü kontrol et
                if (isset($user['user_type'])) {
                    if ($user['user_type'] == 6) {
                        // Öğrencinin ID'sini alın
                        $studentId = $user['id'];

                        // Öğrenciye ait velileri çekmek için sorgu
                        $getParentsQuery = "SELECT p.id, p.first_name, p.last_name, p.tc_identity
               FROM student_parents sp
               JOIN users p ON sp.parent_id = p.id
               WHERE sp.student_id = ?";

                        $stmtParents = $db->prepare($getParentsQuery);
                        $stmtParents->execute([$studentId]);
                        $parents = $stmtParents->fetchAll(PDO::FETCH_ASSOC);

                        // Velileri listeleme
                        if ($parents) {
                            echo '<div class="card mt-3 mb-3">';
                            echo '<div class="card-header">';
                            echo '<h5 class="card-title">İlişkili Olduğu Veliler</h5>';
                            echo '</div>';
                            echo '<div class="card-body">';
                            echo '<table class="table">';
                            echo '<thead>';
                            echo '<tr>';
                            echo '<th>Tam Ad</th>';
                            echo '<th>T.C. Kimlik No</th>';
                            echo '<th>Veli Profili</th>';
                            echo '</tr>';
                            echo '</thead>';
                            echo '<tbody>';

                            foreach ($parents as $parent) {
                                echo '<tr>';
                                echo '<td>' . $parent['first_name'] . ' ' . $parent['last_name'] . '</td>';
                                echo '<td>' . $parent['tc_identity'] . '</td>';
                                echo '<td><a href="user_profile.php?id=' . $parent['id'] . '" class="btn btn-secondary"><i class="fas fa-user"></i></a></td>';
                                echo '</tr>';
                            }

                            echo '</tbody>';
                            echo '</table>';
                            echo '</div>';
                            echo '</div>';
                        } else {
                            echo "Bu öğrenciye ait veli bulunmamaktadır.";
                        }
                    } elseif ($user['user_type'] == 5) {
                        // Kullanıcı veli ise buraya gerekli kodları ekleyebilirsiniz.
                        // Örneğin, velinin bağlı olduğu öğrencileri çekip listeleyebilirsiniz.
                        $parentUserId = $user['id'];

                        // Velinin bağlı olduğu öğrencileri çekmek için sorgu
                        $getStudentsQuery = "SELECT s.id, s.first_name, s.last_name, s.tc_identity
               FROM student_parents sp
               JOIN users s ON sp.student_id = s.id
               WHERE sp.parent_id = ?";

                        $stmtStudents = $db->prepare($getStudentsQuery);
                        $stmtStudents->execute([$parentUserId]);
                        $students = $stmtStudents->fetchAll(PDO::FETCH_ASSOC);

                        // Öğrencileri listeleme
                        if ($students) {
                            echo '<div class="card mt-3 mb-3">';
                            echo '<div class="card-header">';
                            echo '<h5 class="card-title">İlişkili Olduğu Öğrenciler</h5>';
                            echo '</div>';
                            echo '<div class="card-body">';
                            echo '<table class="table">';
                            echo '<thead>';
                            echo '<tr>';
                            echo '<th>Tam Ad</th>';
                            echo '<th>T.C. Kimlik No</th>';
                            echo '<th>Öğrenci Profili</th>';
                            echo '</tr>';
                            echo '</thead>';
                            echo '<tbody>';

                            foreach ($students as $student) {
                                echo '<tr>';
                                echo '<td>' . $student['first_name'] . ' ' . $student['last_name'] . '</td>';
                                echo '<td>' . $student['tc_identity'] . '</td>';
                                echo '<td><a href="user_profile.php?id=' . $student['id'] . '" class="btn btn-secondary"><i class="fas fa-user"></i></a></td>';
                                echo '</tr>';
                            }

                            echo '</tbody>';
                            echo '</table>';
                            echo '</div>';
                            echo '</div>';
                        } else {
                            echo "Bu veliye bağlı öğrenci bulunmamaktadır.";
                        }
                    } else {
                        echo "Bu kullanıcı türü desteklenmemektedir.";
                    }
                } else {
                    echo "Kullanıcı türü belirtilmemiş.";
                }
                ?>


                <div class="card mt-3 mb-3">
                    <div class="card-header">
                        <h5 class="card-title">Notlar</h5>
                    </div>
                    <div class="card-body">
                        <?php echo !empty($user['notes']) ? $user['notes'] : 'Henüz not yazılmamış'; ?>

                    </div>
                </div>
           </div>
        </div>



            <?php
            // Kullanıcı türüne göre içeriği belirle
            if ($user['user_type'] == 4 || $user['user_type'] == 6) {
                if ($user_id !== null) {
                    $query = "SELECT
            CONCAT(u_teacher.first_name, ' ', u_teacher.last_name) AS teacher_name,
            a.name AS academy_name,
            ac.class_name AS class_name,
            CONCAT(u_student.first_name, ' ', u_student.last_name) AS student_name,
            c.course_name AS lesson_name,
            sc.course_date_1,
            sc.course_date_2,
            sc.course_date_3,
            sc.course_date_4,
            sc.course_attendance_1,
            sc.course_attendance_2,
            sc.course_attendance_3,
            sc.course_attendance_4,
            sc.course_fee,
            sc.debt_amount,
            sc.id AS course_plan_id,
            sc.created_at,
            sc.updated_at,
            CONCAT(u_created_by.first_name, ' ', u_created_by.last_name) AS created_by_name,
            CONCAT(u_updated_by.first_name, ' ', u_updated_by.last_name) AS updated_by_name
        FROM
            course_plans sc
            INNER JOIN users u_teacher ON sc.teacher_id = u_teacher.id
            INNER JOIN academies a ON sc.academy_id = a.id AND a.id IN (" . implode(",", $allowedAcademies) . ")
            INNER JOIN academy_classes ac ON sc.class_id = ac.id
            INNER JOIN users u_student ON sc.student_id = u_student.id
            INNER JOIN courses c ON sc.course_id = c.id
            LEFT JOIN users u_created_by ON sc.created_by_user_id = u_created_by.id
            LEFT JOIN users u_updated_by ON sc.updated_by_user_id = u_updated_by.id
        WHERE
            u_student.id = :user_id OR u_teacher.id = :user_id";

                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                    $stmt->execute();

                    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    // Display active and completed cards in separate rows
                    echo '<div class="row">';
                    echo '<div class="col-md-6 mt-5 mb-3"><h3>Aktif Dersler</h3>';
                    foreach ($results as $result) {
                        // Check if the last class date is more than 1 day ago
                        if (strtotime($result['course_date_4']) < strtotime('-1 day')) {
                            continue; // Skip to next iteration if the course is completed
                        }

                        displayCourseCard($result);
                    }
                    echo '</div>';

                    echo '<div class="col-md-6 mt-5 mb-3"><h3>Arşivlenen Dersler</h3>';
                    foreach ($results as $result) {
                        // Check if the last class date is not more than 1 day ago
                        if (strtotime($result['course_date_4']) >= strtotime('-1 day')) {
                            continue; // Skip to next iteration if the course is not completed
                        }

                        displayCourseCard($result);
                    }
                    echo '</div>';
                    echo '</div>';
                }
            }

            function displayCourseCard($result)
            {
                // Kartın border rengini belirle
                global $user;
                if ($result['debt_amount'] == 0 && $result['course_attendance_1'] && $result['course_attendance_2'] && $result['course_attendance_3'] && $result['course_attendance_4']) {
                    // 4 derse katıldı ve borcu yoksa: Yeşil
                    $cardBorderStyle = 'border-success';
                } elseif ($result['debt_amount'] > 0) {
                    // Borcu varsa: Kırmızı
                    $cardBorderStyle = 'border-danger';
                } elseif (!$result['course_attendance_1'] || !$result['course_attendance_2'] || !$result['course_attendance_3'] || !$result['course_attendance_4']) {
                    // 4 derse katılmamış veya borcu yoksa: Mavi
                    $cardBorderStyle = 'border-primary';
                } else {
                    // Diğer durumlar için: Gri
                    $cardBorderStyle = 'border-gray';
                }

                echo '
    <div class="col-md-6 mb-5 mt-5">
        <div class="card ' . $cardBorderStyle . '">
            <div class="card-header">
                <h6 class="card-title"><strong>' . ($user['user_type'] == 4 ? $result['student_name'] : $result['teacher_name']) . ' ile ' . $result['lesson_name'] . '</strong></h6>
            </div>
            <div class="card-body">
                <p class="card-text">Akademi: ' . $result['academy_name'] . '</p>
                <p class="card-text">Sınıf: ' . $result['class_name'] . '</p>
                <p class="card-text">1. Ders: ' . date("d.m.Y H:i", strtotime($result['course_date_1'])) . '</p>
                <p class="card-text">2. Ders: ' . date("d.m.Y H:i", strtotime($result['course_date_2'])) . '</p>
                <p class="card-text">3. Ders: ' . date("d.m.Y H:i", strtotime($result['course_date_3'])) . '</p>
                <p class="card-text">4. Ders: ' . date("d.m.Y H:i", strtotime($result['course_date_4'])) . '</p>';

                for ($i = 1; $i <= 4; $i++) {
                    echo "<p class='card-text'>{$i}. Katılım: ";

                    if ($result["course_attendance_$i"] == 0) {
                        echo "<i class='fas fa-calendar-check text-primary'></i>";
                    } elseif ($result["course_attendance_$i"] == 1) {
                        echo "<i class='fas fa-calendar-check text-success'></i>";
                    } elseif ($result["course_attendance_$i"] == 2) {
                        echo "<i class='fas fa-calendar-check text-danger'></i>";
                    } elseif ($result["course_attendance_$i"] == 3) {
                        // Change the icon or style for Yeniden Planla if there is a corresponding entry
                        echo "<i class='fas fa-calendar-check text-success'></i>";
                    } else {
                        echo "<i class='fas fa-question text-secondary'></i>";
                    }

                    echo "</p>";
                }

                echo '
                <p class="card-text">Ders Ücreti: ' . $result['course_fee'] . ' TL</p>
                <p class="card-text">Borç: ' . $result['debt_amount'] . ' TL</p>
                <p class="card-text small">Oluşturan: ' . $result['created_by_name'] . '</p>
                <p class="card-text small">Oluşturulma: ' . date("d.m.Y H:i", strtotime($result['created_at'])) . '</p>
                <p class="card-text small">Güncelleyen: ' . $result['updated_by_name'] . '</p>
                <p class="card-text small">Güncellenme: ' . date("d.m.Y H:i", strtotime($result['updated_at'])) . '</p>
                <a href="edit_course_plan.php?id=' . $result['course_plan_id'] . '" class="btn btn-danger btn-sm"><i class="fas fa-pencil-alt"></i></a>
                <a href="add_payment.php?id=' . $result['course_plan_id'] . '" class="btn btn-success btn-sm"><i class="fas fa-cash-register"></i> ₺</a>
                <a href="../reports/generate_invoice_request.php?course_plan_id=' . $result['course_plan_id'] . '" class="btn btn-success btn-sm"><i class="fas fa-file-invoice"></i></a>
                <a href="../reports/student_certificate.php?student_id=' . $result['course_plan_id'] . '" class="btn btn-success btn-sm"><i class="fas fa-graduation-cap"></i></a>
            </div>
        </div>
    </div>';
            }
            ?>



    </main>

<?php require_once('../admin/partials/footer.php'); ?>
