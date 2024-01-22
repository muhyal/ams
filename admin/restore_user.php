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
global $db;
// Oturum kontrolü
session_start();
session_regenerate_id(true);

if (!isset($_SESSION["admin_id"])) {
    header("Location: index.php"); // Giriş sayfasına yönlendir
    exit();
}

require_once(__DIR__ . '/../config/db_connection.php');

if (isset($_GET["id"]) && is_numeric($_GET["id"])) {
    $user_id = $_GET["id"];

    // Silinmiş kullanıcıyı geri al
    $query = "UPDATE users SET deleted_at = NULL WHERE id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        // Başarıyla geri alındıysa kullanıcıları listeleme sayfasına yönlendir
        header("Location: users.php");
        exit();
    } else {
        // Hata durumunda kullanıcıyı listeleme sayfasına yönlendir
        echo "Kullanıcı geri alınamadı.";
        header("Location: users.php");
        exit();
    }
} else {
    // Geçersiz veya eksik kullanıcı kimliği durumunda kullanıcıları listeleme sayfasına yönlendir
    header("Location: users.php");
    exit();
}
?>
