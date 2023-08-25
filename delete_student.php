<?php
session_start();

// Oturum kontrolü
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php"); // Giriş sayfasına yönlendir
    exit();
}
global $db;
require_once "db_connection.php"; // Veritabanı bağlantısı

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["id"])) {
    $studentId = $_GET["id"];

    // Öğrenci bilgilerini veritabanından alın
    $query = "SELECT * FROM students WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$studentId]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<h3>Silmek istediğiniz öğrenciyi aşağıda onaylayın:</h3>
<p>Öğrenci Adı: <?php echo $student['firstname']; ?></p>
<p>Öğrenci Soyadı: <?php echo $student['lastname']; ?></p>
<p>T.C. Kimlik No: <?php echo $student['tc_identity']; ?></p>
<p>Öğrenci Telefon: <?php echo $student['phone']; ?></p>
<p>Öğrenci E-Posta: <?php echo $student['email']; ?></p>


<form action="process_delete_student.php" method="POST">
    <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
    <p>Öğrenciyi silmek istediğinizden emin misiniz?</p>
    <button type="submit">Öğrenciyi Sil</button>
    <a href="student_list.php">İptal</a>
</form>
