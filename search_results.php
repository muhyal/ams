<?php
// Veritabanı bağlantısı ve sorgu işlemleri gibi kodlar burada yer alır
global $db, $showErrors, $siteUrl, $siteName, $siteShortName;
// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);

// Oturum kontrolü
session_start();
session_regenerate_id(true);

if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php"); // Giriş sayfasına yönlendir
    exit();
}

require_once "db_connection.php";

// formdan gelen search_type değerini alalım
$searchType = $_GET['search_type'] ?? '';

// Belirli kullanıcı türlerine göre ve belirli sütunlarda arama sorgularını hazırlayın ve sonuçları alın
if ($searchType === "user") {
    // Kullanıcı arama sorgusu
    $query = "SELECT * FROM users WHERE 
              (first_name LIKE :searchQuery OR last_name LIKE :searchQuery OR username LIKE :searchQuery OR email LIKE :searchQuery OR phone LIKE :searchQuery) 
              AND (user_type = 4 OR user_type = 6 OR user_type = 5)";
} elseif ($searchType === "student") {
    // Öğrenci arama sorgusu
    $query = "SELECT * FROM users WHERE 
              (first_name LIKE :searchQuery OR last_name LIKE :searchQuery OR phone LIKE :searchQuery) 
              AND user_type = 6";
} elseif ($searchType === "class") {
    // Sınıf arama sorgusu
    $query = "SELECT * FROM academy_classes WHERE class_name LIKE :searchQuery";
} elseif ($searchType === "teacher") {
    // Öğretmen arama sorgusu
    $query = "SELECT * FROM users WHERE 
              (first_name LIKE :searchQuery OR last_name LIKE :searchQuery OR phone LIKE :searchQuery) 
              AND user_type = 4";
} elseif ($searchType === "course") {
    // Ders arama sorgusu
    $query = "SELECT * FROM courses WHERE course_name LIKE :searchQuery";
}
$searchQuery = $_GET['q'] ?? ''; // searchQuery değişkenini tanımlayın

$stmt = $db->prepare($query);
$stmt->bindValue(":searchQuery", "%$searchQuery%");
$stmt->execute();
$searchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Arama Sonuçları</h2>

<?php if (empty($searchResults)) { ?>
    <p>Hiçbir sonuç bulunamadı.</p>
<?php } else { ?>
    <ul>
        <?php foreach ($searchResults as $result) { ?>
            <li>
                <?php if ($searchType === "user" || $searchType === "student" || $searchType === "teacher") { ?>
                    Kullanıcı Adı: <?php echo $result['username']; ?><br>
                    Adı Soyadı: <?php echo $result['first_name'] . ' ' . $result['last_name']; ?><br>
                    E-posta: <?php echo $result['email']; ?><br>
                    Telefon: <?php echo $result['phone']; ?>
                <?php } elseif ($searchType === "class") { ?>
                    Sınıf Adı: <?php echo $result['class_name']; ?>
                <?php } elseif ($searchType === "course") { ?>
                    Ders: <?php echo $result['course_name']; ?>
                <?php } ?>

                <!-- Düzenleme ve Silme Bağlantıları -->
                <?php if ($searchType === "user" || $searchType === "student" || $searchType === "teacher") { ?>
                    <a href="user_profile.php?id=<?php echo $result['id']; ?>">Profil</a>
                    <a href="edit_user.php?id=<?php echo $result['id']; ?>">Düzenle</a>
                    <a href="delete_user.php?id=<?php echo $result['id']; ?>" onclick="return confirm('Bu kullanıcıyı silmek istediğinizden emin misiniz?')">Sil</a>
                <?php } elseif ($searchType === "class") { ?>
                    <a href="classes.php?edit_id=<?php echo $result['id']; ?>">Düzenle</a>
                    <a href="classes.php?delete_id=<?php echo $result['id']; ?>" onclick="return confirm('Bu sınıfı silmek istediğinizden emin misiniz?')">Sil</a>
                <?php } elseif ($searchType === "course") { ?>
                    <a href="courses.php?edit_id=<?php echo $result['id']; ?>">Düzenle</a>
                    <a href="courses.php?delete_id=<?php echo $result['id']; ?>" onclick="return confirm('Bu dersi silmek istediğinizden emin misiniz?')">Sil</a>
                <?php } ?>
            </li>
        <?php } ?>
    </ul>
<?php } ?>
