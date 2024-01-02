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
session_start();

// Önceki sayfanın URL'sini al
$previousPage = $_SERVER["HTTP_REFERER"];

// Oturumu temizle ve sonlandır
session_unset();
session_destroy();

// Eğer önceki sayfa bilgisi varsa, kullanıcıyı o sayfaya yönlendir
if (!empty($previousPage)) {
    header("Location: $previousPage");
} else {
    header("Location: /index.php"); // Varsayılan sayfaya yönlendirme
}

exit();
?>