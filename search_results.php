<?php
// Veritabanı bağlantısı ve sorgu işlemleri gibi kodlar burada yer alır
global $db, $siteUrl, $siteName, $siteShortName;
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Oturum kontrolü
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php"); // Giriş sayfasına yönlendir
    exit();
}

require_once "db_connection.php";

// formdan gelen search_type değerini alalım
$searchType = $_GET['search_type'] ?? '';

// Arama sorgusunu hazırlayın ve sonuçları alın
if ($searchType === "user") {
    // Kullanıcı arama sorgusu
    $query = "SELECT * FROM users WHERE firstname LIKE :searchQuery";
} elseif ($searchType === "student") {
    // Öğrenci arama sorgusu
    $query = "SELECT * FROM students WHERE firstname LIKE :searchQuery OR lastname LIKE :searchQuery";
} elseif ($searchType === "class") {
    // Sınıf arama sorgusu
    $query = "SELECT * FROM classes WHERE class_name LIKE :searchQuery";
    } elseif ($searchType === "teacher") {
    // Öğretmen arama sorgusu
    $query = "SELECT * FROM teachers WHERE first_name LIKE :searchQuery OR last_name LIKE :searchQuery";
} elseif ($searchType === "course") {
    // Ders arama sorgusu
    $query = "SELECT * FROM courses WHERE course_name LIKE :searchQuery";
}
$searchQuery = $_GET['q'] ?? ''; // searchQuery değişkenini tanımlayın

$stmt = $db->prepare($query);
$stmt->bindValue(":searchQuery", "%$searchQuery%");
$stmt->execute();
$searchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once "admin_panel_header.php";
?>
<div class="container-fluid">
    <div class="row">
        <?php
        require_once "admin_panel_sidebar.php";
        ?>
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
                <h2>Arama Sonuçları</h2>
            </div>

<?php if (empty($searchResults)) { ?>
    <p>Hiçbir sonuç bulunamadı.</p>
<?php } else { ?>
    <ul>
        <?php foreach ($searchResults as $result) { ?>
            <li>
                <?php if ($searchType === "user") { ?>
                    Kullanıcı Adı: <?php echo $result['firstname']; ?>
                <?php } elseif ($searchType === "student") { ?>
                    Öğrenci: <?php echo $result['firstname'] . ' ' . $result['lastname']; ?>
                <?php } elseif ($searchType === "class") { ?>
                    Sınıf Adı: <?php echo $result['class_name']; ?>
                <?php } elseif ($searchType === "teacher") { ?>
                    Öğretmen: <?php echo $result['first_name'] . ' ' . $result['last_name']; ?>
                <?php } elseif ($searchType === "course") { ?>
                    Ders: <?php echo $result['course_name']; ?>
                <?php } ?>
            </li>
        <?php } ?>
    </ul>
<?php } ?>
<?php
require_once "footer.php";
?>
