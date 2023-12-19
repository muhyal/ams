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

// Öğrenci doğum günleri
$studentQuery = "SELECT firstname, lastname, birthdate FROM students";
$stmtStudent = $db->prepare($studentQuery);
$stmtStudent->execute();
$studentBirthdays = $stmtStudent->fetchAll(PDO::FETCH_ASSOC);

// Öğretmen doğum günleri
$teacherQuery = "SELECT first_name, last_name, birth_date FROM teachers";
$stmtTeacher = $db->prepare($teacherQuery);
$stmtTeacher->execute();
$teacherBirthdays = $stmtTeacher->fetchAll(PDO::FETCH_ASSOC);
?>

    <style>
        .container {
            display: flex;
            justify-content: space-between;
        }

        .column {
            flex: 1;
            padding: 20px;
            border: 1px solid #ccc;
            margin: 10px;
        }

        ul {
            list-style-type: none;
            padding: 0;
        }
    </style>

<div class="container-fluid">
    <div class="row">
        <?php
        require_once "admin_panel_sidebar.php";
        ?>
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">


<div class="container">
    <div class="column">
        <h2>Öğrenci Doğum Günleri</h2>
        <ul>
            <?php foreach ($studentBirthdays as $student): ?>
                <?php
                // Doğum günü tarihini alın
                $birthdayDate = new DateTime($student['birthdate']);
                $todayDate = new DateTime();
                $age = $todayDate->diff($birthdayDate)->y;
                ?>
                <li><?php echo $student['firstname'] . ' ' . $student['lastname'] . ' (' . $age . ' yaşında) - ' . $birthdayDate->format('d.m.Y'); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="column">
        <h2>Öğretmen Doğum Günleri</h2>
        <ul>
            <?php foreach ($teacherBirthdays as $teacher): ?>
                <?php
                // Doğum günü tarihini alın
                $birthdayDate = new DateTime($teacher['birth_date']);
                $todayDate = new DateTime();
                $age = $todayDate->diff($birthdayDate)->y;
                ?>
                <li><?php echo $teacher['first_name'] . ' ' . $teacher['last_name'] . ' (' . $age . ' yaşında) - ' . $birthdayDate->format('d.m.Y'); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php
require_once "footer.php";
?>
