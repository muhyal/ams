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

if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php"); // Giriş sayfasına yönlendir
    exit();
}

require_once "db_connection.php";

// Kullanıcı bilgilerini kullanabilirsiniz
$admin_id = $_SESSION["admin_id"];
$admin_username = $_SESSION["admin_username"];

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
    $academy_id = $_POST["academy"];
    $teacher_id = $_POST["teacher"];
    $course_id = $_POST["course"];
    $class_id = $_POST["class"];

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

    // Öğrencinin bağlı olduğu akademi, öğretmen, ders ve sınıf bilgilerini güncelleme işlemi
    $update_student_course_query = "
    UPDATE student_courses
    SET academy_id = ?
    WHERE student_id = ?";
    $stmt_course = $db->prepare($update_student_course_query);
    $stmt_course->execute([
        $academy_id, $student_id
    ]);

    // Öğrencinin bağlı olduğu dersi kontrol et
    $select_student_course_query = "
    SELECT * FROM student_courses WHERE student_id = ?";
    $stmt_select_student_course = $db->prepare($select_student_course_query);
    $stmt_select_student_course->execute([$student_id]);

// Eğer öğrenci-ders ilişkisi daha önce eklenmemişse, ekle
    if ($stmt_select_student_course->rowCount() == 0) {
        $insert_student_course_query = "INSERT INTO student_courses (student_id, course_id, academy_id) VALUES (?, ?, ?)";
        $stmt_insert_student_course = $db->prepare($insert_student_course_query);
        $stmt_insert_student_course->execute([$student_id, $course_id, $academy_id]);
    } else {
        // Eğer öğrenci-ders ilişkisi varsa, güncelle
        $update_student_course_query = "
        UPDATE student_courses SET course_id = ? WHERE student_id = ?";
        $stmt_student_course = $db->prepare($update_student_course_query);
        $stmt_student_course->execute([$course_id, $student_id]);
    }

    $update_student_class_query = "
    UPDATE student_courses
    SET class_id = ?
    WHERE student_id = ?";
    $stmt_class = $db->prepare($update_student_class_query);
    $stmt_class->execute([
        $class_id, $student_id
    ]);

// Öğrencinin bağlı olduğu öğretmeni kontrol et
    $select_student_teacher_query = "
    SELECT * FROM student_teachers WHERE student_id = ?";
    $stmt_select_student_teacher = $db->prepare($select_student_teacher_query);
    $stmt_select_student_teacher->execute([$student_id]);

// Eğer öğrenci-öğretmen ilişkisi daha önce eklenmemişse, ekle
    if ($stmt_select_student_teacher->rowCount() == 0) {
        $insert_student_teacher_query = "
        INSERT INTO student_courses (student_id, teacher_id) VALUES (?, ?)";
        $stmt_insert_student_teacher = $db->prepare($insert_student_teacher_query);
        $stmt_insert_student_teacher->execute([$student_id, $teacher_id]);
    } else {
        // Eğer öğrenci-öğretmen ilişkisi varsa, güncelle
        $update_student_teacher_query = "
        UPDATE student_courses SET teacher_id = ? WHERE student_id = ?";
        $stmt_student_teacher = $db->prepare($update_student_teacher_query);
        $stmt_student_teacher->execute([$teacher_id, $student_id]);
    }

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

// Öğretmenleri veritabanından çekme
$queryTeachers = "SELECT id, first_name, last_name FROM teachers";
$stmtTeachers = $db->prepare($queryTeachers);
$stmtTeachers->execute();
$teachers = $stmtTeachers->fetchAll(PDO::FETCH_ASSOC);

// Dersleri veritabanından çekme
$queryCourses = "SELECT id, course_name FROM courses";
$stmtCourses = $db->prepare($queryCourses);
$stmtCourses->execute();
$courses = $stmtCourses->fetchAll(PDO::FETCH_ASSOC);

// Sınıfları veritabanından çekme
$queryClasses = "SELECT id, class_name FROM classes";
$stmtClasses = $db->prepare($queryClasses);
$stmtClasses->execute();
$classes = $stmtClasses->fetchAll(PDO::FETCH_ASSOC);

// Akademileri veritabanından çekme
$queryAcademies = "SELECT id, name FROM academies";
$stmtAcademies = $db->prepare($queryAcademies);
$stmtAcademies->execute();
$academies = $stmtAcademies->fetchAll(PDO::FETCH_ASSOC);
?>
<?php require_once "admin_panel_header.php"; ?>

    <div class="container-fluid">
    <div class="row">
<?php require_once "admin_panel_sidebar.php"; ?>

    <!-- Geri Dönme ve Öğrenci Listesi -->
    <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
        <h4>Öğrenci <?php echo $student['firstname']; ?> <?php echo $student['lastname']; ?> Düzenleniyor</h4>
    </div>

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group mr-2">
                        <button onclick="history.back()" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Geri dön
                        </button>
                        <a href="student_list.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-list"></i> Öğrenci Listesi
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <br>

            <form method="post" action="">
                <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                <div class="form-group mt-3">
                    <label for="new_firstname">Yeni Adı:</label>
                    <input type="text" id="new_firstname" name="new_firstname" class="form-control" value="<?php echo $student['firstname']; ?>" required>
                </div>

                <div class="form-group mt-3">
                    <label for="new_lastname">Yeni Soyadı:</label>
                    <input type="text" id="new_lastname" name="new_lastname" class="form-control" value="<?php echo $student['lastname']; ?>" required>
                </div>

                <div class="form-group mt-3">
                    <label for="new_tc_identity">Yeni TC Kimlik No:</label>
                    <input type="text" id="new_tc_identity" name="new_tc_identity" class="form-control" value="<?php echo $student['tc_identity']; ?>" required>
                </div>

                <div class="form-group mt-3">
                <label for="new_phone">Yeni Cep Telefonu:</label>
                <input type="text" id="new_phone" name="new_phone" class="form-control" value="<?php echo $student['phone']; ?>" required>
                </div>
                <div class="form-group mt-3">
                <label for="new_email">Yeni E-posta:</label>
                <input type="email" id="new_email" name="new_email" class="form-control" value="<?php echo $student['email']; ?>" required>
                    </div>

                <div class="form-group mt-3">
                <label for="new_blood_type">Yeni Kan Grubu:</label>
                <input type="text" id="new_blood_type" name="new_blood_type" class="form-control" value="<?php echo $student['blood_type']; ?>" required>
                </div>
                <div class="form-group mt-3">
                <label for="new_health_issue">Yeni Sağlık Sorunu:</label>
                <input type="text" id="new_health_issue" name="new_health_issue" class="form-control" value="<?php echo $student['health_issue']; ?>" required>
                </div>
                <div class="form-group mt-3">
                    <label for="new_birthdate">Yeni Doğum Tarihi:</label>
                    <input type="date" id="new_birthdate" name="new_birthdate" class="form-control" value="<?php echo $student['birthdate']; ?>" required>
                </div>

                <div class="form-group mt-3">
                <label for="new_blood_type">Yeni İl:</label>
                <input type="text" id="new_city" name="new_city" class="form-control" value="<?php echo $student['city']; ?>" required>
                </div>
                <div class="form-group mt-3">
                <label for="new_health_issue">Yeni İlçe:</label>
                <input type="text" id="new_district" name="new_district" class="form-control" value="<?php echo $student['district']; ?>" required>
                </div>
                <div class="form-group mt-3">
                <label for="new_health_issue">Yeni Adres:</label>
                <input type="text" id="new_address" name="new_address" class="form-control" value="<?php echo $student['address']; ?>" required>
                </div>
                <div class="form-group mt-3">
                    <label for="new_parent_firstname">Yeni Veli Adı:</label>
                    <input type="text" id="new_parent_firstname" name="new_parent_firstname" class="form-control" value="<?php echo $student['parent_firstname']; ?>" required>
                </div>
                <div class="form-group mt-3">
                    <label for="new_parent_lastname">Yeni Veli Soyadı:</label>
                    <input type="text" id="new_parent_lastname" name="new_parent_lastname" class="form-control" value="<?php echo $student['parent_lastname']; ?>" required>
                </div>

                <div class="form-group mt-3">
                    <label for="new_parent_phone">Yeni Veli Telefonu:</label>
                    <input type="text" id="new_parent_phone" name="new_parent_phone" class="form-control" value="<?php echo $student['parent_phone']; ?>" required>
                </div>

                <div class="form-group mt-3">
                    <label for="new_parent_email">Yeni Veli E-posta:</label>
                    <input type="email" id="new_parent_email" name="new_parent_email" class="form-control" value="<?php echo $student['parent_email']; ?>" required>
                </div>

                <!-- Akademi Seçimi -->
                <div class="form-group mt-3">
                    <h5>Akademi Seçimi</h5>
                    <select class="form-control" name="academy" required>
                        <option value="">Akademi Seçin</option>
                        <?php foreach ($academies as $academy): ?>
                            <option value="<?php echo $academy['id']; ?>"><?php echo $academy['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Öğretmen Seçimi -->
                <div class="form-group mt-3">
                    <h5>Öğretmen Seçimi</h5>
                    <select class="form-control" name="teacher" required>
                        <option value="">Öğretmen Seçin</option>
                        <?php foreach ($teachers as $teacher): ?>
                            <option value="<?php echo $teacher['id']; ?>"><?php echo $teacher['first_name'] . ' ' . $teacher['last_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Ders Seçimi -->
                <div class="form-group mt-3">
                    <h5>Ders Seçimi</h5>
                    <select class="form-control" name="course" required>
                        <option value="">Ders Seçin</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $course['id']; ?>"><?php echo $course['course_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Sınıf Seçimi -->
                <div class="form-group mt-3">
                    <h5>Sınıf Seçimi</h5>
                    <select class="form-control" name="class" required>
                        <option value="">Sınıf Seçin</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>"><?php echo $class['class_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group mt-3">
                    <button type="submit" class="btn btn-primary">Güncelle</button>
                </div>
            </form>
    </main>
    </div>
    </div>

<?php require_once "footer.php"; ?>