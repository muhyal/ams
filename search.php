<?php
global $resetPasswordDescription, $db, $showErrors, $siteName, $siteShortName, $siteUrl, $config;
// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);
require_once "config.php";

// Oturum kontrolü
session_start();
session_regenerate_id(true);

if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php"); // Giriş sayfasına yönlendir
    exit();
}

require_once "db_connection.php"; // Veritabanı bağlantısı

// Kullanıcı bilgilerini kullanabilirsiniz
$admin_id = $_SESSION["admin_id"];
$admin_username = $_SESSION["admin_username"];
?>
<?php require_once "admin_panel_header.php"; ?>
<div class="container-fluid">
    <div class="row">
        <?php require_once "admin_panel_sidebar.php"; ?>
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
                <h2>Arama Yap</h2>
            </div>

            <form id="searchForm" class="mb-3">
                <div class="row">
                    <div class="col-md-6">
                        <input type="text" class="form-control" id="searchQuery" name="q" required placeholder="Arama yapılacak kelime">
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" id="searchType" name="search_type">
                            <option value="user">Kullanıcı</option>
                            <option value="student">Öğrenci</option>
                            <option value="teacher">Öğretmen</option>
                            <option value="course">Ders</option>
                            <option value="class">Sınıf</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-primary" onclick="performSearch()">Ara</button>
                    </div>
                </div>
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
        </main>
    </div>
</div>

<?php require_once "footer.php"; ?>
