<?php
global $db;
session_start();
// Oturum kontrolü
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php"); // Giriş sayfasına yönlendir
    exit();
}

require_once "db_connection.php"; // Veritabanı bağlantısı

// Kullanıcı bilgilerini kullanabilirsiniz
$admin_id = $_SESSION["admin_id"];
$admin_username = $_SESSION["admin_username"];

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once "config.php";
global $siteName, $siteShortName, $siteUrl;
require_once "admin_panel_header.php";
?>
<div class="container-fluid">
    <div class="row">
        <?php
        require_once "admin_panel_sidebar.php";
        ?>
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
                <h2>Arama Yap</h2>
            </div>

<form id="searchForm">
    <input type="text" id="searchQuery" name="q" required>
    <select id="searchType" name="search_type">
        <option value="user">Kullanıcı</option>
        <option value="student">Öğrenci</option>
        <option value="teacher">Öğretmen</option>
        <option value="course">Ders</option>
        <option value="class">Sınıf</option>
    </select>
    <button type="button" onclick="performSearch()">Ara</button>
</form>

<div id="searchResults"></div>

<script>
    function performSearch() {
        const searchQuery = document.getElementById("searchQuery").value;
        const searchType = document.getElementById("searchType").value;

        // AJAX isteği gönderme
        const xhr = new XMLHttpRequest();
        xhr.open("GET", `search_results.php?q=${searchQuery}&search_type=${searchType}`, true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                document.getElementById("searchResults").innerHTML = xhr.responseText;
            }
        };
        xhr.send();
    }
</script>
<?php
require_once "footer.php";
?>

