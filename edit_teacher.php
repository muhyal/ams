<?php
global $db, $showErrors, $siteName, $siteShortName, $siteUrl;
// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);

// Oturum kontrolü
session_start();
session_regenerate_id(true);

if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php"); // Giriş sayfasına yönlendir
    exit();
}

require_once "config.php";
require_once "db_connection.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST["id"];
    $firstName = $_POST["first_name"];
    $lastName = $_POST["last_name"];
    $tcIdentity = $_POST["tc_identity"];
    $birthDate = $_POST["birth_date"];
    $phone = $_POST["phone"];
    $email = $_POST["email"];
    $selectedCourse = $_POST["course"];
    $selectedClass = $_POST["class"];

    // Veritabanındaki öğretmeni güncelle
    $query = "UPDATE teachers SET first_name = ?, last_name = ?, tc_identity = ?, birth_date = ?, phone = ?, email = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$firstName, $lastName, $tcIdentity, $birthDate, $phone, $email, $id]);

}


if (isset($_GET["id"])) {
    $teacher_id = $_GET["id"];
    $select_query = "SELECT teachers.*, courses.course_name, classes.class_name 
                     FROM teachers
                     LEFT JOIN teacher_courses ON teachers.id = teacher_courses.teacher_id
                     LEFT JOIN courses ON teacher_courses.course_id = courses.id
                     LEFT JOIN classes ON teacher_courses.class_id = classes.id
                     WHERE teachers.id = ?";
    $stmt = $db->prepare($select_query);
    $stmt->execute([$teacher_id]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<?php
require_once "admin_panel_header.php";
?>
<div class="container-fluid">
    <div class="row">
        <?php
        require_once "admin_panel_sidebar.php";
        ?>
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
                <h2>Öğretmen Düzenle</h2>
            </div>
            <form method="post">
                <input type="hidden" name="id" value="<?php echo $teacher["id"]; ?>">
                <label for="first_name">Adı:</label>
                <input type="text" id="first_name" name="first_name" value="<?php echo $teacher["first_name"]; ?>" required><br>
                <label for="last_name">Soyadı:</label>
                <input type="text" id="last_name" name="last_name" value="<?php echo $teacher["last_name"]; ?>" required><br>
                <label for="tc_identity">T.C. Kimlik No</label>
                <input type="text" id="tc_identity" name="tc_identity" value="<?php echo $teacher["tc_identity"]; ?>" required><br>
                <label for="birth_date">Doğum Tarihi:</label>
                <input type="date" id="birth_date" name="birth_date" value="<?php echo $teacher["birth_date"]; ?>" required><br>
                <label for="phone">Telefon:</label>
                <input type="tel" id="phone" name="phone" value="<?php echo $teacher["phone"]; ?>"><br>
                <label for="email">E-posta:</label>
                <input type="email" id="email" name="email" value="<?php echo $teacher["email"]; ?>" required><br>


                    <button type="submit" name="edit_teacher">Öğretmeni Düzenle</button>

                </form>


            <a href="teachers_list.php">Öğretmen Listesi</a>
            <?php
            require_once "footer.php";
            ?>
