<?php
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