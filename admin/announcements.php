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
if (!isset($_SESSION["admin_id"])) {
    header("Location: index.php");
    exit();
}

require_once(__DIR__ . '/../config/db_connection.php');
require_once('../config/config.php');

// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);

// Yetki kontrolü fonksiyonu
function checkPermission() {
    if ($_SESSION["admin_type"] != 1) {
        // Yetki hatası
        echo "Bu işlemi gerçekleştirmek için yetkiniz yok!";
        exit();
    }
}

// Duyuru işlemleri
if (isset($_POST['add_announcement'])) {
    // Yeni duyuru ekleme
    $title = htmlspecialchars($_POST['title']);
    $content = htmlspecialchars($_POST['content']);
    $created_by = $_SESSION['admin_id'];

    $query = "INSERT INTO announcements (title, content, created_by, created_at) VALUES (?, ?, ?, NOW())";
    $stmt = $db->prepare($query);
    $stmt->execute([$title, $content, $created_by]);
}

if (isset($_POST['edit_announcement_submit'])) {
    // Duyuru düzenleme
    $announcement_id = $_POST['edit_announcement'];
    $title = htmlspecialchars($_POST['title']);
    $content = htmlspecialchars($_POST['content']);
    $updated_by = $_SESSION['admin_id'];

    $query = "UPDATE announcements SET title = ?, content = ?, updated_by = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$title, $content, $updated_by, $announcement_id]);
}

if (isset($_GET['delete_announcement'])) {
    // Duyuru silme
    $announcement_id = $_GET['delete_announcement'];
    $deleted_by = $_SESSION['admin_id'];

    // Soft delete işlemi
    $query = "UPDATE announcements SET deleted_by = ?, deleted_at = NOW() WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$deleted_by, $announcement_id]);
}

// Duyuruları çekme (soft delete kontrolü eklenmiştir)
$query = "SELECT a.id, a.title, a.content, a.created_at, a.updated_at, a.deleted_at, u.first_name, u.last_name,
                 uc.first_name AS edited_first_name, uc.last_name AS edited_last_name,
                 ud.first_name AS deleted_first_name, ud.last_name AS deleted_last_name
          FROM announcements a
          LEFT JOIN users u ON a.created_by = u.id
          LEFT JOIN users uc ON a.updated_by = uc.id
          LEFT JOIN users ud ON a.deleted_by = ud.id
          WHERE a.deleted_at IS NULL";
$stmt = $db->prepare($query);
$stmt->execute();
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                <button class="btn btn-primary" onclick="toggleAddForm()">Duyuru Ekle <i class="fas fa-plus"></i></button>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Duyuru Yönetimi</h3>
                </div>

                <!-- Duyuru Ekleme Formu -->
                <form method="post" action="announcements.php" class="mb-4" id="addForm" style="display: none;">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="title" class="form-label">Başlık:</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="content" class="form-label">İçerik:</label>
                            <textarea name="content" class="form-control" required></textarea>
                        </div>
                    </div>
                    <button type="submit" name="add_announcement" class="btn btn-success mt-3">Duyuru Ekle <i class="fas fa-plus"></i></button>
                </form>

                <!-- Duyuruları Listeleme -->
                <table class="table">
                    <thead>
                    <tr>
                        <th>Başlık</th>
                        <th>İçerik</th>
                        <th>Oluşturan</th>
                        <th>Oluşturulma</th>
                        <th>Düzenleyen</th>
                        <th>Düzenleme</th>
                        <th>Silen</th>
                        <th>Silme</th>
                        <th>İşlemler</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($announcements as $announcement): ?>
                        <tr>
                            <td><?php echo $announcement['title']; ?></td>
                            <td><?php echo $announcement['content']; ?></td>
                            <td><?php echo $announcement['first_name'] . ' ' . $announcement['last_name']; ?></td>
                            <td>
                                <?php
                                if ($announcement['created_at']) {
                                    echo date('d.m.Y H:i', strtotime($announcement['created_at']));
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td><?php echo $announcement['edited_first_name'] . ' ' . $announcement['edited_last_name']; ?></td>
                            <td>
                                <?php
                                if ($announcement['updated_at']) {
                                    echo date('d.m.Y H:i', strtotime($announcement['updated_at']));
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td>
                                <?php echo ($announcement['deleted_first_name'] && $announcement['deleted_last_name']) ?
                                    $announcement['deleted_first_name'] . ' ' . $announcement['deleted_last_name'] : '-'; ?>
                            </td>
                            <td>
                                <?php
                                if ($announcement['deleted_at']) {
                                    echo date('d.m.Y H:i', strtotime($announcement['deleted_at']));
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>

                            <td>
                                <button class="btn btn-warning" onclick="showEditForm(<?php echo $announcement['id']; ?>)">
                                    <i class="fas fa-edit"></i> Düzenle
                                </button>
                                <a href="announcements.php?delete_announcement=<?php echo $announcement['id']; ?>" class="btn btn-danger" onclick="return confirm('Duyuruyu silmek istediğinizden emin misiniz?')">
                                    <i class="fas fa-trash-alt"></i> Sil
                                </a>
                            </td>
                        </tr>

                        <!-- Düzenleme Formu (Başlangıç) -->
                        <tr id="editForm_<?php echo $announcement['id']; ?>" style="display: none;">
                            <td colspan="10">
                                <form method="post" action="announcements.php" class="mb-4">
                                    <input type="hidden" name="edit_announcement" value="<?php echo $announcement['id']; ?>">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label for="title" class="form-label">Duyuru Başlığı:</label>
                                            <input type="text" name="title" value="<?php echo $announcement['title']; ?>" class="form-control" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="content" class="form-label">Duyuru Metni:</label>
                                            <textarea name="content" class="form-control" required><?php echo $announcement['content']; ?></textarea>
                                        </div>
                                    </div>
                                    <button type="submit" name="edit_announcement_submit" class="btn btn-success mt-3"><i class="fas fa-save"></i> Güncellemeleri Kaydet</button>
                                    <button type="button" class="btn btn-secondary mt-3" onclick="hideEditForm(<?php echo $announcement['id']; ?>)"><i class="fas fa-times"></i> Vazgeç</button>
                                </form>
                            </td>
                        </tr>
                        <!-- Düzenleme Formu (Bitiş) -->
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <script>
                    // JavaScript fonksiyonu ile duyuru ekleme formunu göster/gizle
                    function toggleAddForm() {
                        var addForm = document.getElementById('addForm');
                        addForm.style.display = (addForm.style.display === 'none' || addForm.style.display === '') ? 'block' : 'none';
                    }

                    // JavaScript fonksiyonu ile düzenleme formunu göster
                    function showEditForm(announcementId) {
                        var editForm = document.getElementById('editForm_' + announcementId);
                        editForm.style.display = 'table-row';
                    }

                    // JavaScript fonksiyonu ile düzenleme formunu gizle
                    function hideEditForm(announcementId) {
                        var editForm = document.getElementById('editForm_' + announcementId);
                        editForm.style.display = 'none';
                    }
                </script>
            </div>
        </main>
    </div>
</div>
