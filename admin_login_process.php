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
global $db, $showErrors;
// Oturum kontrolü
session_start();
session_regenerate_id(true);

require_once "config.php";
require 'vendor/autoload.php';

use Infobip\Api\SmsApi;
use Infobip\Configuration;
use Infobip\Model\SmsAdvancedTextualRequest;
use Infobip\Model\SmsDestination;
use Infobip\Model\SmsTextualMessage;

require_once "db_connection.php";

// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF token kontrolü
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        die("CSRF hatası! İşlem reddedildi.");
    }

    $identifier = $_POST["identifier"];
    $password = $_POST["password"];

    // Form alanlarının doğrulaması
    if (empty($identifier) || empty($password)) {
        die("Eksik giriş bilgileri.");
    }

    // Check if the identifier is a valid email format
    $column = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
    $query = "SELECT * FROM users WHERE $column = ?";

    try {
        $stmt = $db->prepare($query);
        $stmt->execute([$identifier]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password, $admin["password"])) {

            $allowedUserTypes = [1, 2, 3];
            if (in_array($admin["user_type"], $allowedUserTypes)) {
                // Send an SMS using Infobip
                global $config, $BASE_URL, $API_KEY, $username, $SENDER, $MESSAGE_TEXT, $siteName, $siteShortName;

                $phone = $admin["phone"]; // Assuming you have a phone number in your admins table

                $smsConfiguration = new Configuration(host: $BASE_URL, apiKey: $API_KEY);

                $sendSmsApi = new SmsApi(config: $smsConfiguration);

                $destination = new SmsDestination(
                    to: $phone
                );

                $message = new SmsTextualMessage(destinations: [$destination]);

                $message = new SmsTextualMessage(destinations: [$destination], from: $SENDER, text: "Merhaba $username, $siteName - $siteShortName üzerinde yönetici oturumu açıldı. Bilginiz dışında ise lütfen kontrol ediniz.");

                $request = new SmsAdvancedTextualRequest(messages: [$message]);

                try {
                    $smsResponse = $sendSmsApi->sendSmsMessage($request);

                    // Handle the response, log or display relevant information
                    if ($smsResponse->getMessages()[0]->getStatus()->getGroupName() === 'PENDING') {
                        echo 'SMS gönderim bekliyor.';
                    } else {
                        echo 'SMS başarıyla gönderildi.';
                    }
                } catch (\Throwable $exception) {
                    echo 'SMS gönderimi başarısız. Hata: ' . $exception->getMessage();
                }

                $_SESSION["admin_id"] = $admin["id"];
                $_SESSION["admin_username"] = $admin["username"];
                $_SESSION["admin_first_name"] = $admin["first_name"];
                $_SESSION["admin_last_name"] = $admin["last_name"];
                $_SESSION["admin_type"] = $admin["user_type"];
                header("Location: admin_panel.php");
                exit();
            } else {
                echo "Bu alana giriş yapma yetkiniz yok!";
            }
        } else {
            echo "Hatalı giriş bilgileri.";
        }
    } catch (PDOException $e) {
        echo "Hata: " . $e->getMessage();
    } finally {
        // Veritabanı bağlantısını kapat
        $db = null;
    }
}
?>
