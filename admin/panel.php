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
    header("Location: index.php"); // Giriş sayfasına yönlendir
    exit();
}

require_once(__DIR__ . '/../config/db_connection.php');
require_once('../config/config.php');
require_once "../src/functions.php";

// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);

// Kullanıcı ve akademi ilişkisini çekmek için bir SQL sorgusu
$getUserAcademyQuery = "SELECT academy_id FROM user_academy_assignment WHERE user_id = :user_id";
$stmtUserAcademy = $db->prepare($getUserAcademyQuery);
$stmtUserAcademy->bindParam(':user_id', $_SESSION["admin_id"], PDO::PARAM_INT);
$stmtUserAcademy->execute();
$associatedAcademies = $stmtUserAcademy->fetchAll(PDO::FETCH_COLUMN);

// Eğer kullanıcı hiçbir akademide ilişkilendirilmemişse veya bu akademilerden hiçbiri yoksa, uygun bir işlemi gerçekleştirin
if (empty($associatedAcademies)) {
    echo "Kullanıcınız bu işlem için yetkili değil!";
    exit();
}

// Eğitim danışmanının erişebileceği akademilerin listesini güncelle
$allowedAcademies = $associatedAcademies;

// Kullanıcı bilgileri sorgusu
$query = "
    SELECT
        users.id AS user_id,
        users.first_name,
        users.last_name,
        users.email,
        users.tc_identity,
        users.phone,
        user_types.type_name,
        users.verification_time_email_confirmed,
        users.verification_time_sms_confirmed
    FROM users
    INNER JOIN user_types ON users.user_type = user_types.id
LIMIT 5;
";

$stmt = $db->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->closeCursor();


// Öğretmenler listesi sorgusu
$query = "
    SELECT
        users.id AS user_id,
        users.first_name,
        users.last_name,
        users.tc_identity,
        users.email,
        users.phone,
        MAX(academies.name) AS academy_name,
        MAX(academy_classes.class_name) AS class_name,
        MAX(courses.course_name) AS course_name
    FROM
        users
    INNER JOIN course_plans ON users.id = course_plans.teacher_id
    INNER JOIN academies ON course_plans.academy_id = academies.id
    INNER JOIN academy_classes ON course_plans.class_id = academy_classes.id
    INNER JOIN courses ON course_plans.course_id = courses.id
    WHERE
        users.user_type = 4
        AND academies.id IN (" . implode(",", $allowedAcademies) . ")
    GROUP BY
        users.id
    LIMIT 5;
";

$stmt = $db->prepare($query);
$stmt->execute();
$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->closeCursor();

// Dersler listesi sorgusu
$query = "SELECT * FROM courses";
$stmt = $db->query($query);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Sınıf listesi sorgusu
$query = "SELECT * FROM academy_classes";
$stmt = $db->query($query);
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Öğrenci listesi sorgusu
$query = "
    SELECT
        users.id AS user_id,
        users.first_name,
        users.last_name,
        users.email,
        users.tc_identity,
        users.phone,
        MAX(academies.name) AS academy_name,
        MAX(academy_classes.class_name) AS class_name,
        MAX(courses.course_name) AS course_name,
        CONCAT(MAX(users_teachers.first_name), ' ', MAX(users_teachers.last_name)) AS teacher_name
    FROM users
    INNER JOIN course_plans ON users.id = course_plans.student_id
    INNER JOIN academies ON course_plans.academy_id = academies.id
    INNER JOIN courses ON course_plans.course_id = courses.id
    INNER JOIN users AS users_teachers ON course_plans.teacher_id = users_teachers.id AND users_teachers.user_type = 4
    INNER JOIN academy_classes ON course_plans.class_id = academy_classes.id
    WHERE
        users.user_type = 6
        AND academies.id IN (" . implode(",", $allowedAcademies) . ")
    GROUP BY
        users.id
    LIMIT 5;
";

$stmt = $db->prepare($query);
$stmt->execute();
$student_course_teacher_relations = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->closeCursor();

// Öğrenci sayısını getir
$studentCountQuery = "SELECT COUNT(*) as student_count FROM users WHERE user_type = 6";
$stmtStudentCount = $db->query($studentCountQuery);
$studentCount = $stmtStudentCount->fetch(PDO::FETCH_ASSOC);

// Öğretmen sayısını getir
$teacherCountQuery = "SELECT COUNT(*) as teacher_count FROM users WHERE user_type = 4";
$stmtTeacherCount = $db->query($teacherCountQuery);
$teacherCount = $stmtTeacherCount->fetch(PDO::FETCH_ASSOC);


// Kullanıcı sayısını getir
$userCountQuery = "SELECT COUNT(*) as user_count FROM users";
$stmtUserCount = $db->query($userCountQuery);
$userCount = $stmtUserCount->fetch(PDO::FETCH_ASSOC);

// Akademi sayısını getir
$academyCountQuery = "SELECT COUNT(*) as academy_count FROM academies";
$stmtAcademyCount = $db->query($academyCountQuery);
$academyCount = $stmtAcademyCount->fetch(PDO::FETCH_ASSOC);

if (isset($_POST['get_datetime'])) {
    // Return current date and time in JSON format
    $current_datetime = date("d.m.Y H:i");
    echo json_encode(['datetime' => $current_datetime]);
    exit();
}

// Bu haftanın başlangıç ve bitiş tarihlerini belirle
$thisWeekStart = new DateTime();
$thisWeekStart->setISODate(date('Y'), date('W'));
$thisWeekStart->setTime(0, 0, 0);

$thisWeekEnd = clone $thisWeekStart;
$thisWeekEnd->modify('+6 days');
$thisWeekEnd->setTime(23, 59, 59);

// Bu hafta doğan öğrencileri seç
$studentQuery = "SELECT id AS student_id, first_name, last_name, birth_date FROM users WHERE user_type = 6 AND DATE_FORMAT(birth_date, '%m-%d') BETWEEN DATE_FORMAT(:start, '%m-%d') AND DATE_FORMAT(:end, '%m-%d')";
$stmtStudent = $db->prepare($studentQuery);
$str = $thisWeekStart->format('Y-m-d');
$stmtStudent->bindParam(':start', $str);
$str1 = $thisWeekEnd->format('Y-m-d');
$stmtStudent->bindParam(':end', $str1);
$stmtStudent->execute();
$studentBirthdays = $stmtStudent->fetchAll(PDO::FETCH_ASSOC);


// Bu hafta doğan öğretmenleri seç
$teacherQuery = "SELECT id AS teacher_id, first_name, last_name, birth_date FROM users WHERE user_type = 4 AND DATE_FORMAT(birth_date, '%m-%d') BETWEEN DATE_FORMAT(:start, '%m-%d') AND DATE_FORMAT(:end, '%m-%d')";
$stmtTeacher = $db->prepare($teacherQuery);
$stmtTeacher->bindParam(':start', $str);
$stmtTeacher->bindParam(':end', $str1);
$stmtTeacher->execute();
$teacherBirthdays = $stmtTeacher->fetchAll(PDO::FETCH_ASSOC);

// Tanışma Dersleri listesi sorgusu
$introductoryCoursesQuery = "
    SELECT
        sc.id,
        CONCAT(u_teacher.first_name, ' ', u_teacher.last_name) AS teacher_name,
        u_teacher.id AS teacher_id,
        a.name AS academy_name,
        ac.class_name AS class_name,
        CONCAT(u_student.first_name, ' ', u_student.last_name) AS student_name,
        u_student.id AS student_id,
        c.course_name AS lesson_name,
        sc.course_date,
        sc.course_attendance
    FROM
        introductory_course_plans sc
        INNER JOIN users u_teacher ON sc.teacher_id = u_teacher.id AND u_teacher.user_type = 4
        INNER JOIN academies a ON sc.academy_id = a.id
        INNER JOIN academy_classes ac ON sc.class_id = ac.id
        INNER JOIN users u_student ON sc.student_id = u_student.id AND u_student.user_type = 6
        INNER JOIN courses c ON sc.course_id = c.id
    WHERE
        a.id IN (" . implode(",", $allowedAcademies) . ")
    LIMIT 5;
";

$stmtIntroductoryCourses = $db->prepare($introductoryCoursesQuery);
$stmtIntroductoryCourses->execute();
$introductoryCourses = $stmtIntroductoryCourses->fetchAll(PDO::FETCH_ASSOC);
$stmtIntroductoryCourses->closeCursor();

// Ders Planları listesi sorgusu
$coursePlansQuery = "
    SELECT
        sc.id,
        CONCAT(u_teacher.first_name, ' ', u_teacher.last_name) AS teacher_name,
        u_teacher.id AS teacher_id,
        a.name AS academy_name,
        ac.class_name AS class_name,
        CONCAT(u_student.first_name, ' ', u_student.last_name) AS student_name,
        u_student.id AS student_id,
        c.course_name AS lesson_name,
        sc.course_date_1,
        sc.course_date_2,
        sc.course_date_3,
        sc.course_date_4,
        sc.course_attendance_1,
        sc.course_attendance_2,
        sc.course_attendance_3,
        sc.course_attendance_4
    FROM
        course_plans sc
        INNER JOIN users u_teacher ON sc.teacher_id = u_teacher.id AND u_teacher.user_type = 4
        INNER JOIN academies a ON sc.academy_id = a.id
        INNER JOIN academy_classes ac ON sc.class_id = ac.id
        INNER JOIN users u_student ON sc.student_id = u_student.id AND u_student.user_type = 6
        INNER JOIN courses c ON sc.course_id = c.id
    WHERE
        a.id IN (" . implode(",", $allowedAcademies) . ")
    LIMIT 5; 
";

$stmtCoursePlans = $db->prepare($coursePlansQuery);
$stmtCoursePlans->execute();
$coursePlans = $stmtCoursePlans->fetchAll(PDO::FETCH_ASSOC);
$stmtCoursePlans->closeCursor();

$last7DaysSalesQuery = "
    SELECT
        a.name AS academy_name,
        DATE(sc.created_at) AS sale_date,
        COUNT(*) AS total_courses
    FROM
        course_plans sc
        RIGHT JOIN academies a ON sc.academy_id = a.id
    WHERE
        sc.created_at >= DATE(NOW()) - INTERVAL 7 DAY OR sc.created_at IS NULL
    GROUP BY
        a.id, sale_date
    ORDER BY
        academy_name, sale_date;
";

$stmtLast7DaysSales = $db->prepare($last7DaysSalesQuery);
$stmtLast7DaysSales->execute();
$last7DaysSales = $stmtLast7DaysSales->fetchAll(PDO::FETCH_ASSOC);
$stmtLast7DaysSales->closeCursor();

// Akademelerin isimlerini çekme
$academyNamesQuery = "SELECT id, name FROM academies";
$stmtAcademyNames = $db->query($academyNamesQuery);
$academyNamesData = $stmtAcademyNames->fetchAll(PDO::FETCH_KEY_PAIR);

// Toplam ders sayılarını çekme
$sqlTotalCourses = "SELECT academy_id, COUNT(student_id) AS total_courses FROM course_plans GROUP BY academy_id";
$stmtTotalCourses = $db->prepare($sqlTotalCourses);
$stmtTotalCourses->execute();
$resultTotalCourses = $stmtTotalCourses->fetchAll(PDO::FETCH_ASSOC);

// Verileri diziye alma
$academy_names_courses = [];
$total_courses_counts = [];

foreach ($resultTotalCourses as $row) {
    $academy_names_courses[] = $academyNamesData[$row["academy_id"]];
    $total_courses_counts[] = $row["total_courses"];
}

// Toplam öğrenci sayılarını çekme
$sqlTotalStudents = "SELECT academy_id, COUNT(DISTINCT student_id) AS total_students FROM course_plans GROUP BY academy_id";
$stmtTotalStudents = $db->prepare($sqlTotalStudents);
$stmtTotalStudents->execute();
$resultTotalStudents = $stmtTotalStudents->fetchAll(PDO::FETCH_ASSOC);

// Ders adlarını çekme
$sqlCourses = "SELECT id, course_name FROM courses";
$stmtCourses = $db->query($sqlCourses);
$coursesData = $stmtCourses->fetchAll(PDO::FETCH_KEY_PAIR);

// Toplam ders planı sayılarını çekme
$sqlTotalCoursePlans = "SELECT course_id, COUNT(student_id) AS total_course_plans FROM course_plans GROUP BY course_id";
$stmtTotalCoursePlans = $db->prepare($sqlTotalCoursePlans);
$stmtTotalCoursePlans->execute();
$resultTotalCoursePlans = $stmtTotalCoursePlans->fetchAll(PDO::FETCH_ASSOC);


// Verileri diziye alma
$course_names_plans = [];
$total_course_plans_counts = [];

foreach ($resultTotalCoursePlans as $row) {
    $course_names_plans[] = $coursesData[$row["course_id"]];
    $total_course_plans_counts[] = $row["total_course_plans"];
}


// Verileri diziye alma
$academy_names_students = [];
$total_students_counts = [];


foreach ($resultTotalStudents as $row) {
    $academy_names_students[] = $academyNamesData[$row["academy_id"]];
    $total_students_counts[] = $row["total_students"];
}
?>
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
require_once(__DIR__ . '/partials/header.php');
?>
        <?php
        require_once(__DIR__ . '/partials/sidebar.php');
        ?>
          <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
              <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                  <h1 class="h4"><i class="fas fa-dashboard"></i> Genel Bakış</h1>

                  <form id="searchForm" class="mb-3">
                      <div class="row">
                          <div class="col-md-6">
                              <input type="text" class="form-control" id="searchQuery" name="q" required placeholder="Arama yapılacak kelime">
                          </div>
                          <div class="col-md-4">
                              <select class="form-select" id="searchType" name="search_type">
                                  <option value="user">Kullanıcı</option>
                                  <option value="student">Öğrenci</option>
                                  <option value="teacher">Öğretmen</option>
                                  <option value="course">Ders</option>
                                  <option value="class">Sınıf</option>
                              </select>
                          </div>
                          <div class="col-md-2">
                              <button type="button" class="btn btn-primary" onclick="performSearch()">Ara</button>
                          </div>
                      </div>
                  </form>
              </div>

              <div id="searchResults"></div>



              <div class="row">
                  <div class="col-md-3">
                      <div class="alert alert-success" role="alert">
                          <i class="fas fa-users"></i> Toplam Kullanıcı: <?php echo $userCount['user_count']; ?>
                      </div>
                  </div>
                  <div class="col-md-3">
                      <div class="alert alert-success" role="alert">
                          <i class="fas fa-graduation-cap"></i> Toplam Öğrenci: <?php echo $studentCount['student_count']; ?>
                      </div>
                  </div>
                  <div class="col-md-3">
                      <div class="alert alert-success" role="alert">
                          <i class="fas fa-chalkboard-teacher"></i> Toplam Öğretmen: <?php echo $teacherCount['teacher_count']; ?>
                      </div>
                  </div>
                  <div class="col-md-3">
                      <div class="alert alert-success" role="alert">
                          <i class="fas fa-university"></i> Toplam Akademi: <?php echo $academyCount['academy_count']; ?>
                      </div>
                  </div>
              </div>


              <style>
                  .chart-container {
                      display: flex;
                      justify-content: space-between;
                      flex-wrap: wrap; /* Eğer tüm grafikler yan yana sığmazsa alt satıra geçmesini sağlamak için ekledik */
                  }

                  .chart {
                      width: 40%; /* %100 genişlik */
                      height: 250px;
                  }

                  .chart:nth-child(n+2) {
                      width: 20%; /* %100 genişlik */
                  }


              </style>
              <div class="chart-container mt-3">
                  <div class="chart">
                      <canvas id="last7DaysSalesChart"></canvas>
                  </div>

                  <div class="chart">
                      <canvas id="academyCourseChart"></canvas>
                  </div>

                  <div class="chart">
                      <canvas id="academyStudentChart"></canvas>
                  </div>

                  <div class="chart">
                      <canvas id="totalCoursePlansChart"></canvas>
                  </div>
              </div>

             <script>
                  // PHP'den gelen kurs satışları verileri (JSON formatında)
                  var last7DaysSalesData = <?php echo json_encode($last7DaysSales); ?>;

                  // Tüm günlerin tarihleri (son 7 gün) - doğru sıralama
                  var allDays = Array.from({ length: 7 }, (_, i) => {
                      var date = new Date();
                      date.setDate(date.getDate() - i);
                      return date.toISOString().split('T')[0];
                  }).reverse(); // Tarihleri ters çevir

                  // Unikal akademilerin listesi
                  var academies = [...new Set(last7DaysSalesData.map(sale => sale.academy_name))];

                  // Renk paleti
                  var backgroundColors = [
                      'rgba(255, 99, 132, 0.2)',
                      'rgba(255, 159, 64, 0.2)',
                      'rgba(255, 205, 86, 0.2)',
                      'rgba(75, 192, 192, 0.2)',
                      'rgba(54, 162, 235, 0.2)',
                      'rgba(153, 102, 255, 0.2)',
                      'rgba(201, 203, 207, 0.2)'
                  ];

                  var borderColors = [
                      'rgb(255, 99, 132)',
                      'rgb(255, 159, 64)',
                      'rgb(255, 205, 86)',
                      'rgb(75, 192, 192)',
                      'rgb(54, 162, 235)',
                      'rgb(153, 102, 255)',
                      'rgb(201, 203, 207)'
                  ];

                  // Renk atama için bir nesne
                  var academyColors = {};

                  // Akademilere sırayla renk atama
                  academies.forEach((academy, index) => {
                      academyColors[academy] = {
                          backgroundColor: backgroundColors[index % backgroundColors.length],
                          borderColor: borderColors[index % borderColors.length],
                          borderWidth: 1
                      };
                  });

                  // Chart.js ile çubuk grafiği yapma
                  var ctx = document.getElementById('last7DaysSalesChart').getContext('2d');

                  var datasets = academies.map(academy => {
                      return {
                          label: academy,
                          data: allDays.map(day => {
                              var sale = last7DaysSalesData.find(s => s.sale_date === day && s.academy_name === academy);
                              return sale ? sale.total_courses : 0;
                          }),
                          ...academyColors[academy]
                      };
                  });

                  var formattedDates = allDays.map(date => {
                      const [year, month, day] = date.split('-');
                      return `${day}.${month}`;
                  });

                  var myChart = new Chart(ctx, {
                      type: 'line',
                      data: {
                          labels: formattedDates,
                          datasets: datasets
                      },
                      options: {
                          plugins: {
                              legend: {
                                  display: false,
                                  position: 'top', // 'bottom', 'top', 'left', 'right' gibi değerler kullanılabilir
                                  labels: {
                                      font: {
                                          size: 10 // veya başka bir değer
                                      }
                                  }
                              },
                              title: {
                                  display: true,
                                  text: 'Son 7 Günün Satışları',
                                  font: {
                                      size: 10 // veya başka bir değer
                                  }
                              }
                          },
                          elements: {
                              line: {
                                  fill: false,
                                  tension: 0.4,
                                  borderWidth: 3 // Çizgi kalınlığını istediğiniz değere ayarlayın
                              }
                          },
                          scales: {
                              y: {
                                  beginAtZero: true
                              }
                          }
                      }
                  });
             </script>


              <script>
                  var academyNamesCourses = <?php echo json_encode(array_values($academy_names_courses)); ?>;
                  var totalCoursesCounts = <?php echo json_encode(array_values($total_courses_counts)); ?>;

                  var ctxCourses = document.getElementById('academyCourseChart').getContext('2d');
                  var academyCourseChart = new Chart(ctxCourses, {
                      type: 'radar',
                      data: {
                          labels: academyNamesCourses,
                          datasets: [{
                              data: totalCoursesCounts,
                              backgroundColor: backgroundColors.slice(0, totalCoursesCounts.length),
                              borderColor: borderColors.slice(0, totalCoursesCounts.length),
                              borderWidth: 1
                          }]
                      },
                      options: {
                          plugins: {
                              legend: {
                                  display: false,
                                  position: 'top', // 'bottom', 'top', 'left', 'right' gibi değerler kullanılabilir
                                  labels: {
                                      font: {
                                          size: 10 // veya başka bir değer
                                      }
                                  }
                              },
                              title: {
                                  display: true,
                                  text: 'Akademi x Toplam Ders', // Burada başlığı istediğiniz gibi değiştirebilirsiniz
                                  font: {
                                      size: 10 // veya başka bir değer
                                  }
                              }
                          },
                          scale: {
                              ticks: {
                                  beginAtZero: true
                              }
                          }
                      }
                  });
              </script>


              <script>
                  var academyNamesStudents = <?php echo json_encode(array_values($academy_names_students)); ?>;
                  var totalStudentsCounts = <?php echo json_encode(array_values($total_students_counts)); ?>;

                  var ctxStudents = document.getElementById('academyStudentChart').getContext('2d');
                  var academyStudentChart = new Chart(ctxStudents, {
                      type: 'polarArea',
                      data: {
                          labels: academyNamesStudents,
                          datasets: [{
                              data: totalStudentsCounts,
                              backgroundColor: backgroundColors.slice(0, totalStudentsCounts.length),
                              borderColor: borderColors.slice(0, totalStudentsCounts.length),
                              borderWidth: 1
                          }]
                      },
                      options: {
                          plugins: {
                              legend: {
                                  display: false,
                                  position: 'left', // 'bottom', 'top', 'left', 'right' gibi değerler kullanılabilir
                                  labels: {
                                      font: {
                                          size: 10 // veya başka bir değer
                                      }
                                  }
                              },
                              title: {
                                  display: true,
                                  text: 'Akademi x Toplam Öğrenci', // Burada başlığı istediğiniz gibi değiştirebilirsiniz
                                  font: {
                                      size: 10 // veya başka bir değer
                                  }
                              }
                          },
                          scale: {
                              ticks: {
                                  beginAtZero: true
                              }
                          }
                      }
                  });
              </script>

              <script>
                  var courseNamesPlans = <?php echo json_encode(array_values($course_names_plans)); ?>;
                  var totalCoursePlansCounts = <?php echo json_encode(array_values($total_course_plans_counts)); ?>;

                  var ctxTotalCoursePlans = document.getElementById('totalCoursePlansChart').getContext('2d');
                  var totalCoursePlansChart = new Chart(ctxTotalCoursePlans, {
                      type: 'polarArea',
                      data: {
                          labels: courseNamesPlans,
                          datasets: [{
                              data: totalCoursePlansCounts,
                              backgroundColor: backgroundColors.slice(0, totalCoursePlansCounts.length),
                              borderColor: borderColors.slice(0, totalCoursePlansCounts.length),
                              borderWidth: 1
                          }]
                      },
                      options: {
                          plugins: {
                              legend: {
                                  display: false,
                                  position: 'left', // 'bottom', 'top', 'left', 'right' gibi değerler kullanılabilir
                                  labels: {
                                      font: {
                                          size: 10 // veya başka bir değer
                                      }
                                  }
                              },
                              title: {
                                  display: true,
                                  text: 'Derslere Göre Toplam Planlar', // Burada başlığı istediğiniz gibi değiştirebilirsiniz
                                  font: {
                                      size: 10 // veya başka bir değer
                                  }
                              }
                          },
                          scale: {
                              ticks: {
                                  beginAtZero: true
                              }
                          }
                      }
                  });
              </script>




              <h4 class="mt-5" style="display: inline-block; margin-right: 10px;">Tanışma Dersleri</h4>
              <small><a href="introductory_courses.php">Tüm Tanışma Dersleri</a></small>
              <div class="table-responsive small">
                  <table class="table table-striped table-sm" style="border: 1px solid #ddd;">
                      <thead>
                      <tr>
                          <th>Öğretmen</th>
                          <th>Öğrenci</th>
                          <th>Akademi</th>
                          <th>Sınıf</th>
                          <th>Ders</th>
                          <th>Ders Tarihi</th>
                          <th>Katılım Durumu</th>
                          <th></th>
                      </tr>
                      </thead>
                      <tbody>
                      <?php foreach ($introductoryCourses as $introductoryCourse): ?>
                          <tr>
                              <td><a class="text-decoration-none text-black" href='user_profile.php?id=<?= $introductoryCourse['teacher_id'] ?>'><i class='fas fa-user text-secondary'></i> <?= $introductoryCourse['teacher_name'] ?></a></td>
                              <td><a class="text-decoration-none text-black" href='user_profile.php?id=<?= $introductoryCourse['student_id'] ?>'><i class='fas fa-user text-secondary'></i> <?= $introductoryCourse['student_name'] ?></td>
                              <td><?= $introductoryCourse['academy_name'] ?></td>
                              <td><?= $introductoryCourse['class_name'] ?></td>
                              <td><?= $introductoryCourse['lesson_name'] ?></td>
                              <td style="font-size: small;"><?= date(DATETIME_FORMAT, strtotime($introductoryCourse['course_date'])); ?></td>
                              <td>
                                  <?php
                                  $attendanceStatus = $introductoryCourse['course_attendance'];

                                  switch ($attendanceStatus) {
                                      case 0:
                                          echo "<i class='fas fa-calendar-day text-primary'></i> Planlandı"; // Henüz katılmadı ve planlandı durumu için takvim simgesi
                                          break;
                                      case 1:
                                          echo "<i class='fas fa-calendar-check text-success'></i> Katıldı"; // Katılım varsa yeşil tik
                                          break;
                                      case 2:
                                          echo "<i class='fas fa-calendar-times text-danger'></i> Katılmadı"; // Katılmadı durumu için kırmızı çarpı
                                          break;
                                      case 3:
                                          echo "<i class='fas fa-calendar-times text-warning'></i> Mazeretli"; // Mazeretli durumu için sarı çarpı
                                          break;
                                      default:
                                          echo "<i class='fas fa-question text-secondary'></i> Belirsiz"; // Belirli bir duruma uygun işlem yapılmadıysa soru işareti
                                          break;
                                  }
                                  ?>
                              </td>                              <td>
                                  <a href="edit_introductory_course_plan.php?id=<?php echo $introductoryCourse['id']; ?>" class="btn btn-primary btn-sm">
                                      <i class="fas fa-edit"></i>
                                  </a>
                              </td>
                          </tr>
                      <?php endforeach; ?>
                      </tbody>
                  </table>
              </div>

              <h4 style="display: inline-block; margin-right: 10px;">Ders Planları</h4>
              <small><a href="course_plans.php">Tüm Ders Planları</a></small>
              <div class="table-responsive small">
                  <table class="table table-striped table-sm" style="border: 1px solid #ddd;">
                      <thead>
                      <tr>
                          <th>Öğretmen</th>
                          <th>Öğrenci</th>
                          <th>Akademi</th>
                          <th>Sınıf</th>
                          <th>Ders</th>
                          <th>1. Ders</th>
                          <th>2. Ders</th>
                          <th>3. Ders</th>
                          <th>4. Ders</th>
                          <th><i class="fas fa-clipboard-check"></i> 1</th>
                          <th><i class="fas fa-clipboard-check"></i> 2</th>
                          <th><i class="fas fa-clipboard-check"></i> 3</th>
                          <th><i class="fas fa-clipboard-check"></i> 4</th>
                          <th></th>
                      </tr>
                      </thead>
                      <tbody>
                      <?php foreach ($coursePlans as $coursePlan): ?>
                          <tr>
                              <td><a class="text-decoration-none text-black" href='user_profile.php?id=<?= $coursePlan['teacher_id'] ?>'><i class='fas fa-user text-secondary'></i> <?= $coursePlan['teacher_name'] ?></a></td>
                              <td><a class="text-decoration-none text-black" href='user_profile.php?id=<?= $coursePlan['student_id'] ?>'><i class='fas fa-user text-secondary'></i> <?= $coursePlan['student_name'] ?></td>
                              <td><?= $coursePlan['academy_name'] ?></td>
                              <td><?= $coursePlan['class_name'] ?></td>
                              <td><?= $coursePlan['lesson_name'] ?></td>
                              <td style="font-size: small;"><?= date(DATETIME_FORMAT, strtotime($coursePlan['course_date_1'])); ?></td>
                              <td style="font-size: small;"><?= date(DATETIME_FORMAT, strtotime($coursePlan['course_date_2'])); ?></td>
                              <td style="font-size: small;"><?= date(DATETIME_FORMAT, strtotime($coursePlan['course_date_3'])); ?></td>
                              <td style="font-size: small;"><?= date(DATETIME_FORMAT, strtotime($coursePlan['course_date_4'])); ?></td>

                              <?php for ($i = 1; $i <= 4; $i++): ?>
                                  <td>
                                      <?php
                                      $attendanceStatus = $coursePlan["course_attendance_$i"];

                                      switch ($attendanceStatus) {
                                          case 0:
                                              echo "<i class='fas fa-calendar-day text-primary'></i>"; // Henüz katılmadı ve planlandı durumu için takvim simgesi
                                              break;
                                          case 1:
                                              echo "<i class='fas fa-calendar-check text-success'></i>"; // Katılım varsa yeşil tik
                                              break;
                                          case 2:
                                              echo "<i class='fas fa-calendar-times text-danger'></i>"; // Katılmadı durumu için kırmızı çarpı
                                              break;
                                          case 3:
                                              echo "<i class='fas fa-calendar-times text-warning'></i></a>"; // Mazeretli durumu için sarı çarpı
                                              break;
                                          default:
                                              echo "<i class='fas fa-question text-secondary'></i>"; // Belirli bir duruma uygun işlem yapılmadıysa soru işareti
                                              break;
                                      }
                                      ?>
                                  </td>
                              <?php endfor; ?>


                              <td>
                                  <a href="edit_course_plan.php?id=<?= $coursePlan['id']; ?>" class="btn btn-warning btn-sm">
                                      <i class="fas fa-edit"></i>
                                  </a>
                                  <a href="course_plans.php?id=<?= $coursePlan['id']; ?>" class="btn btn-primary btn-sm">
                                      <i class="fas fa-user-graduate"></i>
                                  </a>
                              </td>
                          </tr>
                      <?php endforeach; ?>

                      </tbody>
                  </table>
              </div>

              <h4 style="display: inline-block; margin-right: 10px;">Telafi Dersleri</h4>
              <small><a href="rescheduled_courses.php">Tüm Telafi Dersleri</a></small>
              <div class="table-responsive small">
                  <table class="table table-striped table-sm" style="border: 1px solid #ddd;">
                      <thead>
                      <tr>
                                  <th>Telafi Ders Planı</th>
                                  <th>Öğretmen</th>
                                  <th>Akademi</th>
                                  <th>Sınıf</th>
                                  <th>Öğrenci</th>
                                  <th>Ders</th>
                                  <th>Telafi Dersi Tarihi</th>
                                  <th>Telafi Dersi Katılım</th>
                                  <th>İşlemler</th>
                              </tr>
                              </thead>
                              <tbody>
                              <?php
                              // Veritabanı sorgularını burada gerçekleştirin ve sonuçları tabloya ekleyin
                              $rescheduled_courses_query = "
    SELECT
        rc.id,
        cp.id AS course_plan_id,
        CONCAT(u_teacher.first_name, ' ', u_teacher.last_name) AS teacher_name,
        u_teacher.id AS teacher_id,
        a.name AS academy_name,
        ac.class_name AS class_name,
        CONCAT(u_student.first_name, ' ', u_student.last_name) AS student_name,
        u_student.id AS student_id,
        c.course_name AS lesson_name,
        rc.course_date,
        rc.course_attendance
    FROM
        rescheduled_courses rc
        INNER JOIN course_plans cp ON rc.course_plan_id = cp.id
        INNER JOIN users u_teacher ON cp.teacher_id = u_teacher.id AND u_teacher.user_type = 4
        INNER JOIN academies a ON cp.academy_id = a.id
        INNER JOIN academy_classes ac ON cp.class_id = ac.id
        INNER JOIN users u_student ON cp.student_id = u_student.id AND u_student.user_type = 6
        INNER JOIN courses c ON cp.course_id = c.id
    WHERE
        a.id IN (" . implode(",", $allowedAcademies) . ")
    LIMIT 5;
";

                              $stmt = $db->query($rescheduled_courses_query);
                              $rescheduled_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

                              foreach ($rescheduled_courses as $rescheduled_course) {
                                  echo "<tr>";
                                  echo "<td><a href='course_plans.php?id={$rescheduled_course['course_plan_id']}' class='btn btn-outline-success btn-sm'><i class='fas fa-external-link-alt'></i> İlgili Ders Planı</a></td>"; // View button added
                                  echo "<td><a href='user_profile.php?id={$rescheduled_course['teacher_id']}'>{$rescheduled_course['teacher_name']}</a></td>";
                                  echo "<td>{$rescheduled_course['academy_name']}</td>";
                                  echo "<td>{$rescheduled_course['class_name']}</td>";
                                  echo "<td><a href='user_profile.php?id={$rescheduled_course['student_id']}'>{$rescheduled_course['student_name']}</a></td>";
                                  echo "<td>{$rescheduled_course['lesson_name']}</td>";
                                  // Tarih ve saatleri Türkiye tarih ve saat dilimine göre biçimlendir
                                  echo "<td>" . date('d.m.Y H:i', strtotime($rescheduled_course['course_date'])) . "</td>";

                                  // Ders katılımları
                                  echo "<td>";

                                  switch ($rescheduled_course['course_attendance']) {
                                      case 0:
                                          echo "<i class='fas fa-calendar-check text-primary'></i> Planlandı"; // Henüz katılmadı ve planlandı durumu için takvim simgesi
                                          break;
                                      case 1:
                                          echo "<i class='fas fa-calendar-check text-success'></i> Katıldı"; // Katılım varsa yeşil tik
                                          break;
                                      case 2:
                                          echo "<i class='fas fa-calendar-times text-danger'></i> Katılmadı"; // Katılmadı durumu için kırmızı çarpı
                                          break;
                                      case 3:
                                          echo "<i class='fas fa-calendar-times text-warning'></i></a> Mazeretli"; // Mazeretli durumu için sarı çarpı
                                          break;
                                      default:
                                          echo "<i class='fas fa-question text-secondary'></i> Belirsiz"; // Belirli bir duruma uygun işlem yapılmadıysa soru işareti
                                          break;
                                  }

                                  echo "</td>";
                                  echo "<td><a href='edit_rescheduled_course_plan.php?id={$rescheduled_course['id']}' class='btn btn-primary btn-sm'><i class='fas fa-edit'></i></a></td>";
                                  echo "</tr>";

                              }
                              ?>
                              </tbody>
                          </table>

              </div>


              <h4 style="display: inline-block; margin-right: 10px;">Öğrenciler</h4>
    <small><a href="students.php">Tüm Öğrenciler</a></small>
              <div class="table-responsive small">
                  <table class="table table-striped table-sm" style="border: 1px solid #ddd;">
        <thead>
        <tr>
            <!--  <th scope="col">#</th>-->
            <th scope="col">Tam Ad</th>
            <th scope="col">E-posta</th>
            <th scope="col">T.C. No</th>
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
              <td><?= $relation['first_name'] ?> <?= $relation['last_name'] ?></td>
              <td onmouseover="this.innerHTML='<?= isset($relation['email']) ? $relation['email'] : '' ?>'" onmouseout="this.innerHTML='<?= isset($relation['email']) ? '******************' . strstr($relation['email'], '@') : '' ?>'"><?= isset($relation['email']) ? '*******************' . strstr($relation['email'], '@') : '' ?></td>
              <td onmouseover="this.innerHTML='<?= isset($relation['tc_identity']) ? $relation['tc_identity'] : '' ?>'" onmouseout="this.innerHTML='<?= isset($relation['tc_identity']) ? '*******' . substr($relation['tc_identity'], -4) : '' ?>'"><?= isset($relation['tc_identity']) ? '*******' . substr($relation['tc_identity'], -4) : '' ?></td>
              <td onmouseover="this.innerHTML='<?= isset($relation['phone']) ? $relation['phone'] : '' ?>'" onmouseout="this.innerHTML='<?= isset($relation['phone']) ? '******' . substr($relation['phone'], -4) : '' ?>'"><?= isset($relation['phone']) ? '******' . substr($relation['phone'], -4) : '' ?></td>
              <td><?= $relation['academy_name'] ?></td>
              <td><?= $relation['class_name'] ?></td>
              <td><?= $relation['course_name'] ?></td>
              <td><?= $relation['teacher_name'] ?></td>
              <td>
                  <a href="user_profile.php?id=<?php echo $relation['user_id']; ?>" class="btn btn-primary btn-sm">
                      <i class="fas fa-user"></i>
                  </a>
                  <a href="edit_user.php?id=<?php echo $relation['user_id']; ?>" class="btn btn-warning btn-sm">
                      <i class="fas fa-edit"></i>
                  </a>
              </td>
          </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
            <!-- Öğretmenler Tablosu -->
            <h4 style="display: inline-block; margin-right: 10px;">Öğretmenler</h4>
            <small><a href="teachers.php">Tüm Öğretmenler</a></small>
              <div class="table-responsive small">
                  <table class="table table-striped table-sm" style="border: 1px solid #ddd;">
                    <thead>
                    <tr>
                        <!--  <th scope="col">#</th>-->
                        <th scope="col">Tam Ad</th>
                          <th scope="col">Sınıf</th>
                          <th scope="col">Ders</th>
                          <th scope="col">T.C. No</th>
                          <th scope="col">E-posta</th>
                          <th scope="col">Telefon</th>
                        <th scope="col"></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($teachers as $teacher): ?>
                        <tr>
<!--                            <td>--><?php //= isset($teacher['user_id']) ? $teacher['user_id'] : '' ?><!--</td>-->
                            <td><?= isset($teacher['first_name']) ? $teacher['first_name'] : '' ?> <?= isset($teacher['last_name']) ? $teacher['last_name'] : '' ?></td>
                            <td><?= isset($teacher['class_name']) ? $teacher['class_name'] : '' ?></td>
                            <td><?= isset($teacher['course_name']) ? $teacher['course_name'] : '' ?></td>
                            <td onmouseover="revealOriginal(this)" onmouseout="hideOriginal(this)">
                                <span class="hidden-value" style="display: none;"><?= isset($teacher['tc_identity']) ? $teacher['tc_identity'] : '' ?></span>
                                <span class="masked-value"><?= isset($teacher['tc_identity']) ? '*******' . substr($teacher['tc_identity'], -4) : '' ?></span>
                            </td>

                            <script>
                                function revealOriginal(element) {
                                    element.querySelector('.hidden-value').style.display = 'inline';
                                    element.querySelector('.masked-value').style.display = 'none';
                                }

                                function hideOriginal(element) {
                                    element.querySelector('.hidden-value').style.display = 'none';
                                    element.querySelector('.masked-value').style.display = 'inline';
                                }
                            </script>
                            <td onmouseover="this.innerHTML='<?= isset($teacher['email']) ? $teacher['email'] : '' ?>'" onmouseout="this.innerHTML='<?= isset($teacher['email']) ? '******************' . strstr($teacher['email'], '@') : '' ?>'"><?= isset($teacher['email']) ? '*******************' . strstr($teacher['email'], '@') : '' ?></td>
                            <td onmouseover="this.innerHTML='<?= isset($teacher['phone']) ? $teacher['phone'] : '' ?>'" onmouseout="this.innerHTML='<?= isset($teacher['phone']) ? '******' . substr($teacher['phone'], -4) : '' ?>'"><?= isset($teacher['phone']) ? '******' . substr($teacher['phone'], -4) : '' ?></td>
                            <td>
                                <a href="user_profile.php?id=<?= $teacher['user_id'] ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-user"></i>
                                </a>
                                <a href="edit_user.php?id=<?php echo $teacher['user_id']; ?>" class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

              <h4 style="display: inline-block; margin-right: 10px;">Kullanıcılar</h4>
              <small><a href="users.php">Tüm Kullanıcılar</a></small>
              <div class="table-responsive small">
                  <table class="table table-striped table-sm" style="border: 1px solid #ddd;">
                      <thead>
                      <tr>
                          <!--  <th scope="col">#</th>-->
                          <th scope="col">Tam Ad</th>
                          <th scope="col">E-posta</th>
                          <th scope="col">T.C. No</th>
                          <th scope="col">Telefon</th>
                          <th scope="col">E-posta</th>
                          <th scope="col">SMS</th>
                          <th scope="col">Rolü</th>
                          <th scope="col"></th>

                      </tr>
                      </thead>
                      <tbody>
                      <?php foreach ($users as $user): ?>
                          <tr>
                              <!--  <th scope="row"><?= $user['id'] ?></th>-->
                              <td><?= $user['first_name'] ?> <?= $user['last_name'] ?></td>
                              <td onmouseover="this.innerHTML='<?= isset($user['email']) ? $user['email'] : '' ?>'" onmouseout="this.innerHTML='<?= isset($user['email']) ? '******************' . strstr($user['email'], '@') : '' ?>'"><?= isset($user['email']) ? '*******************' . strstr($user['email'], '@') : '' ?></td>
                              <td onmouseover="this.innerHTML='<?= isset($user['tc_identity']) ? $user['tc_identity'] : '' ?>'" onmouseout="this.innerHTML='<?= isset($user['tc_identity']) ? '*******' . substr($user['tc_identity'], -4) : '' ?>'"><?= isset($user['tc_identity']) ? '*******' . substr($user['tc_identity'], -4) : '' ?></td>
                              <td onmouseover="this.innerHTML='<?= isset($user['phone']) ? $user['phone'] : '' ?>'" onmouseout="this.innerHTML='<?= isset($user['phone']) ? '******' . substr($user['phone'], -4) : '' ?>'"><?= isset($user['phone']) ? '******' . substr($user['phone'], -4) : '' ?></td>
                              <td><?= $user['verification_time_email_confirmed'] ? '<i class="fas fa-check text-success"></i> Doğrulandı' : '<i class="fas fa-times text-danger"></i> Doğrulanmadı' ?></td>
                              <td><?= $user['verification_time_sms_confirmed'] ? '<i class="fas fa-check text-success"></i> Doğrulandı' : '<i class="fas fa-times text-danger"></i> Doğrulanmadı' ?></td>
                              <td><?= $user['type_name'] ?></td>
                              <td>
                                  <a href="user_profile.php?id=<?= $user['user_id'] ?>" class="btn btn-primary btn-sm">
                                      <i class="fas fa-user"></i>
                                  </a>
                                  <a href="edit_user.php?id=<?php echo $user['user_id']; ?>" class="btn btn-warning btn-sm">
                                      <i class="fas fa-edit"></i>
                                  </a>
                              </td>
                          </tr>
                      <?php endforeach; ?>
                      </tbody>
                  </table>
              </div>


              <div class="container">
                  <div class="row">
                      <div class="col-md-6">
                          <h5><i class="fas fa-birthday-cake"></i> Öğrenci Doğum Günleri (Bu Hafta)</h5>
                              <table class="table table-striped table-sm" style="border: 1px solid #ddd;">
                                  <thead>
                                  <tr>
                                  <th>Ad</th>
                                  <th>Soyad</th>
                                  <th>Yaş</th>
                                  <th>Doğum Tarihi</th>
                                  <th>Profil</th>
                              </tr>
                              </thead>
                              <tbody>
                              <?php foreach ($studentBirthdays as $student): ?>
                                  <?php
                                  $birthdayDate = new DateTime($student['birth_date']);
                                  $todayDate = new DateTime();
                                  $age = $todayDate->diff($birthdayDate)->y;
                                  ?>
                                  <tr>
                                      <td><?php echo $student['first_name']; ?></td>
                                      <td><?php echo $student['last_name']; ?></td>
                                      <td><?php echo $age; ?></td>
                                      <td><?php echo $birthdayDate->format('d.m.Y'); ?></td>
                                      <td><a href="user_profile.php?id=<?php echo $student['student_id']; ?>" class="btn btn-primary btn-sm">
                                              <i class="fas fa-user"></i>
                                          </a></td>
                                  </tr>
                              <?php endforeach; ?>
                              </tbody>
                          </table>
                      </div>
                      <div class="col-md-6">
                          <h5><i class="fas fa-birthday-cake"></i> Öğretmen Doğum Günleri (Bu Hafta)</h5>
                              <table class="table table-striped table-sm" style="border: 1px solid #ddd;">
                              <thead>
                              <tr>
                                  <th>Ad</th>
                                  <th>Soyad</th>
                                  <th>Yaş</th>
                                  <th>Doğum Tarihi</th>
                                  <th>Profil</th>
                              </tr>
                              </thead>
                              <tbody>
                              <?php foreach ($teacherBirthdays as $teacher): ?>
                                  <?php
                                  $birthdayDate = new DateTime($teacher['birth_date']);
                                  $todayDate = new DateTime();
                                  $age = $todayDate->diff($birthdayDate)->y;
                                  ?>
                                  <tr>
                                      <td><?php echo $teacher['first_name']; ?></td>
                                      <td><?php echo $teacher['last_name']; ?></td>
                                      <td><?php echo $age; ?></td>
                                      <td><?php echo $birthdayDate->format('d.m.Y'); ?></td>
                                      <td><a href="user_profile.php?id=<?php echo $teacher['teacher_id']; ?>" class="btn btn-primary btn-sm">
                                              <i class="fas fa-user"></i>
                                          </a></td>
                                  </tr>
                              <?php endforeach; ?>
                              </tbody>
                          </table>
                      </div>
                  </div>
              </div>
      </div>
</div>

              <script>
                  // Enter tuşuna basıldığında performSearch fonksiyonunu çağırma
                  document.getElementById("searchForm").addEventListener("keydown", function(event) {
                      if (event.key === "Enter") {
                          event.preventDefault();
                          performSearch();
                      }
                  });

                  function performSearch() {
                      const searchQuery = document.getElementById("searchQuery").value;
                      const searchType = document.getElementById("searchType").value;

                      // AJAX isteği gönderme
                      const xhr = new XMLHttpRequest();
                      xhr.open("GET", `search_results.php?q=${searchQuery}&search_type=${searchType}`, true);
                      xhr.onreadystatechange = function() {
                          if (xhr.readyState === 4 && xhr.status === 200) {
                              document.getElementById("searchResults").innerHTML = xhr.responseText;
                          }
                      };
                      xhr.send();
                  }
              </script>

              <?php require_once('../admin/partials/footer.php'); ?>

