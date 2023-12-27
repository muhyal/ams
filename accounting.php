<?php
global $db, $showErrors, $siteName, $siteShortName, $siteUrl;
session_start();
// Oturum kontrolü
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php"); // Giriş sayfasına yönlendir
    exit();
}
require_once "db_connection.php";
require_once "config.php";
require_once "admin_panel_header.php";
// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);

// Ödeme yöntemi için bir dizin oluşturun
$paymentMethods = [
    'cash' => 1,
    'credit_card' => 2
];

// Formdan gelen değeri alın
$payment_method_add = $_POST["payment_method_add"];

// Ödeme yöntemini sayısal değere dönüştürün
$payment_method_id = $paymentMethods[$payment_method_add];

// Form gönderildi mi kontrolü
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Veri ekleme
    if (isset($_POST["add_entry"])) {
        $academy_id_add = $_POST["academy_id_add"];
        $student_id_add = $_POST["student_id_add"];
        $course_id_add = $_POST["course_id_add"];
        $amount_add = $_POST["amount_add"];
        $entry_date_add = $_POST["entry_date_add"];
        $payment_method_add = $_POST["payment_method_add"];

        // SQL sorgusunu güncelleyin
        $sql = "INSERT INTO accounting_entries (academy_id, student_id, course_id, amount, entry_date, payment_method) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$academy_id_add, $student_id_add, $course_id_add, $amount_add, $entry_date_add, $payment_method_id]);


        if ($stmt->rowCount()) {
            echo "Veri başarıyla eklendi.";
        } else {
            echo "Veri eklenirken bir hata oluştu.";
        }
    }
}

// Öğrenci bilgilerini getir
$sql_students = "SELECT * FROM students";
$stmt_students = $db->query($sql_students);
$students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

// Ders bilgilerini getir
$sql_courses = "SELECT * FROM courses";
$stmt_courses = $db->query($sql_courses);
$courses = $stmt_courses->fetchAll(PDO::FETCH_ASSOC);

// Akademi bilgilerini getir
$sql_academies = "SELECT * FROM academies";
$stmt_academies = $db->query($sql_academies);
$academies = $stmt_academies->fetchAll(PDO::FETCH_ASSOC);
?>


    <div class="container-fluid">
    <div class="row">
<?php
require_once "admin_panel_sidebar.php";
?>
    <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
        <h2>Muhasebe</h2>
    </div>

<!-- Veri Ekleme Formu -->
<h3>Giriş Ekle</h3>
    <form method="post">
        <div class="form-group">
            <label for="academy_id_add">Akademi:</label>
            <select name="academy_id_add" class="form-control">
                <?php foreach ($academies as $academy): ?>
                    <option value="<?= $academy['id'] ?>"><?= $academy['name'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="student_id_add">Öğrenci:</label>
            <select name="student_id_add" class="form-control">
                <?php foreach ($students as $student): ?>
                    <option value="<?= $student['id'] ?>">
                        <?= $student['firstname'] . ' ' . $student['lastname'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="course_id_add">Ders:</label>
            <select name="course_id_add" class="form-control">
                <?php foreach ($courses as $course): ?>
                    <option value="<?= $course['id'] ?>"><?= $course['course_name'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="amount_add">Tutar:</label>
            <input type="text" name="amount_add" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="entry_date_add">Tarih:</label>
            <input type="date" name="entry_date_add" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="payment_method_add">Ödeme yöntemi:</label>
            <select name="payment_method_add" class="form-control">
                <option value="cash">Nakit</option>
                <option value="credit_card">Kredi Kartı</option>
            </select>
        </div>

        <button type="submit" name="add_entry" class="btn btn-primary">Giriş Ekle</button>
    </form>

<?php
require_once "footer.php";
?>