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
// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);

// Kullanıcı bilgilerini kullanabilirsiniz
$admin_id = $_SESSION["admin_id"];
$admin_username = $_SESSION["admin_username"];

// Öğretmenleri veritabanından çekme
$queryTeachers = "SELECT id, first_name, last_name FROM teachers";
$stmtTeachers = $db->prepare($queryTeachers);
$stmtTeachers->execute();
$teachers = $stmtTeachers->fetchAll(PDO::FETCH_ASSOC);

// Dersleri veritabanından çekme
$queryCourses = "SELECT id, course_name FROM courses";
$stmtCourses = $db->prepare($queryCourses);
$stmtCourses->execute();
$courses = $stmtCourses->fetchAll(PDO::FETCH_ASSOC);

// Sınıfları veritabanından çekme
$queryClasses = "SELECT id, class_name FROM classes";
$stmtClasses = $db->prepare($queryClasses);
$stmtClasses->execute();
$classes = $stmtClasses->fetchAll(PDO::FETCH_ASSOC);

// Akademileri veritabanından çekme
$queryAcademies = "SELECT id, name FROM academies";
$stmtAcademies = $db->prepare($queryAcademies);
$stmtAcademies->execute();
$academies = $stmtAcademies->fetchAll(PDO::FETCH_ASSOC);
?>
<?php
require_once "admin_panel_header.php";
?>
    <script src="/js/jquery.min.js"></script>
    <script src="/js/jquery.inputmask.min.js"></script>
    <script>
        function addPrefixToPhoneInput(input) {
            // Inputa tıklanıldığında 90 değerini otomatik olarak ekleyin
            if (!input.value.startsWith("90")) {
                input.value = "90" + input.value;
            }
        }
    </script>

<div class="container-fluid">
    <div class="row">
        <?php
        require_once "admin_panel_sidebar.php";
        ?>
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
                <h5>Öğrenci Ekle</h5>
            </div>
<form action="process_add_student.php" method="post" id="studentForm">
    <label class="form-label" for="firstname">Öğrenci Adı:</label>
    <input class="form-control" type="text" name="firstname" required><br>
    <label class="form-label" for="lastname">Öğrenci Soyadı:</label>
    <input class="form-control" type="text" name="lastname" required><br>
    <label class="form-label" for="tc_identity">TC Kimlik No:</label>
    <input class="form-control" type="text" name="tc_identity" required><br>
    <!-- Doğum Tarihi -->
    <h5>Doğum Tarihi</h5>
    <label class="form-label" for="birthdate">Doğum Tarihi:</label>
    <input class="form-control" type="date" id="birthdate" name="birthdate" required><br>
    <label class="form-label" for="phone">Cep Telefonu:</label>
    <input class="form-control" type="text" name="phone" onclick="addPrefixToPhoneInput(this)"><br>
    <label class="form-label" for="email">E-posta Adresi:</label>
    <input class="form-control" type="email" name="email" required><br>

    <!-- Veli Bilgileri -->
    <h5>Veli Bilgileri</h5>
    <label class="form-label" for="parent_checkbox"><input class="form-control" type="checkbox" id="parent_checkbox" name="parent_checkbox">Kendi bilgilerimi kullanmak istiyorum</label><br>
    <label class="form-label" for="parent_firstname">Veli Adı:</label>
    <input class="form-control" type="text" name="parent_firstname" id="parent_firstname" required><br>
    <label class="form-label" for="parent_lastname">Veli Soyadı:</label>
    <input class="form-control" type="text" name="parent_lastname" id="parent_lastname" required><br>
    <label class="form-label" for="parent_phone">Veli Cep Telefonu:</label>
    <input class="form-control" type="text" name="parent_phone" id="parent_phone" required onclick="addPrefixToPhoneInput(this)"><br>
    <label class="form-label" for="parent_email">Veli E-posta Adresi:</label>
    <input class="form-control" type="email" name="parent_email" id="parent_email" required><br>

    <!-- Acil Durum İletişim Bilgileri -->
    <h5>Acil Durum İletişim Bilgileri</h5>
    <label class="form-label" for="emergency_contact">Acil Durumda Aranacak Kişi:</label>
    <input class="form-control" type="text" name="emergency_contact" required><br>
    <label class="form-label" for="emergency_phone">Acil Durumda Aranacak Kişi Telefonu:</label>
    <input class="form-control" type="text" name="emergency_phone" required onclick="addPrefixToPhoneInput(this)"><br>

    <!-- Adres Bilgileri -->
    <h5>Adres Bilgileri</h5>

    <label class="form-label" for="city">İl:</label>
    <input class="form-control" type="text" name="city" required><br>

    <label class="form-label" for="district">İlçe:</label>
    <input class="form-control" type="text" name="district" required><br>

    <div class="form-group">
        <label for="address">Adres:</label>
        <textarea class="form-control" id="address" name="address" rows="3" required></textarea>
    </div>


    <!-- Kan Grubu ve Rahatsızlık Bilgisi -->
    <h5>Kan Grubu ve Rahatsızlık Bilgisi</h5>
    <label class="form-label" for="blood_type">Kan Grubu:</label>
    <input class="form-control" type="text" name="blood_type" required><br>
    <label class="form-label" for="health_issue">Bilinen Rahatsızlık:</label>
    <input class="form-control" type="text" name="health_issue"><br>

    <!-- Akademi Seçimi -->
        <h5>Akademi Seçimi</h5>
        <div class="form-group">
            <select class="form-control" name="academy" required>
                <option value="">Akademi Seçin</option>
                <?php foreach ($academies as $academy): ?>
                    <option value="<?php echo $academy['id']; ?>"><?php echo $academy['name']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>

    <!-- Öğretmen Seçimi -->
        <h5>Öğretmen Seçimi</h5>
        <div class="form-group">
            <select class="form-control" name="teacher" required>
                <option value="">Öğretmen Seçin</option>
                <?php foreach ($teachers as $teacher): ?>
                    <option value="<?php echo $teacher['id']; ?>"><?php echo $teacher['first_name'] . ' ' . $teacher['last_name']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>

    <!-- Ders Seçimi -->
        <h5>Ders Seçimi</h5>
        <div class="form-group">
            <select class="form-control" name="course" required>
                <option value="">Ders Seçin</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?php echo $course['id']; ?>"><?php echo $course['course_name']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>

    <!-- Sınıf Seçimi -->
        <h5>Sınıf Seçimi</h5>
        <div class="form-group">
            <select class="form-control" name="class" required>
                <option value="">Sınıf Seçin</option>
                <?php foreach ($classes as $class): ?>
                    <option value="<?php echo $class['id']; ?>"><?php echo $class['class_name']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>


    <button type="submit" class="btn btn-primary">Ekle</button>
    <button onclick="history.back()" class="btn btn-primary">Geri dön</button>
    <button onclick="window.location.href='student_list.php'" class="btn btn-secondary">Öğrenci listesi</button>
</form><br>
        </main>
    </div>
</div>

<script>
    const parentCheckbox = document.getElementById('parent_checkbox');
    const parentFirstName = document.getElementById('parent_firstname');
    const parentLastName = document.getElementById('parent_lastname');
    const parentPhone = document.getElementById('parent_phone');
    const parentEmail = document.getElementById('parent_email');

    parentCheckbox.addEventListener('change', function() {
        if (this.checked) {
            parentFirstName.value = document.querySelector('input[name="firstname"]').value;
            parentLastName.value = document.querySelector('input[name="lastname"]').value;
            parentPhone.value = document.querySelector('input[name="phone"]').value;
            parentEmail.value = document.querySelector('input[name="email"]').value;
        } else {
            parentFirstName.value = '';
            parentLastName.value = '';
            parentPhone.value = '';
            parentEmail.value = '';
        }
    });

    $(document).ready(function() {
        $('[data-mask]').inputmask();
    });
</script>

<?php
require_once "footer.php";
?>
