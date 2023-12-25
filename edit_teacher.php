<?php
global $db, $showErrors, $siteName, $siteShortName, $siteUrl;
// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);

require_once "config.php";require_once "db_connection.php";

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
    $query = "UPDATE teachers SET first_name = ?, last_name = ?, tc_identity = ?, birth_date = ?, phone = ?, email = ?, course_id = ?, class_id = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$firstName, $lastName, $tcIdentity, $birthDate, $phone, $email, $selectedCourse, $selectedClass, $id]);

    // Öğretmenin hangi akademilerde görev aldığını al
    $selectedAcademies = isset($_POST["academies"]) ? $_POST["academies"] : [];

    // Önce öğretmenin mevcut akademi ilişkilerini sil
    $deleteAcademyRelationQuery = "DELETE FROM academy_teachers WHERE teacher_id = ?";
    $deleteAcademyRelationStmt = $db->prepare($deleteAcademyRelationQuery);
    $deleteAcademyRelationStmt->execute([$id]);

    // Sonra yeni akademi ilişkilerini ekleyerek öğretmeni güncelle
    foreach ($selectedAcademies as $academyId) {
        $insertAcademyRelationQuery = "INSERT INTO academy_teachers (academy_id, teacher_id) VALUES (?, ?)";
        $insertAcademyRelationStmt = $db->prepare($insertAcademyRelationQuery);
        $insertAcademyRelationStmt->execute([$academyId, $id]);
    }
}

 // Öğretmenin hangi derslerde görev aldığını al
    $selectedCourses = isset($_POST["courses"]) ? $_POST["courses"] : [];

    // Önce öğretmenin mevcut ders ilişkilerini sil
    $deleteCourseRelationQuery = "DELETE FROM course_teachers WHERE teacher_id = ?";
    $deleteCourseRelationStmt = $db->prepare($deleteCourseRelationQuery);
    $deleteCourseRelationStmt->execute([$id]);

    // Sonra yeni ders ilişkilerini ekleyerek öğretmeni güncelle
    foreach ($selectedCourses as $courseId) {
        $insertCourseRelationQuery = "INSERT INTO course_teachers (course_id, teacher_id) VALUES (?, ?)";
        $insertCourseRelationStmt = $db->prepare($insertCourseRelationQuery);
        $insertCourseRelationStmt->execute([$courseId, $id]);
    }


if (isset($_GET["id"])) {
    $teacher_id = $_GET["id"];
    $select_query = "SELECT teachers.*, courses.course_name, classes.class_name 
                     FROM teachers
                     LEFT JOIN courses ON teachers.course_id = courses.id
                     LEFT JOIN classes ON teachers.class_id = classes.id
                     WHERE teachers.id = ?";
    $stmt = $db->prepare($select_query);
    $stmt->execute([$teacher_id]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Courses and Classes
$courses_query = "SELECT * FROM courses";
$classes_query = "SELECT * FROM classes";

$courses_stmt = $db->query($courses_query);
$classes_stmt = $db->query($classes_query);

$courses = $courses_stmt->fetchAll(PDO::FETCH_ASSOC);
$classes = $classes_stmt->fetchAll(PDO::FETCH_ASSOC);

// Akademileri getirme
$academies_query = "SELECT * FROM academies";
$academies_stmt = $db->query($academies_query);
$academies = $academies_stmt->fetchAll(PDO::FETCH_ASSOC);

// Dersleri getir
$teacherCoursesQuery = "SELECT course_id FROM course_teachers WHERE teacher_id = ?";
$teacherCoursesStmt = $db->prepare($teacherCoursesQuery);
$teacherCoursesStmt->execute([$teacher_id]);
$teacherCourses = $teacherCoursesStmt->fetchAll(PDO::FETCH_COLUMN);

// Dersleri getirme
$courses_query = "SELECT * FROM courses";
$courses_stmt = $db->query($courses_query);
$courses = $courses_stmt->fetchAll(PDO::FETCH_ASSOC);


// Öğretmenin mevcut akademilerini getirme
$teacherAcademiesQuery = "SELECT academy_id FROM academy_teachers WHERE teacher_id = ?";
$teacherAcademiesStmt = $db->prepare($teacherAcademiesQuery);
$teacherAcademiesStmt->execute([$id]);
$teacherAcademies = $teacherAcademiesStmt->fetchAll(PDO::FETCH_COLUMN);

// Öğretmenin hangi derslerde görev aldığını al
    $selectedCourses = isset($_POST["courses"]) ? $_POST["courses"] : [];

    // Önce öğretmenin mevcut ders ilişkilerini sil
    $deleteCourseRelationQuery = "DELETE FROM course_teachers WHERE teacher_id = ?";
    $deleteCourseRelationStmt = $db->prepare($deleteCourseRelationQuery);
    $deleteCourseRelationStmt->execute([$teacher_id]);

    // Sonra yeni ders ilişkilerini ekleyerek öğretmeni güncelle
    foreach ($selectedCourses as $courseId) {
        $insertCourseRelationQuery = "INSERT INTO course_teachers (teacher_id, course_id) VALUES (?, ?)";
        $insertCourseRelationStmt = $db->prepare($insertCourseRelationQuery);
        $insertCourseRelationStmt->execute([$teacher_id, $courseId]);

}

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
                <!-- Ders Seçimi -->
                <label for="course">Ders:</label>
                <select id="course" name="course">
                    <?php foreach ($courses as $course): ?>
                        <option value="<?= $course['id'] ?>" <?= ($teacher['course_id'] == $course['id']) ? 'selected' : '' ?>>
                            <?= $course['course_name'] ?>
                        </option>
                    <?php endforeach; ?>
                </select><br>

                <!-- Sınıf Seçimi -->
                <label for="class">Sınıf:</label>
                <select id="class" name="class">
                    <?php foreach ($classes as $class): ?>
                        <option value="<?= $class['id'] ?>" <?= ($teacher['class_id'] == $class['id']) ? 'selected' : '' ?>>
                            <?= $class['class_name'] ?>
                        </option>
                    <?php endforeach; ?>
                </select><br>

                <!-- Akademileri Seçim Kutuları -->
                <label for="academies">Görev Aldığı Akademiler:</label><br>
                <?php foreach ($academies as $academy): ?>
                    <?php
                    // Öğretmenin görev aldığı akademilerin tam listesi
                    $teacherAcademyIds = array_map('intval', $teacherAcademies);
                    ?>
                    <input type="checkbox" id="academy_<?php echo $academy['id']; ?>" name="academies[]" value="<?php echo $academy['id']; ?>"
                        <?php echo in_array($academy['id'], $teacherAcademyIds) ? 'checked' : ''; ?>>
                    <label for="academy_<?php echo $academy['id']; ?>"><?php echo $academy['name']; ?></label><br>
                <?php endforeach; ?>
                <br>

                <!-- Dersleri Gösteren Form -->
                <h3>Görev Aldığı Dersler</h3>
                <form method="post">
                    <?php foreach ($courses as $course): ?>
                        <input type="checkbox" id="course_<?php echo $course['id']; ?>" name="courses[]" value="<?php echo $course['id']; ?>"
                            <?php echo in_array($course['id'], $teacherCourses) ? 'checked' : ''; ?>>
                        <label for="course_<?php echo $course['id']; ?>"><?php echo $course['course_name']; ?></label><br>
                    <?php endforeach; ?>
                    <br>

                    <button type="submit" name="update_courses">Dersleri Güncelle</button>
                </form>

                <button type="submit" name="edit_teacher">Öğretmeni Düzenle</button>
            </form>

            <a href="teachers_list.php">Öğretmen Listesi</a>
            <?php
            require_once "footer.php";
            ?>
