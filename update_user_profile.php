<?php
global $db;
session_start();
session_regenerate_id(true);

require_once "db_connection.php";

// Kullanıcı oturum kontrolü yapılır
if (!isset($_SESSION["user_id"])) {
    header("Location: user_login.php");
    exit();
}

// Formdan gelen veriler alınır
$user_id = $_SESSION["user_id"];
$firstname = $_POST["firstname"];
$lastname = $_POST["lastname"];
$email = $_POST["email"];
$tc = $_POST["tc"];
$phone = $_POST["phone"];

// Güncelleme sorgusu hazırlanır
$query = "UPDATE users SET firstname = ?, lastname = ?, email = ?, tc = ?, phone = ? WHERE id = ?";
$stmt = $db->prepare($query);

// Güncelleme sorgusu çalıştırılır
$result = $stmt->execute([$firstname, $lastname, $email, $tc, $phone, $user_id]);

if ($result) {
    // Başarılı güncelleme durumunda kullanıcıyı bilgilendir
    $_SESSION["success_message"] = "Profil bilgileriniz güncellendi.";
} else {
    // Hata durumunda kullanıcıyı bilgilendir
    $_SESSION["error_message"] = "Profil bilgileriniz güncellenirken bir hata oluştu.";
}

// Profil sayfasına yönlendirme yapılır
header("Location: user_profile.php");
exit();
?>
