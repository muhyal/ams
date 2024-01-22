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
global $db, $siteVerifyDescription, $showErrors, $siteName, $siteShortName, $siteUrl, $config;
// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);
require_once(__DIR__ . '/config/db_connection.php');
require_once(__DIR__ . '/config/config.php');
require_once(__DIR__ . '/user/partials/header.php');
?>

<div class="px-4 py-5 my-5 text-center">
    <div class="col-lg-6 mx-auto">
        <p class="lead mb-4"><?php echo htmlspecialchars($siteVerifyDescription, ENT_QUOTES, 'UTF-8'); ?></p>
        <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
            <?php
            if ((isset($_GET['email']) || isset($_GET['phone'])) && isset($_GET['code'])) {
                $userSmsData = null;
                $userEmailData = null;
                $verificationCode = htmlspecialchars($_GET['code'], ENT_QUOTES, 'UTF-8');

                if (isset($_GET['phone'])) {
                    $userEmail = htmlspecialchars($_GET['phone'], ENT_QUOTES, 'UTF-8');
                    // SMS doğrulaması için sorgu
                    $querySms = "SELECT * FROM users WHERE phone = ? AND verification_code_sms = ?";
                    $stmtSms = $db->prepare($querySms);
                    $stmtSms->execute([$userEmail, $verificationCode]);
                    $userSmsData = $stmtSms->fetch(PDO::FETCH_ASSOC);
                } else {
                    $userEmail = htmlspecialchars($_GET['email'], ENT_QUOTES, 'UTF-8');
                    // E-posta doğrulaması için sorgu
                    $queryEmail = "SELECT * FROM users WHERE email = ? AND verification_code_email = ?";
                    $stmtEmail = $db->prepare($queryEmail);
                    $stmtEmail->execute([$userEmail, $verificationCode]);
                    $userEmailData = $stmtEmail->fetch(PDO::FETCH_ASSOC);
                }

                if ($userEmailData !== null) {
                    $verificationTime = date('Y-m-d H:i:s'); // Şu anki zamanı al
                    $verificationIP = $_SERVER['REMOTE_ADDR'];

                    // E-posta doğrulaması başarılı
                    if ($userEmailData['verification_code_email'] == $verificationCode) {
                        $updateQuery = "UPDATE users SET verification_time_email_confirmed = ?, verification_ip_email = ? WHERE email = ?";
                        $updateStmt = $db->prepare($updateQuery);
                        $updateStmt->execute([$verificationTime, $verificationIP, $userEmail]);
                        echo "E-posta doğrulaması başarılı! IP adresiniz " . htmlspecialchars($verificationIP, ENT_QUOTES, 'UTF-8') . " olarak kaydedildi. Bu sayfayı kapatabilirsiniz.";
                    }
                } elseif ($userSmsData !== null) {
                    $verificationTime = date('Y-m-d H:i:s'); // Şu anki zamanı al
                    $verificationIP = $_SERVER['REMOTE_ADDR'];

                    // SMS doğrulaması başarılı
                    if ($userSmsData['verification_code_sms'] == $verificationCode) {
                        $updateQuery = "UPDATE users SET verification_time_sms_confirmed = ?, verification_ip_sms = ? WHERE phone = ?";
                        $updateStmt = $db->prepare($updateQuery);
                        $updateStmt->execute([$verificationTime, $verificationIP, $userSmsData['phone']]);
                        echo "SMS doğrulaması başarılı! IP adresiniz " . htmlspecialchars($verificationIP, ENT_QUOTES, 'UTF-8') . " olarak kaydedildi. Bu sayfayı kapatabilirsiniz.";
                    }
                } else {
                    // Her iki yöntemle de doğrulama başarısız
                    echo "Geçersiz doğrulama bağlantısı.";
                }
            } else {
                echo "Geçersiz doğrulama bağlantısı.";
            }

            // Aynı sayfa üzerinden tıklanabilir linkler gösterilirse kullanıcıya daha iyi bir deneyim sunabilirsiniz.
            if (isset($userEmailData)) {
                echo '<br><a href="verify.php?email=' . htmlspecialchars($userEmail, ENT_QUOTES, 'UTF-8') . '&code=' . htmlspecialchars($verificationCode, ENT_QUOTES, 'UTF-8') . '">E-posta doğrulamasını tekrarla (Opsiyonel)</a>';
            }
            if (isset($userSmsData)) {
                echo '<br><a href="verify.php?phone=' . htmlspecialchars($userSmsData['phone'], ENT_QUOTES, 'UTF-8') . '&code=' . htmlspecialchars($verificationCode, ENT_QUOTES, 'UTF-8') . '">SMS doğrulamasını tekrarla (Opsiyonel)</a>';
            }
            ?>
        </div>
    </div>
</div>

<?php require_once(__DIR__ . '/user/partials/footer.php'); ?>
