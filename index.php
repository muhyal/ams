<?php global $siteVirtualClassroomUrl, $siteAcademyUrl;
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
global $siteHeroDescription, $showErrors, $siteName, $siteShortName, $siteUrl;
// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);
require_once "config.php";
require_once "header.php";
?>
<div class="px-4 py-5 my-5 text-center">
    <h1 class="display-5 fw-bold text-body-emphasis"><?php echo $siteName ?> - <?php echo $siteShortName ?></h1>
    <div class="col-lg-6 mx-auto">
      <p class="lead mb-4"><?php echo $siteHeroDescription ?></p>
      <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
              <a href="<?php echo $siteVirtualClassroomUrl ?>" target="_blank" class="btn btn-success btn-md px-4 gap-3" role="button" aria-pressed="true">
                  <i class="bi bi-door-open"></i> Sanal Sınıf
              </a>
              <a href="<?php echo $siteUrl ?>/agreement.php" target="_self" class="btn btn-primary btn-md px-4 gap-3" role="button" aria-pressed="true">
                  <i class="bi bi-file-earmark-text"></i> Sözleşmeler
              </a>
              <a href="<?php echo $siteAcademyUrl ?>/" target="_blank" class="btn btn-outline-secondary btn-md px-4" role="button" aria-pressed="true">
                  <i class="bi bi-arrow-right-circle"></i> <?php echo $siteName ?>'ye git
              </a>
          </div>
    </div>
  </div>
<?php
require_once "footer.php";
?>