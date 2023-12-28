<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Oturum kontrolü
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php"); // Giriş sayfasına yönlendir
    exit();
}

// Veritabanı bağlantısı ve gerekli dosyaları include edin
global $db;
require_once "db_connection.php";

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["id"])) {
    $studentId = $_GET["id"];

    // Öğrenci bilgilerini çekme sorgusu
    $query = "SELECT firstname, lastname, tc_identity, phone, email
              FROM students
              WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$studentId]);
    $studentInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$studentInfo) {
        echo "Öğrenci bulunamadı.";
        exit();
    }
} else if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["student_id"])) {
    $studentId = $_POST["student_id"];

    // Öğrenciyi silme sorgusu
    $deleteQuery = "DELETE FROM students WHERE id = ?";
    $stmt = $db->prepare($deleteQuery);

    if ($stmt->execute([$studentId])) {
        // Başarılı silme işlemi
        header("Location: student_list.php"); // Öğrenci listesine yönlendir
        exit();
    } else {
        echo "Öğrenci silinirken bir hata oluştu.";
    }
} else {
    echo "Geçersiz istek.";
}
?>
<?php
require_once "admin_panel_header.php";
?>
    <div class="container-fluid">
        <div class="row">
            <?php require_once "admin_panel_sidebar.php"; ?>
            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
                    <h2>Öğrenci Sil</h2>
                </div>

                <p><strong>Öğrenci Adı:</strong> <?php echo $studentInfo['firstname']; ?></p>
                <p><strong>Öğrenci Soyadı:</strong> <?php echo $studentInfo['lastname']; ?></p>
                <p><strong>T.C. Kimlik No:</strong> <?php echo $studentInfo['tc_identity']; ?></p>
                <p><strong>Öğrenci Telefon:</strong> <?php echo $studentInfo['phone']; ?></p>
                <p><strong>Öğrenci E-Posta:</strong> <?php echo $studentInfo['email']; ?></p>

                <p><strong>Öğrenciyi silmek istediğinizden emin misiniz?</strong></p>

                <form method="POST" action="delete_student.php">
                    <input type="hidden" name="student_id" value="<?php echo $studentId; ?>">
                    <button type="submit" name="confirm_delete" class="btn btn-danger">Öğrenciyi Sil</button>
                    <a href="student_list.php" class="btn btn-secondary">İptal</a>
                </form>
            </main>
        </div>
    </div>

<?php require_once "footer.php"; ?>