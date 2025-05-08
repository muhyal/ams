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

<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header">
                <h4>Sistem Bilgileri</h4>
            </div>
            <div class="card-body">
                <h5>PHP Bilgileri</h5>
                <?php
                // PHP sürümünü al
                echo "<p><strong>PHP Sürümü:</strong> " . phpversion() . "</p>";
                
                // PHP bellek kullanımı
                echo "<p><strong>Bellek Kullanımı:</strong> " . formatBytes(memory_get_usage()) . "</p>";
                
                // Maksimum upload boyutu
                echo "<p><strong>Maksimum Upload Boyutu:</strong> " . ini_get('upload_max_filesize') . "</p>";
                
                // Sunucu bilgileri
                echo "<p><strong>Sunucu Yazılımı:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
                ?>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h4>PHP Eklentileri</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-sm">
                        <thead>
                            <tr>
                                <th>Eklenti Adı</th>
                                <th>Versiyon</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $extensions = get_loaded_extensions();
                            sort($extensions); // Eklentileri alfabetik sırala
                            foreach ($extensions as $extension) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($extension) . "</td>";
                                echo "<td>" . phpversion($extension) . "</td>";
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h4>Sistem Durumu</h4>
            </div>
            <div class="card-body">
                <?php
                // Disk kullanımı
                $totalSpace = disk_total_space("/");
                $freeSpace = disk_free_space("/");
                $usedSpace = $totalSpace - $freeSpace;
                $usedPercentage = round(($usedSpace / $totalSpace) * 100, 2);
                
                echo "<p><strong>Disk Kullanımı:</strong></p>";
                echo "<div class='progress mb-3'>";
                echo "<div class='progress-bar' role='progressbar' style='width: {$usedPercentage}%' ";
                echo "aria-valuenow='{$usedPercentage}' aria-valuemin='0' aria-valuemax='100'>";
                echo "{$usedPercentage}%</div></div>";
                echo "<p>Kullanılan: " . formatBytes($usedSpace) . " / Toplam: " . formatBytes($totalSpace) . "</p>";
                ?>
            </div>
        </div>
    </div>
</div>

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
