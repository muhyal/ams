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

global $showErrors, $siteName, $siteShortName, $siteUrl, $db;
// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);
require_once "config.php";

// Oturum kontrolü
if (session_status() == PHP_SESSION_NONE) {
    session_start();
    // CSRF token oluşturma veya varsa alınması
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

$csrf_token = $_SESSION['csrf_token'];

require_once "db_connection.php";
require 'vendor/autoload.php';

use Infobip\Api\SmsApi;
use Infobip\Configuration;
use Infobip\Model\SmsAdvancedTextualRequest;
use Infobip\Model\SmsDestination;
use Infobip\Model\SmsTextualMessage;

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // reCAPTCHA token'ını alma
    $recaptchaToken = $_POST['recaptcha_response'] ?? '';

    // reCAPTCHA doğrulama
    $recaptchaVerify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=" . RECAPTCHA_SECRET_KEY . "&response={$recaptchaToken}");
    $recaptchaResponse = json_decode($recaptchaVerify);

    // reCAPTCHA doğrulaması başarılı değilse işlemi reddet
    if (!$recaptchaResponse->success) {
        die("reCAPTCHA doğrulaması başarısız. İşlem reddedildi.");
    }


    // CSRF token kontrolü
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        die("CSRF hatası! İşlem reddedildi.");
    }

    // Kullanıcı girişi alınan değerler
    $identifier = $_POST["identifier"];
    $password = $_POST["password"];

// Kullanıcının giriş yaptığı sütunu belirleme
    $column = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

// SQL sorgusu için parametreli sorgu kullanımı
    $query = "SELECT * FROM users WHERE $column = :identifier";

    try {
        $stmt = $db->prepare($query);
        $stmt->bindParam(':identifier', $identifier, PDO::PARAM_STR);
        $stmt->execute();

        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin) {
            // Hesap aktif ve silinmemiş mi kontrolü
            if (!$admin["is_active"]) {
                die("Hesap pasif durumda. Giriş yapılamaz.");
            }

            if ($admin["deleted_at"]) {
                die("Hesap silinmiş durumda. Giriş yapılamaz.");
            }

            if (password_verify($password, $admin["password"])) {
                $allowedUserTypes = [1, 2, 3];
                if (in_array($admin["user_type"], $allowedUserTypes)) {
                    // Check if Infobip is enabled
                    global $config, $siteName, $siteShortName;

                    if ($config['infobip']['enabled']) {
                        // Send an SMS using Infobip
                        $phone = $admin["phone"];

                        $smsConfiguration = new Configuration(
                            host: $config['infobip']['BASE_URL'],
                            apiKey: $config['infobip']['API_KEY']
                        );

                        $sendSmsApi = new SmsApi(config: $smsConfiguration);

                        $destination = new SmsDestination(
                            to: $phone
                        );

                        $text = "Merhaba, $siteName - $siteShortName üzerinde yönetici oturumu açıldı. Bilginiz dışında ise lütfen kontrol ediniz.";
                        $message = new SmsTextualMessage(destinations: [$destination], from: $config['infobip']['SENDER'], text: $text);

                        $request = new SmsAdvancedTextualRequest(messages: [$message]);

                        try {
                            $smsResponse = $sendSmsApi->sendSmsMessage($request);

                            if ($smsResponse->getMessages()[0]->getStatus()->getGroupName() === 'PENDING') {
                                echo 'SMS gönderim bekliyor.';
                            } else {
                                echo 'SMS başarıyla gönderildi.';
                            }
                        } catch (\Throwable $exception) {
                            echo 'SMS gönderimi başarısız. Hata: ' . $exception->getMessage();
                        }
                    }

                    // Set session variables and redirect to admin panel
                    $_SESSION["admin_id"] = $admin["id"];
                    $_SESSION["admin_username"] = $admin["username"];
                    $_SESSION["admin_first_name"] = $admin["first_name"];
                    $_SESSION["admin_last_name"] = $admin["last_name"];
                    $_SESSION["admin_type"] = $admin["user_type"];
                    header("Location: admin_panel.php");
                    exit();
                } else {
                    echo "Bu alana giriş yapma yetkiniz yok!";
                }
            } else {
                echo "Hatalı giriş bilgileri.";
            }
        } else {
            echo "Hatalı giriş bilgileri.";
        }
    } catch (PDOException $e) {
        echo "Hata: " . $e->getMessage();
    } finally {
        // Veritabanı bağlantısını kapat
        $db = null;
    }
}
?>
<?php
require_once "header.php";
?>
<main class="form-signin w-100 m-auto">
    <form method="post" action="admin_login.php">
        <!-- reCAPTCHA v3 için gizli alan -->
        <input type="hidden" name="recaptcha_response" id="recaptcha_response">

        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        <img class="mb-4" src="./assets/brand/default_logo.png" alt="<?php echo $siteName ?>" title="<?php echo $siteName ?>" width="100" height="100">
        <h1 class="h3 mb-3 fw-normal">Yönetici Paneli</h1>
        <div class="form-floating">
            <input type="text" class="form-control" id="identifier" name="identifier" placeholder="@doremuzikakademi.com" autofocus="" required>
            <label for="floatingInput">E-posta / Kullanıcı adı</label>
        </div>
        <div class="form-floating">
            <input type="password" class="form-control" name="password" id="password" placeholder="Şifre" required>
            <label for="floatingPassword">Şifre</label>
            <div class="form-group mt-3">
                <a class="text-light-emphasis text-decoration-none" href="admin_reset_password.php">Şifremi unuttum</a>
            </div>
        </div>
        <div class="form-group mt-3">
            <button class="btn btn-primary w-100 py-2" type="submit">
                <i class="fas fa-sign-in-alt"></i> Oturum aç
            </button>
            <a href="<?php echo $siteUrl ?>" class="btn btn-secondary w-100 py-2 mt-2">
                <i class="fas fa-home"></i> <?php echo $siteName ?> - <?php echo $siteShortName ?>
            </a>
        </div>
    </form>
</main>
<?php
require_once "footer.php";
?>
