<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Oturum kontrolü
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php"); // Giriş sayfasına yönlendir
    exit();
}

require_once "db_connection.php";

// Silme işlemi için formdan gelen ID'yi alın
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["id"])) {
    $userId = $_POST["id"];

    // Kullanıcıyı silme işlemini gerçekleştirin
    $deleteQuery = "DELETE FROM users WHERE id = ?";
    $stmt = $db->prepare($deleteQuery);
    $stmt->execute([$userId]);

    // Kullanıcı silindikten sonra yönlendirme yapabilirsiniz
    header("Location: user_list.php");
    exit();
}

// Silme formunu göstermek için kullanıcının bilgilerini çekin
if (isset($_GET["id"])) {
    $userId = $_GET["id"];
    $getUserQuery = "SELECT * FROM users WHERE id = ?";
    $stmt = $db->prepare($getUserQuery);
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Kullanıcı Silme</title>
</head>
<body>
<h2>Kullanıcı Silme</h2>

<?php if (isset($user)): ?>
    <p>Kullanıcı Adı: <?php echo $user["firstname"] . " " . $user["lastname"]; ?></p>
    <p>E-posta: <?php echo $user["email"]; ?></p>
<?php else: ?>
    <p>Kullanıcı bulunamadı.</p>
<?php endif; ?>

<form method="post" action="">
    <input type="hidden" name="id" value="<?php echo $userId; ?>">
    <button type="submit" onclick="return confirm('Kullanıcıyı silmek istediğinizden emin misiniz?')">Kullanıcıyı Sil</button>
</form>

<a href="user_list.php">Kullanıcı Listesine Geri Dön</a>
</body>
</html>
