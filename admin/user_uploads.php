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

// Silme işlemi için kontrol
if (isset($_POST['delete_file']) && isset($_POST['file_id'])) {
    $fileId = $_POST['file_id'];
    
    // Önce dosya bilgilerini al
    $getFileQuery = "SELECT filename FROM user_uploads WHERE id = ?";
    $stmt = $db->prepare($getFileQuery);
    $stmt->execute([$fileId]);
    $fileInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Dosyayı fiziksel olarak sil
    if ($fileInfo) {
        $filePath = __DIR__ . '/../uploads/user_uploads/' . $fileInfo['filename'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        // Veritabanından kaydı sil
        $deleteQuery = "DELETE FROM user_uploads WHERE id = ?";
        $stmt = $db->prepare($deleteQuery);
        $stmt->execute([$fileId]);
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Filtreleme için sorguyu güncelle
if (isset($_GET['file_type']) && !empty($_GET['file_type'])) {
    $query .= " WHERE user_uploads.file_type = :file_type";
    $stmt = $db->prepare($query);
    $stmt->execute(['file_type' => $_GET['file_type']]);
} else {
    $stmt = $db->query($query);
}

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

            <div class="mb-3">
                <form class="form-inline">
                    <label class="mr-2">Dosya Türü:</label>
                    <select name="file_type" class="form-control mr-2">
                        <option value="">Tümü</option>
                        <option value="health_report" <?php echo isset($_GET['file_type']) && $_GET['file_type'] == 'health_report' ? 'selected' : ''; ?>>Sağlık Raporu</option>
                        <option value="permission_document" <?php echo isset($_GET['file_type']) && $_GET['file_type'] == 'permission_document' ? 'selected' : ''; ?>>İzin Belgesi</option>
                        <option value="payment_receipt" <?php echo isset($_GET['file_type']) && $_GET['file_type'] == 'payment_receipt' ? 'selected' : ''; ?>>Ödeme Belgesi</option>
                        <option value="petition" <?php echo isset($_GET['file_type']) && $_GET['file_type'] == 'petition' ? 'selected' : ''; ?>>Dilekçe</option>
                        <option value="photo" <?php echo isset($_GET['file_type']) && $_GET['file_type'] == 'photo' ? 'selected' : ''; ?>>Fotoğraf</option>
                        <option value="other" <?php echo isset($_GET['file_type']) && $_GET['file_type'] == 'other' ? 'selected' : ''; ?>>Diğer</option>
                    </select>
                    <button type="submit" class="btn btn-primary">Filtrele</button>
                </form>
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
        <th>İşlemler</th> 
                </tr>
                </thead>
                <tbody>
                <?php foreach ($fileUploads as $upload): ?>
        <tr>
            <td>
                <?php if (in_array(pathinfo($upload['filename'], PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif'])): ?>
                    <img src="/uploads/user_uploads/<?php echo $upload['filename']; ?>" style="width: 30px; height: 30px;" alt="Dosya Küçük Resmi">
                <?php else: ?>
                    <i class="fas fa-file fa-2x"></i>
                <?php endif; ?>
            </td>
            <td><?php echo htmlspecialchars($upload['first_name'] . ' ' . $upload['last_name']); ?></td>
            <td><?php echo htmlspecialchars($upload['filename']); ?></td>
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
                    case 'photo':
                        echo 'Fotoğraf';
                        break;
                    case 'other':
                        echo 'Diğer';
                        break;
                    default:
                        echo htmlspecialchars($fileType);
                }
                ?>
            </td>
            <td><?php echo htmlspecialchars($upload['description']); ?></td>
            <td><?php echo date('d.m.Y H:i', strtotime($upload['upload_date'])); ?></td>
            <td>
                <div class="btn-group">
                    <a href="/user/dl.php?file=<?php echo urlencode($upload['filename']); ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-download"></i>
                    </a>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Bu dosyayı silmek istediğinizden emin misiniz?');">
                        <input type="hidden" name="file_id" value="<?php echo $upload['id']; ?>">
                        <button type="submit" name="delete_file" class="btn btn-danger btn-sm">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
                </tbody>
            </table>
        </main>
    </div>
</div>

<?php require_once('../admin/partials/footer.php'); ?>
