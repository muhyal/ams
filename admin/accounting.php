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
    header("Location: index.php"); // Giriş sayfasına yönlendir
    exit();
}

require_once(__DIR__ . '/../config/db_connection.php');
require_once('../config/config.php');

// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);

// Varsayılan tarih aralığı: Bugünden bu ayın başına kadar
$defaultStartDate = date("Y-m-01");
$defaultEndDate = date("Y-m-d");

// Kullanıcıdan gelen tarih aralığı değerleri
$startDate = isset($_POST['startDate']) ? $_POST['startDate'] : $defaultStartDate;
$endDate = isset($_POST['endDate']) ? $_POST['endDate'] : $defaultEndDate;

// Veritabanı sorgusu
$query = "SELECT
            academies.id AS academy_id,
            academies.name AS academy_name,
            COUNT(course_plans.id) AS total_course_plans,
            COALESCE(SUM(accounting.amount), 0) AS total_payments,
            COALESCE(SUM(course_plans.debt_amount), 0) AS total_debt
            FROM academies
            LEFT JOIN course_plans ON academies.id = course_plans.academy_id
            LEFT JOIN accounting ON course_plans.id = accounting.course_plan_id
            WHERE course_date_1 BETWEEN :startDate AND :endDate
            GROUP BY academies.id";

$stmt = $db->prepare($query);
$stmt->bindParam(":startDate", $startDate, PDO::PARAM_STR);
$stmt->bindParam(":endDate", $endDate, PDO::PARAM_STR);
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php
require_once(__DIR__ . '/partials/header.php');
?>
<div class="container-fluid">
    <div class="row">
<?php
require_once(__DIR__ . '/partials/sidebar.php');
?>
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
                <h2>Muhasebe</h2>
            </div>

            <div class="container mt-4">
                <div class="row">
                    <div class="col-md-5">
                        <form id="dateForm" method="post">
                            <div class="form-group">
                                <label for="startDate">Başlangıç Tarihi:</label>
                                <input type="date" id="startDate" name="startDate" class="form-control" required value="<?= $defaultStartDate ?>">
                            </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-group">
                            <label for="endDate">Bitiş Tarihi:</label>
                            <input type="date" id="endDate" name="endDate" class="form-control" required value="<?= $defaultEndDate ?>">
                        </div>
                    </div>
                    <div class="col-md-12 mb-3 mt-3">
                        <button type="submit" class="btn btn-primary">Hesapla</button>
                    </div>
                    </form>
                </div>
            </div>




            <div class="row mt-4">
                <?php
                if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                    // Form gönderildiyse, yeni tarihleri al
                    $startDate = isset($_POST['startDate']) ? $_POST['startDate'] : $defaultStartDate;
                    $endDate = isset($_POST['endDate']) ? $_POST['endDate'] : $defaultEndDate;

                    // Yeni tarihlerle veritabanı sorgusunu çalıştır
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(":startDate", $startDate, PDO::PARAM_STR);
                    $stmt->bindParam(":endDate", $endDate, PDO::PARAM_STR);
                    $stmt->execute();
                    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }

                foreach ($result as $row) {
                    echo '
                <div class="col-md-4 mb-4">
                    <div class="card">
                    <div class="card-header">
                            <h5 class="card-title">' . $row['academy_name'] . '</h5>
        </div>
                        <div class="card-body">
                            <p class="card-text">Toplam Satış: ' . $row['total_course_plans'] . ' Kur</p>
                            <p class="card-text">Toplam Alınan Ödeme: ' . $row['total_payments'] . ' TL</p>
                            <p class="card-text">Toplam Kalan Borç: ' . $row['total_debt'] . ' TL</p>
                        </div>
                    </div>
                </div>';
                }
                ?>
            </div>

            <?php require_once('../admin/partials/footer.php'); ?>
        </main>
    </div>
</div>