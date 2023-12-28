<?php
global $resetPasswordDescription, $db, $showErrors, $siteName, $siteShortName, $siteUrl, $config;
require_once "db_connection.php";
require_once "config.php";
// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

require 'vendor/autoload.php';

if (isset($_POST["reset_request"])) {
    // Şifre sıfırlama talebi gönderildiğinde
    $email = $_POST["email"];

    // Veritabanında admini e-posta adresine göre ara
    $query = "SELECT * FROM admins WHERE email = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin) {
        // Belirlediğiniz zaman dilimini ayarlayın
        date_default_timezone_set('Europe/Istanbul');

        // Şifre sıfırlama için token oluştur
        $token = bin2hex(random_bytes(32));
        $tokenExpiry = date("Y-m-d H:i:s", strtotime("+1 hour"));

        // Token'ı ve süresini veritabanına kaydet
        $updateQuery = "UPDATE admins SET reset_token = ?, reset_token_expiry = ? WHERE id = ?";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->execute([$token, $tokenExpiry, $admin["id"]]);

        // Token ile şifre sıfırlama bağlantısı oluştur
        $resetLink = "admin_reset_password.php?token=" . $token;

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
            $mail->Body    = "Merhaba, eğer bu şifre sıfırlama isteğini siz talep ettiyseniz, <a href='$siteUrl/$resetLink'>şifrenizi sıfırlamak için tıklayın</a>. Siz talep etmediyseniz farklı bir işlem yapmanız gerekmeyecektir.";

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
    $token = $_GET["token"];

    // Token'ın geçerliliğini kontrol et
    $query = "SELECT * FROM admins WHERE reset_token = ? AND reset_token_expiry >= NOW()";
    $stmt = $db->prepare($query);
    $stmt->execute([$token]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin) {
        // Form gönderildiğinde yeni şifreyi güncelle
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $newPassword = $_POST["new_password"];
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            // Şifreyi güncelle
            $updateQuery = "UPDATE admins SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?";
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
<?php
require_once "admin_panel_header.php";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Şifre Sıfırlama (Yönetici)</title>
</head>
<body>
<?php if (!isset($_GET["token"])): ?>
    <!-- Şifre sıfırlama talebi gönderme formu -->
    <form method="post" action="">
        <label for="email">E-posta:</label>
        <input type="email" id="email" name="email" required>
        <input type="submit" name="reset_request" value="Şifre Sıfırlama Talebi Gönder">
    </form>
<?php else: ?>
    <!-- Yeni şifre belirleme formu -->
    <form method="post" action="">
        <label for="new_password">Yeni Şifre:</label>
        <input type="password" id="new_password" name="new_password" required>
        <input type="submit" value="Şifreyi Güncelle">
    </form>
<?php endif; ?>
</body>
</html>
