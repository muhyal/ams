<?php
// Veritabanı bağlantısı ve diğer gerekli dosyaların dahil edilmesi
global $db;
session_start();
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php");
    exit();
}
require_once "db_connection.php";
require_once "config.php";
require_once "admin_panel_header.php";

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

            <table class="table">
                <thead>
                <tr>
                    <th>Akademi Adı</th>
                    <th>İl</th>
                    <th>İlçe</th>
                    <th>Adres</th>
                    <th>E-posta</th>
                    <th>Çalışma Saatleri</th>
                    <th>Öğrenciler</th>
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
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </main>
    </div>
</div>

<?php require_once "footer.php"; ?>
