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
if (!isset($_SESSION["admin_id"]) || $_SESSION["admin_type"] != 1) {
    // Kullanıcı oturumu yoksa veya user_type değeri 1'e eşit değilse, hata mesajı göster ve işlemi sonlandır
    echo "Bu sayfayı görüntülemek için yetkiniz bulunmamaktadır.";
    exit();
}

require_once(__DIR__ . '/../config/db_connection.php');
require_once('../config/config.php');

// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);
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
                <h2>Hakkında</h2>
            </div>

<h4>PHP Bilgileri</h4>
<?php
// PHP sürümünü al
echo "<p>PHP Sürümü: " . phpversion() . "</p>";

// Yüklü PHP eklentilerini al
$extensions = get_loaded_extensions();
echo "<p>Yüklü PHP Eklentileri: " . implode(", ", $extensions) . "</p>";
?>

<?php
// Yardımcı fonksiyon: baytları insan dostu bir formata dönüştürür
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');

    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);

    $bytes /= (1 << (10 * $pow));

    return round($bytes, $precision) . ' ' . $units[$pow];
}
?>
<?php require_once('../admin/partials/footer.php'); ?>
        </main>
