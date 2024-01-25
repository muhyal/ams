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
global $db, $showErrors, $siteName, $siteShortName, $siteUrl, $config;
// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);
require_once('../config/config.php');
// Oturum kontrolü
session_start();
session_regenerate_id(true);

if (!isset($_SESSION["admin_id"])) {
    header("Location: index.php"); // Giriş sayfasına yönlendir
    exit();
}

require_once(__DIR__ . '/../config/db_connection.php');

// Kullanıcı ve akademi ilişkisini çekmek için bir SQL sorgusu
$getUserAcademyQuery = "SELECT academy_id FROM user_academy_assignment WHERE user_id = :user_id";
$stmtUserAcademy = $db->prepare($getUserAcademyQuery);
$stmtUserAcademy->bindParam(':user_id', $_SESSION["admin_id"], PDO::PARAM_INT);
$stmtUserAcademy->execute();
$associatedAcademies = $stmtUserAcademy->fetchAll(PDO::FETCH_COLUMN);

// Eğer kullanıcı hiçbir akademide ilişkilendirilmemişse veya bu akademilerden hiçbiri yoksa, uygun bir işlemi gerçekleştirin
if (empty($associatedAcademies)) {
    echo "Kullanıcınız bu işlem için yetkili değil!";
    exit();
}

// Eğitim danışmanının erişebileceği akademilerin listesini güncelle
$allowedAcademies = $associatedAcademies;

// Kullanıcı bilgilerini kullanabilirsiniz
$admin_id = $_SESSION["admin_id"];
$admin_username = $_SESSION["admin_username"];

// Öğrenci listesi sorgusu
$query = "
    SELECT 
        u.id,
        u.first_name,
        u.last_name,
        u.tc_identity,
        u.birth_date,
        u.phone,
        u.email,
        MAX(academies.name) AS academy_name
    FROM 
        users u
        INNER JOIN course_plans cp ON u.id = cp.student_id
        LEFT JOIN academies ON cp.academy_id = academies.id 
    WHERE 
        u.user_type = '6'
        AND academies.id IN (" . implode(",", $allowedAcademies) . ")  -- Sadece yetkilendirilmiş akademilere ait öğrencileri getir
    GROUP BY 
        u.id;
";

$stmt = $db->query($query);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);


?>
<?php
require_once(__DIR__ . '/partials/header.php');
?>
<div class="container-fluid">
    <div class="row">
        <?php
        require_once(__DIR__ . '/partials/sidebar.php');
        ?>
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
                <h2>Öğrenci Listesi</h2>
            </div>
            <script>
                $(document).ready( function () {
                    // Tabloyu Datatables ile başlatma ve Türkçe dilini kullanma
                    $('#studentsTable').DataTable({
                        "language": {
                            "url": "https://cdn.datatables.net/plug-ins/1.10.25/i18n/Turkish.json"
                        },
                        "responsive": true
                    });
                });
            </script>
            <style>
                table {
                    width: 100%;
                    border-collapse: collapse;
                    overflow-x: auto;
                    background-color: #f8f9fa; /* Arka plan rengi */
                }

                th, td {
                    border: 1px solid #dee2e6; /* Kenarlık rengi */
                    padding: 12px;
                    text-align: left;
                    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Hafif gölgelendirme */
                }

                th {
                    background-color: #6b6b6b; /* Başlık arka plan rengi */
                    color: #ffffff; /* Başlık metin rengi */
                }

                tbody tr:nth-child(even) {
                    background-color: #f2f2f2; /* Sıra arka plan rengi */
                }

                tbody tr:hover {
                    background-color: #e9ecef; /* Hover efekti arka plan rengi */
                }

                .action-buttons {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                }

                .action-buttons a {
                    display: block;
                    margin-bottom: 5px;
                    padding: 8px;
                    text-align: center;
                    background-color: #007bff; /* Buton arka plan rengi */
                    color: #ffffff; /* Buton metin rengi */
                    text-decoration: none;
                    border-radius: 5px;
                }

                .action-buttons a:hover {
                    background-color: #0056b3; /* Hover efekti arka plan rengi */
                }
            </style>
            <div class="table-responsive">

            <table id="studentsTable">
    <thead>
    <tr><th>Akademi</th>
        <th>Adı</th>
        <th>Soyadı</th>
        <th>T.C. Kimlik No</th>
        <th>Doğum Tarihi</th>
        <th>Cep Telefonu</th>
        <th>E-posta</th>
        <!-- <th>Veli Ad Soyad</th>
         <th>Veli Telefon</th>
         <th>Veli E-Posta</th>
           <th>Acil Durumda Aranacak Kişi</th>
           <th>Acil Durumda Aranacak Kişi Telefonu</th>
           <th>Kan Grubu</th>
           <th>Bilinen Rahatsızlık</th>
           <th>İl</th>
           <th>İlçe</th>
           <th>Adres</th>-->
          <th>İşlemler</th>
      </tr>
      </thead>
      <tbody>
      <!-- Öğrenci bilgilerini listeleyen döngü -->
    <?php foreach ($students as $student): ?>
        <tr>
            <td><?php echo $student['academy_name']; ?></td>
            <td><?php echo $student['first_name']; ?></td>
            <td><?php echo $student['last_name']; ?></td>
            <td><?php echo $student['tc_identity']; ?></td>
            <td><?php echo date('d.m.Y', strtotime($student['birth_date'])); ?></td>
            <td><?php echo $student['phone']; ?></td>
            <td><?php echo $student['email']; ?></td>
            <!--<td><?php echo $student['parent_first_name'] . ' ' . $student['parent_last_name']; ?></td>
            <td><?php echo $student['parent_phone']; ?></td>
            <td><?php echo $student['parent_email']; ?></td>
            <td><?php echo $student['blood_type']; ?></td>
            <td><?php echo $student['health_issue']; ?></td>
            <td><?php echo $student['city']; ?></td>
            <td><?php echo $student['district']; ?></td>
            <td><?php echo $student['address']; ?></td>-->
            <td>
                <a class="btn btn-primary" href="user_profile.php?id=<?php echo $student['id']; ?>"><i class="fas fa-user"></i></a>
                <a class="btn btn-warning" href="edit_user.php?id=<?php echo $student['id']; ?>"><i class="fas fa-edit"></i></a>
                <a class="btn btn-danger" href="delete_user.php?id=<?php echo $student['id']; ?>"><i class="fas fa-trash-alt"></i></a>
            </td>


        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
            </div>

<?php require_once('../admin/partials/footer.php'); ?>

