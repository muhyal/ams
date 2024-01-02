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
<!DOCTYPE html>
<html>
<head>
    <title>Sözleşme Dosyaları</title>
</head>
<body>
<h2>Sözleşme Dosyaları</h2>
<ul>
    <?php
    $pdfFolder = './assets/pdfs/student-under-18/'; // PDF dosyalarının saklandığı klasör
    $pdfFiles = scandir($pdfFolder);

    foreach ($pdfFiles as $file) {
        if ($file !== '.' && $file !== '..') {
            echo '<embed src="' . $pdfFolder . $file . '" " width="100%" height="600px" type="application/pdf">';
        }
    }
    ?>
</ul>
</body>
</html>
