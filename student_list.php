<?php
global $db, $showErrors, $siteName, $siteShortName, $siteUrl, $config;
// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);
require_once "config.php";
session_start();
// Oturum kontrolü
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php"); // Giriş sayfasına yönlendir
    exit();
}

require_once "db_connection.php"; // Veritabanı bağlantısı

// Kullanıcı bilgilerini kullanabilirsiniz
$admin_id = $_SESSION["admin_id"];
$admin_username = $_SESSION["admin_username"];

require_once "admin_panel_header.php";

// Öğrenci listesi sorgusu
$query = "SELECT students.*, parents.*, emergency_contacts.*, addresses.* 
          FROM students 
          LEFT JOIN parents ON students.id = parents.student_id 
          LEFT JOIN emergency_contacts ON students.id = emergency_contacts.student_id 
          LEFT JOIN addresses ON students.id = addresses.student_id";

$stmt = $db->query($query);

// Öğrenci verilerini alın
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="row">
        <?php
        require_once "admin_panel_sidebar.php";
        ?>
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
                <h2>Öğrenci Listesi</h2>
            </div>

    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }
    </style>

<table>
    <thead>
    <tr>
        <th>Öğrenci Adı</th>
        <th>Öğrenci Soyadı</th>
        <th>Öğrenci TC Kimlik No</th>
        <th>Doğum Tarihi</th>
        <th>Öğrenci Cep Telefonu</th>
        <th>Öğrenci E-posta</th>
        <th>Veli Ad Soyad</th>
        <th>Veli Telefon</th>
        <th>Veli E-Posta</th>
        <th>Acil Durumda Aranacak Kişi</th>
        <th>Acil Durumda Aranacak Kişi Telefonu</th>
        <th>Kan Grubu</th>
        <th>Bilinen Rahatsızlık</th>
        <th>İl</th>
        <th>İlçe</th>
        <th>Adres</th>
        <th>Profil</th> <!-- Profil Bağlantısı -->
        <th>Düzenle</th> <!-- Düzenleme Bağlantısı -->
        <th>Sil</th> <!-- Silme Bağlantısı -->
    </tr>
    </thead>
    <tbody>
    <!-- Öğrenci bilgilerini listeleyen döngü -->
    <?php foreach ($students as $student): ?>
        <tr>
            <td><?php echo $student['firstname']; ?></td>
            <td><?php echo $student['lastname']; ?></td>
            <td><?php echo $student['tc_identity']; ?></td>
            <td><?php echo $student['birthdate']; ?></td>
            <td><?php echo $student['phone']; ?></td>
            <td><?php echo $student['email']; ?></td>
            <td><?php echo $student['parent_firstname'] . ' ' . $student['parent_lastname']; ?></td> <!-- Veli bilgisi -->
            <td><?php echo $student['parent_phone']; ?></td> <!-- Veli telefonu -->
            <td><?php echo $student['parent_email']; ?></td> <!-- Veli e-postası -->
            <td><?php echo $student['emergency_contact']; ?></td>
            <td><?php echo $student['emergency_phone']; ?></td>
            <td><?php echo $student['blood_type']; ?></td>
            <td><?php echo $student['health_issue']; ?></td>
            <td><?php echo $student['city']; ?></td>
            <td><?php echo $student['district']; ?></td>
            <td><?php echo $student['address']; ?></td>
            <td><a href="student_profile.php?id=<?php echo $student['id']; ?>">Profil</a></td>
            <td><a href="edit_student.php?id=<?php echo $student['id']; ?>">Düzenle</a></td>
            <td><a href="delete_student.php?id=<?php echo $student['id']; ?>">Sil</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php
require_once "footer.php";
?>
