<?php
require_once "db_connection.php"; // Veritabanı bağlantısı

if (isset($_GET['email']) && isset($_GET['code'])) {
    $userEmail = $_GET['email'];
    $verificationCode = $_GET['code'];

    // Veritabanında doğrulama kodunu ve kullanıcının e-postasını kontrol et
    $query = "SELECT * FROM users WHERE email = ? AND verification_code = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$userEmail, $verificationCode]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Doğrulama işlemi başarılı, kullanıcıyı doğrulanmış olarak işaretle
        $verificationTime = date('Y-m-d H:i:s'); // Şu anki zamanı al
        $updateQuery = "UPDATE users SET verification_time = ? WHERE email = ?";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->execute([$verificationTime, $userEmail]);

        // Kullanıcının doğrulama işlemi başarılı, IP adresini kaydet
        $verificationIP = $_SERVER['REMOTE_ADDR'];
        $updateIPQuery = "UPDATE users SET verification_ip = ? WHERE email = ?";
        $updateIPStmt = $db->prepare($updateIPQuery);
        $updateIPStmt->execute([$verificationIP, $userEmail]);

        echo "Hesabınız başarıyla doğrulandı! IP adresiniz kaydedildi: $verificationIP";
    } else {
        echo "Geçersiz doğrulama bağlantısı.";
    }
} else {
    echo "Geçersiz doğrulama bağlantısı.";
}
?>
