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
?>
<?php
require_once "admin_panel_header.php";
?>
<div class="container-fluid">
    <div class="row">

        <?php
        require_once "admin_panel_sidebar.php";
        ?>
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">



<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="btn-toolbar mb-2 mb-md-0">
                <div class="btn-group mr-2">
                    <button onclick="history.back()" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Geri dön
                    </button>
                    <a href="teacher_list.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-list"></i> Öğretmen Listesi
                    </a>
                    <?php
                    // "id" anahtarının $_GET dizisinde varlığını kontrol et
                    if (isset($_GET['id'])) {
                        $teacher_id = $_GET['id'];
                        ?>
                        <a href="edit_teacher.php?id=<?php echo $teacher_id; ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-edit"></i> Öğretmeni Düzenle
                        </a>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
<br>

<?php
// Check if the "teacher_id" key exists in the $_GET array
if (isset($_GET['id'])) {
    $teacher_id = $_GET['id'];

    // Öğretmen ve diğer bilgileri birleştiren sorgu
    $query = "SELECT id, first_name, last_name, tc_identity, birth_date, phone, email FROM teachers WHERE id = ?";

    $stmt = $db->prepare($query);
    $stmt->execute([$teacher_id]);

    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($teacher) {
        // Display teacher information
        ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-subtitle">
                    <?php echo $teacher['first_name']; ?> <?php echo $teacher['last_name']; ?> için öğretmen bilgileri
                </h3>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><strong>Ad:</strong> <?php echo $teacher['first_name']; ?></li>
                    <li class="list-group-item"><strong>Soyad:</strong> <?php echo $teacher['last_name']; ?></li>
                    <li class="list-group-item"><strong>T.C. Kimlik No:</strong> <?php echo $teacher['tc_identity']; ?></li>
                    <li class="list-group-item"><strong>Cep Telefonu:</strong> <?php echo $teacher['phone']; ?></li>
                    <li class="list-group-item"><strong>E-posta:</strong> <?php echo $teacher['email']; ?></li>
                </ul>
            </div>
        </div>
        <?php
    } else {
        echo "Öğretmen bulunamadı.";
    }
} else {
    echo "Geçersiz öğretmen ID'si.";
}
?>
