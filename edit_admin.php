<?php
global $db;
require_once "db_connection.php";

session_start();

$allowedRoles = array(1); // "sa" rolü için rol değeri (örneğin 1)
$currentUserRole = $_SESSION['admin_role'];

if (!in_array($currentUserRole, $allowedRoles)) {
    header("Location: access_denied.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $admin_id = $_POST["admin_id"];
    $new_username = $_POST["new_username"];
    $new_email = $_POST["new_email"];
    $new_password = $_POST["new_password"];

    // Şifre değişikliği yapılacak mı kontrolü
    if (!empty($new_password)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_query = "UPDATE admins SET username = ?, email = ?, password = ? WHERE id = ?";
        $stmt = $db->prepare($update_query);
        $stmt->execute([$new_username, $new_email, $hashed_password, $admin_id]);
    } else {
        $update_query = "UPDATE admins SET username = ?, email = ? WHERE id = ?";
        $stmt = $db->prepare($update_query);
        $stmt->execute([$new_username, $new_email, $admin_id]);
    }

    header("Location: admin_list.php"); // Yönetici listesine geri dön
    exit();
}

// Yönetici verisini çekme
if (isset($_GET["id"])) {
    $admin_id = $_GET["id"];
    $select_query = "SELECT * FROM admins WHERE id = ?";
    $stmt = $db->prepare($select_query);
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Yönetici Düzenle</title>
</head>
<body>
<h1>Yönetici Düzenle</h1>

<form method="post" action="">
    <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
    <label for="new_username">Yeni Kullanıcı Adı:</label>
    <input type="text" id="new_username" name="new_username" value="<?php echo $admin['username']; ?>" required><br>

    <label for="new_email">Yeni E-posta:</label>
    <input type="email" id="new_email" name="new_email" value="<?php echo $admin['email']; ?>" required><br>

    <label for="new_password">Yeni Şifre (Boş bırakabilirsiniz):</label>
    <input type="password" id="new_password" name="new_password"><br>

    <input type="submit" value="Kaydet">
</form>
</body>
</html>
