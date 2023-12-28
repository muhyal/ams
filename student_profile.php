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
?>
<?php
require_once "admin_panel_header.php";
?>
    <div class="container-fluid">
    <div class="row">

<?php
require_once "admin_panel_sidebar.php";
?>
    <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">


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
        ?>

        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group mr-2">
                            <button onclick="history.back()" class="btn btn-sm btn-outline-secondary">Geri dön</button>
                            <a href="student_list.php" class="btn btn-sm btn-outline-secondary">Öğrenci Listesi</a>

                        </div>
                    </div>
                </div>
            </div>
        </div>
<br>
        <div class="card">
            <div class="card-header">
                <h3 class="card-subtitle"><?php echo $student['firstname']; ?> <?php echo $student['lastname']; ?> için öğrenci bilgileri</h3>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><strong>Ad:</strong> <?php echo $student['firstname']; ?></li>
                    <li class="list-group-item"><strong>Soyad:</strong> <?php echo $student['lastname']; ?></li>
                    <li class="list-group-item"><strong>T.C. Kimlik No:</strong> <?php echo $student['tc_identity']; ?></li>
                    <li class="list-group-item"><strong>Cep Telefonu:</strong> <?php echo $student['phone']; ?></li>
                    <li class="list-group-item"><strong>E-posta:</strong> <?php echo $student['email']; ?></li>
                    <li class="list-group-item"><strong>Veli Adı Soyadı:</strong> <?php echo $student['parent_firstname'] . ' ' . $student['parent_lastname']; ?></li>
                    <li class="list-group-item"><strong>Veli Telefonu:</strong> <?php echo $student['parent_phone']; ?></li>
                    <li class="list-group-item"><strong>Veli E-posta:</strong> <?php echo $student['parent_email']; ?></li>
                    <li class="list-group-item"><strong>Acil Durum Kişi:</strong> <?php echo $student['emergency_contact']; ?></li>
                    <li class="list-group-item"><strong>Acil Durum Telefonu:</strong> <?php echo $student['emergency_phone']; ?></li>
                    <li class="list-group-item"><strong>Kan Grubu:</strong> <?php echo $student['blood_type']; ?></li>
                    <li class="list-group-item"><strong>Rahatsızlık:</strong> <?php echo $student['health_issue']; ?></li>
                    <li class="list-group-item"><strong>İl:</strong> <?php echo $student['city']; ?></li>
                    <li class="list-group-item"><strong>İlçe:</strong> <?php echo $student['district']; ?></li>
                    <li class="list-group-item"><strong>Adres:</strong> <?php echo $student['address']; ?></li>
                </ul>
            </div>
        </div>
        <?php
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


