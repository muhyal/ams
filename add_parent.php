<?php
global $db;
session_start();

// Oturum kontrolü
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php"); // Giriş sayfasına yönlendir
    exit();
}

// Veritabanı bağlantısı ve veli ekleme işlemleri burada gerçekleştirilecektir
require_once "db_connection.php"; // Veritabanı bağlantısı sağladığınız dosyanın adını buraya ekleyin

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $parentFirstname = $_POST["parent_firstname"];
    $parentLastname = $_POST["parent_lastname"];
    $parentTcIdentity = $_POST["parent_tc_identity"];
    $parentPhone = $_POST["parent_phone"];
    $parentEmail = $_POST["parent_email"];

    // Veli ekleme sorgusunu hazırlayın ve veritabanına ekleyin
    $insertParentQuery = "INSERT INTO parents (firstname, lastname, tc_identity, phone, email) VALUES (?, ?, ?, ?, ?)";
    $insertParentStmt = $db->prepare($insertParentQuery);
    $insertParentStmt->execute([$parentFirstname, $parentLastname, $parentTcIdentity, $parentPhone, $parentEmail]);

    // Veli ekleme işlemi tamamlandıktan sonra öğrenci ekleme sayfasına yönlendirin
    header("Location: add_student.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Veli Ekleme Formu</title>
</head>
<body>
<h1>Veli Ekleme Formu</h1>
<form action="add_parent.php" method="post">
    <label for="parent_firstname">Adı:</label>
    <input type="text" id="parent_firstname" name="parent_firstname" required><br><br>

    <label for="parent_lastname">Soyadı:</label>
    <input type="text" id="parent_lastname" name="parent_lastname" required><br><br>

    <label for="parent_tc_identity">TC Kimlik No:</label>
    <input type="text" id="parent_tc_identity" name="parent_tc_identity" required><br><br>

    <label for="parent_phone">Telefon:</label>
    <input type="text" id="parent_phone" name="parent_phone" required><br><br>

    <label for="parent_email">E-posta:</label>
    <input type="email" id="parent_email" name="parent_email" required><br><br>

    <button type="submit">Veli Ekle</button>
</form>
</body>
</html>
