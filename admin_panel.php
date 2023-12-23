<?php
global $db;
session_start();
// Oturum kontrolü
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php");
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

// Kullanıcıları veritabanından çekme
$query = "SELECT * FROM users";
$stmt = $db->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Öğretmenler listesi sorgusu
$query = "
    SELECT teachers.id AS teacher_id, teachers.first_name, teachers.last_name,
           teachers.tc_identity, teachers.email, teachers.phone,
           classes.class_name, courses.course_name
    FROM teachers
    LEFT JOIN courses ON teachers.course_id = courses.id
    LEFT JOIN classes ON teachers.class_id = classes.id
";


$stmt = $db->prepare($query);
$stmt->execute();
$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Dersler listesi sorgusu
$query = "SELECT * FROM courses";
$stmt = $db->query($query);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Sınıf listesi sorgusu
$query = "SELECT * FROM classes";
$stmt = $db->query($query);

// Sınıf verilerini alın
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Öğrenci listesi sorgusu
$query = "
    SELECT students.id AS student_id, students.firstname, students.lastname, students.email, students.tc_identity, students.phone,
           academies.name AS academy_name, courses.course_name, CONCAT(teachers.first_name, ' ', teachers.last_name) AS teacher_name,
           student_classes.id AS class_id, classes.class_name
    FROM students
    INNER JOIN academy_students ON students.id = academy_students.student_id
    INNER JOIN academies ON academy_students.academy_id = academies.id
    INNER JOIN student_courses ON students.id = student_courses.student_id
    INNER JOIN courses ON student_courses.course_id = courses.id
    INNER JOIN teachers ON courses.teacher_id = teachers.id
    INNER JOIN student_classes ON students.id = student_classes.student_id
    INNER JOIN classes ON student_classes.class_id = classes.id
";


$stmt = $db->prepare($query);
$stmt->execute();
$student_course_teacher_relations = $stmt->fetchAll(PDO::FETCH_ASSOC);



// Öğrenci sayısını getir
$studentCountQuery = "SELECT COUNT(*) as student_count FROM students";
$stmtStudentCount = $db->query($studentCountQuery);
$studentCount = $stmtStudentCount->fetch(PDO::FETCH_ASSOC);

// Öğretmen sayısını getir
$teacherCountQuery = "SELECT COUNT(*) as teacher_count FROM teachers";
$stmtTeacherCount = $db->query($teacherCountQuery);
$teacherCount = $stmtTeacherCount->fetch(PDO::FETCH_ASSOC);

?>
    <div class="container-fluid">
      <div class="row">
        <?php
        require_once "admin_panel_sidebar.php";
        ?>
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
          <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
            <h1 class="h2">Genel Bakış</h1>

          </div>


            <div class="row">
                <div class="col-md-6">
                    <div class="alert alert-info" role="alert">
                        Toplam Öğrenci Sayısı: <?php echo $studentCount['student_count']; ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="alert alert-info" role="alert">
                        Toplam Öğretmen Sayısı: <?php echo $teacherCount['teacher_count']; ?>
                    </div>
                </div>
            </div>

    <h4 style="display: inline-block; margin-right: 10px;">Öğrenciler</h4>
    <small><a style="color: #2b2f32;" href="student_list.php">Tüm Öğrenciler</a></small>
    <div class="table-responsive">
    <table class="table table-striped table-sm">
        <thead>
        <tr>
            <th scope="col">#</th>
            <th scope="col">Ad</th>
            <th scope="col">Soyad</th>
            <th scope="col">E-posta</th>
            <th scope="col">T.C. Kimlik No</th>
            <th scope="col">Telefon</th>
            <th scope="col">Akademi</th>
            <th scope="col">Sınıf</th>
            <th scope="col">Ders</th>
            <th scope="col">Öğretmen</th>
            <th scope="col">Profil</th>
        </tr>
        </thead>
      <tbody>
      <?php foreach ($student_course_teacher_relations as $relation): ?>
          <tr>
              <th scope="row"><?= $relation['student_id'] ?></th>
              <td><?= $relation['firstname'] ?></td>
              <td><?= $relation['lastname'] ?></td>
              <td><?= $relation['email'] ?></td>
              <td><?= $relation['tc_identity'] ?></td>
              <td><?= $relation['phone'] ?></td>
              <td><?= $relation['academy_name'] ?></td>
              <td><?= $relation['class_name'] ?></td>
              <td><?= $relation['course_name'] ?></td>
              <td><?= $relation['teacher_name'] ?></td>
              <td><a href="student_profile.php?id=<?php echo $relation['student_id']; ?>">Git</a></td>
          </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
            <!-- Öğretmenler Tablosu -->
            <h4 style="display: inline-block; margin-right: 10px;">Öğretmenler</h4>
            <small><a style="color: #2b2f32;" href="teachers_list.php">Tüm Öğretmenler</a></small>
            <div class="table-responsive">
                <table class="table table-striped table-sm">
                    <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">Ad</th>
                        <th scope="col">Soyad</th>
                        <th scope="col">Sınıf</th>
                        <th scope="col">Ders</th>
                        <th scope="col">T.C. Kimlik No</th>
                        <th scope="col">E-posta</th>
                        <th scope="col">Telefon</th>
                        <!-- Diğer öğretmen sütunları -->
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($teachers as $teacher): ?>
                        <tr>
                            <th scope="row"><?= isset($teacher['teacher_id']) ? $teacher['teacher_id'] : '' ?></th>
                            <td><?= isset($teacher['first_name']) ? $teacher['first_name'] : '' ?></td>
                            <td><?= isset($teacher['last_name']) ? $teacher['last_name'] : '' ?></td>
                            <td><?= isset($teacher['class_name']) ? $teacher['class_name'] : '' ?></td>
                            <td><?= isset($teacher['course_name']) ? $teacher['course_name'] : '' ?></td>
                            <td><?= isset($teacher['tc_identity']) ? $teacher['tc_identity'] : '' ?></td>
                            <td><?= isset($teacher['email']) ? $teacher['email'] : '' ?></td>
                            <td><?= isset($teacher['phone']) ? $teacher['phone'] : '' ?></td>

                            <!-- Diğer öğretmen verileri -->
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Dersler Tablosu -->
            <h4 style="display: inline-block; margin-right: 10px;">Dersler</h4>
            <small><a style="color: #2b2f32;" href="courses.php">Tüm Dersler</a></small>
            <div class="table-responsive">
                <table class="table table-striped table-sm">
                    <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">Ders Adı</th>
                        <th scope="col">Ders Kodu</th>
                        <th scope="col">Açıklama</th>
                        <!-- Diğer ders sütunları -->
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($courses as $course): ?>
                        <tr>
                            <th scope="row"><?= $course['id'] ?></th>
                            <td><?= $course['course_name'] ?></td>
                            <td><?= $course['course_code'] ?></td>
                            <td><?= $course['description'] ?></td>
                            <!-- Diğer ders verileri -->
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

                <h4 style="display: inline-block; margin-right: 10px;">Sınıflar</h4>
                <small><a style="color: #2b2f32;" href="class_list.php">Tüm Sınıflar</a></small>
                <div class="table-responsive">
                    <table class="table table-striped table-sm">
                        <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Ad</th>
                            <th scope="col">Kod</th>
                            <th scope="col">Açıklama</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($classes as $class): ?>
                            <tr>
                                <th scope="row"><?= $class['id'] ?></th>
                                <td><?= $class['class_name'] ?></td>
                                <td><?= $class['class_code'] ?></td>
                                <td><?= $class['class_description'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <h4 style="display: inline-block; margin-right: 10px;">Kullanıcılar</h4>
            <small><a style="color: #2b2f32;" href="user_list.php">Tüm Kullanıcılar</a></small>
            <div class="table-responsive">
                <table class="table table-striped table-sm">
                    <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">Ad</th>
                        <th scope="col">Soyad</th>
                        <th scope="col">E-posta</th>
                        <th scope="col">T.C. Kimlik No</th>
                        <th scope="col">Telefon</th>
                        <th scope="col">E-posta Doğrulaması</th>
                        <th scope="col">SMS Doğrulaması</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <th scope="row"><?= $user['id'] ?></th>
                            <td><?= $user['firstname'] ?></td>
                            <td><?= $user['lastname'] ?></td>
                            <td><?= $user['email'] ?></td>
                            <td><?= $user['tc'] ?></td>
                            <td><?= $user['phone'] ?></td>
                            <td><?= $user['verification_time_email_confirmed'] ? 'Doğrulandı' : 'Doğrulanmadı' ?></td>
                            <td><?= $user['verification_time_sms_confirmed'] ? 'Doğrulandı' : 'Doğrulanmadı' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
      </div>
</div>
<?php
require_once "footer.php";
?>
