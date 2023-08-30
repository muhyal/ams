<?php
session_start();

$allowedRoles = array(1); // "sa" rolü için rol değeri (örneğin 1)
$currentUserRole = $_SESSION['admin_role'];

if (!in_array($currentUserRole, $allowedRoles)) {
    header("Location: access_denied.php");
    exit;
}

// Kullanıcı bilgilerini kullanabilirsiniz
$admin_id = $_SESSION["admin_id"];
$admin_username = $_SESSION["admin_username"];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Yönetici Kaydı</title>
</head>
<body>
    <h1>Yönetici Kaydı</h1>

    <form method="post" action="admin_register_process.php">
        <label for="username">Yönetici Kullanıcı Adı:</label>
        <input type="text" id="username" name="username" required><br>

        <label for="email">E-posta:</label>
        <input type="email" id="email" name="email" required><br>

        <label for="password">Şifre:</label>
        <input type="password" id="password" name="password" required><br>

        <input type="submit" value="Kaydet">
    </form>
</body>
</html>
