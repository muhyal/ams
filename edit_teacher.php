<?php
global $db;
require_once "db_connection.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST["id"];
    $firstName = $_POST["first_name"];
    $lastName = $_POST["last_name"];
    $birthDate = $_POST["birth_date"];
    $phone = $_POST["phone"];
    $email = $_POST["email"];

    // Veritabanındaki öğretmeni güncelle
    $query = "UPDATE teachers SET first_name = ?, last_name = ?, birth_date = ?, phone = ?, email = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$firstName, $lastName, $birthDate, $phone, $email, $id]);
}

// Öğretmen bilgilerini al
if (isset($_GET["edit_id"])) {
    $editId = $_GET["edit_id"];
    $editTeacher = $db->query("SELECT * FROM teachers WHERE id = $editId")->fetch(PDO::FETCH_ASSOC);
}

// Türkçe tarih biçimine çevirme
$birthDate = $editTeacher["birth_date"] ? date("d-m-Y", strtotime($editTeacher["birth_date"])) : "";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Öğretmen Düzenleme</title>
</head>
<body>
<h1>Öğretmen Düzenleme</h1>
<form method="post">
    <input type="hidden" name="id" value="<?php echo $editTeacher["id"]; ?>">
    <label for="first_name">Adı:</label>
    <input type="text" id="first_name" name="first_name" value="<?php echo $editTeacher["first_name"]; ?>" required><br>
    <label for="last_name">Soyadı:</label>
    <input type="text" id="last_name" name="last_name" value="<?php echo $editTeacher["last_name"]; ?>" required><br>
    <label for="birth_date">Doğum Tarihi:</label>
    <input type="date" id="birth_date" name="birth_date" value="<?php echo $editTeacher["birth_date"]; ?>" required><br>
    <label for="phone">Telefon:</label>
    <input type="tel" id="phone" name="phone" value="<?php echo $editTeacher["phone"]; ?>"><br>
    <label for="email">E-posta:</label>
    <input type="email" id="email" name="email" value="<?php echo $editTeacher["email"]; ?>" required><br>
    <button type="submit" name="edit_teacher">Öğretmeni Düzenle</button>
</form>
<a href="teachers_list.php">Öğretmen Listesi</a>
</body>
</html>
