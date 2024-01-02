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
session_start();
session_regenerate_id(true);

// Oturum kontrolü
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php"); // Giriş sayfasına yönlendir
    exit();
}
require_once "db_connection.php";
require_once "config.php";
// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);
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
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
                <h2>Sınıf Ekle</h2>
            </div>
            <form action="process_add_class.php" method="POST" class="mb-4">
                <div class="form-group">
                    <label for="class_name">Sınıf Adı:</label>
                    <input type="text" name="class_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="class_code">Sınıf Kodu:</label>
                    <input type="text" name="class_code" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="class_description">Sınıf Açıklaması:</label>
                    <input type="text" name="class_description" class="form-control">
                </div>
                <button type="submit" class="btn btn-primary">Ekle</button>
                <button onclick="history.back()" class="btn btn-primary">Geri dön</button>
                <button onclick="window.location.href='class_list.php'" class="btn btn-secondary">Sınıf listesi</button>
            </form>

<?php
require_once "footer.php";
?>