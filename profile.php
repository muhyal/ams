<?php
global $db, $showErrors, $siteName, $siteShortName, $siteUrl;
// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);
require_once "config.php";

// Oturum kontrolü
session_start();
session_regenerate_id(true);

// Oturum kontrolü
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php"); // Giriş sayfasına yönlendir
    exit();
}

require_once "db_connection.php";
require_once "config.php";

// Profil türünü alın (student, teacher, user vb.)
$profileType = $_GET['type']; // URL'den türü alabilir veya başka bir yöntem kullanabilirsiniz.

// Profil ID'sini alın (örneğin, student_id, teacher_id, user_id)
$profileID = $_GET['id']; // URL'den ID'yi alabilir veya başka bir yöntem kullanabilirsiniz.

// Profil türüne göre sorgu oluşturun ve veritabanından ilgili profil bilgilerini alın
$query = "";
$bindings = [];

switch ($profileType) {
    case 'student':
        $query = "SELECT * FROM students WHERE id = ?";
        $bindings = [$profileID];
        break;
    case 'teacher':
        $query = "SELECT * FROM teachers WHERE id = ?";
        $bindings = [$profileID];
        break;
    case 'user':
        $query = "SELECT * FROM users WHERE id = ?";
        $bindings = [$profileID];
        break;
    // Diğer profiller için gerekirse case'ler ekleyebilirsiniz.
}

$stmt = $db->prepare($query);
$stmt->execute($bindings);
$profileData = $stmt->fetch(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil (Test)</title>
</head>
<body>

<!-- Profil bilgilerini gösterme bölümü -->
<div>
    <h1><?= $profileData['firstname'] . ' ' . $profileData['lastname'] ?> Profili</h1>
    <!-- Diğer profil bilgilerini gösterme -->
    <p>Email: <?= $profileData['email'] ?></p>
    <p>Telefon: <?= $profileData['phone'] ?></p>
    <!-- vb. -->
</div>

<!-- Profille ilgili diğer bilgiler -->

</body>
</html>
