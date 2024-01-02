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
global $db, $showErrors, $siteName, $siteShortName, $siteUrl;
session_start();
session_regenerate_id(true);

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
<?php require_once "admin_panel_header.php"; ?>

<script src="./assets/js/jquery.min.js"></script>
<script src="./assets/js/jquery.inputmask.min.js"></script>
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
        <?php require_once "admin_panel_sidebar.php"; ?>
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
                <h5>Öğrenci Ekle</h5>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group mr-2">
                        <button onclick="history.back()" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Geri dön
                        </button>
                        <a href="student_list.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-list"></i> Öğrenci Listesi
                        </a>
                    </div>
                </div>
            </div>

            <form action="process_add_student.php" method="post" id="studentForm">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label" for="firstname">Öğrenci Adı:</label>
                            <input class="form-control" type="text" name="firstname" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="lastname">Öğrenci Soyadı:</label>
                            <input class="form-control" type="text" name="lastname" required>
                        </div>


                <div class="mb-3">
                    <label class="form-label" for="tc_identity">TC Kimlik No:</label>
                    <input class="form-control" type="text" name="tc_identity" required>
                </div>

                <div class="mb-3">
<!--                    <h5>Doğum Tarihi</h5>-->
                    <label class="form-label" for="birthdate">Doğum Tarihi:</label>
                    <input class="form-control" type="date" id="birthdate" name="birthdate" required>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="phone">Cep Telefonu:</label>
                    <input class="form-control" type="text" name="phone" onclick="addPrefixToPhoneInput(this)">
                </div>

                <div class="mb-3">
                    <label class="form-label" for="email">E-posta Adresi:</label>
                    <input class="form-control" type="email" name="email" required>
                </div>

                        <!-- Adres Bilgileri -->
                        <div class="mb-3">
                            <h5>Adres Bilgileri</h5>
                            <label class="form-label" for="city">İl:</label>
                            <input class="form-control" type="text" name="city" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="district">İlçe:</label>
                            <input class="form-control" type="text" name="district" required>
                        </div>

                        <div class="mb-3">
                            <label for="address">Adres:</label>
                            <textarea class="form-control" id="address" name="address" rows="3" required></textarea>
                        </div>

                        <!-- Kan Grubu ve Rahatsızlık Bilgisi -->
                        <div class="mb-3">
<!--                            <h5 class="mb-3">Kan Grubu ve Rahatsızlık Bilgisi</h5>-->

                            <div class="mb-3">
                                <label class="form-label" for="blood_type">Kan Grubu:</label>
                                <input class="form-control" type="text" name="blood_type" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="health_issue">Bilinen Rahatsızlık:</label>
                                <input class="form-control" type="text" name="health_issue">
                            </div>
                        </div>

                    </div>
                        <div class="col-md-6">

                <!-- Veli Bilgileri -->
                <div class="mb-3">
                    <h5>Veli Bilgileri</h5>
                    <label class="form-check">
                        <input type="checkbox" id="parent_checkbox" name="parent_checkbox" class="form-check-input">
                        Kendi bilgilerimi kullanmak istiyorum
                    </label>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="parent_firstname">Veli Adı:</label>
                    <input class="form-control" type="text" name="parent_firstname" id="parent_firstname" required>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="parent_lastname">Veli Soyadı:</label>
                    <input class="form-control" type="text" name="parent_lastname" id="parent_lastname" required>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="parent_phone">Veli Cep Telefonu:</label>
                    <input class="form-control" type="text" name="parent_phone" id="parent_phone" value="90" required onclick="addPrefixToPhoneInput(this)">
                </div>

                <div class="mb-3">
                    <label class="form-label" for="parent_email">Veli E-posta Adresi:</label>
                    <input class="form-control" type="email" name="parent_email" id="parent_email" required>
                </div>

                <!-- Acil Durum İletişim Bilgileri -->
                <div class="mb-3">
                    <h5>Acil Durum İletişim Bilgileri</h5>
                    <label class="form-label" for="emergency_contact">Acil Durumda Aranacak Kişi:</label>
                    <input class="form-control" type="text" name="emergency_contact" required>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="emergency_phone">Acil Durumda Aranacak Kişi Telefonu:</label>
                    <input class="form-control" type="text" name="emergency_phone" required onclick="addPrefixToPhoneInput(this)">
                </div>


                <!-- Akademi Seçimi -->
                <div class="mb-3">
                    <h5 class="mb-3">Akademi Seçimi</h5>
                    <div class="mb-3">
                        <select class="form-control" name="academy" required>
                            <option value="">Akademi Seçin</option>
                            <?php foreach ($academies as $academy): ?>
                                <option value="<?php echo $academy['id']; ?>"><?php echo $academy['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Öğretmen Seçimi -->
                <div class="mb-3">
                    <h5 class="mb-3">Öğretmen Seçimi</h5>
                    <div class="mb-3">
                        <select class="form-control" name="teacher" required>
                            <option value="">Öğretmen Seçin</option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?php echo $teacher['id']; ?>"><?php echo $teacher['first_name'] . ' ' . $teacher['last_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Ders Seçimi -->
                <div class="mb-3">
                    <h5 class="mb-3">Ders Seçimi</h5>
                    <div class="mb-3">
                        <select class="form-control" name="course" required>
                            <option value="">Ders Seçin</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?php echo $course['id']; ?>"><?php echo $course['course_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Sınıf Seçimi -->
                <div class="mb-3">
                    <h5 class="mb-3">Sınıf Seçimi</h5>
                    <div class="mb-3">
                        <select class="form-control" name="class" required>
                            <option value="">Sınıf Seçin</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['id']; ?>"><?php echo $class['class_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                        </div>
                            <div class="form-group mt-3">
                                <button type="submit" class="btn btn-primary">Öğrenci Ekle</button>
                            </div>
            </form>
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
