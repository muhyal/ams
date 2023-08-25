<?php
// Veritabanı bağlantısı ve gerekli dosyaları include edin
global $db;
require_once "db_connection.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstname = isset($_POST['firstname']) ? $_POST['firstname'] : '';
    $lastname = isset($_POST['lastname']) ? $_POST['lastname'] : '';
    $tc_identity = isset($_POST['tc_identity']) ? $_POST['tc_identity'] : '';
    $phone = isset($_POST['phone']) ? $_POST['phone'] : '';
    $email = isset($_POST['email']) ? $_POST['email'] : '';

    // Öğrenci ekleme sorgusu
    $insertQuery = "INSERT INTO students (firstname, lastname, tc_identity, phone, email) VALUES (?, ?, ?, ?, ?)";
    $insertStmt = $db->prepare($insertQuery);

    if ($insertStmt->execute([$firstname, $lastname, $tc_identity, $phone, $email])) {
        echo "Öğrenci başarıyla eklendi.";
    } else {
        echo "Öğrenci eklenirken bir hata oluştu.";
    }
} else {
    echo "Geçersiz istek.";
}
?>
