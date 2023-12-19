<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once "config.php";
global $siteName, $siteShortName, $siteUrl;
require_once "agreement_header.php";
?>
<body class="d-flex h-100 text-center text-bg-dark">
<svg xmlns="http://www.w3.org/2000/svg" class="d-none">
    <symbol id="check2" viewBox="0 0 16 16">
        <path d="M13.854 3.646a.5.5 0 0 1 0 .708l-7 7a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6.5 10.293l6.646-6.647a.5.5 0 0 1 .708 0z"/>
    </symbol>
    <symbol id="circle-half" viewBox="0 0 16 16">
        <path d="M8 15A7 7 0 1 0 8 1v14zm0 1A8 8 0 1 1 8 0a8 8 0 0 1 0 16z"/>
    </symbol>
    <symbol id="moon-stars-fill" viewBox="0 0 16 16">
        <path d="M6 .278a.768.768 0 0 1 .08.858 7.208 7.208 0 0 0-.878 3.46c0 4.021 3.278 7.277 7.318 7.277.527 0 1.04-.055 1.533-.16a.787.787 0 0 1 .81.316.733.733 0 0 1-.031.893A8.349 8.349 0 0 1 8.344 16C3.734 16 0 12.286 0 7.71 0 4.266 2.114 1.312 5.124.06A.752.752 0 0 1 6 .278z"/>
        <path d="M10.794 3.148a.217.217 0 0 1 .412 0l.387 1.162c.173.518.579.924 1.097 1.097l1.162.387a.217.217 0 0 1 0 .412l-1.162.387a1.734 1.734 0 0 0-1.097 1.097l-.387 1.162a.217.217 0 0 1-.412 0l-.387-1.162A1.734 1.734 0 0 0 9.31 6.593l-1.162-.387a.217.217 0 0 1 0-.412l1.162-.387a1.734 1.734 0 0 0 1.097-1.097l.387-1.162zM13.863.099a.145.145 0 0 1 .274 0l.258.774c.115.346.386.617.732.732l.774.258a.145.145 0 0 1 0 .274l-.774.258a1.156 1.156 0 0 0-.732.732l-.258.774a.145.145 0 0 1-.274 0l-.258-.774a1.156 1.156 0 0 0-.732-.732l-.774-.258a.145.145 0 0 1 0-.274l.774-.258c.346-.115.617-.386.732-.732L13.863.1z"/>
    </symbol>
    <symbol id="sun-fill" viewBox="0 0 16 16">
        <path d="M8 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM8 0a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 0zm0 13a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 13zm8-5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2a.5.5 0 0 1 .5.5zM3 8a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2A.5.5 0 0 1 3 8zm10.657-5.657a.5.5 0 0 1 0 .707l-1.414 1.415a.5.5 0 1 1-.707-.708l1.414-1.414a.5.5 0 0 1 .707 0zm-9.193 9.193a.5.5 0 0 1 0 .707L3.05 13.657a.5.5 0 0 1-.707-.707l1.414-1.414a.5.5 0 0 1 .707 0zm9.193 2.121a.5.5 0 0 1-.707 0l-1.414-1.414a.5.5 0 0 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .707zM4.464 4.465a.5.5 0 0 1-.707 0L2.343 3.05a.5.5 0 1 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .708z"/>
    </symbol>
</svg>

<div class="cover-container d-flex w-100 h-100 p-3 mx-auto flex-column">
    <header class="mb-auto">
        <div>
            <h3 class="float-md-start mb-0"><?php echo $siteName ?> - <?php echo $siteShortName ?></h3>
            <nav class="nav nav-masthead justify-content-center float-md-end">
                <a class="nav-link fw-bold py-1 px-0 active" aria-current="page" href="agreement.php">Sözleşmeler</a>
            </nav>
        </div>
    </header>

    <main class="px-3">
        <br>
        <p class="lead">Aşağıdan size en uygun kategoriyi seçerek ilgili sözleşmeleri görüntüleyebilirsiniz. Sözleşme onayında zaman damgalı SMS ve e-posta ile onay alınmakta ve onayınız kaydedilmektedir.</p>
    </main>

<?php
// Klasörleri tanımlayın
$folder1 = "pdfs/student-under-18";
$folder2 = "pdfs/student-over-18-years-old";

// Seçilen klasörü belirlemek için GET parametresini alın
$selectedFolder = isset($_GET['folder']) ? $_GET['folder'] : $folder1;
?>
    <p class="lead">
    <p>Seçilen Kategori: <?php echo $selectedFolder; ?></p>
<a class="btn btn-md btn-light fw-bold border-white bg-white" href="agreement.php?folder=<?php echo $folder1; ?>">Veli tarafından onaylanan 18 yaşından küçük öğrenciler için sözleşmeler</a><br>
<a class="btn btn-md btn-light fw-bold border-white bg-white" href="agreement.php?folder=<?php echo $folder2; ?>">18 yaşından büyük yetişkin öğrencilerin onayladığı sözleşmeler</a><br><br>
    </p>

    <?php
// Seçilen klasördeki PDF dosyalarını listele
$pdfFiles = scandir($selectedFolder);
foreach ($pdfFiles as $pdfFile) {
    if (pathinfo($pdfFile, PATHINFO_EXTENSION) === 'pdf') {
        echo "<a style='color: white' class=\"lead\" href=\"agreement.php?folder=$selectedFolder&pdf=$pdfFile\" target=\"_self\">$pdfFile</a><br>";
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
<?php
require_once "agreement_footer.php";
?>

