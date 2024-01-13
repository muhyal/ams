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

<?php if (empty($searchResults)) { ?>
    <div class="alert alert-info" role="alert">
        Hiçbir sonuç bulunamadı.
    </div>
<?php } else { ?>
    <h2>Arama Sonuçları</h2>
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
            <tr>
                <?php if ($searchType === "user" || $searchType === "student" || $searchType === "teacher") { ?>
                    <th>Kullanıcı Adı</th>
                    <th>Adı Soyadı</th>
                    <th>E-posta</th>
                    <th>Telefon</th>
                <?php } elseif ($searchType === "class") { ?>
                    <th>Sınıf Adı</th>
                <?php } elseif ($searchType === "course") { ?>
                    <th>Ders</th>
                <?php } ?>
                <th>İşlemler</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($searchResults as $result) { ?>
                <tr>
                    <?php if ($searchType === "user" || $searchType === "student" || $searchType === "teacher") { ?>
                        <td><?php echo $result['username']; ?></td>
                        <td><?php echo $result['first_name'] . ' ' . $result['last_name']; ?></td>
                        <td><?php echo $result['email']; ?></td>
                        <td><?php echo $result['phone']; ?></td>
                    <?php } elseif ($searchType === "class") { ?>
                        <td><?php echo $result['class_name']; ?></td>
                    <?php } elseif ($searchType === "course") { ?>
                        <td><?php echo $result['course_name']; ?></td>
                    <?php } ?>
                    <td>
                        <?php if ($searchType === "user" || $searchType === "student" || $searchType === "teacher") { ?>
                            <a href="user_profile.php?id=<?php echo $result['id']; ?>" class="btn btn-info btn-sm"><i class="fas fa-user"></i></a>
                            <a href="edit_user.php?id=<?php echo $result['id']; ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                            <a href="delete_user.php?id=<?php echo $result['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bu kullanıcıyı silmek istediğinizden emin misiniz?')"><i class="fas fa-trash-alt"></i></a>
                        <?php } elseif ($searchType === "class") { ?>
                            <a href="classes.php?edit_id=<?php echo $result['id']; ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                            <a href="classes.php?delete_id=<?php echo $result['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bu sınıfı silmek istediğinizden emin misiniz?')"><i class="fas fa-trash-alt"></i></a>
                        <?php } elseif ($searchType === "course") { ?>
                            <a href="courses.php?edit_id=<?php echo $result['id']; ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                            <a href="courses.php?delete_id=<?php echo $result['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bu dersi silmek istediğinizden emin misiniz?')"><i class="fas fa-trash-alt"></i></a>
                        <?php } ?>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
<?php } ?>
