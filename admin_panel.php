<?php
session_start();

// Oturum kontrolü
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php"); // Giriş sayfasına yönlendir
    exit();
}

// Kullanıcı bilgilerini kullanabilirsiniz
$admin_id = $_SESSION["admin_id"];
$admin_username = $_SESSION["admin_username"];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Paneli</title>
</head>
<body>
    <h1>Admin Paneli - Hoş Geldiniz, <?php echo $admin_username; ?></h1>

    <!-- Admin paneli içeriği burada -->

    <a href="logout.php">Çıkış Yap</a>
</body>
</html>
