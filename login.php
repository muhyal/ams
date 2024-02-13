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
global $showErrors, $db;
require_once(__DIR__ . '/config/db_connection.php');
require_once(__DIR__ . '/config/config.php');
require_once(__DIR__ . '/src/functions.php');

// Oturum kontrolü
session_start();
session_regenerate_id(true);

$option = getConfigurationFromDatabase($db);
extract($option, EXTR_IF_EXISTS);

// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // reCAPTCHA token'ını alma
    $recaptchaToken = $_POST['recaptcha_response'] ?? '';

    // reCAPTCHA doğrulama
    $recaptchaVerify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=" . $option['recaptcha_secret_key'] . "&response={$recaptchaToken}");
    $recaptchaResponse = json_decode($recaptchaVerify);

    // reCAPTCHA doğrulaması başarısızsa işlemi reddet
    if (!$recaptchaResponse->success) {
        $errorMessage = "reCAPTCHA doğrulaması başarısız. İşlem reddedildi.";
    }

    // CSRF token kontrolü
    if (!isset($_POST['csrf_request']) || $_POST['csrf_request'] !== '1') {
        $errorMessage = "CSRF hatası! İşlem reddedildi.";
    }

    $identifier = htmlspecialchars($_POST["identifier"]); // Kullanıcı adı veya E-posta
    $password = htmlspecialchars($_POST["password"]);

    // Form alanlarının doğrulaması
    if (empty($identifier) || empty($password)) {
        $errorMessage = "Eksik giriş bilgileri.";
    }

    // Check if the identifier is a valid email format
    $column = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? "email" : "username";
    $query = "SELECT * FROM users WHERE $column = ?";

    try {
        $stmt = $db->prepare($query);
        $stmt->execute([$identifier]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check if the user account is active
        if ($user && $user["is_active"] == 1) {
            // Check if the user is active
            if ($user["deleted_at"] !== null) {
                $errorMessage = "Bu hesap silinmiştir. Giriş yapılamaz.";
            } else {
                // Check if the password is correct
                if (password_verify($password, $user["password"])) {
                    // Kullanıcı girişi başarılı, oturum başlat
                    $_SESSION["user_id"] = $user["id"];
                    $_SESSION["user_type"] = $user["user_type"];

                    // Kullanıcı türüne göre yönlendirme yap
                    header("Location: /user/panel.php");
                    exit();
                } else {
                    // Kullanıcı doğrulanamadı, hata mesajı gösterme
                    $errorMessage = "Hatalı e-posta adresi ya da şifre girdiniz.";
                }
            }
        } else {
            $errorMessage = "Bu hesap şu anda pasiftir. Giriş yapılamaz.";
        }
    } catch (PDOException $e) {
        $errorMessage = "Hata: " . $e->getMessage();
    }

}
?>

<?php require_once(__DIR__ . '/user/partials/header.php'); ?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <!-- Uyarı Mesajları -->
            <?php
            if (isset($_GET["success_message"])) {
                echo '<div class="alert alert-success" id="success-alert" role="alert">' . htmlspecialchars($_GET["success_message"]) . '</div>';
            }
            if (isset($_GET["error_message"])) {
                echo '<div class="alert alert-danger" id="error-alert" role="alert">' . htmlspecialchars($_GET["error_message"]) . '</div>';
            }
            ?>
        </div>
    </div>
</div>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <!-- Uyarı Mesajları -->
            <?php
            if (isset($errorMessage)) {
                echo '<div class="alert alert-danger" id="error-alert" role="alert">' . htmlspecialchars($errorMessage) . '</div>';
            }
            ?>
        </div>
    </div>
</div>


<main class="form-signin w-100 m-auto">
    <form method="post" action="login.php">
        <!-- reCAPTCHA v3 için gizli alan -->
        <input type="hidden" name="recaptcha_response" id="recaptcha_response">

        <input type="hidden" name="csrf_request" value="1">

        <img id="logo-body" class="mb-5 mt-5" src="/assets/brand/default_logo_light.png" alt=" <?php echo $option['site_name']; ?>" title=" <?php echo $option['site_name']; ?>" width="80%" height="%80">
        <h1 class="h3 mb-3 fw-normal"><?= translate('user_panel', $selectedLanguage) ?></h1>

        <div class="form-floating">
            <input type="text" class="form-control" id="identifier" name="identifier" placeholder="<?= translate('email_username', $selectedLanguage) ?>" autofocus="" required>
            <label for="floatingInput"><?= translate('email_username', $selectedLanguage) ?></label>
        </div>

        <div class="form-floating">
            <input type="password" class="form-control" name="password" id="password" placeholder="<?= translate('password', $selectedLanguage) ?>" required>
            <label for="floatingInput"><?= translate('password', $selectedLanguage) ?></label>
            <span class="eye-icon" onclick="togglePasswordVisibility()">
                <i class="bi bi-eye"></i>
            </span>
        </div>

        <div class="form-group mt-3">
            <a class="text-light-emphasis text-decoration-none" href="reset_password.php"><?= translate('lost_password', $selectedLanguage) ?></a>
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
                <i class="fas fa-sign-in-alt"></i> <?= translate('login', $selectedLanguage) ?>
            </button>
            <a href="<?php echo $option['site_url']; ?>" class="btn btn-secondary w-100 py-2 mt-2">
                <i class="fas fa-home"></i>  <?php echo $option['site_name']; ?> -  <?php echo $option['site_short_name']; ?>
            </a>
        </div>
    </form>
</main>

<?php require_once(__DIR__ . '/user/partials/footer.php'); ?>
