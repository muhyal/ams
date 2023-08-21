<!DOCTYPE html>
<html>
<head>
    <title>Admin Girişi</title>
</head>
<body>
    <h1>Admin Girişi</h1>

    <form method="post" action="admin_login_process.php">
        <label for="username">Kullanıcı Adı:</label>
        <input type="text" id="username" name="username" required><br>

        <label for="password">Şifre:</label>
        <input type="password" id="password" name="password" required><br>

        <input type="submit" value="Giriş Yap">
    </form>
</body>
</html>
