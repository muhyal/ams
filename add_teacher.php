<?php
global $db;
require_once "db_connection.php";

// Dersleri veritabanından çekme
$query = "SELECT * FROM courses";
$stmt = $db->query($query);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Sınıfları veritabanından çekme
$query = "SELECT * FROM classes";
$stmt = $db->query($query);
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstName = $_POST["first_name"];
    $lastName = $_POST["last_name"];
    $birthDate = $_POST["birth_date"];
    $phone = $_POST["phone"];
    $email = $_POST["email"];
    $selectedCourse = $_POST["course"];
    $selectedClass = $_POST["class"];

    // Veritabanına yeni öğretmen ekleyin
    $query = "INSERT INTO teachers (first_name, last_name, birth_date, phone, email, course_id, class_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->execute([$firstName, $lastName, $birthDate, $phone, $email, $selectedCourse, $selectedClass]);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Öğretmen Ekleme</title>
</head>
<body>
<h1>Öğretmen Ekleme</h1>
<form method="post">
    <label for="first_name">Adı:</label>
    <input type="text" id="first_name" name="first_name" required><br>
    <label for="last_name">Soyadı:</label>
    <input type="text" id="last_name" name="last_name" required><br>
    <label for="birth_date">Doğum Tarihi:</label>
    <input type="date" id="birth_date" name="birth_date" required><br>
    <label for="phone">Telefon:</label>
    <input type="tel" id="phone" name="phone"><br>
    <label for="email">E-posta:</label>
    <input type="email" id="email" name="email" required><br>

    <!-- Ders Seçimi -->
    <label for="course">Ders:</label>
    <select id="course" name="course">
        <?php foreach ($courses as $course): ?>
            <option value="<?= $course['id'] ?>"><?= $course['course_name'] ?></option>
        <?php endforeach; ?>
    </select><br>

    <!-- Sınıf Seçimi -->
    <label for="class">Sınıf:</label>
    <select id="class" name="class">
        <?php foreach ($classes as $class): ?>
            <option value="<?= $class['id'] ?>"><?= $class['class_name'] ?></option>
        <?php endforeach; ?>
    </select><br>

    <button type="submit" name="add_teacher">Öğretmen Ekle</button>
</form>
<a href="teachers_list.php">Öğretmen Listesi</a>
</body>
</html>
