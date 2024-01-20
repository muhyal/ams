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
 */
global $db;
session_start();
session_regenerate_id(true);

// Kullanıcı girişi kontrolü
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php");
    exit();
}

require_once "db_connection.php"; // Veritabanı bağlantısı

require 'vendor/autoload.php';

use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberFormat;
use League\ISO3166\ISO3166;

// Ülkeleri al
$phoneNumberUtil = PhoneNumberUtil::getInstance();
$iso3166 = new ISO3166();

// Kullanıcı bilgilerini kullanabilirsiniz
$admin_id = $_SESSION["admin_id"];
$student_id = isset($_GET['student_id']) ? $_GET['student_id'] : null;
$admin_username = $_SESSION["admin_username"];

// Kullanıcı değişkenini tanımla
$user = null;

// URL parametrelerinden kullanıcı ID'sini al
if (isset($_GET["id"])) {
    $user_id = $_GET["id"];

    // Kullanıcı tipini al
    $query = "SELECT users.*, 
            CONCAT(u_created_by.first_name, ' ', u_created_by.last_name) AS created_by_name,
            CONCAT(u_updated_by.first_name, ' ', u_updated_by.last_name) AS updated_by_name,
            CONCAT(u_deleted_by.first_name, ' ', u_deleted_by.last_name) AS deleted_by_name
         FROM users
         LEFT JOIN users u_created_by ON users.created_by_user_id = u_created_by.id
         LEFT JOIN users u_updated_by ON users.updated_by_user_id = u_updated_by.id
         LEFT JOIN users u_deleted_by ON users.deleted_by_user_id = u_deleted_by.id
         WHERE users.id = :user_id";

    $stmt = $db->prepare($query);
    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // Belirtilen ID'ye sahip kullanıcı yoksa, kullanıcı listesine yönlendir
        header("Location: users.php");
        exit();
    }

    // Kullanıcı tipini al
    $user_type = $user['user_type'];
} else {
    // Kullanıcı ID sağlanmadıysa yönlendir
    header("Location: users.php");
    exit();
}

// Kullanıcı ve akademi ilişkisini çekmek için bir SQL sorgusu
$getUserAcademyQuery = "SELECT academy_id FROM user_academy_assignment WHERE user_id = :user_id";
$stmtUserAcademy = $db->prepare($getUserAcademyQuery);
$stmtUserAcademy->bindParam(':user_id', $_SESSION["admin_id"], PDO::PARAM_INT);
$stmtUserAcademy->execute();
$associatedAcademies = $stmtUserAcademy->fetchAll(PDO::FETCH_COLUMN);

// Eğer kullanıcı hiçbir akademide ilişkilendirilmemişse veya bu akademilerden hiçbiri yoksa, uygun bir işlemi gerçekleştirin
if (empty($associatedAcademies)) {
    echo "Kullanıcınız bu işlem için yetkili değil!";
    exit();
}

// Eğitim danışmanının erişebileceği akademilerin listesini güncelle
$allowedAcademies = $associatedAcademies;

$queryUserType = "SELECT type_name FROM user_types WHERE id = :user_type_id";
$stmtUserType = $db->prepare($queryUserType);
$stmtUserType->bindParam(":user_type_id", $user['user_type'], PDO::PARAM_INT);
$stmtUserType->execute();
$userType = $stmtUserType->fetchColumn();


// Header ve sidebar dosyalarını dahil et
require_once "admin_panel_header.php";
require_once "admin_panel_sidebar.php";
?>
<style>
    .notes-box {
        border: 1px solid #ccc;
        padding: 10px;
        border-radius: 5px;
        margin-top: 10px;
    }

    .notes-box label {
        font-weight: bold;
    }

    .notes-box div {
        margin-top: 5px;
    }
</style>


<!-- Ana içerik -->
<main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
        <h2>Kullanıcı Profili</h2>
    </div>


    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group mr-2">
                        <button onclick="history.back()" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Geri dön
                        </button>
                        <a href="users.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-list"></i> Kullanıcı Listesi
                        </a>
                        <a href="edit_user.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-edit"></i> Kullanıcı Düzenle
                        </a>
                        <a href="user_profile.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-user-lock"></i> Şifre Gönder
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <br>
    <div class="row">
        <div class="col-md-6">
            <h3>Kullanıcı Bilgileri</h3>
            <ul>
                <li><strong>No:</strong> <?= $user['id'] ?></li>
                <li><strong>Kullanıcı adı:</strong> <?= $user['username'] ?></li>
                <li><strong>T.C. Kimlik No:</strong> <?= $user['tc_identity'] ?></li>
                <li><strong>Ad:</strong> <?= $user['first_name'] ?></li>
                <li><strong>Soyad:</strong> <?= $user['last_name'] ?></li>
                <li><strong>E-posta:</strong> <?= $user['email'] ?></li>
                <li><strong>Telefon:</strong> <?= $user['phone'] ?></li>
                <li><strong>İl:</strong> <?= $user['city'] ? $user['city'] : 'Henüz belli değil'; ?></li>
                <li><strong>İlçe:</strong> <?= $user['district'] ? $user['district'] : 'Henüz belli değil'; ?></li>
                <?php
                function getCountryName($countryCode) {
                    global $iso3166;

                    // Türkiye'nin alpha2 kodu TR ise "Türkiye" döndür, aksi takdirde ISO3166 kütüphanesinden al
                    return ($countryCode === 'TR') ? 'Türkiye' : $iso3166->alpha2($countryCode)['name'] ?? $countryCode;
                }
                ?>
                <li><strong>Ülke:</strong> <?= ($user['country']) ? getCountryName($user['country']) : 'Henüz belli değil'; ?></li>                <li><strong>Doğum Tarihi:</strong> <?php echo $user['birth_date'] ? date(DATE_FORMAT, strtotime($user['birth_date'])) : 'Belli değil'; ?></li>
                <?php
                // Kullanıcının doğum tarihi
                $birthDate = $user['birth_date'];

                if ($birthDate) {
                    // Bugünün tarihini al
                    $today = new DateTime();

                    // Doğum tarihini DateTime nesnesine dönüştür
                    $birthDateTime = new DateTime($birthDate);

                    // Yaşı hesapla
                    $age = $today->diff($birthDateTime)->y;

                    echo '<li><strong>Yaş:</strong> ' . $age . '</li>';
                } else {
                    echo '<li><strong>Yaş:</strong> Bilgi bulunmuyor</li>';
                }
                ?>
                <li><strong>Kan Grubu:</strong> <?= $user['blood_type'] ? $user['blood_type'] : 'Henüz belli değil'; ?></li>
                <li><strong>Bilinen Sağlık Sorunu:</strong> <?= $user['health_issue'] ? $user['health_issue'] : 'Henüz belli değil'; ?></li>
                <li><strong>Acil Durum Kişisi:</strong> <?= $user['emergency_phone'] ? $user['emergency_phone'] : 'Henüz belli değil'; ?></li>
                <li><strong>SMS Onay Durumu:</strong> <?= $user['verification_time_sms_confirmed'] ? '<i class="fas fa-check text-success"></i> Doğrulandı' : '<i class="fas fa-times text-danger"></i> Doğrulanmadı' ?></li>
                <li><strong>E-posta Onay Durumu:</strong> <?= $user['verification_time_email_confirmed'] ? '<i class="fas fa-check text-success"></i> Doğrulandı' : '<i class="fas fa-times text-danger"></i> Doğrulanmadı' ?></li>
                <li><strong>Fatura Türü:</strong> <?= $user['invoice_type'] == 'individual' ? 'Bireysel' : 'Kurumsal' ?></li>
                <?php if ($user['invoice_type'] == 'individual'): ?>
                    <li><strong>T.C. Kimlik No:</strong> <?= $user['tc_identity'] ?></li>
                <?php elseif ($user['invoice_type'] == 'corporate'): ?>
                    <li><strong>Şirket Ünvanı:</strong> <?= $user['tax_company_name'] ?></li>
                    <li><strong>Vergi Dairesi:</strong> <?= $user['tax_office'] ?></li>
                    <li><strong>Vergi Numarası:</strong> <?= $user['tax_number'] ?></li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="col-md-6">
            <h3>Diğer Bilgiler</h3>
            <ul>
                <li><strong>Kullanıcı Türü:</strong> <?= $userType ?></li>
                <li><strong>Oluşturan:</strong> <?= $user['created_by_name'] ?></li>
                <li><strong>Oluşturulma:</strong> <?= $user['created_at'] ? date(DATETIME_FORMAT, strtotime($user['created_at'])) : 'Henüz belli değil'; ?></li>
                <li><strong>Güncelleyen:</strong> <?= $user['updated_by_name'] ?></li>
                <li><strong>Güncellenme:</strong> <?= $user['updated_at'] ? date(DATETIME_FORMAT, strtotime($user['updated_at'])) : 'Henüz belli değil'; ?></li>
                <li><strong>Silen:</strong> <?= $user['deleted_by_name'] ?></li>
                <li><strong>Silinme:</strong> <?= $user['deleted_at'] ? date(DATETIME_FORMAT, strtotime($user['deleted_at'])) : 'Henüz belli değil'; ?></li>
                <li><strong>Oluşturulma Tarihi:</strong> <?= $user['created_at'] ? date(DATETIME_FORMAT, strtotime($user['created_at'])) : 'Henüz belli değil'; ?></li>
                <li><strong>SMS Gönderilme Zamanı:</strong> <?php echo $user['verification_time_sms_sent'] ? date(DATETIME_FORMAT, strtotime($user['verification_time_sms_sent'])) : 'Henüz belli değil'; ?></li>
                <li><strong>SMS Onay Zamanı:</strong> <?php echo $user['verification_time_sms_confirmed'] ? date(DATETIME_FORMAT, strtotime($user['verification_time_sms_confirmed'])) : 'Henüz belli değil'; ?></li>
                <li><strong>SMS Onay IP:</strong> <?= $user['verification_ip_sms'] ? $user['verification_ip_sms'] : 'Henüz belli değil'; ?></li>
                <li><strong>E-posta Gönderilme Zamanı:</strong> <?php echo $user['verification_time_email_sent'] ? date(DATETIME_FORMAT, strtotime($user['verification_time_email_sent'])) : 'Henüz belli değil'; ?></li>
                <li><strong>E-posta Onay Zamanı:</strong> <?php echo $user['verification_time_email_confirmed'] ? date(DATETIME_FORMAT, strtotime($user['verification_time_email_confirmed'])) : 'Henüz belli değil'; ?></li>
                <li><strong>E-posta Onay IP:</strong> <?= $user['verification_ip_email'] ? $user['verification_ip_email'] : 'Henüz belli değil'; ?></li>
            </ul>
            <div class="notes-box">
                <label for="notes">Notlar:</label>
                <div><?php echo $user['notes']; ?></div>
            </div>
            </ul>
        </div>

        <?php
        // Kullanıcı türüne göre içeriği belirle
        if ($user['user_type'] == 4 || $user['user_type'] == 6) {
            if ($user_id !== null) {
                $query = "SELECT
            CONCAT(u_teacher.first_name, ' ', u_teacher.last_name) AS teacher_name,
            a.name AS academy_name,
            ac.class_name AS class_name,
            CONCAT(u_student.first_name, ' ', u_student.last_name) AS student_name,
            c.course_name AS lesson_name,
            sc.course_date_1,
            sc.course_date_2,
            sc.course_date_3,
            sc.course_date_4,
            sc.course_attendance_1,
            sc.course_attendance_2,
            sc.course_attendance_3,
            sc.course_attendance_4,
            sc.course_fee,
            sc.debt_amount,
            sc.id AS course_plan_id,
            sc.created_at,
            sc.updated_at,
            CONCAT(u_created_by.first_name, ' ', u_created_by.last_name) AS created_by_name,
            CONCAT(u_updated_by.first_name, ' ', u_updated_by.last_name) AS updated_by_name
        FROM
            course_plans sc
            INNER JOIN users u_teacher ON sc.teacher_id = u_teacher.id
            INNER JOIN academies a ON sc.academy_id = a.id AND a.id IN (" . implode(",", $allowedAcademies) . ")
            INNER JOIN academy_classes ac ON sc.class_id = ac.id
            INNER JOIN users u_student ON sc.student_id = u_student.id
            INNER JOIN courses c ON sc.course_id = c.id
            LEFT JOIN users u_created_by ON sc.created_by_user_id = u_created_by.id
            LEFT JOIN users u_updated_by ON sc.updated_by_user_id = u_updated_by.id
        WHERE
            u_student.id = :user_id OR u_teacher.id = :user_id";

                $stmt = $db->prepare($query);
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->execute();

                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Display active and completed cards in separate rows
                echo '<div class="row">';
                echo '<div class="col-md-6"><h3>Aktif Dersler</h3>';
                foreach ($results as $result) {
                    // Check if the last class date is more than 1 day ago
                    if (strtotime($result['course_date_4']) < strtotime('-1 day')) {
                        continue; // Skip to next iteration if the course is completed
                    }

                    displayCourseCard($result);
                }
                echo '</div>';

                echo '<div class="col-md-6"><h3>Arşivlenen Dersler</h3>';
                foreach ($results as $result) {
                    // Check if the last class date is not more than 1 day ago
                    if (strtotime($result['course_date_4']) >= strtotime('-1 day')) {
                        continue; // Skip to next iteration if the course is not completed
                    }

                    displayCourseCard($result);
                }
                echo '</div>';
                echo '</div>';
            }
        }

        function displayCourseCard($result)
        {
            // Kartın border rengini belirle
            global $user;
            if ($result['debt_amount'] == 0 && $result['course_attendance_1'] && $result['course_attendance_2'] && $result['course_attendance_3'] && $result['course_attendance_4']) {
                // 4 derse katıldı ve borcu yoksa: Yeşil
                $cardBorderStyle = 'border-success';
            } elseif ($result['debt_amount'] > 0) {
                // Borcu varsa: Kırmızı
                $cardBorderStyle = 'border-danger';
            } elseif (!$result['course_attendance_1'] || !$result['course_attendance_2'] || !$result['course_attendance_3'] || !$result['course_attendance_4']) {
                // 4 derse katılmamış veya borcu yoksa: Mavi
                $cardBorderStyle = 'border-primary';
            } else {
                // Diğer durumlar için: Gri
                $cardBorderStyle = 'border-gray';
            }

            echo '
    <div class="col-md-6 mb-5 mt-5">
        <div class="card ' . $cardBorderStyle . '">
            <div class="card-header">
                <h6 class="card-title"><strong>' . ($user['user_type'] == 4 ? $result['student_name'] : $result['teacher_name']) . ' ile ' . $result['lesson_name'] . '</strong></h6>
            </div>
            <div class="card-body">
                <p class="card-text">Akademi: ' . $result['academy_name'] . '</p>
                <p class="card-text">Sınıf: ' . $result['class_name'] . '</p>
                <p class="card-text">1. Ders: ' . date("d.m.Y H:i", strtotime($result['course_date_1'])) . '</p>
                <p class="card-text">2. Ders: ' . date("d.m.Y H:i", strtotime($result['course_date_2'])) . '</p>
                <p class="card-text">3. Ders: ' . date("d.m.Y H:i", strtotime($result['course_date_3'])) . '</p>
                <p class="card-text">4. Ders: ' . date("d.m.Y H:i", strtotime($result['course_date_4'])) . '</p>';

            for ($i = 1; $i <= 4; $i++) {
                echo "<p class='card-text'>{$i}. Katılım: ";

                if ($result["course_attendance_$i"] == 0) {
                    echo "<i class='fas fa-calendar-check text-primary'></i>";
                } elseif ($result["course_attendance_$i"] == 1) {
                    echo "<i class='fas fa-calendar-check text-success'></i>";
                } elseif ($result["course_attendance_$i"] == 2) {
                    echo "<i class='fas fa-calendar-check text-danger'></i>";
                } elseif ($result["course_attendance_$i"] == 3) {
                    // Change the icon or style for Yeniden Planla if there is a corresponding entry
                    echo "<i class='fas fa-calendar-check text-success'></i>";
                } else {
                    echo "<i class='fas fa-question text-secondary'></i>";
                }

                echo "</p>";
            }

            echo '
                <p class="card-text">Ders Ücreti: ' . $result['course_fee'] . ' TL</p>
                <p class="card-text">Borç: ' . $result['debt_amount'] . ' TL</p>
                <p class="card-text small">Oluşturan: ' . $result['created_by_name'] . '</p>
                <p class="card-text small">Oluşturulma: ' . date("d.m.Y H:i", strtotime($result['created_at'])) . '</p>
                <p class="card-text small">Güncelleyen: ' . $result['updated_by_name'] . '</p>
                <p class="card-text small">Güncellenme: ' . date("d.m.Y H:i", strtotime($result['updated_at'])) . '</p>
                <a href="edit_course_plan.php?id=' . $result['course_plan_id'] . '" class="btn btn-danger btn-sm"><i class="fas fa-pencil-alt"></i></a>
                <a href="add_payment.php?id=' . $result['course_plan_id'] . '" class="btn btn-success btn-sm"><i class="fas fa-cash-register"></i> ₺</a>
                <a href="generate_invoice_request.php?course_plan_id=' . $result['course_plan_id'] . '" class="btn btn-success btn-sm"><i class="fas fa-file-invoice"></i></a>
                <a href="student_certificate.php?student_id=' . $result['course_plan_id'] . '" class="btn btn-success btn-sm"><i class="fas fa-graduation-cap"></i></a>
            </div>
        </div>
    </div>';
        }
        ?>





    </div>
</main>

<?php require_once "footer.php"; ?>
