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

    $identifier = $_POST["identifier"]; // Kullanıcı adı veya E-posta
    $password = $_POST["password"];

    // Form alanlarının doğrulaması
    if (empty($identifier) || empty($password)) {
        die("Eksik giriş bilgileri.");
    }

    // Check if the identifier is a valid email format
    if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
        $query = "SELECT * FROM users WHERE email = ?";
    } else {
        $query = "SELECT * FROM users WHERE username = ?";
    }

    try {
        $stmt = $db->prepare($query);
        $stmt->execute([$identifier]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user["password"])) {
            // Kullanıcı girişi başarılı, oturum başlat
            $_SESSION["user_id"] = $user["id"];
            header("Location: user_panel.php"); // Kullanıcı paneline yönlendirme
            exit();
        } else {
            echo '<div class="alert alert-danger" role="alert">
                Hatalı e-posta adresi ya da şifre girdiniz.
            </div>';
        }
    } catch (PDOException $e) {
        echo "Hata: " . $e->getMessage();
    }
    // Veritabanı bağlantısını kapat
    $db = null;
}
?>
