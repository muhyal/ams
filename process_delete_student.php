<?php
session_start();

// Oturum kontrolü
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php"); // Giriş sayfasına yönlendir
    exit();
}

// Veritabanı bağlantısı ve gerekli dosyaları include edin
global $db;
require_once "db_connection.php";

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["id"])) {
    $studentId = $_GET["id"];

    // Öğrenci bilgilerini çekme sorgusu
    $query = "SELECT students.*, parents.*, emergency_contacts.*, addresses.* 
              FROM students 
              LEFT JOIN parents ON students.id = parents.student_id 
              LEFT JOIN emergency_contacts ON students.id = emergency_contacts.student_id 
              LEFT JOIN addresses ON students.id = addresses.student_id
              WHERE students.id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$studentId]);
    $studentInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$studentInfo) {
        echo "Öğrenci bulunamadı.";
        exit();
    }
} else {
    echo "Geçersiz istek.";
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Öğrenci Silme Onayı</title>
</head>
<body>

<h2>Silmek istediğiniz öğrenciyi aşağıda onaylayın:</h2>
<p><strong>Öğrenci Adı:</strong> <?php echo $studentInfo['firstname']; ?></p>
<p><strong>Öğrenci Soyadı:</strong> <?php echo $studentInfo['lastname']; ?></p>
<p><strong>T.C. Kimlik No:</strong> <?php echo $studentInfo['tc_identity']; ?></p>
<p><strong>Öğrenci Telefon:</strong> <?php echo $studentInfo['phone']; ?></p>
<p><strong>Öğrenci E-Posta:</strong> <?php echo $studentInfo['email']; ?></p>

<p><strong>Öğrenciyi silmek istediğinizden emin misiniz?</strong></p>

<form method="POST" action="process_delete_student.php">
    <input type="hidden" name="student_id" value="<?php echo $studentInfo['id']; ?>">
    <input type="submit" name="confirm_delete" value="Öğrenciyi Sil">
    <a href="student_list.php">İptal</a>
</form>

</body>
</html>
