<?php
global $db, $showErrors, $siteName, $siteShortName, $siteUrl;
session_start();
// Oturum kontrolü
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php"); // Giriş sayfasına yönlendir
    exit();
}
require_once "db_connection.php";
require_once "config.php";
require_once "admin_panel_header.php";
// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);

// Tüm akademileri getir
$query = "SELECT * FROM academies";
$stmt = $db->query($query);
$academies = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once "admin_panel_sidebar.php"; ?>
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
                <h2>Tüm Akademiler ve Öğrencileri</h2>
            </div>

            <div class="card-columns">
                <?php foreach ($academies as $academy): ?>
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $academy["name"]; ?></h5>
                            <p class="card-text">
                                İl: <?php echo $academy["city"]; ?><br>
                                İlçe: <?php echo $academy["district"]; ?><br>
                                Adres: <?php echo $academy["address"]; ?><br>
                                E-posta: <?php echo $academy["email"]; ?><br>
                                Çalışma Saatleri: <?php echo $academy["working_hours"]; ?>
                            </p>
                        </div>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">
                                <strong>Öğrenciler:</strong><br>
                                <?php
                                $academyId = $academy["id"];
                                $studentsInAcademyQuery = "SELECT students.* FROM students
                                                          INNER JOIN academy_students ON students.id = academy_students.student_id
                                                          WHERE academy_students.academy_id = ?";
                                $studentsInAcademyStmt = $db->prepare($studentsInAcademyQuery);
                                $studentsInAcademyStmt->execute([$academyId]);
                                $studentsInAcademy = $studentsInAcademyStmt->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($studentsInAcademy as $student) {
                                    echo $student["firstname"] . " " . $student["lastname"] . "<br>";
                                }
                                ?>
                            </li>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
</div>



<?php require_once "footer.php"; ?>
