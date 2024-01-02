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

// Oturum kontrolü
session_start();
session_regenerate_id(true);

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
<?php require_once "admin_panel_header.php"; ?>
<div class="container-fluid">
    <div class="row">
        <?php require_once "admin_panel_sidebar.php"; ?>
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
                <h2>Sınıf Listesi</h2>
            </div>
            <a href="add_class.php" class="btn btn-primary mb-3">Sınıf Ekle</a>
            <table class="table">
                <thead>
                <tr>
                    <th scope="col">No</th>
                    <th scope="col">Sınıf Adı</th>
                    <th scope="col">Sınıf Kodu</th>
                    <th scope="col">Sınıf Açıklaması</th>
                    <th scope="col">İşlemler</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $selectQuery = "SELECT * FROM classes";
                $stmt = $db->prepare($selectQuery);
                $stmt->execute();
                $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($classes as $class) {
                    echo "<tr>";
                    echo "<td>{$class['id']}</td>";
                    echo "<td>{$class['class_name']}</td>";
                    echo "<td>{$class['class_code']}</td>";
                    echo "<td>{$class['class_description']}</td>";
                    echo '<td>
                                  <a href="edit_class.php?id='.$class['id'].'" class="btn btn-warning">Düzenle</a>
                                  <a href="delete_class.php?id='.$class['id'].'" class="btn btn-danger" onclick="return confirm(\'Sınıfı silmek istediğinizden emin misiniz?\')">Sil</a>
                                  </td>';
                    echo "</tr>";
                }
                ?>
                </tbody>
            </table>
        </main>
    </div>
</div>
<?php require_once "footer.php"; ?>
