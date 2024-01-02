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

// Bugünün tarihini al
$currentDate = date('d-m-Y');

// Uyarı mesajları için değişkenler
$alertMessage = '';
$alertColor = '';

// Akademileri çek
$sql_academies = "SELECT * FROM academies";
$stmt_academies = $db->query($sql_academies);
$academies = $stmt_academies->fetchAll(PDO::FETCH_ASSOC);

// Öğrenci bilgilerini çek
$sql_students = "SELECT * FROM students";
$stmt_students = $db->query($sql_students);
$students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

// Ders bilgilerini çek
$sql_courses = "SELECT * FROM courses";
$stmt_courses = $db->query($sql_courses);
$courses = $stmt_courses->fetchAll(PDO::FETCH_ASSOC);

// Ödeme yöntemlerini çek
$sql_payment_methods = "SELECT * FROM payment_methods";
$stmt_payment_methods = $db->query($sql_payment_methods);
$payment_methods = $stmt_payment_methods->fetchAll(PDO::FETCH_ASSOC);

// Form gönderildi mi kontrolü
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Veri ekleme
    if (isset($_POST["add_entry"])) {
        // Academy_id_add değerini al
        $academy_id_add = isset($_POST["academy_id_add"]) ? $_POST["academy_id_add"] : null;
        $student_id_add = $_POST["student_id_add"];
        $course_id_add = $_POST["course_id_add"];
        $amount_add = $_POST["amount_add"];
        $entry_date_add = $_POST["entry_date_add"];

        // Ödeme yöntemini sayısal değere dönüştürün
        $payment_method_add = isset($_POST["payment_method_add"]) ? $_POST["payment_method_add"] : '';
        $payment_method_id = $payment_method_add; // Değişiklik yapıldı

        // Bakiye güncelleme
        $sql_update_balance = "UPDATE accounting SET balance = balance + ? WHERE academy_id = ?";
        $stmt_update_balance = $db->prepare($sql_update_balance);
        $stmt_update_balance->execute([$amount_add, $academy_id_add]);

        // SQL sorgusunu güncelleyin
        $sql = "INSERT INTO accounting_entries (academy_id, student_id, course_id, amount, entry_date, payment_method) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$academy_id_add, $student_id_add, $course_id_add, $amount_add, $entry_date_add, $payment_method_id]);

        if ($stmt->rowCount()) {
            $alertMessage = 'Muhasebe kaydı başarıyla eklendi.';
            $alertColor = 'success';
        } else {
            $alertMessage = 'Muhasebe kaydı eklenirken bir hata oluştu.';
            $alertColor = 'danger';
        }
    }
}

// Muhasebe girdilerini çek
$sql_entries = "
    SELECT 
        accounting_entries.id,
        accounting_entries.academy_id,
        accounting_entries.student_id,
        accounting_entries.course_id,
        accounting_entries.amount,
        accounting_entries.entry_date,
        accounting_entries.payment_method
    FROM accounting_entries
";
$stmt_entries = $db->query($sql_entries);
$entries = $stmt_entries->fetchAll(PDO::FETCH_ASSOC) ?? [];
?>
<?php
require_once "admin_panel_header.php";
?>
<div class="container-fluid">
    <div class="row">
        <?php require_once "admin_panel_sidebar.php"; ?>
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
                <h2>Muhasebe</h2>
            </div>

            <!-- Uyarı Mesajları -->
            <?php if (!empty($alertMessage)): ?>
                <div class="alert alert-<?= $alertColor ?>" role="alert">
                    <?= $alertMessage ?>
                </div>
            <?php endif; ?>

            <!-- Veri Ekleme Formu -->
            <h5>Kayıt Ekle</h5>
            <form method="post">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mt-3">
                            <label for="academy_id_add">Akademi:</label>
                            <select name="academy_id_add" class="form-control">
                                <?php foreach ($academies as $academy): ?>
                                    <option value="<?= $academy['id'] ?>"><?= $academy['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group mt-3">
                            <label for="student_id_add">Öğrenci:</label>
                            <select name="student_id_add" class="form-control">
                                <?php foreach ($students as $student): ?>
                                    <option value="<?= $student['id'] ?>">
                                        <?= $student['firstname'] . ' ' . $student['lastname'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group mt-3">
                            <label for="course_id_add">Ders:</label>
                            <select name="course_id_add" class="form-control">
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?= $course['id'] ?>"><?= $course['course_name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group mt-3">
                            <label for="amount_add">Tutar:</label>
                            <input type="text" name="amount_add" class="form-control" required>
                        </div>

                        <div class="form-group mt-3">
                            <label for="entry_date_add">Tarih:</label>
                            <input type="datetime-local" name="entry_date_add" class="form-control" onchange="validateDate()" required>
                        </div>

                        <div class="form-group mt-3">
                            <label for="payment_method_add">Ödeme yöntemi:</label>
                            <select name="payment_method_add" class="form-control">
                                <?php foreach ($payment_methods as $method): ?>
                                    <option value="<?= $method['id'] ?>"><?= $method['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-group mt-3">
                <button type="submit" name="add_entry" class="btn btn-primary">Giriş Ekle</button>
                </div>
            </form>

            <script>
                function getCurrentDateTime() {
                    var currentDate = new Date();
                    var year = currentDate.getFullYear();
                    var month = (currentDate.getMonth() + 1).toString().padStart(2, '0');
                    var day = currentDate.getDate().toString().padStart(2, '0');
                    var hours = currentDate.getHours().toString().padStart(2, '0');
                    var minutes = currentDate.getMinutes().toString().padStart(2, '0');
                    var seconds = currentDate.getSeconds().toString().padStart(2, '0');
                    return `${year}-${month}-${day}T${hours}:${minutes}`;
                }

                function validateDate() {
                    var dateInput = document.querySelector('input[name="entry_date_add"]');
                    if (!dateInput.value) {
                        // Eğer tarih seçilmemişse, otomatik doldur
                        dateInput.value = getCurrentDateTime();
                    }
                }

                // Sayfa yüklendiğinde tarih alanını otomatik doldur
                document.addEventListener("DOMContentLoaded", function () {
                    validateDate();
                });
            </script>
            <?php require_once "footer.php"; ?>
        </main>
    </div>
</div>
