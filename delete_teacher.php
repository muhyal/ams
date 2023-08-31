<?php
// Veritabanı bağlantısını sağlayın
global $db;
require_once "db_connection.php";

if (isset($_GET["id"])) {
    $id = $_GET["id"];

    // Öğretmeni veritabanından alın
    $query = "SELECT * FROM teachers WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$teacher) {
        echo "Öğretmen bulunamadı.";
        exit();
    }
}

if (isset($_POST["confirm_delete"])) {
    // Öğretmeni veritabanından sil
    $query = "DELETE FROM teachers WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);

    header("Location: teachers_list.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Öğretmen Silme Onayı</title>
</head>
<body>
<h1>Öğretmeni Sil</h1>
<p>Aşağıdaki öğretmeni silmek istediğinize emin misiniz?</p>
<p>Ad: <?php echo $teacher["firstname"]; ?></p>
<p>Soyad: <?php echo $teacher["lastname"]; ?></p>
<p>Email: <?php echo $teacher["email"]; ?></p>

<form method="post">
    <input type="hidden" name="id" value="<?php echo $id; ?>">
    <button type="submit" name="confirm_delete">Sil</button>
</form>
</body>
</html>
