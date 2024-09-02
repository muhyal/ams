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
global $db, $showErrors, $siteName, $siteShortName, $siteUrl, $config, $amsVersion, $siteHeroDescription;
require_once(__DIR__ . '/../../src/functions.php');
$option = getConfigurationFromDatabase($db);
extract($option, EXTR_IF_EXISTS);
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
                <p>&copy; <?php echo (new DateTime())->format('Y') ?>, <?php echo $option['site_name']; ?>. <?= translate('all_rights_reserved', $selectedLanguage) ?></p>
            </div>
        </div>
    </div>
</footer>

<!-- CKEditor 5 Entegrasyonu -->
<script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
<script>
    document.querySelectorAll('textarea').forEach(function(element) {
        ClassicEditor
            .create(element)
            .catch(error => {
                console.error(error);
            });
    });
</script>

<script>
    // Tüm [data-bs-toggle="popover"] öğelerini seç
    const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');

    // Her bir öğe için Popover oluştur ve etkinleştir
    const popoverList = [...popoverTriggerList].map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl));

    const popover = new bootstrap.Popover('.popover-dismiss', {
        trigger: 'focus'
    })
</script>
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
<script src="https://www.google.com/recaptcha/api.js?render=<?php echo $option['recaptcha_site_key']; ?>"></script>
<script>
    // Recaptcha token'ını alma
    grecaptcha.ready(function () {
        grecaptcha.execute('<?php echo $option['recaptcha_site_key']; ?>', { action: 'login' }).then(function (token) {
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

            // Check if the logo element exists
            if (logo) {
                // Set the source of the logo based on the color mode
                if (colorMode === 'dark') {
                    logo.src = "/assets/brand/default_logo_dark.png";
                } else {
                    logo.src = "/assets/brand/default_logo_light.png";
                }
            }
        }

        // Initial logos update
        updateLogos();

        // Watch for changes to the data-bs-theme attribute
        const observer = new MutationObserver(updateLogos);
        observer.observe(document.documentElement, { attributes: true, attributeFilter: ['data-bs-theme'] });
    });
</script>
<script>
    $(document).ready(function () {
        // select2 uygula
        $('#country').select2();
        $('#student').select2();
        $('#parent').select2();
        $('#studentDropdown').select2();
        $('#teacherDropdown').select2();
        $('[name="selectCoursePlan"]').select2();
        $('#addICPClassDropdown').select2();
        $('#addICPStudentDropdown').select2();
        $('#addICPTeacherDropdown').select2();
        $('#addRCPClassDropdown').select2();
        $('#addRCPStudentDropdown').select2();
        $('#addRCPTeacherDropdown').select2();
        $('#addRCPCoursePlanDropdown').select2();
        $('#cities').select2();
        $('#districts').select2();

    });
</script>
<script>
    $(document).ready(function () {
        // Geçerli sayfa URL'sini al
        var currentUrl = window.location.href;

        // Eğer geçerli URL, "admin/panel.php" içermiyorsa DataTables'i uygula
        if (currentUrl.indexOf("admin/panel.php") === -1 && currentUrl.indexOf("admin/user_profile.php") === -1 && currentUrl.indexOf("admin/academy_date_course_plans.php") === -1) {
            $('table').DataTable({
                pageLength: 25, // Her sayfada gösterilecek satır sayısı
                lengthMenu: [5, 10, 25], // Sayfa uzunluğu seçenekleri
                //autoFill: {
                //    focus: 'click'
                //},
                responsive: true,
                dom: '<"bottom" Blfrtip>', // Düğmeleri aşağıda göstermek için düzenleme
                buttons: [
                    {
                        extend: 'copy',
                        text: '<i class="fas fa-copy"></i>',
                        className: 'btn btn-light btn-sm'
                    },
                    {
                        extend: 'csv',
                        text: '<i class="fas fa-file-csv"></i>',
                        className: 'btn btn-light btn-sm'
                    },
                    {
                        extend: 'excel',
                        text: '<i class="fas fa-file-excel"></i>',
                        className: 'btn btn-light btn-sm'
                    },
                    {
                        extend: 'pdf',
                        text: '<i class="fas fa-file-pdf"></i>',
                        className: 'btn btn-light btn-sm'
                    },
                    {
                        extend: 'print',
                        text: '<i class="fas fa-print"></i>',
                        className: 'btn btn-light btn-sm'
                    }
                ],
                language: {
                    "url": "https://cdn.datatables.net/plug-ins/1.10.25/i18n/Turkish.json"
                },
            });
        }
        // Eğer içeriyorsa, DataTables'i etkinleştirmemeyi veya başka bir işlem yapmayı düşünebilirsiniz.
    });
</script>


</body>
</html>
