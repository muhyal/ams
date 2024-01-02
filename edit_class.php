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
require_once "config.php";

// Oturum kontrolü
session_start();
session_regenerate_id(true);

if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php"); // Giriş sayfasına yönlendir
    exit();
}

require_once "db_connection.php"; // Veritabanı bağlantısı

// Kullanıcı bilgilerini kullanabilirsiniz
$admin_id = $_SESSION["admin_id"];
$admin_username = $_SESSION["admin_username"];

if (isset($_GET['id'])) {
    $classId = $_GET['id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Düzenlenmiş sınıf bilgilerini al
        $className = $_POST['class_name'];
        $classDescription = $_POST['class_description'];
        $classCode = $_POST['class_code'];

        // Sınıf bilgilerini güncelle
        $updateQuery = "UPDATE classes SET class_name = ?, class_code = ?, class_description = ? WHERE id = ?";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->execute([$className, $classCode, $classDescription, $classId]);

        //header("Location: class_list.php");
        //exit;
    }

    // Sınıf bilgilerini veritabanından çek
    $query = "SELECT * FROM classes WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$classId]);
    $classData = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
   header("Location: class_list.php");
   exit;
}
?>
<?php
require_once "admin_panel_header.php";
?>
<div class="container-fluid">
    <div class="row">
        <?php require_once "admin_panel_sidebar.php"; ?>
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
                <h2>Sınıf Düzenle</h2>
            </div>

            <form method="post">
                <div class="form-group mt-3">
                    <label for="class_name">Sınıf Adı:</label>
                    <input type="text" class="form-control" name="class_name" value="<?php echo $classData['class_name']; ?>">
                </div>

                <div class="form-group mt-3">
                    <label for="class_code">Sınıf Kodu:</label>
                    <input type="text" class="form-control" name="class_code" value="<?php echo $classData['class_code']; ?>">
                </div>

                <div class="form-group mt-3">
                    <label for="class_description">Sınıf Açıklaması:</label>
                    <textarea class="form-control" name="class_description"><?php echo $classData['class_description']; ?></textarea>
                </div>
                <div class="form-group mt-3">
                <button type="submit" class="btn btn-primary">Kaydet</button>
                </div>
            </form>
        </main>
    </div>
</div>

<?php require_once "footer.php"; ?>
