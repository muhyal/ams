<?php
global $db;
require_once "db_connection.php";

if (isset($_GET['id'])) {
    $classId = $_GET['id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Sınıfı sil
        $deleteQuery = "DELETE FROM classes WHERE id = ?";
        $deleteStmt = $db->prepare($deleteQuery);
        $deleteStmt->execute([$classId]);

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
    <title>Sınıf Sil</title>
</head>
<body>
<h1>Silmek İstediğiniz Sınıfı Onaylayın</h1>
<p>Silmek istediğiniz sınıfı aşağıda onaylayın:</p>

<p><strong>Sınıf Adı:</strong> <?php echo $classData['class_name']; ?></p>
<p><strong>Sınıf Kodu:</strong> <?php echo $classData['class_code']; ?></p>
<p><strong>Sınıf Açıklaması:</strong> <?php echo $classData['class_description']; ?></p>

<form method="post">
    <button type="submit">Sınıfı Sil</button>
</form>
</body>
</html>
