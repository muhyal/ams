<?php
/**
 * @copyright Copyright (c) 2024, KUTBU
 *
 * @author Muhammed Yalçınkaya <muhammed.yalcinkaya@kutbu.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
global $db, $showErrors, $siteName, $siteShortName, $siteUrl;

// Oturum kontrolü
session_start();
session_regenerate_id(true);

if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php"); // Giriş sayfasına yönlendir
    exit();
}
require_once "db_connection.php";
require_once "config.php";
// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);

// Öğrenci doğum günleri
$studentQuery = "SELECT first_name, last_name, birth_date FROM users WHERE user_type = 6";
$stmtStudent = $db->prepare($studentQuery);
$stmtStudent->execute();
$studentBirthdays = $stmtStudent->fetchAll(PDO::FETCH_ASSOC);

// Öğretmen doğum günleri
$teacherQuery = "SELECT first_name, last_name, birth_date FROM users WHERE user_type = 4";
$stmtTeacher = $db->prepare($teacherQuery);
$stmtTeacher->execute();
$teacherBirthdays = $stmtTeacher->fetchAll(PDO::FETCH_ASSOC);

?>
<?php
require_once "admin_panel_header.php";
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
                $birthdayDate = new DateTime($student['birth_date']);
                $todayDate = new DateTime();
                $age = $todayDate->diff($birthdayDate)->y;
                ?>
                <li><?php echo $student['first_name'] . ' ' . $student['last_name'] . ' (' . $age . ' yaşında) - ' . $birthdayDate->format('d.m.Y'); ?></li>
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
