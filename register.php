<?php
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

// Oturum kontrolü
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php"); // Giriş sayfasına yönlendir
    exit();
}

require_once "db_connection.php"; // Veritabanı bağlantısı
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'config.php'; // SMTP ayarlarını içeren dosya


use Infobip\Infobip;
use Infobip\Api\Configuration;
use Infobip\Api\SendSingleTextualSms;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tc = $_POST["tc"];
    $firstname = $_POST["firstname"];
    $lastname = $_POST["lastname"];
    $email = $_POST["email"];
    $phone = $_POST["phone"];
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);

    // Kullanıcının daha önce kayıtlı olup olmadığını kontrol et
    $queryCheck = "SELECT * FROM users WHERE email = ? OR tc = ? OR phone = ?";
    $stmtCheck = $db->prepare($queryCheck);
    $stmtCheck->execute([$email, $tc, $phone]);
    $existingUser = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($existingUser) {
        echo "Bu e-posta, TC kimlik numarası veya telefon numarası zaten kayıtlı!";
    } else {
        // Yeni kayıt işlemi
        $verificationCode = generateVerificationCode(); // Rastgele doğrulama kodu oluştur

        $insertQuery = "INSERT INTO users (tc, firstname, lastname, email, phone, password, verification_code) VALUES (?, ?, ?, ?, ?, ?, ?)";

        try {
            $stmt = $db->prepare($insertQuery);
            $stmt->execute([$tc, $firstname, $lastname, $email, $phone, $password, $verificationCode]);

            // E-posta gönderme işlemi
    sendVerificationEmail($email, $verificationCode);

            echo "Kullanıcı kaydedildi, doğrulama e-postası gönderildi.";
        } catch (PDOException $e) {
            echo "Hata: " . $e->getMessage();
        }
    }
}

// Rastgele doğrulama kodu oluşturma fonksiyonu
function generateVerificationCode() {
    return mt_rand(100000, 999999); // Örnek: 6 haneli rastgele kod
}

// E-posta gönderme fonksiyonu
function sendVerificationEmail($to, $verificationCode) {
    global $config; // Global olarak config dosyasını kullanabilmek için

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

        // E-posta ayarları
        $mail->setFrom($fromAddress, $fromName);
        $mail->addAddress($to);

        $mail->Subject = 'Hesap Doğrulama';
        $mail->Body = "Hesabınızı doğrulamak için aşağıdaki bağlantıya tıklayınız: " . getVerificationLink($to, $verificationCode);

        // E-postayı gönder
        $mail->send();
    } catch (Exception $e) {
        // E-posta gönderimi hatası
        echo "E-posta gönderimi başarısız oldu. Hata: {$mail->ErrorInfo}";
    }
}

// Doğrulama bağlantısı oluşturma
function getVerificationLink($email, $code) {
    return "http://oim/verify.php?email=$email&code=$code";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Kayıt Formu</title>
</head>
<body>
    <h2>Kayıt Formu</h2>
    <form method="post" action="">
        <label for="tc">TC Kimlik No:</label>
        <input type="text" name="tc" required><br>
        <label for="firstname">Ad:</label>
        <input type="text" name="firstname" required><br>
        <label for="lastname">Soyad:</label>
        <input type="text" name="lastname" required><br>
        <label for="email">E-posta:</label>
        <input type="email" name="email" required><br>
        <label for="phone">Telefon:</label>
        <input type="text" name="phone" required><br>
        <label for="password">Şifre:</label>
        <input type="

password" name="password" required><br>
        <input type="submit" value="Kayıt Ol">
    </form>
</body>
</html>
