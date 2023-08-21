<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "db_connection.php"; // Veritabanı bağlantısı

if (isset($_GET['email']) && isset($_GET['code'])) {
    $userEmail = $_GET['email'];
    $verificationCode = $_GET['code'];

    // Veritabanında doğrulama kodlarını ve kullanıcının e-postasını kontrol et
    $query = "SELECT * FROM users WHERE email = ? AND (verification_code_email = ? OR verification_code_sms = ?)";
    $stmt = $db->prepare($query);
    $stmt->execute([$userEmail, $verificationCode, $verificationCode]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $verificationTime = date('Y-m-d H:i:s'); // Şu anki zamanı al

        // Doğrulama kodunun eşleştiği doğrulama yöntemine göre zaman damgasını ve IP adresini güncelle
        if ($user['verification_code_email'] == $verificationCode) {
            $updateQuery = "UPDATE users SET verification_time_email_confirmed = ?, verification_ip_email = ? WHERE email = ?";
        } elseif ($user['verification_code_sms'] == $verificationCode) {
            $updateQuery = "UPDATE users SET verification_time_sms_confirmed = ?, verification_ip_sms = ? WHERE email = ?";
        }

        $verificationIP = $_SERVER['REMOTE_ADDR'];

        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->execute([$verificationTime, $verificationIP, $userEmail]);


        echo "Hesabınız başarıyla doğrulandı! IP adresiniz kaydedildi: $verificationIP";
    } else {
        echo "Geçersiz doğrulama bağlantısı.";
    }
} else {
    echo "Geçersiz doğrulama bağlantısı.";
}


// Aynı sayfa üzerinden tıklanabilir linkler gösterilirse kullanıcıya daha iyi bir deneyim sunabilirsiniz.
echo '<br><a href="verify.php?email='.$userEmail.'&code='.$verificationCode.'">Buraya tıklayarak doğrulamayı tekrarla</a>';
?>
