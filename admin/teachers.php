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
    header("Location: index.php"); // Giriş sayfasına yönlendir
    exit();
}

require_once(__DIR__ . '/../config/db_connection.php');

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
        u.profile_photo,
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

require_once('../config/config.php');
?>
<?php require_once(__DIR__ . '/partials/header.php'); ?>

<div class="container-fluid">
    <div class="row">
        <?php require_once(__DIR__ . '/partials/sidebar.php'); ?>
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
                <h2>Öğretmenler</h2>
            </div>

            <div class="row">
                <?php foreach ($teachers as $teacher): ?>
                    <div class="col-md-3 mb-4">
                        <div class="card">
                            <?php if (!empty($teacher['profile_photo'])): ?>
                                <img src="<?= $teacher['profile_photo'] ?>" alt="<?= $teacher['first_name'] ?> <?= $teacher['last_name'] ?> Fotoğrafı" class="card-img-top rounded-circle mx-auto mt-3" style="width: 100px; height: 100px;">
                            <?php else: ?>
                                <img src="/assets/brand/default_pp.png" alt="Varsayılan Profil Fotoğrafı" class="card-img-top rounded-circle mx-auto mt-3" style="width: 100px; height: 100px;">
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title text-center"><?php echo $teacher['first_name'] . ' ' . $teacher['last_name']; ?></h5>

                                <ul class="list-group mb-2">
                                    <li class="list-group-item"><strong>T.C. Kimlik No:</strong> <?php echo $teacher['tc_identity']; ?></li>
                                    <li class="list-group-item"><strong>Doğum Tarihi:</strong> <?php echo $teacher['birth_date']; ?></li>
                                    <li class="list-group-item"><strong>Telefon:</strong> <?php echo $teacher['phone']; ?></li>
                                    <li class="list-group-item"><strong>E-posta:</strong> <?php echo $teacher['email']; ?></li>
                                </ul>

                                <div class="list-group mb-2">
                                    <li class="list-group-item"><strong>Derse Girdiği Sınıflar:</strong></li>
                                    <?php foreach (explode(',', $teacher['class_name']) as $class): ?>
                                        <li class="list-group-item"><?php echo $class; ?></li>
                                    <?php endforeach; ?>
                                </div>

                                <div class="list-group mb-2">
                                    <li class="list-group-item"><strong>Verdiği Dersler:</strong></li>
                                    <?php foreach (explode(',', $teacher['course_name']) as $course): ?>
                                        <li class="list-group-item"><?php echo $course; ?></li>
                                    <?php endforeach; ?>
                                </div>

                                <div class="btn-group" role="group" aria-label="Öğretmen İşlemleri">
                                    <a href="user_profile.php?id=<?php echo $teacher['id']; ?>" class="btn btn-info btn-sm">
                                        <i class="fas fa-user"></i> Profil
                                    </a>
                                    <a href="edit_user.php?id=<?php echo $teacher['id']; ?>" class="btn btn-warning btn-sm">
                                        <i class="fas fa-pencil-alt"></i> Düzenle
                                    </a>
                                    <a class="btn btn-danger btn-sm" href="?action=delete&id=<?php echo $teacher['id']; ?>" onclick="return confirm('Bu kullanıcıyı silmek istediğinizden emin misiniz?')">
                                        <i class="fas fa-trash-alt"></i> Sil
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <button onclick="location.href='add_user.php'" type="button" class="btn btn-success">
                <i class="fas fa-plus"></i> Yeni Öğretmen Ekle
            </button>
        </main>
    </div>
</div>

<?php require_once('../admin/partials/footer.php'); ?>
