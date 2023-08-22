<!DOCTYPE html>
<html>
<head>
    <title>Sözleşme Görüntüleyici</title>
</head>
<body>
<h2>Sözleşme Görüntüleyici</h2>

<?php
// Klasörleri tanımlayın
$folder1 = "pdfs/student-under-18";
$folder2 = "pdfs/student-over-18-years-old";

// Seçilen klasörü belirlemek için GET parametresini alın
$selectedFolder = isset($_GET['folder']) ? $_GET['folder'] : $folder1;
?>
<p>Seçilen Kategori: <?php echo $selectedFolder; ?></p>
<a href="agreement.php?folder=<?php echo $folder1; ?>">Velilerimizin onayladığı 18 yaşından küçük öğrencilerin sözleşmeleri</a><br>
<a href="agreement.php?folder=<?php echo $folder2; ?>">18 yaşından büyük yetişkin öğrencilerin onayladığı sözleşmeler</a><br><br>
<?php
// Seçilen klasördeki PDF dosyalarını listele
$pdfFiles = scandir($selectedFolder);
foreach ($pdfFiles as $pdfFile) {
    if (pathinfo($pdfFile, PATHINFO_EXTENSION) === 'pdf') {
        echo "<a href=\"agreement.php?folder=$selectedFolder&pdf=$pdfFile\" target=\"_self\">$pdfFile</a><br>";
    }
}

// Seçilen PDF dosyasını göster
if (isset($_GET['pdf'])) {
    $selectedPdf = $_GET['pdf'];
    $pdfPath = "$selectedFolder/$selectedPdf";
    echo '<p><button onclick="history.back()">Geri Dön</button></p>';
    echo "<embed src=\"$pdfPath\" type=\"application/pdf\" width=\"100%\" height=\"600px\">";
}
?>
</body>
</html>
