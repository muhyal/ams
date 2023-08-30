<?php
global $db, $config;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

session_start();
require_once "db_connection.php"; // Veritabanı bağlantısı
// Load Composer's autoloader
require 'vendor/autoload.php';
require 'config.php';

if (isset($_POST["reset_request"])) {
    // Şifre sıfırlama talebi gönderildiğinde
    $email = $_POST["email"];

    // Veritabanında kullanıcıyı e-posta adresine göre ara
    $query = "SELECT * FROM users WHERE email = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Belirlediğiniz zaman dilimini ayarlayın
        date_default_timezone_set('Europe/Istanbul');

        // Şifre sıfırlama için token oluştur
        $token = bin2hex(random_bytes(32));
        $tokenExpiry = date("Y-m-d H:i:s", strtotime("+1 hour"));

        // Token'ı ve süresini veritabanına kaydet
        $updateQuery = "UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->execute([$token, $tokenExpiry, $user["id"]]);

        // Token ile şifre sıfırlama bağlantısı oluştur
        $resetLink = "reset_password.php?token=" . $token;

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
            $mail->ContentType = $config['smtp']['contentType'];

            // E-posta ayarları
            $mail->setFrom($config['smtp']['username'], 'OİM');
            $mail->addAddress($email, $user["firstname"]); // Alıcı adresi ve adı

            $mail->isHTML(true);
            $mail->Subject = 'Şifre Sıfırlama Talebi';
            $mail->Body    = "Şifrenizi sıfırlamak için aşağıdaki bağlantıya tıklayın:<br><a href='$resetLink'>$resetLink</a>";

            $mail->send();

            echo "Şifre sıfırlama bağlantısı e-posta adresinize gönderildi.";
        } catch (Exception $e) {
            echo "E-posta gönderilirken bir hata oluştu: {$mail->ErrorInfo}";
        }
    } else {
        echo "Bu e-posta adresine sahip bir kullanıcı bulunamadı.";
    }

} elseif (isset($_GET["token"])) {
    // Token ile gelen şifre sıfırlama isteği
    $token = $_GET["token"];

    // Token'ın geçerliliğini kontrol et
    $query = "SELECT * FROM users WHERE reset_token = ? AND reset_token_expiry >= NOW()";
    $stmt = $db->prepare($query);
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Form gönderildiğinde yeni şifreyi güncelle
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $newPassword = $_POST["new_password"];
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            // Şifreyi güncelle
            $updateQuery = "UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->execute([$hashedPassword, $user["id"]]);

            echo "Şifreniz başarıyla güncellendi ve giriş ekranına yönlendiriliyorsunuz...";
            header("refresh:3;url=user_login.php"); // 3 saniye sonra login.php'ye yönlendirme
        }
    } else {
        echo "Geçersiz veya süresi dolmuş bir şifre sıfırlama bağlantısı.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Şifre Sıfırlama</title>
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
