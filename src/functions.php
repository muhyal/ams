<?php
// Dil seçimini tarayıcı dilinden al, eğer belirtilmemişse varsayılan olarak "tr" kullan
$selectedLanguage = isset($_GET['lang']) ? $_GET['lang'] : (isset($_SESSION['selectedLanguage']) ? $_SESSION['selectedLanguage'] : substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2));

// Dil seçimini sakla, böylece diğer sayfalarda kullanabilirsiniz
$_SESSION['selectedLanguage'] = $selectedLanguage;

// Çeviri fonksiyonunu tanımla
function translate($key, $language) {
    // Dil seçimine göre çeviri dosyasını yükle
    $translations = include "../translations/$language.php";
    return $translations[$key] ?? $key;
}
?>