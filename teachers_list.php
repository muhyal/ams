<?php
// Veritabanı bağlantısını sağlayın
global $db;
require_once "db_connection.php";

// Öğretmenler tablosunu öğrenciler ve dersler tablolarıyla birleştirerek çekin
$query = "
    SELECT 
        teachers.id,
        teachers.first_name,
        teachers.last_name,
        teachers.birth_date,
        teachers.phone,
        teachers.email,
        classes.class_name,
        courses.course_name
    FROM teachers
    LEFT JOIN classes ON teachers.class_id = classes.id
    LEFT JOIN courses ON teachers.course_id = courses.id
";

$stmt = $db->query($query);
$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Öğretmen Listesi</title>
</head>
<body>
<h1>Öğretmen Listesi</h1>
<table>
    <tr>
        <th>ID</th>
        <th>Ad</th>
        <th>Soyad</th>
        <th>Doğum Tarihi</th>
        <th>Telefon</th>
        <th>E-posta</th>
        <th>Sınıf</th>
        <th>Ders</th>
        <th>İşlemler</th>
    </tr>
    <?php foreach ($teachers as $teacher): ?>
        <tr>
            <td><?php echo $teacher['id']; ?></td>
            <td><?php echo $teacher['first_name']; ?></td>
            <td><?php echo $teacher['last_name']; ?></td>
            <td><?php echo $teacher['birth_date']; ?></td>
            <td><?php echo $teacher['phone']; ?></td>
            <td><?php echo $teacher['email']; ?></td>
            <td><?php echo $teacher['class_name']; ?></td>
            <td><?php echo $teacher['course_name']; ?></td>
            <td>
                <a href="edit_teacher.php?id=<?php echo $teacher['id']; ?>">Düzenle</a>
                <a href="delete_teacher.php?id=<?php echo $teacher['id']; ?>">Sil</a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<a href="add_teacher.php">Yeni Öğretmen Ekle</a>
</body>
</html>
