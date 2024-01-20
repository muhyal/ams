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
// Veritabanı bağlantısını sağlayın
global $siteName, $siteShortName, $siteUrl, $db;
// Oturum kontrolü
session_start();
session_regenerate_id(true);

if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php"); // Giriş sayfasına yönlendir
    exit();
}

require_once "db_connection.php";

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

// Öğretmen listesi sorgusu
$query = "
    SELECT 
        u.id,
        u.first_name,
        u.tc_identity,
        u.last_name,
        u.birth_date,
        u.phone,
        u.email,
        GROUP_CONCAT(DISTINCT c.class_name ORDER BY c.class_name) as class_name,
        GROUP_CONCAT(DISTINCT co.course_name ORDER BY co.course_name) as course_name,
        GROUP_CONCAT(DISTINCT a.name ORDER BY a.name) as academy_name 
    FROM users u
    LEFT JOIN course_plans sc ON u.id = sc.teacher_id
    LEFT JOIN academy_classes c ON sc.class_id = c.id
    LEFT JOIN courses co ON sc.course_id = co.id
    LEFT JOIN academies a ON sc.academy_id = a.id
    WHERE u.user_type = 4
    GROUP BY u.id, u.first_name, u.tc_identity, u.last_name, u.birth_date, u.phone, u.email
";

$stmt = $db->query($query);
$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once "config.php";
?>
<?php
require_once "admin_panel_header.php";
?>
    <div class="container-fluid">
        <div class="row">
            <?php require_once "admin_panel_sidebar.php"; ?>
            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
                    <h2>Öğretmen Listesi</h2>
                </div>
                <script>
                    $(document).ready( function () {
                        // Tabloyu Datatables ile başlatma ve Türkçe dilini kullanma
                        $('#teachersTable').DataTable({
                            "language": {
                                "url": "https://cdn.datatables.net/plug-ins/1.10.25/i18n/Turkish.json"
                            }
                        });
                    });
                </script>
                <div class="table-responsive">
                    <table id="teachersTable" class="table table-bordered">
                        <thead>
                        <tr>
                            <th>No</th>
                            <th>Akademiler</th>
                            <th>Ad</th>
                            <th>Soyad</th>
                            <th>T.C. Kimlik No</th>
                            <th>Doğum Tarihi</th>
                            <th>Telefon</th>
                            <th>E-posta</th>
                            <th>Sınıf</th>
                            <th>Ders</th>
                            <th>İşlemler</th>

                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($teachers as $teacher): ?>
                            <tr>
                                <td><?php echo $teacher['id']; ?></td>
                                <td><?php echo $teacher['academy_name']; ?></td>
                                <td><?php echo $teacher['first_name']; ?></td>
                                <td><?php echo $teacher['last_name']; ?></td>
                                <td><?php echo $teacher['tc_identity']; ?></td>
                                <td><?php echo $teacher['birth_date']; ?></td>
                                <td><?php echo $teacher['phone']; ?></td>
                                <td><?php echo $teacher['email']; ?></td>
                                <td><?php echo $teacher['class_name']; ?></td>
                                <td><?php echo $teacher['course_name']; ?></td>
                                <td>
                                    <a href="user_profile.php?id=<?php echo $teacher['id']; ?>" class="btn btn-info btn-sm">
                                        <i class="fas fa-user"></i>
                                    </a>
                                    <a href="edit_user.php?id=<?php echo $teacher['id']; ?>" class="btn btn-warning btn-sm">
                                        <i class="fas fa-pencil-alt"></i>
                                    </a>
                                    <a href="delete_user.php?id=<?php echo $teacher['id']; ?>" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>

                                </td>

                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <button onclick="location.href='add_user.php'" type="button" class="btn btn-success">
                    <i class="fas fa-plus"></i> Yeni Öğretmen Ekle
                </button>

            </main>
        </div>
    </div>

<?php
require_once "footer.php";
?>