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

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

global $resetPasswordDescription, $db, $showErrors, $siteName, $siteShortName, $siteUrl, $config;

require_once "config/db_connection.php";
require_once "config/config.php";
require 'vendor/autoload.php';

// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);

if (isset($_POST["reset_request"])) {
    // Şifre sıfırlama talebi gönderildiğinde
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

    if ($email === false) {
        die("Geçersiz e-posta adresi.");
    }

    // Veritabanında admini e-posta adresine göre ara
    $query = "SELECT * FROM users WHERE email = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin) {
        // Şifre sıfırlama için token oluştur
        $token = bin2hex(random_bytes(32));
        $tokenExpiry = date("Y-m-d H:i:s", strtotime("+1 hour"));

        // Token'ı ve süresini veritabanına kaydet
        $updateQuery = "UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->execute([$token, $tokenExpiry, $admin["id"]]);

        // Token ile şifre sıfırlama bağlantısı oluştur
        $resetLink = "admin_reset_password.php?token=" . $token;

        // reCAPTCHA token'ını alma
        $recaptchaToken = $_POST['recaptcha_response'] ?? '';

        // reCAPTCHA doğrulama
        $recaptchaVerify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=" . RECAPTCHA_SECRET_KEY . "&response={$recaptchaToken}");
        $recaptchaResponse = json_decode($recaptchaVerify);

        // reCAPTCHA doğrulaması başarısızsa işlemi reddet
        if (!$recaptchaResponse->success) {
            die("reCAPTCHA doğrulaması başarısız. İşlem reddedildi.");
        }

        // E-posta gönderme işlemi
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
            $mail->addAddress($email, $admin["username"]); // Alıcı adresi ve adı

            $mail->isHTML(true);
            $mail->Subject = '=?UTF-8?B?' . base64_encode('Yönetici Şifre Sıfırlama Talebi') . '?='; // Encode subject in UTF-8
            $mail->Body = "Merhaba, eğer bu şifre sıfırlama isteğini siz talep ettiyseniz, <a href='$siteUrl/$resetLink'>şifrenizi sıfırlamak için tıklayın</a>. Siz talep etmediyseniz farklı bir işlem yapmanız gerekmeyecektir.";

            $mail->send();

            echo "Şifre sıfırlama bağlantısı e-posta adresinize gönderildi.";
        } catch (Exception $e) {
            echo "E-posta gönderilirken bir hata oluştu: {$mail->ErrorInfo}";
        }
    } else {
        echo "Bu e-posta adresine sahip bir yönetici bulunamadı.";
    }
} elseif (isset($_GET["token"])) {
    // Token ile gelen şifre sıfırlama isteği
    $token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_STRING);

    if ($token === false) {
        die("Geçersiz token.");
    }

    // Token'ın geçerliliğini kontrol et
    $query = "SELECT * FROM users WHERE reset_token = ? AND reset_token_expiry >= NOW()";
    $stmt = $db->prepare($query);
    $stmt->execute([$token]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin) {
        // Form gönderildiğinde yeni şifreyi güncelle
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $newPassword = filter_input(INPUT_POST, 'new_password', FILTER_SANITIZE_STRING);

            if ($newPassword === false) {
                die("Geçersiz şifre.");
            }

            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            // Şifreyi güncelle
            $updateQuery = "UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->execute([$hashedPassword, $admin["id"]]);

            echo "Şifreniz başarıyla güncellendi ve giriş ekranına yönlendiriliyorsunuz...";
            header("refresh:3;url=admin_login.php"); // 3 saniye sonra admin_login.php'ye yönlendirme
        }
    } else {
        echo "Geçersiz veya süresi dolmuş bir şifre sıfırlama bağlantısı.";
    }
}
?>

<?php require_once "header.php"; ?>

<div class="container mt-5">
    <?php if (!isset($_GET["token"])): ?>
        <!-- Şifre sıfırlama talebi gönderme formu -->
        <div class="row justify-content-center">
            <div class="col-md-6">
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="email" class="form-label">E-posta:</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <!-- reCAPTCHA v3 için gizli alan -->
                        <input type="hidden" name="recaptcha_response" id="recaptcha_response">
                    </div>
                    <div class="form-group mt-3">
                        <button class="btn btn-primary w-100 py-2" name="reset_request" type="submit">
                            <i class="fas fa-sign-in-alt"></i> Şifre Sıfırlama Talebi Gönder
                        </button>
                    </div>
                    <div class="form-group mt-2">
                        <a href="<?php echo $siteUrl ?>" class="btn btn-secondary w-100 py-2">
                            <i class="fas fa-home"></i> <?php echo $siteName ?> - <?php echo $siteShortName ?>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    <?php else: ?>
        <!-- Yeni şifre belirleme formu -->
        <div class="row justify-content-center">
            <div class="col-md-6">
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Yeni Şifre:</label>
                        <input type="password" id="new_password" name="new_password" class="form-control" required>
                    </div>
                    <div class="form-group mt-3">
                        <input type="submit" class="btn btn-primary w-100 py-2" value="Şifreyi Güncelle">
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once "footer.php"; ?>
