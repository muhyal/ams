<?php
session_start();

// Oturum kontrolü
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php"); // Giriş sayfasına yönlendir
    exit();
}

// Veritabanı bağlantısı ve gerekli dosyaları include edin
global $db;
require_once "db_connection.php";

// Öğrenci listesi sorgusu
$query = "SELECT students.*, parents.*, emergency_contacts.*, addresses.* 
          FROM students 
          LEFT JOIN parents ON students.id = parents.student_id 
          LEFT JOIN emergency_contacts ON students.id = emergency_contacts.student_id 
          LEFT JOIN addresses ON students.id = addresses.student_id";

$stmt = $db->query($query);

// Öğrenci verilerini alın
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Öğrenci Listesi</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }
    </style>
</head>
<body>

<!-- Öğrenci Listesi -->
<h2>Öğrenci Listesi</h2>

<table>
    <thead>
    <tr>
        <th>Öğrenci Adı</th>
        <th>Öğrenci Soyadı</th>
        <th>Öğrenci TC Kimlik No</th>
        <th>Öğrenci Cep Telefonu</th>
        <th>Öğrenci E-posta</th>
        <th>Veli Ad Soyad</th>
        <th>Veli Telefon</th>
        <th>Veli E-Posta</th>
        <th>Acil Durumda Aranacak Kişi</th>
        <th>Acil Durumda Aranacak Kişi Telefonu</th>
        <th>Kan Grubu</th> <!-- Yeni sütun -->
        <th>Bilinen Rahatsızlık</th> <!-- Yeni sütun -->
        <th>İl</th> <!-- Yeni sütun -->
        <th>İlçe</th> <!-- Yeni sütun -->
        <th></th> <!-- Düzenleme Bağlantısı -->
        <th></th> <!-- Silme Bağlantısı -->
    </tr>
    </thead>
    <tbody>
    <!-- Öğrenci bilgilerini listeleyen döngü -->
    <?php foreach ($students as $student): ?>
        <tr>
            <td><?php echo $student['firstname']; ?></td>
            <td><?php echo $student['lastname']; ?></td>
            <td><?php echo $student['tc_identity']; ?></td>
            <td><?php echo $student['phone']; ?></td>
            <td><?php echo $student['email']; ?></td>
            <td><?php echo $student['parent_firstname'] . ' ' . $student['parent_lastname']; ?></td> <!-- Veli bilgisi -->
            <td><?php echo $student['parent_phone']; ?></td> <!-- Veli telefonu -->
            <td><?php echo $student['parent_phone']; ?></td> <!-- Veli telefonu -->
            <td><?php echo $student['emergency_contact']; ?></td>
            <td><?php echo $student['phone']; ?></td>
            <td><?php echo $student['blood_type']; ?></td> <!-- Yeni sütun -->
            <td><?php echo $student['health_issue']; ?></td> <!-- Yeni sütun -->
            <td><?php echo $student['city']; ?></td> <!-- Yeni sütun -->
            <td><?php echo $student['district']; ?></td> <!-- Yeni sütun -->
            <td><a href="edit_student.php?id=<?php echo $student['id']; ?>">Düzenle</a></td>
            <td><a href="process_delete_student.php?id=<?php echo $student['id']; ?>">Sil</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

</body>
</html>
