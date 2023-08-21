<?php
require_once "db_connection.php"; // Veritabanı bağlantısı

// Oturum kontrolü
//if (!isset($_SESSION["admin_id"])) {
//    header("Location: admin_login.php"); // Giriş sayfasına yönlendir
//    exit();
//}

// Kullanıcıları veritabanından çekme
$query = "SELECT * FROM users";
$stmt = $db->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Kullanıcı Listesi</title>
</head>
<body>
    <h2>Kullanıcı Listesi</h2>
    <table>
        <tr>
            <th>Ad</th>
            <th>Soyad</th>
            <th>E-posta</th>
            <th>TC Kimlik No</th>
            <th>Telefon</th>
            <th>E-posta Doğrulama IP</th>
            <th>SMS Doğrulama IP</th>
            <th>E-posta Doğrulama Durumu</th>
            <th>SMS Doğrulama Durumu</th>
            <th>E-posta Doğrulama Gönderim Zamanı</th>
            <th>SMS Doğrulama Gönderim Zamanı</th>
             <th>E-posta Doğrulama Zamanı</th>
            <th>SMS Doğrulama Zamanı</th>
        </tr>
        <?php foreach ($users as $user): ?>
        <tr>
            <td><?= $user['firstname'] ?></td>
            <td><?= $user['lastname'] ?></td>
            <td><?= $user['email'] ?></td>
            <td><?= $user['tc'] ?></td>
            <td><?= $user['phone'] ?></td>
            <td><?= $user['verification_ip_email'] ?></td>
            <td><?= $user['verification_ip_sms'] ?></td>
            <td><?= $user['verification_time_email_confirmed'] ? 'Doğrulandı' : 'Doğrulanmadı' ?></td>
            <td><?= $user['verification_time_sms_confirmed'] ? 'Doğrulandı' : 'Doğrulanmadı' ?></td>
            <td><?= $user['verification_time_email_sent'] ?></td>
            <td><?= $user['verification_time_sms_sent'] ?></td>
            <td><?= $user['verification_time_email_confirmed'] ?></td>
            <td><?= $user['verification_time_sms_confirmed'] ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
