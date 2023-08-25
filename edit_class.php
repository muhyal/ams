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
    $classId = $_GET['id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Düzenlenmiş sınıf bilgilerini al
        $className = $_POST['class_name'];
        $classDescription = $_POST['class_description'];

        // Sınıf bilgilerini güncelle
        $updateQuery = "UPDATE classes SET class_name = ?, class_description = ? WHERE id = ?";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->execute([$className, $classDescription, $classId]);

        header("Location: class_list.php");
        exit;
    }

    // Sınıf bilgilerini veritabanından çek
    $query = "SELECT * FROM classes WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$classId]);
    $classData = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    header("Location: class_list.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sınıf Düzenle</title>
</head>
<body>
<h1>Sınıf Düzenle</h1>
<form method="post">
    <label for="class_name">Sınıf Adı:</label>
    <input type="text" name="class_name" value="<?php echo $classData['class_name']; ?>"><br>

    <label for="class_code">Sınıf Kodu:</label>
    <input type="text" name="class_code" value="<?php echo $classData['class_code']; ?>"><br>

    <label for="class_description">Sınıf Açıklaması:</label>
    <textarea name="class_description"><?php echo $classData['class_description']; ?></textarea><br>

    <button type="submit">Kaydet</button>
</form>
</body>
</html>
