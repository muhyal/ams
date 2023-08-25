<?php
global $db, $siteUrl, $siteName, $siteShortName;
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Oturum kontrolü
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php"); // Giriş sayfasına yönlendir
    exit();
}

require_once "db_connection.php";
?>
<form id="searchForm">
    <input type="text" id="searchQuery" name="q" required>
    <select id="searchType" name="search_type">
        <option value="user">Kullanıcı Ara</option>
        <option value="student">Öğrenci Ara</option>
        <option value="class">Sınıf Ara</option>
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

