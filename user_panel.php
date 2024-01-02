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
global $db, $showErrors, $siteName, $siteShortName, $siteUrl, $config;
// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);
require_once "config.php";

session_start();
session_regenerate_id(true);

require_once "db_connection.php"; // Veritabanı bağlantısı

// Oturum kontrolü yaparak giriş yapılmış mı diye kontrol ediyoruz
if (!isset($_SESSION["user_id"])) {
    header("Location: user_login.php"); // Kullanıcı giriş sayfasına yönlendirme
    exit();
}

// Kullanıcının bilgilerini veritabanından alıyoruz
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION["user_id"]]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) { // Kullanıcı bilgileri doğru şekilde alındı mı kontrol ediyoruz
    require_once "header.php";
?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title text-center mb-4">Hoş Geldiniz, <?php echo $user["firstname"] . " " . $user["lastname"]; ?>!</h4>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">Ad Soyad: <?php echo $user["firstname"] . " " . $user["lastname"]; ?></li>
                            <li class="list-group-item">E-posta: <?php echo $user["email"]; ?></li>
                            <li class="list-group-item">E-posta: <?= $user['email'] ?></li>
                            <li class="list-group-item">T.C. Kimlik No: <?= $user['tc'] ?></li>
                            <li class="list-group-item">Telefon: <?= $user['phone'] ?></li>
                            <li class="list-group-item">SMS Gönderilme Zamanı: <?= $user['verification_time_sms_sent'] ?></li>
                            <li class="list-group-item">SMS Onay Zamanı: <?= $user['verification_time_sms_confirmed'] ?></li>
                            <li class="list-group-item">SMS Onay Durumu: <?= $user['verification_time_sms_confirmed'] ? '<i class="fas fa-check text-success"></i> Doğrulandı' : '<i class="fas fa-times text-danger"></i> Doğrulanmadı' ?></li>
                            <li class="list-group-item">SMS Onay IP: <?= $user['verification_ip_sms'] ?></li>
                            <li class="list-group-item">E-posta Gönderilme Zamanı: <?= $user['verification_time_email_sent'] ?></li>
                            <li class="list-group-item">E-posta Onay Zamanı: <?= $user['verification_time_email_confirmed'] ?></li>
                            <li class="list-group-item">E-posta Onay Durumu: <?= $user['verification_time_email_confirmed'] ? '<i class="fas fa-check text-success"></i> Doğrulandı' : '<i class="fas fa-times text-danger"></i> Doğrulanmadı' ?></li>
                            <li class="list-group-item">E-posta Onay IP: <?= $user['verification_ip_email'] ?></li>
                        </ul>
                        <div class="text-center mt-4">
                            <a href="user_profile_edit.php" class="btn btn-primary mr-2">
                                <i class="fas fa-user-edit"></i> Bilgileri güncelle
                            </a>
                            <a href="logout.php" class="btn btn-danger">
                                <i class="fas fa-sign-out-alt"></i> Oturumu kapat
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php
} else {
    echo "Kullanıcı bilgileri alınamadı.";
}
require_once "footer.php";
?>
