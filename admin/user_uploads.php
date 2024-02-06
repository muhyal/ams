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
    header("Location: index.php"); // Giriş sayfasına yönlendir
    exit();
}
require_once(__DIR__ . '/../config/db_connection.php');
require_once('../config/config.php');
require_once "../src/functions.php";

// Kullanıcı ve dosya bilgilerini sorgula
$query = "SELECT user_uploads.id, users.first_name, users.last_name, user_uploads.filename, user_uploads.file_type, user_uploads.description, user_uploads.upload_date
          FROM user_uploads
          INNER JOIN users ON user_uploads.user_id = users.id";
$stmt = $db->query($query);
$fileUploads = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php require_once('../admin/partials/header.php'); ?>

<div class="container-fluid">
    <div class="row">
        <?php require_once(__DIR__ . '/partials/sidebar.php'); ?>
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
                <h2>Kullanıcıların Yüklediği Dosyalar</h2>
            </div>

            <table class="table">
                <thead>
                <tr>
                    <th>Önizleme</th>
                    <th>Yükleyen</th>
                    <th>Dosya Adı</th>
                    <th>Dosya Türü</th>
                    <th>Açıklama</th>
                    <th>Yükleme Tarihi</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($fileUploads as $upload): ?>
                    <tr>
                        <td><img src="/uploads/user_uploads/<?php echo $upload['filename']; ?>" style="width: 30px; height: 30px;" alt="Dosya Küçük Resmi"></td>
                        <td><?php echo $upload['first_name']; ?> <?php echo $upload['last_name']; ?></td>
                        <td><?php echo $upload['filename']; ?></td>
                        <td>
                            <?php
                            $fileType = $upload['file_type'];

                            switch ($fileType) {
                                case 'health_report':
                                    echo 'Sağlık Raporu';
                                    break;
                                case 'permission_document':
                                    echo 'İzin Belgesi';
                                    break;
                                case 'payment_receipt':
                                    echo 'Ödeme Belgesi';
                                    break;
                                case 'petition':
                                    echo 'Dilekçe';
                                    break;
                                case 'other':
                                    echo 'Diğer';
                                    break;
                                case 'photo':
                                    echo 'Fotoğraf';
                                    break;
                                default:
                                    echo $fileType; // Eğer tanımlanmamışsa orijinal değeri göster
                            }
                            ?>
                        </td>                        <td><?php echo $upload['description']; ?></td>
                        <td><?php echo $upload['upload_date']; ?></td>
                        <td>
                            <a href="/user/dl.php?file=<?php echo $upload['filename']; ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-download"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </main>
    </div>
</div>

<?php require_once('../admin/partials/footer.php'); ?>
