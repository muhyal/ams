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

global $showErrors, $siteUrl, $db;
// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);

require_once(__DIR__ . '/user/partials/header.php');
?>

<div class="px-4 py-5 my-5 text-center">
    <h1 class="display-5 fw-bold text-body-emphasis">
        <?php echo $option['site_name']; ?> - <?php echo $option['site_short_name']; ?>
    </h1>
    <div class="col-lg-6 mx-auto">
        <p class="lead mb-4"><?php echo $option['site_hero_description']; ?></p>
        <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
            <a href="<?php echo $option['site_virtual_classroom_url']; ?>" target="_blank"
               class="btn btn-success btn-md px-4 gap-3" role="button" aria-pressed="true">
                <i class="bi bi-door-open"></i> Sanal Sınıf
            </a>
            <a href="<?php echo $option['site_academy_url']; ?>/" target="_blank"
               class="btn btn-primary btn-md px-4 gap-3" role="button" aria-pressed="true">
                <i class="bi bi-file-earmark-text"></i> <?php echo $option['site_name']; ?>'ye git
            </a>
        </div>
    </div>
</div>

<?php require_once(__DIR__ . '/user/partials/footer.php'); ?>
