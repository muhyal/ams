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
// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);
require_once('../config/config.php');
require_once(__DIR__ . '/../config/db_connection.php');

// Oturum kontrolü
session_start();
session_regenerate_id(true);

if (!isset($_SESSION["admin_id"])) {
    header("Location: index.php"); // Giriş sayfasına yönlendir
    exit();
}

// Admin bilgilerini çekme
$admin_id = $_SESSION['admin_id'];
$select_query = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($select_query);
$stmt->execute([$admin_id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

// Form işleme
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_username = htmlspecialchars($_POST["new_username"], ENT_QUOTES, 'UTF-8');
    $new_first_name = htmlspecialchars($_POST["new_first_name"], ENT_QUOTES, 'UTF-8');
    $new_last_name = htmlspecialchars($_POST["new_last_name"], ENT_QUOTES, 'UTF-8');
    $new_phone = htmlspecialchars($_POST["new_phone"], ENT_QUOTES, 'UTF-8');
    $new_email = htmlspecialchars($_POST["new_email"], ENT_QUOTES, 'UTF-8');
    $new_password = htmlspecialchars($_POST["new_password"], ENT_QUOTES, 'UTF-8');


    // Şifre güncelleme kontrolü
    if (!empty($new_password)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_query = "UPDATE users SET username = ?, first_name = ?, last_name = ?, phone = ?, email = ?, password = ? WHERE id = ?";
        $stmt = $db->prepare($update_query);
        $stmt->execute([$new_username,$new_first_name,$new_last_name, $new_phone, $new_email, $hashed_password, $admin_id]);
    } else {
        $update_query = "UPDATE users SET username = ?, first_name = ?, last_name = ?, phone = ?, email = ? WHERE id = ?";
        $stmt = $db->prepare($update_query);
        $stmt->execute([$new_username,$new_first_name,$new_last_name, $new_phone, $new_email, $admin_id]);
    }

    header("Location: profile_edit.php?success=true");
    exit();
}
?>
<?php require_once(__DIR__ . '/partials/header.php');
?>
<div class="container-fluid">
    <div class="row">
       <?php
        require_once(__DIR__ . '/partials/sidebar.php');
?>

        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
                <h2>Profil Düzenle</h2>
            </div>

            <?php
            // Başarı durumu mesajı
            if (isset($_GET['success']) && $_GET['success'] == 'true') {
                echo '<div class="alert alert-success" role="alert">Profil başarıyla güncellendi!</div>';
            }
            ?>

            <form method="post" action="">
                <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">

                <label class="form-label" for="new_username">Yeni Kullanıcı Adı:</label>
                <input class="form-control" type="text" id="new_username" name="new_username" value="<?php echo $admin['username']; ?>" required><br>

                <div class="form-group mt-3">
                    <label for="phone">Yeni Ad:</label>
                    <input type="text" id="new_first_name" name="new_first_name" class="form-control" value="<?php echo $admin['first_name']; ?>" required>
                </div>

                <div class="form-group mt-3">
                    <label for="phone">Yeni Soyad:</label>
                    <input type="text" id="new_last_name" name="new_last_name" class="form-control" value="<?php echo $admin['last_name']; ?>" required>
                </div>

                <div class="form-group mt-3">
                    <label for="phone">Yeni Telefon:</label>
                    <input type="text" id="new_phone" name="new_phone" class="form-control" value="<?php echo $admin['phone']; ?>" required>
                </div>

                <label class="form-label" for="new_email">Yeni E-posta:</label>
                <input class="form-control" type="email" id="new_email" name="new_email" value="<?php echo $admin['email']; ?>" required><br>

                <label class="form-label" for="new_password">Yeni Şifre (Boş bırakabilirsiniz):</label>
                <input class="form-control" type="password" id="new_password" name="new_password"><br>

                <button type="submit" class="btn btn-primary">Güncelle</button>
                <a href="/admin/admins.php" class="btn btn-secondary">Yönetici Listesi</a>
            </form>


            <?php
            $academyAssignmentQuery = "SELECT DISTINCT a.name, a.city, a.district
                            FROM academies a
                            JOIN user_academy_assignment uaa ON a.id = uaa.academy_id
                            WHERE uaa.user_id = ?";
            $academyAssignmentStmt = $db->prepare($academyAssignmentQuery);
            $academyAssignmentStmt->execute([$admin_id]);
            $assignedAcademies = $academyAssignmentStmt->fetchAll(PDO::FETCH_ASSOC);
            ?>

            <div class="form-group mt-3">
                <label for="assigned_academies" class="mb-2">Yetkili Olduğu Akademiler:</label>
                <div class="card shadow-sm">
                    <div class="card-body">
                        <?php if (!empty($assignedAcademies)): ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($assignedAcademies as $assignedAcademy): ?>
                                    <li class="list-group-item py-2">
                                        <?php echo $assignedAcademy['city'] . ' ili ' . $assignedAcademy['district'] . ' ilçesindeki ' . $assignedAcademy['name'] . ' akademisi'; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="card-text m-0">Kullanıcıya atanmış akademi bulunmamaktadır.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>


        </main>
    </div>
</div>

<?php require_once('../admin/partials/footer.php'); ?>

