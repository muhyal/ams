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
session_start();
session_regenerate_id(true);

// Kullanıcı girişi kontrolü
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php");
    exit();
}

require_once "db_connection.php";

// URL parametrelerinden kullanıcı ID'sini al
if (isset($_GET["id"])) {
    $user_id = $_GET["id"];

    // Veritabanından kullanıcı detaylarını çek
    $query = "SELECT * FROM users WHERE id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    // Kullanıcı ID sağlanmadıysa yönlendir
    header("Location: user_list.php");
    exit();
}

// Header ve sidebar dosyalarını dahil et
require_once "admin_panel_header.php";
require_once "admin_panel_sidebar.php";
?>

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
                        <a href="user_list.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-list"></i> Kullanıcı Listesi
                        </a>
                        <a href="edit_user.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-edit"></i> Kullanıcı Düzenle
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
                <li><strong>ID:</strong> <?= $user['id'] ?></li>
                <li><strong>Ad:</strong> <?= $user['firstname'] ?></li>
                <li><strong>Soyad:</strong> <?= $user['lastname'] ?></li>
                <li><strong>E-posta:</strong> <?= $user['email'] ?></li>
                <li><strong>T.C. Kimlik No:</strong> <?= $user['tc'] ?></li>
                <li><strong>Telefon:</strong> <?= $user['phone'] ?></li>
                <li><strong>SMS Gönderilme Zamanı:</strong> <?= $user['verification_time_sms_sent'] ?></li>
                <li><strong>SMS Onay Zamanı:</strong> <?= $user['verification_time_sms_confirmed'] ?></li>
                <li><strong>SMS Onay Durumu:</strong> <?= $user['verification_time_sms_confirmed'] ? '<i class="fas fa-check text-success"></i> Doğrulandı' : '<i class="fas fa-times text-danger"></i> Doğrulanmadı' ?></li>
                <li><strong>SMS Onay IP:</strong> <?= $user['verification_ip_sms'] ?></li>
                <li><strong>E-posta Gönderilme Zamanı:</strong> <?= $user['verification_time_email_sent'] ?></li>
                <li><strong>E-posta Onay Zamanı:</strong> <?= $user['verification_time_email_confirmed'] ?></li>
                <li><strong>E-posta Onay Durumu:</strong> <?= $user['verification_time_email_confirmed'] ? '<i class="fas fa-check text-success"></i> Doğrulandı' : '<i class="fas fa-times text-danger"></i> Doğrulanmadı' ?></li>
                <li><strong>E-posta Onay IP:</strong> <?= $user['verification_ip_email'] ?></li>
            </ul>
        </div>

        <div class="col-md-6">
            <h3>Diğer Bilgiler</h3>
            <ul>
                <!-- Diğer bilgileri buraya ekleyin -->
            </ul>
        </div>
    </div>
</main>

<?php require_once "footer.php"; ?>
