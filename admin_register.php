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
    <title>Admin Kaydı</title>
</head>
<body>
    <h1>Admin Kaydı</h1>

    <form method="post" action="admin_register_process.php">
        <label for="username">Kullanıcı Adı:</label>
        <input type="text" id="username" name="username" required><br>

        <label for="email">E-posta:</label>
        <input type="email" id="email" name="email" required><br>

        <label for="password">Şifre:</label>
        <input type="password" id="password" name="password" required><br>

        <input type="submit" value="Kaydet">
    </form>
</body>
</html>
