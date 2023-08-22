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

// Kullanıcının ID'sini alın
if (isset($_GET["id"])) {
    $userId = $_GET["id"];
    $getUserQuery = "SELECT * FROM users WHERE id = ?";
    $stmt = $db->prepare($getUserQuery);
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Form gönderildiğinde güncelleme işlemini gerçekleştirin
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Formdan gelen verileri alın
    $tc = $_POST["tc"];
    $firstname = $_POST["firstname"];
    $lastname = $_POST["lastname"];
    $email = $_POST["email"];
    $phone = $_POST["phone"];

    // Veritabanında güncelleme işlemi yapın
    $updateQuery = "UPDATE users SET tc = ?, firstname = ?, lastname = ?, email = ?, phone = ? WHERE id = ?";
    $stmt = $db->prepare($updateQuery);
    $stmt->execute([$tc, $firstname, $lastname, $email, $phone, $userId]);

    // Kullanıcıyı güncelledikten sonra yönlendirme yapabilirsiniz
    header("Location: user_list.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Kullanıcı Düzenleme</title>
</head>
<body>
<h2>Kullanıcı Düzenleme</h2>

<?php if (isset($user)): ?>
    <form method="post" action="">
        <label for="tc">TC Kimlik No:</label>
        <input type="text" name="tc" value="<?php echo $user["tc"]; ?>" required><br>
        <label for="firstname">Ad:</label>
        <input type="text" name="firstname" value="<?php echo $user["firstname"]; ?>" required><br>
        <label for="lastname">Soyad:</label>
        <input type="text" name="lastname" value="<?php echo $user["lastname"]; ?>" required><br>
        <label for="email">E-posta:</label>
        <input type="email" name="email" value="<?php echo $user["email"]; ?>" required><br>
        <label for="phone">Telefon:</label>
        <input type="text" name="phone" value="<?php echo $user["phone"]; ?>" required><br>
        <input type="submit" value="Güncelle">
    </form>
<?php else: ?>
    <p>Kullanıcı bulunamadı.</p>
<?php endif; ?>

<a href="user_list.php">Kullanıcı Listesine Geri Dön</a>
</body>
</html>
