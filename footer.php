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
?>
   </main>
   </div>
   </div>
<!-- Boş alan -->
<div style="height: 75px;"></div>
<!-- Footer -->
<footer class="footer fixed-bottom bg-body-secondary">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-6 mt-3">
                <p>&copy; <?php echo (new DateTime())->format('Y') ?>, <?php echo $siteName ?>. Tüm hakları saklıdır.</p>
            </div>
        </div>
    </div>
</footer>

<script src="./assets/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
<script src="./assets/js/dashboard.js"></script></body>
   <script>
    // Sayfa yüklendikten sonra uyarıyı otomatik kapat
    $(document).ready(function(){
        // Başarı uyarısı
        if ($("#success-alert").length > 0) {
            setTimeout(function(){
                $("#success-alert").fadeOut("slow");
            }, 5000);
        }

        // Hata uyarısı
        if ($("#error-alert").length > 0) {
            setTimeout(function(){
                $("#error-alert").fadeOut("slow");
            }, 5000);
        }
    });
   </script>
   </html>
