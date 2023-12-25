<?php
global $db;
require_once "db_connection.php";
require_once "admin_panel_header.php";

session_start();

$allowedRoles = array(1); // "sa" rolü için rol değeri (örneğin 1)
$currentUserRole = $_SESSION['admin_role'];

if (!in_array($currentUserRole, $allowedRoles)) {
    header("Location: access_denied.php");
    exit;
}

if (isset($_GET["id"])) {
    $admin_id = $_GET["id"];

    // Yönetici bilgilerini çekme
    $select_query = "SELECT * FROM admins WHERE id = ?";
    $stmt = $db->prepare($select_query);
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        echo "Yönetici bulunamadı.";
        exit();
    }

    // Onay alınmışsa silme işlemini gerçekleştir
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["confirm"])) {
        $delete_query = "DELETE FROM admins WHERE id = ?";
        $stmt = $db->prepare($delete_query);
        $stmt->execute([$admin_id]);
        header("Location: admin_list.php"); // Yönetici listesine geri dön
        exit();
    }
}
?>
    <div class="container-fluid">
    <div class="row">
<?php
require_once "admin_panel_sidebar.php";
?>
    <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
        <h2>Yönetici Sil</h2>
    </div>

<p>Yöneticiyi silmek istediğinizden emin misiniz?</p>
<p>Kullanıcı Adı: <?php echo $admin['username']; ?></p>
<p>E-posta: <?php echo $admin['email']; ?></p>
<form method="post" action="">
    <input type="hidden" name="confirm" value="yes">
    <button type="submit">Evet, Sil</button>
    <a href="admin_list.php">Hayır, İptal</a>
</form>
<?php
require_once "footer.php";
?>