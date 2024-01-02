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
    <title>Erişim Reddedildi</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            text-align: center;
        }

        .error-code {
            font-size: 72px;
            color: #dc3545;
            margin-bottom: 20px;
        }

        .error-message {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
        }

        .back-link {
            font-size: 18px;
            color: #007bff;
            text-decoration: none;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="error-code">403</div>
    <div class="error-message">Erişim Reddedildi</div>
    <p>Üzgünüz, bu sayfaya erişim izniniz bulunmuyor.</p>
    <a class="back-link" href="javascript:history.back()">Geri Dön</a>
</div>
</body>
</html>
