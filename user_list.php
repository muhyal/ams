<?php
global $db;
session_start();

// Oturum kontrolü
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php"); // Giriş sayfasına yönlendir
    exit();
}

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
            <th>No</th>
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
            <td><?= $user['id'] ?></td>
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
            <td>
                <a href="delete_user.php?id=<?php echo $user["id"]; ?>">Sil</a>
            </td>
            <td>
                <a href="edit_user.php?id=<?php echo $user["id"]; ?>">Düzenle</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <p><button onclick="history.back()">Geri Dön</button></p>
    <p><a href="register.php">Kullanıcı ekle</a></p>
</body>
</html>
