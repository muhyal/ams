<?php
global $resetPasswordDescription, $db, $showErrors, $siteName, $siteShortName, $siteUrl, $config;
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
?>

    <div class="container-fluid">
    <div class="row">
<?php
require_once "admin_panel_sidebar.php";
?>
    <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
            <h2>Öğrenci Profili</h2>
        </div>

<?php
// Öğrenci ID'sini URL'den alın
if (isset($_GET['id'])) {
    $student_id = $_GET['id'];

// Öğrenci ve diğer bilgileri birleştiren sorgu
    $query = "SELECT students.*, 
                 parents.parent_firstname, parents.parent_lastname, parents.parent_phone, parents.parent_email,
                 emergency_contacts.emergency_contact, emergency_contacts.emergency_phone, 
                 addresses.city, addresses.district, addresses.address
          FROM students
          LEFT JOIN parents ON students.id = parents.student_id
          LEFT JOIN emergency_contacts ON students.id = emergency_contacts.student_id
          LEFT JOIN addresses ON students.id = addresses.student_id
          WHERE students.id = ?";


    $stmt = $db->prepare($query);
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($student) {
        // Öğrenci bilgilerini tablo içinde görüntülemek için HTML çıktısı oluşturun
        echo "<table border='1'>";
        echo "<tr><td>Ad</td><td>Soyad</td><td>T.C. Kimlik No</td><td>Cep Telefonu</td><td>E-posta</td><td>Veli Adı Soyadı</td><td>Veli Telefonu</td><td>Veli E-posta</td><td>Acil Durum Kişi</td><td>Acil Durum Telefonu</td><td>Kan Grubu</td><td>Rahatsızlık</td><td>İl</td><td>İlçe</td><td>Adres</td></tr>";
        echo "<tr>";
        echo "<td>" . $student['firstname'] . "</td>";
        echo "<td>" . $student['lastname'] . "</td>";
        echo "<td>" . $student['tc_identity'] . "</td>";
        echo "<td>" . $student['phone'] . "</td>";
        echo "<td>" . $student['email'] . "</td>";
        echo "<td>" . $student['parent_firstname'] . ' ' . $student['parent_lastname'] . "</td>";
        echo "<td>" . $student['parent_phone'] . "</td>";
        echo "<td>" . $student['parent_email'] . "</td>";
        echo "<td>" . $student['emergency_contact'] . "</td>";
        echo "<td>" . $student['emergency_phone'] . "</td>";
        echo "<td>" . $student['blood_type'] . "</td>";
        echo "<td>" . $student['health_issue'] . "</td>";
        echo "<td>" . $student['city'] . "</td>";
        echo "<td>" . $student['district'] . "</td>";
        echo "<td>" . $student['address'] . "</td>";
        echo "</tr>";
        echo "</table>";
    } else {
        echo "Öğrenci bulunamadı.";
    }
} else {
    echo "Geçersiz öğrenci ID'si.";
}
?>

<?php
require_once "footer.php";
?>