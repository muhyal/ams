<?php
session_start();

// Oturum kontrolü
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php"); // Giriş sayfasına yönlendir
    exit();
}

global $db;
require_once "db_connection.php";
require_once "config.php";
global $siteName, $siteShortName, $siteUrl;
require_once "admin_panel_header.php";

// Akademi işlemleri
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["add_academy"])) {
        // Akademi ekleme işlemi
        $name = $_POST["name"];
        $phone_number = $_POST["phone_number"];
        $mobile_number = $_POST["mobile_number"];
        $city = $_POST["city"];
        $district = $_POST["district"];
        $address = $_POST["address"];
        $email = $_POST["email"];
        $working_hours = $_POST["working_hours"];

        $query = "INSERT INTO academies (name, phone_number, mobile_number, city, district, address, email, working_hours)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([$name, $phone_number, $mobile_number, $city, $district, $address, $email, $working_hours]);
    } elseif (isset($_POST["edit_academy"])) {
        // Akademi düzenleme işlemi
        $academyId = $_POST["academy_id"];
        $name = $_POST["name"];
        $phone_number = $_POST["phone_number"];
        $mobile_number = $_POST["mobile_number"];
        $city = $_POST["city"];
        $district = $_POST["district"];
        $address = $_POST["address"];
        $email = $_POST["email"];
        $working_hours = $_POST["working_hours"];

        $query = "UPDATE academies 
                  SET name = ?, phone_number = ?, mobile_number = ?, city = ?, district = ?, address = ?, email = ?, working_hours = ?
                  WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$name, $phone_number, $mobile_number, $city, $district, $address, $email, $working_hours, $academyId]);
    } elseif (isset($_POST["delete_academy"])) {
        // Akademi silme işlemi
        $academyId = $_POST["academy_id"];

        $query = "DELETE FROM academies WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$academyId]);
    }
}

// Tüm akademileri getir
$query = "SELECT * FROM academies";
$stmt = $db->query($query);
$academies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Akademi düzenleme formu
if (isset($_GET["edit"])) {
    $editAcademyId = $_GET["edit"];
    $editQuery = "SELECT * FROM academies WHERE id = ?";
    $editStmt = $db->prepare($editQuery);
    $editStmt->execute([$editAcademyId]);
    $editAcademy = $editStmt->fetch(PDO::FETCH_ASSOC);

    // Akademide görev yapan öğretmenleri getir
    $teachersInAcademyQuery = "SELECT teachers.* FROM teachers
                              INNER JOIN academy_teachers ON teachers.id = academy_teachers.teacher_id
                              WHERE academy_teachers.academy_id = ?";
    $teachersInAcademyStmt = $db->prepare($teachersInAcademyQuery);
    $teachersInAcademyStmt->execute([$editAcademyId]);
    $teachersInAcademy = $teachersInAcademyStmt->fetchAll(PDO::FETCH_ASSOC);
}
    ?>

    <div class="container-fluid">
    <div class="row">
<?php
require_once "admin_panel_sidebar.php";
?>
    <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
        <h2>Akademi İşlemleri</h2>
    </div>

<!-- Akademi Ekleme Formu -->
<form method="post">
    <h3>Akademi Ekle</h3>
    <label for="name">Adı:</label>
    <input type="text" name="name" required><br>
    <label for="phone_number">Sabit Telefon No:</label>
    <input type="text" name="phone_number" required><br>
    <label for="mobile_number">Cep Telefon No:</label>
    <input type="text" name="mobile_number" required><br>
    <label for="city">İl:</label>
    <input type="text" name="city" required><br>
    <label for="district">İlçe:</label>
    <input type="text" name="district" required><br>
    <label for="address">Adres:</label>
    <textarea name="address" required></textarea><br>
    <label for="email">E-posta:</label>
    <input type="email" name="email" required><br>
    <label for="working_hours">Çalışma Saatleri:</label>
    <input type="text" name="working_hours" required><br>
    <button type="submit" name="add_academy">Akademi Ekle</button>
</form>

<hr>

<!-- Tüm Akademileri Listeleme -->
<h3>Tüm Akademiler</h3>
<ul>
    <?php foreach ($academies as $academy): ?>
        <li>
            <?php echo $academy["name"]; ?>
            (<a href="?edit=<?php echo $academy["id"]; ?>">Düzenle</a> |
            <a href="?delete=<?php echo $academy["id"]; ?>">Sil</a>)
        </li>
    <?php endforeach; ?>
</ul>

<?php
// Akademi düzenleme formu
if (isset($_GET["edit"])) {
    $editAcademyId = $_GET["edit"];
    $editQuery = "SELECT * FROM academies WHERE id = ?";
    $editStmt = $db->prepare($editQuery);
    $editStmt->execute([$editAcademyId]);
    $editAcademy = $editStmt->fetch(PDO::FETCH_ASSOC);
    ?>
    <form method="post">
        <h3>Akademi Düzenle</h3>
        <input type="hidden" name="academy_id" value="<?php echo $editAcademy["id"]; ?>">
        <label for="name">Adı:</label>
        <input type="text" name="name" value="<?php echo $editAcademy["name"]; ?>" required><br>
        <label for="phone_number">Sabit Telefon No:</label>
        <input type="text" name="phone_number" value="<?php echo $editAcademy["phone_number"]; ?>" required><br>
        <label for="mobile_number">Cep Telefon No:</label>
        <input type="text" name="mobile_number" value="<?php echo $editAcademy["mobile_number"]; ?>" required><br>
        <label for="city">İl:</label>
        <input type="text" name="city" value="<?php echo $editAcademy["city"]; ?>" required><br>
        <label for="district">İlçe:</label>
        <input type="text" name="district" value="<?php echo $editAcademy["district"]; ?>" required><br>
        <label for="address">Adres:</label>
        <textarea name="address" required><?php echo $editAcademy["address"]; ?></textarea><br>
        <label for="email">E-posta:</label>
        <input type="email" name="email" value="<?php echo $editAcademy["email"]; ?>" required><br>
        <label for="working_hours">Çalışma Saatleri:</label>
        <input type="text" name="working_hours" value="<?php echo $editAcademy["working_hours"]; ?>" required><br>
        <button type="submit" name="edit_academy">Akademi Düzenle</button>
    </form>

    <!-- Akademide Görev Yapan Öğretmenler -->
    <h3>Akademi Öğretmenleri</h3>
    <ul>
        <?php foreach ($teachersInAcademy as $teacher): ?>
            <li><?php echo $teacher["first_name"] . " " . $teacher["last_name"]; ?></li>
        <?php endforeach; ?>
    </ul>
    <?php
}
?>
<?php
require_once "footer.php";
?>