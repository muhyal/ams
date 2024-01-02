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
// Kullanıcı adını al
$adminUsername = $_SESSION['admin_username'];
require_once "db_connection.php";
require_once "config.php";
// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);

// Kullanıcıları veritabanından çekme
$query = "SELECT * FROM users";
$stmt = $db->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Öğretmenler listesi sorgusu
$query = "
   SELECT 
    teachers.id AS teacher_id,
    teachers.first_name,
    teachers.last_name,
    teachers.tc_identity,
    teachers.email,
    teachers.phone,
    classes.class_name,
    courses.course_name
FROM teachers
LEFT JOIN teacher_courses ON teachers.id = teacher_courses.teacher_id
LEFT JOIN courses ON teacher_courses.course_id = courses.id
LEFT JOIN classes ON teacher_courses.class_id = classes.id;
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
           academies.name AS academy_name,classes.class_name AS class_name, courses.course_name, CONCAT(teachers.first_name, ' ', teachers.last_name) AS teacher_name
    FROM students
    INNER JOIN student_courses ON students.id = student_courses.student_id
    INNER JOIN academies ON student_courses.academy_id = academies.id
    INNER JOIN courses ON student_courses.course_id = courses.id
    INNER JOIN teachers ON student_courses.teacher_id = teachers.id
    INNER JOIN classes ON student_courses.class_id = classes.id
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

// Kullanıcı sayısını getir
$userCountQuery = "SELECT COUNT(*) as user_count FROM users";
$stmtUserCount = $db->query($userCountQuery);
$userCount = $stmtUserCount->fetch(PDO::FETCH_ASSOC);

// Kullanıcı sayısını getir
$academyCountQuery = "SELECT COUNT(*) as academy_count FROM academies";
$stmtAcademyCount = $db->query($academyCountQuery);
$academyCount = $stmtAcademyCount->fetch(PDO::FETCH_ASSOC);

if (isset($_POST['get_datetime'])) {
    // Return current date and time in JSON format
    date_default_timezone_set('Europe/Istanbul');
    $current_datetime = date("d.m.Y H:i");
    echo json_encode(['datetime' => $current_datetime]);
    exit();
}
?>
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script>
    // Function to update date and time using AJAX
    function updateDateTime() {
        $.ajax({
            url: window.location.href, // Current page URL
            type: 'POST',
            data: { get_datetime: true },
            dataType: 'json',
            success: function(data) {
                var datetime = data.datetime;
                $('#current-datetime').text(datetime);
            },
            error: function() {
                console.error('Error fetching date and time');
            }
        });
    }

    // Update date and time initially and every 5 seconds
    updateDateTime();
    setInterval(updateDateTime, 5000);
</script>
<?php
require_once "admin_panel_header.php";
?>
        <?php
        require_once "admin_panel_sidebar.php";
        ?>
          <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
              <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                  <h1 class="h4"><i class="fas fa-dashboard"></i> Genel Bakış</h1>

                  <div id="datetime-container">
                      <i class="fas fa-clock-four"></i>
                      <!-- This is where the date and time will be displayed -->
                      <?php
                      date_default_timezone_set('Europe/Istanbul');
                      $current_datetime = date("d.m.Y H:i");
                      echo "<span id='current-datetime'>$current_datetime</span>";
                      ?>
                  </div>
                  Selam 👋, <?php echo $adminUsername; ?> 🍀
                  <div class="btn-group">
                      <button type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                          <i class="fas fa-plus"></i> Yeni
                      </button>
                      <ul class="dropdown-menu">
                          <li><a class="dropdown-item" href="add_user.php"><i class="fas fa-user"></i> Kullanıcı</a></li>
                          <li><a class="dropdown-item" href="add_student.php"><i class="fas fa-user"></i> Öğrenci</a></li>
                          <li><a class="dropdown-item" href="add_teacher.php"><i class="fas fa-chalkboard-teacher"></i> Öğretmen</a></li>
                          <li><a class="dropdown-item" href="accounting.php"><i class="fas fa-file-invoice-dollar"></i> Muhasebe kaydı</a></li>
                      </ul>
                  </div>



              </div>


            <div class="row">
                <div class="col-md-3">
                    <div class="alert alert-info" role="alert">
                        Toplam Kullanıcı Sayısı: <?php echo $userCount['user_count']; ?>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="alert alert-info" role="alert">
                        Toplam Öğrenci Sayısı: <?php echo $studentCount['student_count']; ?>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="alert alert-info" role="alert">
                        Toplam Öğretmen Sayısı: <?php echo $teacherCount['teacher_count']; ?>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="alert alert-info" role="alert">
                        Toplam Akademi Sayısı: <?php echo $academyCount['academy_count']; ?>
                    </div>
                </div>
            </div>


    <h4 style="display: inline-block; margin-right: 10px;">Öğrenciler</h4>
    <small><a style="color: #2b2f32;" href="student_list.php">Tüm Öğrenciler</a></small>
              <div class="table-responsive small">
                  <table class="table table-striped table-sm" style="border: 1px solid #ddd;">
        <thead>
        <tr>
            <!--  <th scope="col">#</th>-->
            <th scope="col">Ad</th>
            <th scope="col">Soyad</th>
            <th scope="col">E-posta</th>
            <th scope="col">T.C. Kimlik No</th>
            <th scope="col">Telefon</th>
            <th scope="col">Akademi</th>
            <th scope="col">Sınıf</th>
            <th scope="col">Ders</th>
            <th scope="col">Öğretmen</th>
            <th scope="col"></th>
        </tr>
        </thead>
      <tbody>
      <?php foreach ($student_course_teacher_relations as $relation): ?>
          <tr>
              <!--   <th scope="row"><?= $relation['student_id'] ?></th>-->
              <td><?= $relation['firstname'] ?></td>
              <td><?= $relation['lastname'] ?></td>
              <td><?= $relation['email'] ?></td>
              <td><?= $relation['tc_identity'] ?></td>
              <td><?= $relation['phone'] ?></td>
              <td><?= $relation['academy_name'] ?></td>
              <td><?= $relation['class_name'] ?></td>
              <td><?= $relation['course_name'] ?></td>
              <td><?= $relation['teacher_name'] ?></td>
              <td>
                  <a href="student_profile.php?id=<?php echo $relation['student_id']; ?>" class="btn btn-primary btn-sm">
                      <i class="fas fa-arrow-right"></i>
                  </a>
              </td>
          </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
            <!-- Öğretmenler Tablosu -->
            <h4 style="display: inline-block; margin-right: 10px;">Öğretmenler</h4>
            <small><a style="color: #2b2f32;" href="teacher_list.php">Tüm Öğretmenler</a></small>
              <div class="table-responsive small">
                  <table class="table table-striped table-sm" style="border: 1px solid #ddd;">
                    <thead>
                    <tr>
                        <!--   <th scope="col">#</th>-->
                          <th scope="col">Ad</th>
                          <th scope="col">Soyad</th>
                          <th scope="col">Sınıf</th>
                          <th scope="col">Ders</th>
                          <th scope="col">T.C. Kimlik No</th>
                          <th scope="col">E-posta</th>
                          <th scope="col">Telefon</th>
                          <th scope="col"></th>

                          <!-- Diğer öğretmen sütunları -->
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($teachers as $teacher): ?>
                        <tr>
                            <!--  <th scope="row"><?= isset($teacher['teacher_id']) ? $teacher['teacher_id'] : '' ?></th>-->
                            <td><?= isset($teacher['first_name']) ? $teacher['first_name'] : '' ?></td>
                            <td><?= isset($teacher['last_name']) ? $teacher['last_name'] : '' ?></td>
                            <td><?= isset($teacher['class_name']) ? $teacher['class_name'] : '' ?></td>
                            <td><?= isset($teacher['course_name']) ? $teacher['course_name'] : '' ?></td>
                            <td><?= isset($teacher['tc_identity']) ? $teacher['tc_identity'] : '' ?></td>
                            <td><?= isset($teacher['email']) ? $teacher['email'] : '' ?></td>
                            <td><?= isset($teacher['phone']) ? $teacher['phone'] : '' ?></td>
                            <td>
                                <a href="teacher_profile.php?id=<?= $teacher['teacher_id'] ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-arrow-right"></i>
                                </a>
                            </td>

                            <!-- Diğer öğretmen verileri -->
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

              <h4 style="display: inline-block; margin-right: 10px;">Kullanıcılar</h4>
              <small><a style="color: #2b2f32;" href="user_list.php">Tüm Kullanıcılar</a></small>
              <div class="table-responsive small">
                  <table class="table table-striped table-sm" style="border: 1px solid #ddd;">
                      <thead>
                      <tr>
                          <!--  <th scope="col">#</th>-->
                          <th scope="col">Ad</th>
                          <th scope="col">Soyad</th>
                          <th scope="col">E-posta</th>
                          <th scope="col">T.C. Kimlik No</th>
                          <th scope="col">Telefon</th>
                          <th scope="col">E-posta Doğrulaması</th>
                          <th scope="col">SMS Doğrulaması</th>
                          <th scope="col"></th>

                      </tr>
                      </thead>
                      <tbody>
                      <?php foreach ($users as $user): ?>
                          <tr>
                              <!--  <th scope="row"><?= $user['id'] ?></th>-->
                              <td><?= $user['firstname'] ?></td>
                              <td><?= $user['lastname'] ?></td>
                              <td><?= $user['email'] ?></td>
                              <td><?= $user['tc'] ?></td>
                              <td><?= $user['phone'] ?></td>
                              <td><?= $user['verification_time_email_confirmed'] ? '<i class="fas fa-check text-success"></i> Doğrulandı' : '<i class="fas fa-times text-danger"></i> Doğrulanmadı' ?></td>
                              <td><?= $user['verification_time_sms_confirmed'] ? '<i class="fas fa-check text-success"></i> Doğrulandı' : '<i class="fas fa-times text-danger"></i> Doğrulanmadı' ?></td>
                              <td>
                                  <a href="user_profile.php?id=<?= $user['id'] ?>" class="btn btn-primary btn-sm">
                                      <i class="fas fa-arrow-right"></i>
                                  </a>
                              </td>
                          </tr>
                      <?php endforeach; ?>
                      </tbody>
                  </table>
              </div>

            <!-- Dersler Tablosu -->
            <h4 style="display: inline-block; margin-right: 10px;">Dersler</h4>
            <small><a style="color: #2b2f32;" href="courses.php">Tüm Dersler</a></small>
              <div class="table-responsive small">
                  <table class="table table-striped table-sm" style="border: 1px solid #ddd;">
                    <thead>
                    <tr>
                        <!--  <th scope="col">#</th>-->
                          <th scope="col">Ders Adı</th>
                          <th scope="col">Ders Kodu</th>
                          <th scope="col">Açıklama</th>
                          <!-- Diğer ders sütunları -->
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($courses as $course): ?>
                        <tr>
                            <!--    <th scope="row"><?= $course['id'] ?></th>-->
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
              <div class="table-responsive small">
                  <table class="table table-striped table-sm" style="border: 1px solid #ddd;">
                        <thead>
                        <tr>
                            <!--   <th scope="col">#</th>-->
                            <th scope="col">Ad</th>
                            <th scope="col">Kod</th>
                            <th scope="col">Açıklama</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($classes as $class): ?>
                            <tr>
                             <!--   <th scope="row"><?= $class['id'] ?></th>-->
                                <td><?= $class['class_name'] ?></td>
                                <td><?= $class['class_code'] ?></td>
                                <td><?= $class['class_description'] ?></td>
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
