<?php
global $db;
session_start();
// Oturum kontrolü
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php"); // Giriş sayfasına yönlendir
    exit();
}

require_once "db_connection.php"; // Veritabanı bağlantısı

// Kullanıcı bilgilerini kullanabilirsiniz
$admin_id = $_SESSION["admin_id"];
$admin_username = $_SESSION["admin_username"];

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once "config.php";
global $siteName, $siteShortName, $siteUrl;
require_once "admin_panel_header.php";

// Ders ekleme işlemi
if (isset($_POST["add_course"])) {
    $courseName = $_POST["course_name"];
    $courseCode = $_POST["course_code"];
    $courseDescription = $_POST["description"];

    $query = "INSERT INTO courses (course_name, course_code, description) VALUES (?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->execute([$courseName, $courseCode, $courseDescription]);
}

// Ders düzenleme işlemi
if (isset($_POST["edit_course"])) {
    $id = $_POST["id"];
    $courseName = $_POST["course_name"];
    $courseCode = $_POST["course_code"];
    $courseDescription = $_POST["description"];

    $query = "UPDATE courses SET course_name = ?, course_code = ?, description = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$courseName, $courseCode, $courseDescription, $id]);
}

// Ders silme işlemi
if (isset($_GET["delete_id"])) {
    $deleteId = $_GET["delete_id"];

    $query = "DELETE FROM courses WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$deleteId]);
}

$courses = $db->query("SELECT * FROM courses")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="row">
        <?php
        require_once "admin_panel_sidebar.php";
        ?>
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
                <h2>Ders Yönetimi</h2>
            </div>

            <!-- Ders Düzenleme Formu -->
            <?php
            if (isset($_GET["edit_id"])) {
                $editId = $_GET["edit_id"];
                $editCourse = $db->query("SELECT * FROM courses WHERE id = $editId")->fetch(PDO::FETCH_ASSOC);
                ?>
                <h2>Ders Düzenle</h2>
                <form method="post">
                    <input type="hidden" name="id" value="<?php echo $editCourse["id"]; ?>">
                    <label for="course_name">Ders Adı:</label>
                    <input type="text" id="course_name" name="course_name" value="<?php echo $editCourse["course_name"]; ?>" required><br>
                    <label for="course_name">Ders Açıklaması:</label>
                    <input type="text" id="description" name="description" value="<?php echo $editCourse["description"]; ?>" required><br>
                    <label for="course_code">Ders Kodu:</label>
                    <input type="text" id="course_code" name="course_code" value="<?php echo $editCourse["course_code"]; ?>" required><br>
                    <button type="submit" name="edit_course">Ders Düzenle</button>
                </form>
            <?php } ?>

            <!-- Ders Listesi -->
            <h2>Ders Listesi</h2>
            <table border="1">
                <tr>
                    <th>No</th>
                    <th>Ders Adı</th>
                    <th>Ders Açıklaması</th>
                    <th>Ders Kodu</th>
                    <th>İşlemler</th>
                </tr>
                <?php foreach ($courses as $course): ?>
                    <tr>
                        <td><?php echo $course["id"]; ?></td>
                        <td><?php echo $course["course_name"]; ?></td>
                        <td><?php echo $course["description"]; ?></td>
                        <td><?php echo $course["course_code"]; ?></td>
                        <td>
                            <a href="?edit_id=<?php echo $course["id"]; ?>">Düzenle</a>
                            <a href="?delete_id=<?php echo $course["id"]; ?>" onclick="return confirm('Dersi silmek istediğinizden emin misiniz?')">Sil</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>

            <!-- Ders Ekleme Formu -->
            <h2>Ders Ekle</h2>
            <form method="post">
                <label for="course_name">Ders Adı:</label>
                <input type="text" id="course_name" name="course_name" required><br>
                <label for="course_name">Ders Açıklaması:</label>
                <input type="text" id="description" name="description" required><br>
                <label for="course_code">Ders Kodu:</label>
                <input type="text" id="course_code" name="course_code" required><br>
                <button type="submit" name="add_course">Ders Ekle</button>
            </form>




<?php
require_once "footer.php";
?>

