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

// $userType parametresini al
$userType = isset($_GET['type']) ? (int)$_GET['type'] : 0;

// Veritabanından sözleşme metnini çekme
$userTypeKey = 'user_agreement_' . $userType;
$query = "SELECT option_value FROM options WHERE option_name = :userTypeKey LIMIT 1";
$stmt = $db->prepare($query);
$stmt->bindParam(':userTypeKey', $userTypeKey);
$stmt->execute();
$userTypeText = $stmt->fetchColumn();

// Eğer sözleşme metni bulunamazsa varsayılan bir mesaj ayarla
if (!$userTypeText) {
    $userTypeText = "Bilinmeyen kullanıcı sözleşmesi metni.";
} else {
    $userTypeText = htmlspecialchars_decode($userTypeText, ENT_QUOTES);
}


$verificationTime = null;
$verificationIP = null;

function updateVerificationStatus($db, $verificationId, $verificationTimeColumn, $verificationIPColumn, $signatureColumn = null, $signatureData = null)
{
    $verificationTime = date('Y-m-d H:i:s');
    $verificationIP = $_SERVER['REMOTE_ADDR'];

    // Güncelleme sorgusu
    $updateQuery = "UPDATE verifications SET $verificationTimeColumn = :verificationTime, $verificationIPColumn = :verificationIP";
    $params = [
        ':verificationTime' => $verificationTime,
        ':verificationIP' => $verificationIP,
        ':verificationId' => $verificationId
    ];

    // Eğer imza verisi varsa, sorguya ekleyin
    if ($signatureColumn && $signatureData) {
        $updateQuery .= ", $signatureColumn = :signatureData";
        $params[':signatureData'] = $signatureData;
    }

    $updateQuery .= " WHERE id = :verificationId";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->execute($params);

    echo '<div class="alert alert-success" role="alert">Doğrulama başarılı! IP adresiniz ' . $verificationIP . ' olarak kaydedildi. Bu sayfayı kapatabilirsiniz.</div>';
}

$isUserVerified = false;

if ((!isset($_GET['email']) && !isset($_GET['phone'])) || !isset($_GET['code'])) {
    echo '<div class="alert alert-danger" role="alert">Geçersiz doğrulama bağlantısı! Lütfen size gönderilen doğru doğrulama bağlantısını kullanın.</div>';
} else {
    $code = $_GET['code'];
    if (isset($_GET['email'])) {
        $email = $_GET['email'];
        // Email doğrulama sütunları
        $verificationTimeColumn = 'verification_time_email_confirmed';
        $verificationIPColumn = 'verification_ip_email';
        $verificationCodeColumn = 'verification_code_email';
        $signatureColumn = 'verification_signature_email';

        // Veritabanından veri çekme işlemleri
        $query = "SELECT * FROM verifications WHERE email = ? ORDER BY id DESC LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute([$email]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($userData) {
            $verificationId = $userData['id'];

            if ($userData[$verificationCodeColumn] == $code) {
                if ($userData[$verificationTimeColumn] !== null) {
                    echo '<div class="alert alert-info" role="alert">Bu e-posta zaten doğrulanmış, tekrar doğrulama yapılamaz.</div>';
                } else {
                    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['signatureData']) && isset($_POST['confirmation']) && $_POST['confirmation'] == 'on') {
                        $signatureData = $_POST['signatureData'];
                        updateVerificationStatus($db, $verificationId, $verificationTimeColumn, $verificationIPColumn, $signatureColumn, $signatureData);
                        echo '<div class="alert alert-success" role="alert">E-posta doğrulaması başarılı.</div>';
                    } else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                        echo '<div class="alert alert-warning" role="alert">E-posta doğrulaması için imza eksik veya onaylanmadı.</div>';
                    }
                }
            } else {
                echo '<div class="alert alert-danger" role="alert">Geçersiz doğrulama kodu.</div>';
            }
        } else {
            echo '<div class="alert alert-danger" role="alert">Geçersiz e-posta bilgisi.</div>';
        }
    } elseif (isset($_GET['phone'])) {
        $phone = $_GET['phone'];
        // SMS doğrulama sütunları
        $verificationTimeColumn = 'verification_time_sms_confirmed';
        $verificationIPColumn = 'verification_ip_sms';
        $verificationCodeColumn = 'verification_code_sms';
        $signatureColumn = 'verification_signature_sms';

        $query = "SELECT * FROM verifications WHERE phone = ? ORDER BY id DESC LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute([$phone]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($userData) {
            $verificationId = $userData['id'];

            if ($userData[$verificationCodeColumn] == $code) {
                if ($userData[$verificationTimeColumn] !== null) {
                    echo '<div class="alert alert-info" role="alert">Bu telefon numarası zaten doğrulanmış, tekrar doğrulama yapılamaz.</div>';
                } else {
                    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['signatureData']) && isset($_POST['confirmation']) && $_POST['confirmation'] == 'on') {
                        $signatureData = $_POST['signatureData'];
                        updateVerificationStatus($db, $verificationId, $verificationTimeColumn, $verificationIPColumn, $signatureColumn, $signatureData);
                        echo '<div class="alert alert-success" role="alert">SMS doğrulaması başarılı.</div>';
                    } else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                        echo '<div class="alert alert-warning" role="alert">SMS doğrulaması için imza eksik veya onaylanmadı.</div>';
                    }
                }
            } else {
                echo '<div class="alert alert-danger" role="alert">Geçersiz doğrulama kodu.</div>';
            }
        } else {
            echo '<div class="alert alert-danger" role="alert">Geçersiz telefon bilgisi.</div>';
        }
    } else {
        echo '<div class="alert alert-danger" role="alert">Geçersiz doğrulama bağlantısı.</div>';
    }
}


?>
<!-- SignaturePad kütüphanesi CDN üzerinden eklendi -->
<script src="https://unpkg.com/signature_pad"></script>

<div class="col-lg-6 mx-auto">
    <p class="h1 text-center mt-3 mb-3">Sözleşme</p>
    <p class="lead mt-3 mb-3"><?php echo $userTypeText; ?></p>
    <p class="mt-3 mb-3"><?php echo htmlspecialchars($siteVerifyDescription, ENT_QUOTES, 'UTF-8'); ?></p>
</div>




<div class="px-4 py-5 my-5 text-center">
    <div class="col-lg-6 mx-auto">
        <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
            <?php
            if (isset($_GET['email']) || isset($_GET['phone'])) {
                $userField = isset($_GET['phone']) ? 'phone' : 'email';
                $verificationTimeColumn = isset($_GET['phone']) ? 'verification_time_sms_confirmed' : 'verification_time_email_confirmed';
                $verificationIPColumn = isset($_GET['phone']) ? 'verification_ip_sms' : 'verification_ip_email';
                $verificationCodeColumn = isset($_GET['phone']) ? 'verification_code_sms' : 'verification_code_email';
                $signatureColumn = isset($_GET['phone']) ? 'verification_signature_sms' : 'verification_signature_email';

                $verificationId = isset($_GET['verification_id']) ? (int)$_GET['verification_id'] : 0;

                if ($verificationId > 0) {
                    // Doğru alanın değerini al
                    $userValue = isset($_GET['phone']) ? $_GET['phone'] : $_GET['email'];

                    // Fetch data based on verification_id
                    $query = "SELECT * FROM verifications WHERE $userField = ? ORDER BY created_at DESC LIMIT 1";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$userValue]);
                    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($userData) {
                        if ($userData[$verificationTimeColumn] !== null) {
                            $isUserVerified = true;
                            echo '<div class="alert alert-info" role="alert">Bu kullanıcı zaten doğrulanmış, tekrar doğrulama işlemi yapılamaz.</div>';
                        } else {
                            // Doğrulanmamışsa ve gelen POST verisi varsa işlemleri gerçekleştir
                            if (isset($_POST['signatureData']) && isset($_POST['confirmation']) && $_POST['confirmation'] == 'on') {
                                // Diğer işlemleri gerçekleştir
                                $verificationCode = $_GET['code'];
                                $signatureData = $_POST['signatureData'];
                                $verificationTime = date('Y-m-d H:i:s');
                                $verificationIP = $_SERVER['REMOTE_ADDR'];

                                // İmza verisini burada kaydedin
                                $updateQuery = "UPDATE verifications 
                                    SET $verificationTimeColumn = ?, 
                                        $verificationIPColumn = ?, 
                                        $signatureColumn = ? 
                                    WHERE id = ?";
                                $updateStmt = $db->prepare($updateQuery);
                                $updateStmt->execute([$verificationTime, $verificationIP, $signatureData, $verificationId]);

                                echo '<div class="alert alert-success" role="alert">Doğrulama başarılı!</div>';
                            } else {
                                echo '<div class="alert alert-warning" role="alert">İmza eksik veya onaylanmadı.</div>';
                            }
                        }
                    } else {
                        echo '<div class="alert alert-danger" role="alert">Geçersiz doğrulama bağlantısı.</div>';
                    }
                } else {
                    echo '<div class="alert alert-warning" role="alert">Lütfen dijital imzanızı atın ve onay kutucuğunu işaretleyin.</div>';
                }
            } else {
                echo '<div class="alert alert-warning" role="alert">Lütfen dijital imzanızı atın ve onay kutucuğunu işaretleyin.</div>';
            }
            ?>


        </div>
        <div>
            <?php if (!$isUserVerified) : ?>
                <!-- İmza Alanı -->
                <div class="signature-container">
                    <canvas id="signatureCanvas" width="400" height="200" style="border:1px solid #000;"></canvas>
                    <div class="signature-text">Dijital İmzanızı Buraya Atabilirsiniz</div>
                </div>

                <!-- Onay Seçim Kutusu -->
                <div class="form-group" style="background-color: #ffedbb; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                    <div class="form-check form-check-inline">
                        <input type="checkbox" class="form-check-input" id="confirmation">
                        <label class="form-check-label" for="confirmation">Kişisel verilerimin korunması kanunu kapsamında ilgili sözleşmeleri okudum ve onayladım</label>
                    </div>
                </div>

                <!-- İmza Temizle ve Gönder Butonları -->
                <div class="form-group">
                    <button type="button" class="btn btn-primary" onclick="clearSignature()">İmzayı Temizle</button>
                    <button type="button" class="btn btn-primary" onclick="sendSignature()">İmzala & Onayla</button>
                </div>
            <?php endif; ?>

            <!-- İmza Temizleme ve İmza Yazısı Stil -->
            <style>
                .signature-container {
                    position: relative;
                    margin-bottom: 20px;
                }

                .signature-text {
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    font-size: 24px;
                    font-weight: lighter;
                    color: #333;
                }
            </style>
        </div>
    </div>
</div>

<script>
    // İmza alanını oluştur
    var canvas = document.getElementById('signatureCanvas');
    var signaturePad = new SignaturePad(canvas);

    function clearSignature() {
        signaturePad.clear();
    }

    function sendSignature() {
        var signatureData = signaturePad.toDataURL();
        var confirmation = document.getElementById('confirmation').checked;

        // Check if the signature data is empty (no drawing) or confirmation is not checked
        if (signaturePad.isEmpty() || !confirmation) {
            alert("İmza eksik ya da onay kutucuğu seçilmedi. Lütfen hem imzanızı tamamlayıp hem de onay kutucuğunu seçin.");
            return;
        }

        // Formu doldur ve gönder
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = window.location.href;

        var signatureInput = document.createElement('input');
        signatureInput.type = 'hidden';
        signatureInput.name = 'signatureData';
        signatureInput.value = signatureData;
        form.appendChild(signatureInput);

        var confirmationInput = document.createElement('input');
        confirmationInput.type = 'hidden';
        confirmationInput.name = 'confirmation';
        confirmationInput.value = confirmation ? 'on' : 'off';
        form.appendChild(confirmationInput);

        document.body.appendChild(form);
        form.submit();
    }
</script>

<?php require_once(__DIR__ . '/user/partials/footer.php'); ?>
