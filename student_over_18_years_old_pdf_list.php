<!DOCTYPE html>
<html>
<head>
    <title>Sözleşme Dosyaları</title>
</head>
<body>
<h2>Sözleşme Dosyaları</h2>
<ul>
    <?php
    $pdfFolder = './assets/pdfs/student-over-18-years-old/'; // PDF dosyalarının saklandığı klasör
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
