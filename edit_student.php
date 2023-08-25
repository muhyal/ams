<?php
session_start();

// Oturum kontrolü
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php"); // Giriş sayfasına yönlendir
    exit();
}
global $db;
require_once "db_connection.php";

if (isset($_GET['id'])) {
    $studentId = $_GET['id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $firstname = $_POST['firstname'];
        $lastname = $_POST['lastname'];
        $tc_identity = $_POST['tc_identity'];
        $phone = $_POST['phone'];
        $email = $_POST['email'];

        // Öğrenciyi veritabanında güncelleme işlemi
        $query = "UPDATE students SET firstname = ?, lastname = ?, tc_identity = ?, phone = ?, email = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$firstname, $lastname, $tc_identity, $phone, $email, $studentId]);

        header("Location: student_list.php");
        exit;
    }

    // Öğrenci bilgilerini veritabanından çekme işlemi
    $query = "SELECT * FROM students WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$studentId]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    header("Location: student_list.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Öğrenci Düzenle</title>
</head>
<body>
<h1>Öğrenci Düzenle</h1>
<form method="post">
    <label for="firstname">Adı:</label>
    <input type="text" name="firstname" value="<?php echo $student['firstname']; ?>"><br>

    <label for="lastname">Soyadı:</label>
    <input type="text" name="lastname" value="<?php echo $student['lastname']; ?>"><br>

    <label for="tc_identity">TC Kimlik No:</label>
    <input type="text" name="tc_identity" value="<?php echo $student['tc_identity']; ?>"><br>

    <label for="phone">Cep Telefonu:</label>
    <input type="text" name="phone" value="<?php echo $student['phone']; ?>"><br>

    <label for="email">E-posta:</label>
    <input type="email" name="email" value="<?php echo $student['email']; ?>"><br>

    <button type="submit">Kaydet</button>
</form>
</body>
</html>
