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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
<script src="../../assets/js/dashboard.js"></script></body>
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
<!-- Recaptcha kütüphanesi -->
<script src="https://www.google.com/recaptcha/api.js?render=<?php echo RECAPTCHA_SITE_KEY; ?>"></script>
<script>
    // Recaptcha token'ını alma
    grecaptcha.ready(function () {
        grecaptcha.execute('<?php echo RECAPTCHA_SITE_KEY; ?>', { action: 'login' }).then(function (token) {
            document.getElementById('recaptcha_response').value = token;
        });
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Function to detect the current color mode
        function detectColorMode() {
            const theme = document.documentElement.getAttribute('data-bs-theme');
            return theme === 'dark' ? 'dark' : 'light';
        }

        // Function to update the logos based on the color mode
        function updateLogos() {
            const colorMode = detectColorMode();

            // Update the first logo
            updateLogo('logo-header', colorMode);

            // Update the second logo
            updateLogo('logo-body', colorMode);
        }

        // Function to update a specific logo based on the color mode
        function updateLogo(logoId, colorMode) {
            const logo = document.getElementById(logoId);

            // Set the source of the logo based on the color mode
            if (colorMode === 'dark') {
                logo.src = "/assets/brand/default_logo_dark.png";
            } else {
                logo.src = "/assets/brand/default_logo_light.png";
            }
        }

        // Initial logos update
        updateLogos();

        // Watch for changes to the data-bs-theme attribute
        const observer = new MutationObserver(updateLogos);
        observer.observe(document.documentElement, { attributes: true, attributeFilter: ['data-bs-theme'] });
    });
</script>
   </html>
