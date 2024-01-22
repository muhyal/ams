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

// Oturum kontrolü
session_start();
session_regenerate_id(true);

// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // reCAPTCHA token'ını alma
    $recaptchaToken = $_POST['recaptcha_response'] ?? '';

    // reCAPTCHA doğrulama
    $recaptchaVerify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=" . RECAPTCHA_SECRET_KEY . "&response={$recaptchaToken}");
    $recaptchaResponse = json_decode($recaptchaVerify);

    // reCAPTCHA doğrulaması başarısızsa işlemi reddet
    if (!$recaptchaResponse->success) {
        die("reCAPTCHA doğrulaması başarısız. İşlem reddedildi.");
    }

    // CSRF token kontrolü
    if (!isset($_POST['csrf_request']) || $_POST['csrf_request'] !== '1') {
        die("CSRF hatası! İşlem reddedildi.");
    }

    $identifier = htmlspecialchars($_POST["identifier"]); // Kullanıcı adı veya E-posta
    $password = htmlspecialchars($_POST["password"]);

    // Form alanlarının doğrulaması
    if (empty($identifier) || empty($password)) {
        die("Eksik giriş bilgileri.");
    }

    // Check if the identifier is a valid email format
    $column = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? "email" : "username";
    $query = "SELECT * FROM users WHERE $column = ?";

    try {
        $stmt = $db->prepare($query);
        $stmt->execute([$identifier]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user["password"])) {
            // Check if the user is active
            if ($user["is_active"] == 0) {
                echo '<div class="alert alert-danger" role="alert">
            Bu hesap şu anda pasiftir. Giriş yapılamaz.
        </div>';
            } elseif ($user["deleted_at"] !== null) {
                echo '<div class="alert alert-danger" role="alert">
            Bu hesap silinmiştir. Giriş yapılamaz.
        </div>';
            } else {
                // Kullanıcı girişi başarılı, oturum başlat
                $_SESSION["user_id"] = $user["id"];
                $_SESSION["user_type"] = $user["user_type"];

                // Kullanıcı türüne göre yönlendirme yap
                header("Location: /user/panel.php");
                exit();
            }
        } else {
            // Kullanıcı doğrulanamadı, hata mesajı gösterme
            echo '<div class="alert alert-danger" role="alert">
        Hatalı e-posta adresi ya da şifre girdiniz.
    </div>';
        }
    } catch (PDOException $e) {
        echo "Hata: " . $e->getMessage();
    }
    // Veritabanı bağlantısını kapat
    $db = null;
}
?>

<?php require_once(__DIR__ . '/user/partials/header.php'); ?>

<main class="form-signin w-100 m-auto">
    <form method="post" action="login.php">
        <!-- reCAPTCHA v3 için gizli alan -->
        <input type="hidden" name="recaptcha_response" id="recaptcha_response">

        <input type="hidden" name="csrf_request" value="1">

        <img id="logo-body" class="mb-5 mt-5" src="/assets/brand/default_logo_light.png" alt="<?php echo $siteName ?>" title="<?php echo $siteName ?>" width="80%" height="%80">
        <h1 class="h3 mb-3 fw-normal">Kullanıcı Paneli</h1>
        <div class="form-floating">
            <input type="text" class="form-control" id="identifier" name="identifier" placeholder="E-posta / Kullanıcı adı" autofocus="" required>
            <label for="floatingInput">E-posta / Kullanıcı adı</label>
        </div>
        <div class="form-floating">
            <input type="password" class="form-control" name="password" id="password" placeholder="Şifre" required>
            <label for="floatingPassword">Şifre</label>
            <div class="form-group mt-3">
                <a class="text-light-emphasis text-decoration-none" href="reset_password.php">Şifremi unuttum</a>
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

<?php require_once(__DIR__ . '/user/partials/footer.php'); ?>
