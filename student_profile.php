<?php
global $db;
require_once "db_connection.php";

// Öğrenci ID'sini URL'den alın
if (isset($_GET['id'])) {
    $student_id = $_GET['id'];

    // Öğrenci ve diğer bilgileri birleştiren sorgu
    $query = "SELECT students.*, parents.parent_firstname, parents.parent_lastname, parents.parent_phone, parents.parent_email,
              emergency_contacts.emergency_contact, emergency_contacts.emergency_phone, 
              addresses.city, addresses.district
              FROM students
              LEFT JOIN parents ON students.id = parents.student_id
              LEFT JOIN emergency_contacts ON students.id = emergency_contacts.student_id
              LEFT JOIN addresses ON students.id = addresses.student_id
              WHERE students.id = ?";

    $stmt = $db->prepare($query);
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($student) {
        // Öğrenci bilgilerini tablo içinde görüntülemek için HTML çıktısı oluşturun
        echo "<h1>Öğrenci Profili</h1>";
        echo "<table border='1'>";
        echo "<tr><td>Ad</td><td>Soyad</td><td>TC Kimlik No</td><td>Cep Telefonu</td><td>E-posta</td><td>Veli Adı Soyadı</td><td>Veli Telefonu</td><td>Veli E-posta</td><td>Acil Durum Kişi</td><td>Acil Durum Telefonu</td><td>Kan Grubu</td><td>Rahatsızlık</td><td>İl</td><td>İlçe</td></tr>";
        echo "<tr>";
        echo "<td>" . $student['firstname'] . "</td>";
        echo "<td>" . $student['lastname'] . "</td>";
        echo "<td>" . $student['tc_identity'] . "</td>";
        echo "<td>" . $student['phone'] . "</td>";
        echo "<td>" . $student['email'] . "</td>";
        echo "<td>" . $student['parent_firstname'] . ' ' . $student['parent_lastname'] . "</td>";
        echo "<td>" . $student['parent_phone'] . "</td>";
        echo "<td>" . $student['parent_email'] . "</td>";
        echo "<td>" . $student['emergency_contact'] . "</td>";
        echo "<td>" . $student['emergency_phone'] . "</td>";
        echo "<td>" . $student['blood_type'] . "</td>";
        echo "<td>" . $student['health_issue'] . "</td>";
        echo "<td>" . $student['city'] . "</td>";
        echo "<td>" . $student['district'] . "</td>";
        echo "</tr>";
        echo "</table>";
    } else {
        echo "Öğrenci bulunamadı.";
    }
} else {
    echo "Geçersiz öğrenci ID'si.";
}
