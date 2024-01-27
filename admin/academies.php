<?php
/**
 * @copyright Copyright (c) 2024, KUTBU
 * @author Muhammed Yalçınkaya <muhammed.yalcinkaya@kutbu.com>
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
 */

global $db, $showErrors, $siteName, $siteShortName, $siteUrl;
session_start();
session_regenerate_id(true);

// Oturum kontrolü
if (!isset($_SESSION["admin_id"])) {
    header("Location: index.php");
    exit();
}

require_once(__DIR__ . '/../config/db_connection.php');
require_once('../config/config.php');

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

// Yetki kontrolü fonksiyonu
function checkPermission() {
    if ($_SESSION["admin_type"] != 1) {
        // Yetki hatası
        echo "Bu işlemi gerçekleştirmek için yetkiniz yok!";
        exit();
    }
}

// Akademi işlemleri
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    checkPermission();

    if (isset($_POST["add_academy"])) {
        // Akademi ekleme işlemi
        $name = $_POST["name"];
        $phone_number = $_POST["phone_number"];
        $mobile_number = $_POST["mobile_number"];
        $city = $_POST["city"];
        $district = $_POST["district"];
        $address = $_POST["address"];
        $email = $_POST["email"];
        $working_hours = $_POST["working_hours"];

        $query = "INSERT INTO academies (name, phone_number, mobile_number, city, district, address, email, working_hours)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([$name, $phone_number, $mobile_number, $city, $district, $address, $email, $working_hours]);
    } elseif (isset($_POST["edit_academy"])) {
        // Akademi düzenleme işlemi
        $academyId = $_POST["academy_id"];
        $name = $_POST["name"];
        $phone_number = $_POST["phone_number"];
        $mobile_number = $_POST["mobile_number"];
        $city = $_POST["city"];
        $district = $_POST["district"];
        $address = $_POST["address"];
        $email = $_POST["email"];
        $working_hours = $_POST["working_hours"];
        $query = "UPDATE academies 
                  SET name = ?, phone_number = ?, mobile_number = ?, city = ?, district = ?, address = ?, email = ?, working_hours = ?
                  WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$name, $phone_number, $mobile_number, $city, $district, $address, $email, $working_hours, $academyId]);
    } elseif (isset($_POST["delete_academy"])) {
        checkPermission();


        // Akademi silme işlemi
        $academyId = $_POST["academy_id"];
        // Uyarı mesajı
        echo '<script>
                var confirmDelete = confirm("Bu akademiyi silmek istediğinizden emin misiniz?");
                if (confirmDelete) {
                    window.location.href = "?delete=' . $academyId . '";
                }
              </script>';
    }
}

// Silme işlemi gerçekleşirse
if (isset($_GET["delete"])) {
    $deleteAcademyId = $_GET["delete"];

    $deleteQuery = "DELETE FROM academies WHERE id = ?";
    $deleteStmt = $db->prepare($deleteQuery);
    $deleteStmt->execute([$deleteAcademyId]);

    echo '<div class="alert alert-success" role="alert">
            Akademi başarıyla silindi!
          </div>';
}

// Tüm akademileri getir
$query = "SELECT * FROM academies WHERE id IN (" . implode(",", $allowedAcademies) . ")";
$stmt = $db->query($query);
$academies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Akademi düzenleme formu
if (isset($_GET["edit"])) {
    $editAcademyId = $_GET["edit"];
    $editQuery = "SELECT * FROM academies WHERE id = ?";
    $editStmt = $db->prepare($editQuery);
    $editStmt->execute([$editAcademyId]);
    $editAcademy = $editStmt->fetch(PDO::FETCH_ASSOC);
}
?>

<?php
require_once(__DIR__ . '/partials/header.php');
?>
<div class="container-fluid">
    <div class="row">
        <?php
        require_once(__DIR__ . '/partials/sidebar.php');
        ?>        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
                <h2>Akademi İşlemleri</h2>
                <!-- Akademi Ekle Butonu -->
                <button class="btn btn-primary" onclick="showAddForm()">Akademi Ekle</button>
            </div>

            <!-- Tüm Akademileri Listeleme -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Tüm Akademiler</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($academies as $academy): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo $academy["name"]; ?></h5>
                                        <p class="card-text">
                                            <?php echo $academy["city"] . ', ' . $academy["district"]; ?>
                                        </p>
                                        <a href="?edit=<?php echo $academy["id"]; ?>" class="btn btn-warning btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>

                                        <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?php echo $academy['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <a href="#" id="datepicker-<?php echo $academy["id"]; ?>" class="btn btn-primary btn-sm"><i class="fa fa-calendar-day"></i> Ciro Raporu</a>

                                        <!-- Öğretmenler -->
                                        <?php
                                        $teachersQuery = "SELECT DISTINCT u.id, u.first_name, u.last_name
                                FROM course_plans cp
                                JOIN users u ON cp.teacher_id = u.id
                                WHERE cp.academy_id = ?
                                LIMIT 5";
                                        $teachersStmt = $db->prepare($teachersQuery);
                                        $teachersStmt->execute([$academy["id"]]);
                                        $teachers = $teachersStmt->fetchAll(PDO::FETCH_ASSOC);

                                        if (!empty($teachers)) {
                                            echo '<h5 class="card-title mt-3">Öğretmenler:</h5>';
                                            echo '<ul>';
                                            foreach ($teachers as $teacher) {
                                                echo '<li><a class="text-decoration-none text-black" href="user_profile.php?id=' . $teacher["id"] . '">' . $teacher["first_name"] . ' ' . $teacher["last_name"] . '</a></li>';
                                            }
                                            echo '</ul>';
                                        }
                                        ?>

                                        <!-- Öğrenciler -->
                                        <?php
                                        $studentsQuery = "SELECT DISTINCT u.id, u.first_name, u.last_name
                                FROM course_plans cp
                                JOIN users u ON cp.student_id = u.id
                                WHERE cp.academy_id = ?
                                LIMIT 5";
                                        $studentsStmt = $db->prepare($studentsQuery);
                                        $studentsStmt->execute([$academy["id"]]);
                                        $students = $studentsStmt->fetchAll(PDO::FETCH_ASSOC);

                                        if (!empty($students)) {
                                            echo '<h5 class="card-title">Öğrenciler:</h5>';
                                            echo '<ul>';
                                            foreach ($students as $student) {
                                                echo '<li><a class="text-decoration-none text-black" href="user_profile.php?id=' . $student["id"] . '">' . $student["first_name"] . ' ' . $student["last_name"] . '</a></li>';
                                            }
                                            echo '</ul>';
                                        }
                                        ?>

                                        <!-- Dersler -->
                                        <?php
                                        $coursesQuery = "SELECT DISTINCT c.course_name
                                FROM course_plans cp
                                JOIN courses c ON cp.course_id = c.id
                                WHERE cp.academy_id = ?
                                LIMIT 5";
                                        $coursesStmt = $db->prepare($coursesQuery);
                                        $coursesStmt->execute([$academy["id"]]);
                                        $courses = $coursesStmt->fetchAll(PDO::FETCH_ASSOC);

                                        if (!empty($courses)) {
                                            echo '<h5 class="card-title">Dersler:</h5>';
                                            echo '<ul>';
                                            foreach ($courses as $course) {
                                                echo '<li>' . $course["course_name"] . '</li>';
                                            }
                                            echo '</ul>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>


            <!-- Akademi Ekleme Formu -->
            <form method="post" id="addForm" style="display: none;">
                <h3>Akademi Ekle</h3>
                <div class="mb-3">
                    <label for="name" class="form-label">Adı:</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="phone_number" class="form-label">Sabit Telefon No:</label>
                    <input type="text" name="phone_number" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="mobile_number" class="form-label">Cep Telefon No:</label>
                    <input type="text" name="mobile_number" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="city" class="form-label">İl:</label>
                    <input type="text" name="city" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="district" class="form-label">İlçe:</label>
                    <input type="text" name="district" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="address" class="form-label">Adres:</label>
                    <textarea name="address" class="form-control" required></textarea>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">E-posta:</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="working_hours" class="form-label">Çalışma Saatleri:</label>
                    <input type="text" name="working_hours" class="form-control" required>
                </div>
                <div class="mb-3">
                    <button type="submit" name="add_academy" class="btn btn-primary">Akademi Ekle</button>
                </div>
            </form>

            <hr>


            <?php
            if (isset($_GET["edit"])) {
                $editAcademyId = $_GET["edit"];
                $editQuery = "SELECT * FROM academies WHERE id = ?";
                $editStmt = $db->prepare($editQuery);
                $editStmt->execute([$editAcademyId]);
                $editAcademy = $editStmt->fetch(PDO::FETCH_ASSOC);
                ?>
                <!-- Akademi Düzenleme Formu -->
                <form method="post" id="editForm" <?php if (isset($_GET["edit"])) echo 'style="display: block;"'; else echo 'style="display: none;"'; ?>>
                    <h3>Akademi Düzenle</h3>

                    <input type="hidden" name="academy_id" value="<?php echo $editAcademy["id"]; ?>">
                    <div class="mb-3">
                        <label for="name" class="form-label">Adı:</label>
                        <input type="text" name="name" class="form-control" value="<?php echo $editAcademy["name"]; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone_number" class="form-label">Sabit Telefon No:</label>
                        <input type="text" name="phone_number" class="form-control" value="<?php echo $editAcademy["phone_number"]; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="mobile_number" class="form-label">Cep Telefon No:</label>
                        <input type="text" name="mobile_number" class="form-control" value="<?php echo $editAcademy["mobile_number"]; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="city" class="form-label">İl:</label>
                        <input type="text" name="city" class="form-control" value="<?php echo $editAcademy["city"]; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="district" class="form-label">İlçe:</label>
                        <input type="text" name="district" class="form-control" value="<?php echo $editAcademy["district"]; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Adres:</label>
                        <textarea name="address" class="form-control" required><?php echo $editAcademy["address"]; ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">E-posta:</label>
                        <input type="email" name="email" class="form-control" value="<?php echo $editAcademy["email"]; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="working_hours" class="form-label">Çalışma Saatleri:</label>
                        <input type="text" name="working_hours" class="form-control" value="<?php echo $editAcademy["working_hours"]; ?>" required>
                    </div>
                    <div class="mb-3">
                        <button type="submit" name="edit_academy" class="btn btn-primary">Akademi Düzenle</button>
                    </div>
                </form>

                <?php
            }
            ?>
            <?php require_once('../admin/partials/footer.php'); ?>
            <script>
                function confirmDelete(academyId) {
                    // Additional permission check for delete operation
                    if (<?php echo $_SESSION["admin_type"]; ?> !== 1) {
                        alert("Bu işlemi gerçekleştirmek için yetkiniz yok!");
                        return;
                    }

                    var confirmDelete = confirm("Bu akademiyi silmek istediğinizden emin misiniz?");
                    if (confirmDelete) {
                        window.location.href = "?delete=" + academyId;
                    }
                }

                function showAddForm() {
                    document.getElementById('addForm').style.display = 'block';
                    document.getElementById('editForm').style.display = 'none';
                    document.getElementById('teachersHeader').style.display = 'none';
                    document.getElementById('teachersList').style.display = 'none';
                }

                function showEditForm() {
                    document.getElementById('editForm').style.display = 'block';
                    document.getElementById('addForm').style.display = 'none';
                    document.getElementById('teachersHeader').style.display = 'block';
                    document.getElementById('teachersList').style.display = 'block';
                }
            </script>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    <?php foreach ($academies as $academy): ?>
                    // Initialize Pikaday date picker for each academy
                    var picker_<?php echo $academy["id"]; ?> = new Pikaday({
                        field: document.getElementById('datepicker-<?php echo $academy["id"]; ?>'),
                        format: 'YYYY-MM-DD',
                        onSelect: function (date) {
                            // Format the selected date
                            var formattedDate = moment(date).format('YYYY-MM-DD');

                            // Get the academy_id from the existing URL
                            var academyId = <?php echo $academy["id"]; ?>;

                            // Build the new URL with the selected date
                            var newUrl = '../reports/generate_daily_turnover_list.php?academy_id=' + academyId + '&date=' + formattedDate;

                            // Redirect to the new URL
                            window.location.href = newUrl;
                        }
                    });
                    <?php endforeach; ?>
                });

            </script>

        </main>
    </div>
</div>