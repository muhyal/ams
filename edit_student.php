<?php
global $db, $showErrors, $siteName, $siteShortName, $siteUrl;
// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);
require_once "config.php";
session_start();

// Oturum kontrolü
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php");
    exit();
}

require_once "db_connection.php";

// Kullanıcı bilgilerini kullanabilirsiniz
$admin_id = $_SESSION["admin_id"];
$admin_username = $_SESSION["admin_username"];

require_once "admin_panel_header.php";

// Giriş yapmış olan kullanıcının rolünü kontrol edin ve gerekirse erişimi engelleyin
$allowedRoles = array(1);
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
    $new_blood_type = $_POST["new_blood_type"];
    $new_health_issue = $_POST["new_health_issue"];
    $new_birthdate = $_POST["new_birthdate"];
    $new_parent_firstname = $_POST["new_parent_firstname"];
    $new_parent_lastname = $_POST["new_parent_lastname"];
    $new_parent_phone = $_POST["new_parent_phone"];
    $new_parent_email = $_POST["new_parent_email"];
    $new_city = $_POST["new_city"];
    $new_district = $_POST["new_district"];
    $new_address = $_POST["new_address"];

    // Öğrenci verilerini güncelleme işlemi
    $update_query = "
    UPDATE students 
    SET 
        firstname = ?, lastname = ?, tc_identity = ?, phone = ?, email = ?,
        blood_type = ?, health_issue = ?, birthdate = ?
    WHERE id = ?";
    $stmt = $db->prepare($update_query);
    $stmt->execute([
        $new_firstname, $new_lastname, $new_tc_identity, $new_phone, $new_email,
        $new_blood_type, $new_health_issue, $new_birthdate, $student_id
    ]);

    // Adres bilgilerini güncelleme işlemi
    $update_address_query = "
    UPDATE addresses 
    SET 
        city = ?, district = ?, address = ?
    WHERE student_id = ?";
    $stmt_address = $db->prepare($update_address_query);
    $stmt_address->execute([
        $new_city, $new_district, $new_address, $student_id
    ]);


// Öğrenci veli verilerini güncelleme işlemi
    $update_parent_query = "
    UPDATE parents 
    SET 
        parent_firstname = ?, parent_lastname = ?, parent_phone = ?, parent_email = ?
    WHERE student_id = ?";
    $stmt_parent = $db->prepare($update_parent_query);
    $stmt_parent->execute([
        $new_parent_firstname, $new_parent_lastname, $new_parent_phone, $new_parent_email,
        $student_id
    ]);

    header("Location: student_list.php");
    exit();
}

/// Öğrenci verilerini çekme
if (isset($_GET["id"])) {
    $student_id = $_GET["id"];
    $select_query = "
        SELECT students.*, parents.*, addresses.*
        FROM students
        LEFT JOIN parents ON students.id = parents.student_id
        LEFT JOIN addresses ON students.id = addresses.student_id
        WHERE students.id = ?";
    $stmt = $db->prepare($select_query);
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
}

?>

<div class="container-fluid">
    <div class="row">
        <?php require_once "admin_panel_sidebar.php"; ?>
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
                <h2>Öğrenci Düzenle</h2>
            </div>

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

                <label for="new_blood_type">Yeni Kan Grubu:</label>
                <input type="text" id="new_blood_type" name="new_blood_type" value="<?php echo $student['blood_type']; ?>" required><br>

                <label for="new_health_issue">Yeni Sağlık Sorunu:</label>
                <input type="text" id="new_health_issue" name="new_health_issue" value="<?php echo $student['health_issue']; ?>" required><br>

                <label for="new_birthdate">Yeni Doğum Tarihi:</label>
                <input type="text" id="new_birthdate" name="new_birthdate" value="<?php echo $student['birthdate']; ?>" required><br>

                <label for="new_blood_type">Yeni İl:</label>
                <input type="text" id="new_city" name="new_city" value="<?php echo $student['city']; ?>" required><br>

                <label for="new_health_issue">Yeni İlçe:</label>
                <input type="text" id="new_district" name="new_district" value="<?php echo $student['district']; ?>" required><br>

                <label for="new_health_issue">Yeni Adres:</label>
                <input type="text" id="new_address" name="new_address" value="<?php echo $student['address']; ?>" required><br>

                <label for="new_parent_firstname">Yeni Veli Adı:</label>
                <input type="text" id="new_parent_firstname" name="new_parent_firstname" value="<?php echo $student['parent_firstname']; ?>" required><br>

                <label for="new_parent_lastname">Yeni Veli Soyadı:</label>
                <input type="text" id="new_parent_lastname" name="new_parent_lastname" value="<?php echo $student['parent_lastname']; ?>" required><br>

                <label for="new_parent_phone">Yeni Veli Telefonu:</label>
                <input type="text" id="new_parent_phone" name="new_parent_phone" value="<?php echo $student['parent_phone']; ?>" required><br>

                <label for="new_parent_email">Yeni Veli E-posta:</label>
                <input type="email" id="new_parent_email" name="new_parent_email" value="<?php echo $student['parent_email']; ?>" required><br>

                <input type="submit" value="Kaydet">
            </form>
            <?php require_once "footer.php"; ?>
