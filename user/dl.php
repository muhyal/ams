<?php
// Dosya yükleme dizini
$uploadDirectory = __DIR__ . '/../uploads/user_uploads/';

// Dosya parametresi kontrolü
if (isset($_GET['file'])) {
    $filename = $_GET['file'];
    $filepath = $uploadDirectory . $filename;

    // Dosyanın var olup olmadığını kontrol et
    if (file_exists($filepath)) {
        // Dosya indirme için uygun başlıkları ayarla
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));

        // Tamponu temizle, çıktıyı gönder ve çık
        ob_clean();
        flush();
        readfile($filepath);
        exit;
    } else {
        // Dosya bulunamadıysa kullanıcıya hata mesajı göster
        echo 'Dosya bulunamadı.';
    }
} else {
    // Geçersiz istek durumunda kullanıcıya uygun bir mesaj göster
    echo 'Dosya indirme için geçersiz istek.';
}
?>
