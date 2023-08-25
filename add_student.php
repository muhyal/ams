<?php
session_start();

// Oturum kontrolü
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php"); // Giriş sayfasına yönlendir
    exit();
}

// Veritabanı bağlantısı ve öğrenci ekleme işlemleri

?>

<!DOCTYPE html>
<html>
<head>
    <title>Öğrenci Ekleme</title>
</head>
<body>
<h1>Öğrenci Ekleme</h1>
<a href="student_list.php">Öğrenci Listesi</a>
<form action="process_add_student.php" method="post">
    <label for="firstname">Öğrenci Adı:</label>
    <input type="text" name="firstname" required><br>
    <label for="lastname">Öğrenci Soyadı:</label>
    <input type="text" name="lastname" required><br>
    <label for="tc_identity">TC Kimlik No:</label>
    <input type="text" name="tc_identity" required><br>
    <label for="phone">Cep Telefonu:</label>
    <input type="text" name="phone" required><br>
    <label for="email">E-posta Adresi:</label>
    <input type="email" name="email" required><br>
    <input type="submit" value="Ekle">
</form>
</body>
</html>
