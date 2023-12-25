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
                <h2>Tüm Akademiler ve Öğretmenleri</h2>
            </div>

            <table class="table">
                <thead>
                <tr>
                    <th>Akademi Adı</th>
                    <th>İl</th>
                    <th>İlçe</th>
                    <th>Adres</th>
                    <th>E-posta</th>
                    <th>Çalışma Saatleri</th>
                    <th>Öğretmenler</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($academies as $academy): ?>
                    <tr>
                        <td><?php echo $academy["name"]; ?></td>
                        <td><?php echo $academy["city"]; ?></td>
                        <td><?php echo $academy["district"]; ?></td>
                        <td><?php echo $academy["address"]; ?></td>
                        <td><?php echo $academy["email"]; ?></td>
                        <td><?php echo $academy["working_hours"]; ?></td>
                        <td>
                            <?php
                            $academyId = $academy["id"];
                            $teachersInAcademyQuery = "SELECT teachers.* FROM teachers
                                                          INNER JOIN academy_teachers ON teachers.id = academy_teachers.teacher_id
                                                          WHERE academy_teachers.academy_id = ?";
                            $teachersInAcademyStmt = $db->prepare($teachersInAcademyQuery);
                            $teachersInAcademyStmt->execute([$academyId]);
                            $teachersInAcademy = $teachersInAcademyStmt->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($teachersInAcademy as $teacher) {
                                echo $teacher["first_name"] . " " . $teacher["last_name"] . "<br>";
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </main>
    </div>
</div>

<?php require_once "footer.php"; ?>
