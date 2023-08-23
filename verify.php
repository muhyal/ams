<?php
global $db;
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "db_connection.php"; // Veritabanı bağlantısı

if ((isset($_GET['email'])  || isset($_GET['phone']) )&& isset($_GET['code'])) {
	$userSmsData=null;
	$userEmailData=null;
	    $verificationCode = $_GET['code'];
	if(isset($_GET['phone'])){
		 $userEmail = $_GET['phone'];
		   // SMS doğrulaması için sorgu
    $querySms = "SELECT * FROM users WHERE phone = ? AND verification_code_sms = ?";
    $stmtSms = $db->prepare($querySms);
    $stmtSms->execute([$userEmail, $verificationCode]);
    $userSmsData = $stmtSms->fetch(PDO::FETCH_ASSOC);
	}else{
		  $userEmail = $_GET['email'];
		    // E-posta doğrulaması için sorgu
    $queryEmail = "SELECT * FROM users WHERE email = ? AND verification_code_email = ?";
    $stmtEmail = $db->prepare($queryEmail);
    $stmtEmail->execute([$userEmail, $verificationCode]);
    $userEmailData = $stmtEmail->fetch(PDO::FETCH_ASSOC);
	}
  




 

    if ($userEmailData !==null) {
        $verificationTime = date('Y-m-d H:i:s'); // Şu anki zamanı al
        $verificationIP = $_SERVER['REMOTE_ADDR'];

        // E-posta doğrulaması başarılı
        if ($userEmailData['verification_code_email'] == $verificationCode) {
            $updateQuery = "UPDATE users SET verification_time_email_confirmed = ?, verification_ip_email = ? WHERE email = ?";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->execute([$verificationTime, $verificationIP, $userEmail]);
            echo "E-posta doğrulaması başarılı! IP adresiniz kaydedildi: $verificationIP";
        }
    } elseif ($userSmsData !== null) {
        $verificationTime = date('Y-m-d H:i:s'); // Şu anki zamanı al
        $verificationIP = $_SERVER['REMOTE_ADDR'];

        // SMS doğrulaması başarılı
        if ($userSmsData['verification_code_sms'] == $verificationCode) {
            $updateQuery = "UPDATE users SET verification_time_sms_confirmed = ?, verification_ip_sms = ? WHERE phone = ?";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->execute([$verificationTime, $verificationIP, $userSmsData['phone']]);
            echo "SMS doğrulaması başarılı! IP adresiniz kaydedildi: $verificationIP";
        }
    } else {
        // Her iki yöntemle de doğrulama başarısız
        echo "Geçersiz doğrulama bağlantısı.";
    }
} else {
    echo "Geçersiz doğrulama bağlantısı.";
}

// Aynı sayfa üzerinden tıklanabilir linkler gösterilirse kullanıcıya daha iyi bir deneyim sunabilirsiniz.
if (isset($userEmailData)) {
    echo '<br><a href="verify.php?email='.$userEmail.'&code='.$verificationCode.'">E-posta doğrulamasını tekrarla</a>';
}
if (isset($userSmsData)) {
    echo '<br><a href="verify.php?phone='.$userSmsData['phone'].'&code='.$verificationCode.'">SMS doğrulamasını tekrarla</a>';
}
?>
