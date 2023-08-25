<?php
// Veritabanı bağlantısı ve gerekli dosyaları include edin
global $db;
require_once "db_connection.php";

// Öğrenci listesi sorgusu
$query = "SELECT * FROM students";
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

<h2>Öğrenci Listesi</h2>

<table>
    <thead>
    <tr>
        <th>Öğrenci Adı</th>
        <th>Öğrenci Soyadı</th>
        <th>Öğrenci TC Kimlik No</th>
        <th>Öğrenci Cep Telefonu</th>
        <th>Öğrenci E-posta</th>
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
            <td><a href="edit_student.php?id=<?php echo $student['id']; ?>">Düzenle</a></td>
            <td><a href="delete_student.php?id=<?php echo $student['id']; ?>">Sil</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>


</body>
</html>
