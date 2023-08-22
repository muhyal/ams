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
    <title>Yönetici Paneli</title>
</head>
<body>
    <h1>Yönetici Paneli - Hoş Geldiniz, <?php echo $admin_username; ?></h1>

    <!-- Yönetici paneli içeriği burada -->
    <a href="register.php">Kullanıcı Kaydet</a><br>
    <a href="user_list.php">Kullanıcı Listesi</a><br>
    <a href="delete_user.php">Kullanıcı Sil</a><br>
    <a href="edit_user.php">Kullanıcı Düzenle</a><br>
    <a href="admin_register.php">Yönetici Kaydet</a><br>
    <a href="agreement.php">Sözleşmeleri Görüntüle</a><br>
    <a href="logout.php">Çıkış Yap</a>
</body>
</html>
