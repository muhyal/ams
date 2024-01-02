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
if (!isset($_SESSION["user_id"])) {
    header("Location: user_login.php");
    exit();
}

require_once "db_connection.php";

// Kullanıcı ID'sini al
$user_id = $_SESSION["user_id"];

// Veritabanından kullanıcı detaylarını çek
$query = "SELECT * FROM users WHERE id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Header ve sidebar dosyalarını dahil et
require_once "header.php"; // Header dosyanızın adını ve konumunu güncelleyin
?>

<!-- Ana içerik -->


    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card">
                    <div class="container-fluid">
                        <br>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="btn-toolbar mb-2 mb-md-0">
                                    <div class="btn-group mr-2">
                                        <button onclick="history.back()" class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-arrow-left"></i> Geri dön
                                        </button>
                                        <a href="user_panel.php" class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-list"></i> Kullanıcı Paneli
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="card-body">

                        <h4 class="card-title text-center mb-4">Bilgileri Güncelle</h4>

                        <form action="update_user_profile.php" method="post">
                    <!-- Form alanlarını buraya ekleyin, örneğin: -->

                            <div class="form-group mt-3">
                        <label for="tc">T.C. Kimlik No:</label>
                        <input type="text" class="form-control" name="tc" value="<?= $user['tc'] ?>" readonly style="background-color: #f8f9fa; cursor: not-allowed;">
                    </div>

                            <div class="form-group mt-3">
                        <label for="firstname">Ad:</label>
                        <input type="text" class="form-control" name="firstname" value="<?= $user['firstname'] ?>" required>
                    </div>

                            <div class="form-group mt-3">
                        <label for="lastname">Soyad:</label>
                        <input type="text" class="form-control" name="lastname" value="<?= $user['lastname'] ?>" required>
                    </div>

                            <div class="form-group mt-3">
                        <label for="email">E-posta:</label>
                        <input type="email" class="form-control" name="email" value="<?= $user['email'] ?>" required>
                    </div>

                            <div class="form-group mt-3">
                        <label for="phone">Telefon:</label>
                        <input type="text" class="form-control" name="phone" value="<?= $user['phone'] ?>" required>
                    </div>
                            <div class="form-group mt-3">
                                <button type="submit" class="btn btn-primary">Bilgileri Güncelle</button>
                            </div>
                </form>
                    </div>
                </div>
            </div>
        </div>

<?php require_once "footer.php"; ?>
