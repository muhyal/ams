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
// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);
require_once "config.php";

// Oturum kontrolü
session_start();
session_regenerate_id(true);

// Oturum kontrolü
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php"); // Giriş sayfasına yönlendir
    exit();
}

// Veritabanı bağlantısı ve gerekli dosyaları include edin
require_once "db_connection.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstname = isset($_POST['firstname']) ? $_POST['firstname'] : '';
    $lastname = isset($_POST['lastname']) ? $_POST['lastname'] : '';
    $tc_identity = isset($_POST['tc_identity']) ? $_POST['tc_identity'] : '';
    $birthdate = isset($_POST['birthdate']) ? $_POST['birthdate'] : '';
    $phone = isset($_POST['phone']) ? $_POST['phone'] : '';
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    $blood_type = isset($_POST['blood_type']) ? $_POST['blood_type'] : '';
    $health_issue = isset($_POST['health_issue']) ? $_POST['health_issue'] : '';

    // Akademi seçimi
    $academy_id = isset($_POST["academy"]) ? $_POST["academy"] : null;

    // Ders seçimi
    $course_id = isset($_POST["course"]) ? $_POST["course"] : null;

    // Veli bilgileri
    $parent_firstname = isset($_POST['parent_firstname']) ? $_POST['parent_firstname'] : '';
    $parent_lastname = isset($_POST['parent_lastname']) ? $_POST['parent_lastname'] : '';
    $parent_phone = isset($_POST['parent_phone']) ? $_POST['parent_phone'] : '';
    $parent_email = isset($_POST['parent_email']) ? $_POST['parent_email'] : '';

    // Acil durum iletişim bilgileri
    $emergency_contact = isset($_POST['emergency_contact']) ? $_POST['emergency_contact'] : '';
    $emergency_phone = isset($_POST['emergency_phone']) ? $_POST['emergency_phone'] : '';

    // Adres bilgileri
    $city = isset($_POST['city']) ? $_POST['city'] : '';
    $district = isset($_POST['district']) ? $_POST['district'] : '';
    $address = isset($_POST['address']) ? $_POST['address'] : '';

    // Eğer akademi ve ders seçimi yapılmışsa ve geçerli id'ler varsa devam et
    if (!empty($academy_id) && is_numeric($academy_id) && !empty($course_id) && is_numeric($course_id)) {
        // Öğrenci ekleme sorgusu
        $insertStudentQuery = "INSERT INTO students (firstname, lastname, tc_identity, birthdate, phone, email, blood_type, health_issue) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $insertStudentStmt = $db->prepare($insertStudentQuery);

        if ($insertStudentStmt->execute([$firstname, $lastname, $tc_identity, $birthdate, $phone, $email, $blood_type, $health_issue])) {
            $studentId = $db->lastInsertId();

            // Veli bilgilerini ekleme sorgusu
            $insertParentQuery = "INSERT INTO parents (student_id, parent_firstname, parent_lastname, parent_phone, parent_email) VALUES (?, ?, ?, ?, ?)";
            $insertParentStmt = $db->prepare($insertParentQuery);
            $insertParentStmt->execute([$studentId, $parent_firstname, $parent_lastname, $parent_phone, $parent_email]);

            // Acil durum iletişim bilgilerini ekleme sorgusu
            $insertEmergencyQuery = "INSERT INTO emergency_contacts (student_id, emergency_contact, emergency_phone) VALUES (?, ?, ?)";
            $insertEmergencyStmt = $db->prepare($insertEmergencyQuery);
            $insertEmergencyStmt->execute([$studentId, $emergency_contact, $emergency_phone]);

            // Adres bilgilerini ekleme sorgusu
            $insertAddressQuery = "INSERT INTO addresses (student_id, city, district, address) VALUES (?, ?, ?, ?)";
            $insertAddressStmt = $db->prepare($insertAddressQuery);
            $insertAddressStmt->execute([$studentId, $city, $district, $address]);

            // Öğrenci-akademi ilişkisi ekleme sorgusu
            $insertAcademyStudentQuery = "INSERT INTO academy_students (academy_id, student_id) VALUES (?, ?)";
            $insertAcademyStudentStmt = $db->prepare($insertAcademyStudentQuery);
            $insertAcademyStudentStmt->execute([$academy_id, $studentId]);

            // Öğrenci-ders ilişkisi ekleme sorgusu
            $insertCourseQuery = "INSERT INTO student_courses (student_id, course_id) VALUES (?, ?)";
            $insertCourseStmt = $db->prepare($insertCourseQuery);
            $insertCourseStmt->execute([$studentId, $course_id]);

            echo "Öğrenci başarıyla eklendi.";
        } else {
            echo "Öğrenci eklenirken bir hata oluştu.";
        }
    } else {
        echo "Geçersiz akademi veya ders seçimi.";
    }
} else {
    echo "Geçersiz istek.";
}
?>
