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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $parentFirstname = $_POST["parent_firstname"];
    $parentLastname = $_POST["parent_lastname"];
    $parentTcIdentity = $_POST["parent_tc_identity"];
    $parentPhone = $_POST["parent_phone"];
    $parentEmail = $_POST["parent_email"];

    // Veli ekleme sorgusunu hazırlayın ve veritabanına ekleyin
    $insertParentQuery = "INSERT INTO parents (firstname, lastname, tc_identity, phone, email) VALUES (?, ?, ?, ?, ?)";
    $insertParentStmt = $db->prepare($insertParentQuery);
    $insertParentStmt->execute([$parentFirstname, $parentLastname, $parentTcIdentity, $parentPhone, $parentEmail]);

    // Veli ekleme işlemi tamamlandıktan sonra öğrenci ekleme sayfasına yönlendirin
    //header("Location: add_student.php");
    //exit();
}
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
                <h2>Veli Ekle</h2>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group mr-2">
                        <button onclick="history.back()" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Geri dön
                        </button>
                        <a href="parent_list.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-list"></i> Veli Listesi
                        </a>
                    </div>
                </div>
            </div>
            <form action="add_parent.php" method="post">
                <div class="form-group">
                    <label for="parent_firstname">Adı:</label>
                    <input type="text" id="parent_firstname" name="parent_firstname" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="parent_lastname">Soyadı:</label>
                    <input type="text" id="parent_lastname" name="parent_lastname" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="parent_tc_identity">TC Kimlik No:</label>
                    <input type="text" id="parent_tc_identity" name="parent_tc_identity" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="parent_phone">Telefon:</label>
                    <input type="text" id="parent_phone" name="parent_phone" value="90" class="form-control" required>

                </div>

                <div class="form-group">
                    <label for="parent_email">E-posta:</label>
                    <input type="email" id="parent_email" name="parent_email" class="form-control" required>
                </div>
                <div class="form-group mt-3">
                <button type="submit" class="btn btn-primary">Veli Ekle</button>
                </div>
            </form>

<?php
require_once "footer.php";
?>