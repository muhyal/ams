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
$query = "SELECT * FROM teachers";
$stmt = $db->query($query);
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
           classes.class_name, courses.course_name, CONCAT(teachers.first_name, ' ', teachers.last_name) AS teacher_name
    FROM students
    INNER JOIN classes ON students.course_id = classes.id
    INNER JOIN courses ON students.course_id = courses.id
    INNER JOIN teachers ON courses.teacher_id = teachers.id
";

$stmt = $db->prepare($query);
$stmt->execute();
$student_course_teacher_relations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
  <body>
    <nav class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0">
      <a class="navbar-brand col-sm-3 col-md-2 mr-0" href="<?php echo $siteUrl ?>/admin_panel.php"><?php echo $siteName ?> - <?php echo $siteShortName ?></a>
      <input class="form-control form-control-dark w-100" type="text" placeholder="Ara" aria-label="Ara">
      <ul class="navbar-nav px-3">
        <li class="nav-item text-nowrap">
          <a class="nav-link" href="logout.php">Oturumu kapat</a>
        </li>
      </ul>
    </nav>
    <div class="container-fluid">
      <div class="row">


        <?php
        require_once "admin_panel_sidebar.php";
        ?>


        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
          <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
            <h1 class="h2">Genel Bakış</h1>
          </div>
<main>
  <h2>Öğrenciler</h2>
  <div class="table-responsive">
    <table class="table table-striped table-sm">
        <thead>
        <tr>
            <th scope="col">#</th>
            <th scope="col">Ad</th>
            <th scope="col">Soyad</th>
            <th scope="col">E-posta</th>
            <th scope="col">T.C. Kimlik</th>
            <th scope="col">Telefon</th>
            <th scope="col">Sınıf</th>
            <th scope="col">Ders</th>
            <th scope="col">Öğretmen</th>
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
              <td><?= $relation['class_name'] ?></td>
              <td><?= $relation['course_name'] ?></td>
              <td><?= $relation['teacher_name'] ?></td>
          </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</main>


            <main>
                <h2>Kullanıcılar</h2>
                <div class="table-responsive">
                    <table class="table table-striped table-sm">
                        <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Ad</th>
                            <th scope="col">Soyad</th>
                            <th scope="col">E-posta</th>
                            <th scope="col">T.C. Kimlik</th>
                            <th scope="col">Telefon</th>
                            <th scope="col">E-posta Doğrulandı Mı?</th>
                            <th scope="col">SMS Doğrulandı Mı?</th>
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
            </main>

            <main>
                <h2>Sınıflar</h2>
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
            </main>

            <!-- Öğretmenler Tablosu -->
            <main>
                <h2>Öğretmenler</h2>
                <div class="table-responsive">
                    <table class="table table-striped table-sm">
                        <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Ad</th>
                            <th scope="col">Soyad</th>
                            <th scope="col">E-posta</th>
                            <th scope="col">Telefon</th>
                            <!-- Diğer öğretmen alanları -->
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($teachers as $teacher): ?>
                            <tr>
                                <th scope="row"><?= $teacher['id'] ?></th>
                                <td><?= $teacher['first_name'] ?></td>
                                <td><?= $teacher['last_name'] ?></td>
                                <td><?= $teacher['email'] ?></td>
                                <td><?= $teacher['phone'] ?></td>
                                <!-- Diğer öğretmen verileri -->
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </main>

            <!-- Dersler Tablosu -->
            <main>
                <h2>Dersler</h2>
                <div class="table-responsive">
                    <table class="table table-striped table-sm">
                        <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Ders Adı</th>
                            <th scope="col">Ders Kodu</th>
                            <th scope="col">Açıklama</th>
                            <!-- Diğer ders alanları -->
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
            </main>


      </div>
</div>


<?php
require_once "footer.php";
?>
