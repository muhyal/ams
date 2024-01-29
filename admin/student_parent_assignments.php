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
 */

global $db, $showErrors, $siteName, $siteShortName, $siteUrl;
session_start();
session_regenerate_id(true);

// Oturum kontrolü
if (!isset($_SESSION["admin_id"])) {
    header("Location: index.php"); // Giriş sayfasına yönlendir
    exit();
}

// "user_type" kontrolü ekleniyor
if (!isset($_SESSION["admin_type"])) {
    // Hata: "user_type" tanımlı değil
    $messages[] = "Hata: Kullanıcı türü belirtilmemiş.";
}

require_once(__DIR__ . '/../config/db_connection.php');
require_once('../config/config.php');

// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);

$messages = []; // Array to store messages

// İlişki ekleme işlemi
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["addRelationship"])) {
    $studentId = $_POST["student"];
    $parentId = $_POST["parent"];

    // Check if the relationship already exists
    $stmtCheckExistingRelationship = $db->prepare("SELECT COUNT(*) FROM student_parents WHERE student_id = ? AND parent_id = ?");
    $stmtCheckExistingRelationship->execute([$studentId, $parentId]);
    $existingRelationshipCount = $stmtCheckExistingRelationship->fetchColumn();

    if ($existingRelationshipCount > 0) {
        $messages[] = "Bu veli zaten bu öğrenci ile ilişkilendirilmiş!";
    } else {
        // Insert the new relationship into the student_parents table
        $stmtInsertRelationship = $db->prepare("INSERT INTO student_parents (student_id, parent_id) VALUES (?, ?)");
        $stmtInsertRelationship->execute([$studentId, $parentId]);

        $messages[] = "Veli ile öğrenci ilişkilendirildi.";
    }
}

/// İlişki silme işlemi
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["deleteRelationship"])) {
    $relationshipId = $_POST["relationshipId"];

    try {
        $db->beginTransaction();

        // Delete the relationship from the student_parents table
        $stmtDeleteRelationship = $db->prepare("DELETE FROM student_parents WHERE id = ?");
        $stmtDeleteRelationship->execute([$relationshipId]);

        $db->commit();
        $messages[] = "Veli ile öğrenci ilişkisi silindi!";
    } catch (PDOException $e) {
        $db->rollBack();
        $messages[] = "Veli ile öğrencinin ilişkisinin silinmesi işlemi sırasında bir hata oluştu: " . $e->getMessage();
    }
}

// Fetch user details for students
$getStudentsQuery = "SELECT id, username, tc_identity, first_name, last_name FROM users WHERE user_type = 6";
$stmtStudents = $db->query($getStudentsQuery);
$students = $stmtStudents->fetchAll(PDO::FETCH_ASSOC);

// Fetch user details for parents
$getParentsQuery = "SELECT id, username, tc_identity, first_name, last_name FROM users WHERE user_type = 5";
$stmtParents = $db->query($getParentsQuery);
$parents = $stmtParents->fetchAll(PDO::FETCH_ASSOC);

// Fetch existing relationships
$getRelationshipsQuery = "SELECT sp.id, s.id AS student_id, s.username AS student_username, s.tc_identity AS student_tc_identity, s.first_name AS student_first_name, s.last_name AS student_last_name,
                                 p.id AS parent_id, p.username AS parent_username, p.tc_identity AS parent_tc_identity, p.first_name AS parent_first_name, p.last_name AS parent_last_name
                          FROM student_parents sp
                          JOIN users s ON sp.student_id = s.id
                          JOIN users p ON sp.parent_id = p.id";
$stmtRelationships = $db->query($getRelationshipsQuery);
$relationships = $stmtRelationships->fetchAll(PDO::FETCH_ASSOC);

// Liste dışında tutulacak kullanıcıları depolamak için dizi oluştur
$excludedUsers = array();

// Mevcut ilişkileri dizide sakla
foreach ($relationships as $relationship) {
    $excludedUsers[] = $relationship['student_id'];
    $excludedUsers[] = $relationship['parent_id'];
    // $relationship['id'] ile ilişki ID'sine de erişebilirsiniz, ihtiyaç olursa kullanabilirsiniz.
}

?>

<?php require_once('../admin/partials/header.php'); ?>

<div class="container-fluid">
    <div class="row">
        <?php require_once(__DIR__ . '/partials/sidebar.php'); ?>

        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
                <h2 class="mb-3">Kullanıcı x Veli İlişkileri</h2>
            </div>

            <?php foreach ($messages as $message): ?>
                <div class="alert alert-<?php echo (strpos($message, 'Hata') === 0 || strpos($message, 'silindi') !== false || strpos($message, 'ilişkilendirilmiş') !== false) ? 'danger' : 'success'; ?>" role="alert">
                    <?= $message ?>
                </div>
            <?php endforeach; ?>

            <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                <div class="row">
                    <!-- Öğrenci Seçimi -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="student">Öğrenci Seçin:</label>
                            <select id="student" name="student" class="form-control" required>
                                <?php foreach ($students as $student) : ?>
                                    <option value="<?php echo $student['id']; ?>"><?php echo $student['first_name'] . ' ' . $student['last_name'] . ' - ' . $student['tc_identity']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Veli Seçimi -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="parent">Veli Seçin:</label>
                            <select id="parent" name="parent" class="form-control" required>
                                <?php foreach ($parents as $parent) : ?>
                                    <option value="<?php echo $parent['id']; ?>"><?php echo $parent['first_name'] . ' ' . $parent['last_name'] . ' - ' . $parent['tc_identity']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <button type="submit" name="addRelationship" class="btn btn-primary btn-sm mt-3 mb-3">İlişkilendir</button>
                        </div>
                    </div>
                </div>
            </form>


            <!-- Mevcut ilişkileri listeleme -->
            <h2>Mevcut İlişkiler</h2>
            <table class="table">
                <thead>
                <tr>
                    <th>Öğrenci Ad Soyad - T.C. No</th>
                    <th>Veli Ad Soyad - T.C. No</th>
                    <th>İşlemler</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($relationships as $relationship) : ?>
                    <?php
                    // Öğrenci bilgilerini al
                    $stmtStudentInfo = $db->prepare("SELECT tc_identity, first_name, last_name FROM users WHERE id = ?");
                    $stmtStudentInfo->execute([$relationship['student_id']]);
                    $studentInfo = $stmtStudentInfo->fetch(PDO::FETCH_ASSOC);

                    // Veli bilgilerini al
                    $stmtParentInfo = $db->prepare("SELECT tc_identity, first_name, last_name FROM users WHERE id = ?");
                    $stmtParentInfo->execute([$relationship['parent_id']]);
                    $parentInfo = $stmtParentInfo->fetch(PDO::FETCH_ASSOC);
                    ?>

                    <tr>
                        <td><?= $studentInfo['first_name'] . ' ' . $studentInfo['last_name'] . ' - ' . $studentInfo['tc_identity']; ?></td>
                        <td><?= $parentInfo['first_name'] . ' ' . $parentInfo['last_name'] . ' - ' . $parentInfo['tc_identity']; ?></td>
                        <td>
                            <form method="post" action="<?= $_SERVER['PHP_SELF']; ?>">
                                <input type="hidden" name="relationshipId" value="<?= $relationship['id']; ?>">
                                <button type="submit" name="deleteRelationship" class="btn btn-danger btn-sm">Sil</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

        </main>
    </div>
</div>

<?php require_once('../admin/partials/footer.php'); ?>
