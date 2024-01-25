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
require_once('../config/config.php');

// Oturum kontrolü
if (session_status() == PHP_SESSION_NONE) {
    session_start();
    // CSRF token oluşturma veya varsa alınması
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

$csrf_token = $_SESSION['csrf_token'];

require_once(__DIR__ . '/../config/db_connection.php');
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

// Hataları tutacak dizi
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // reCAPTCHA token'ını alma
    $recaptchaToken = $_POST['recaptcha_response'] ?? '';

    // reCAPTCHA doğrulama
    $recaptchaVerify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=" . RECAPTCHA_SECRET_KEY . "&response={$recaptchaToken}");
    $recaptchaResponse = json_decode($recaptchaVerify);

    // reCAPTCHA doğrulaması başarısızsa işlemi reddet
    if (!$recaptchaResponse->success) {
        $errors[] = "reCAPTCHA doğrulaması başarısız. İşlem reddedildi.";
    }

    // CSRF token kontrolü
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        $errors[] = "CSRF hatası! İşlem reddedildi.";
    }

    $identifier = htmlspecialchars($_POST["identifier"]); // Kullanıcı adı veya E-posta
    $password = htmlspecialchars($_POST["password"]);

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
                $errors[] = "Hesap pasif durumda. Giriş yapılamaz.";
            }

            if ($admin["deleted_at"]) {
                $errors[] = "Hesap silinmiş durumda. Giriş yapılamaz.";
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
                                $errors[] = 'SMS gönderim bekliyor.';
                            } else {
                                $errors[] = 'SMS başarıyla gönderildi.';
                            }
                        } catch (\Throwable $exception) {
                            $errors[] = 'SMS gönderimi başarısız. Hata: ' . $exception->getMessage();
                        }
                    }

                    // Set session variables and redirect to admin panel
                    $_SESSION["admin_id"] = $admin["id"];
                    $_SESSION["admin_username"] = $admin["username"];
                    $_SESSION["admin_first_name"] = $admin["first_name"];
                    $_SESSION["admin_last_name"] = $admin["last_name"];
                    $_SESSION["admin_type"] = $admin["user_type"];
                    header("Location: panel.php");
                    exit();
                } else {
                    $errors[] = "Bu alana giriş yapma yetkiniz yok!";
                }
            } else {
                $errors[] = "Hatalı giriş bilgileri.";
            }
        } else {
            $errors[] = "Hatalı giriş bilgileri.";
        }
    } catch (PDOException $e) {
        $errors[] = "Hata: " . $e->getMessage();
    } finally {
        // Veritabanı bağlantısını kapat
        $db = null;
    }
}
?>
<?php
require_once('../user/partials/header.php');
?>
<main class="form-signin w-100 m-auto">
    <form method="post" action="index.php">
        <!-- reCAPTCHA v3 için gizli alan -->
        <input type="hidden" name="recaptcha_response" id="recaptcha_response">

        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        <img id="logo-body" class="mb-5 mt-5" src="/assets/brand/default_logo_dark.png" alt="<?php echo $siteName ?>" title="<?php echo $siteName ?>" width="80%" height="%80">
        <h1 class="h3 mb-3 fw-normal">Yönetici Paneli</h1>
        <div class="form-floating">
            <input type="text" class="form-control" id="identifier" name="identifier" placeholder="@doremuzikakademi.com" autofocus="" required>
            <label for="floatingInput">E-posta / Kullanıcı adı</label>
        </div>
        <div class="password-container">
            <input type="password" class="form-control form-control-lg" name="password" id="password" placeholder="Şifre" required>
            <span class="eye-icon" onclick="togglePasswordVisibility()">
        <i class="bi bi-eye"></i>
    </span>
        </div>

        <div class="form-group mt-3">
            <a class="text-light-emphasis text-decoration-none" href="reset_password.php">Şifremi unuttum</a>
        </div>

        <style>
            .password-container {
                position: relative;
            }

            .eye-icon {
                position: absolute;
                right: 10px;
                top: 50%;
                transform: translateY(-50%);
                cursor: pointer;
            }
        </style>

        <script>
            // Toggle password visibility
            function togglePasswordVisibility() {
                const passwordInput = document.getElementById('password');
                const eyeIcon = document.querySelector('.eye-icon');

                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);

                // Change eye icon based on password visibility
                eyeIcon.innerHTML = type === 'password' ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
            }
        </script>

        <div class="form-group mt-3">
            <button class="btn btn-primary w-100 py-2" type="submit">
                <i class="fas fa-sign-in-alt"></i> Oturum aç
            </button>
            <a href="<?php echo $siteUrl ?>" class="btn btn-secondary w-100 py-2 mt-2">
                <i class="fas fa-home"></i> <?php echo $siteName ?> - <?php echo $siteShortName ?>
            </a>
        </div>
    </form>
    <?php
    foreach ($errors as $error) {
        echo "<div id='error-alert' class='alert alert-danger mt-3 mb-3' role='alert'>$error</div>";
    }
    ?>
</main>
<?php
require_once('../admin/partials/footer.php');
?>
