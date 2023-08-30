<?php
global $db;
require_once "db_connection.php";

session_start();

// Giriş yapmış olan kullanıcının rolünü kontrol edin ve gerekirse erişimi engelleyin
$allowedRoles = array(1); // Öğrenci düzenlemesi için uygun olan rollerin değeri
$currentUserRole = $_SESSION['admin_role'];

if (!in_array($currentUserRole, $allowedRoles)) {
    header("Location: access_denied.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = $_POST["student_id"];
    $new_firstname = $_POST["new_firstname"];
    $new_lastname = $_POST["new_lastname"];
    $new_tc_identity = $_POST["new_tc_identity"];
    $new_phone = $_POST["new_phone"];
    $new_email = $_POST["new_email"];

    // Öğrenci verilerini güncelleme işlemi
    $update_query = "UPDATE students SET firstname = ?, lastname = ?, tc_identity = ?, phone = ?, email = ? WHERE id = ?";
    $stmt = $db->prepare($update_query);
    $stmt->execute([$new_firstname, $new_lastname, $new_tc_identity, $new_phone, $new_email, $student_id]);

    header("Location: student_list.php"); // Öğrenci listesine geri dön
    exit();
}

// Öğrenci verisini çekme
if (isset($_GET["id"])) {
    $student_id = $_GET["id"];
    $select_query = "SELECT * FROM students WHERE id = ?";
    $stmt = $db->prepare($select_query);
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Öğrenci Düzenle</title>
</head>
<body>
<h1>Öğrenci Düzenle</h1>

<form method="post" action="">
    <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
    <label for="new_firstname">Yeni Adı:</label>
    <input type="text" id="new_firstname" name="new_firstname" value="<?php echo $student['firstname']; ?>" required><br>

    <label for="new_lastname">Yeni Soyadı:</label>
    <input type="text" id="new_lastname" name="new_lastname" value="<?php echo $student['lastname']; ?>" required><br>

    <label for="new_tc_identity">Yeni TC Kimlik No:</label>
    <input type="text" id="new_tc_identity" name="new_tc_identity" value="<?php echo $student['tc_identity']; ?>" required><br>

    <label for="new_phone">Yeni Cep Telefonu:</label>
    <input type="text" id="new_phone" name="new_phone" value="<?php echo $student['phone']; ?>" required><br>

    <label for="new_email">Yeni E-posta:</label>
    <input type="email" id="new_email" name="new_email" value="<?php echo $student['email']; ?>" required><br>

    <input type="submit" value="Kaydet">
</form>
</body>
</html>
