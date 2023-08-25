<?php
global $db;
require_once "db_connection.php"; // Veritabanı bağlantısı

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $classId = $_POST["class_id"];
    $className = $_POST["class_name"];
    $classCode = $_POST["class_code"];

    $updateQuery = "UPDATE classes SET class_name = ?, class_code = ? WHERE id = ?";
    $stmt = $db->prepare($updateQuery);
    $stmt->execute([$className, $classCode, $classId]);

    header("Location: class_list.php"); // Sınıf listesine yönlendir
}
?>
