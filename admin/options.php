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
global $db, $showErrors, $siteName, $siteShortName, $siteUrl, $userType;

// Oturum kontrolü
session_start();
session_regenerate_id(true);

if (!isset($_SESSION["admin_id"])) {
    header("Location: index.php"); // Giriş sayfasına yönlendir
    exit();
}

require_once(__DIR__ . '/../config/db_connection.php');
require_once('../config/config.php');

$_SESSION['user_type'] = $userType; // Set $userType with the actual user type value

// Yetki kontrolü fonksiyonu
function checkPermission() {
    if ($_SESSION["admin_type"] != 1) {
        // Yetki hatası
        echo "Bu işlemi gerçekleştirmek için yetkiniz yok!";
        exit();
    }
}

// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);

// Fetch default values from the options table
$query = "SELECT * FROM options";
$result = $db->query($query);
$options = $result->fetchAll(PDO::FETCH_ASSOC);

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkPermission();

    // Loop through the submitted data and update the options
    foreach ($_POST as $optionName => $optionValue) {
        // Sanitize input to prevent SQL injection
        $optionValue = htmlspecialchars($optionValue, ENT_QUOTES, 'UTF-8');

        // Update the options in the database
        $updateQuery = "UPDATE options SET option_value = :optionValue WHERE option_name = :optionName";
        $stmt = $db->prepare($updateQuery);
        $stmt->bindParam(':optionValue', $optionValue);
        $stmt->bindParam(':optionName', $optionName);
        $stmt->execute();

        // Update the options array for immediate display
        foreach ($options as &$option) {
            if ($option['option_name'] === $optionName) {
                $option['option_value'] = $optionValue;
                break;
            }
        }
    }
}

checkPermission();
?>

<?php require_once('../admin/partials/header.php'); ?>
<div class="container-fluid">
    <div class="row">
        <?php
        require_once(__DIR__ . '/partials/sidebar.php');
        ?>
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
                <h2>Site Seçenekleri</h2>
            </div>
        <main role="main" class="col-md-12 ml-sm-auto col-lg-12 pt-3 px-4">

            <form action="" method="post" class="container-fluid">
                <?php foreach ($options as $option): ?>
                    <div class="mb-3">
                        <label for="<?php echo $option['option_name']; ?>" class="form-label"><?php echo ucfirst(str_replace('_', ' ', $option['option_name'])); ?>:</label>
                        <input type="text" class="form-control" id="<?php echo $option['option_name']; ?>" name="<?php echo $option['option_name']; ?>" value="<?php echo $option['option_value']; ?>">
                    </div>
                <?php endforeach; ?>

                <button type="submit" class="btn btn-primary">Seçenekleri Kaydet</button>
            </form>

        </main>
    </div>
</div>

<?php require_once('../admin/partials/footer.php'); ?>

