<!DOCTYPE html>
<html>
<head>
    <title>Sınıf Listesi</title>
</head>
<body>
<h1>Sınıf Listesi</h1>
<a href="add_class.php">Sınıf Ekle</a>
<table border="1">
    <tr>
        <th>ID</th>
        <th>Sınıf Adı</th>
        <th>Sınıf Kodu</th>
        <th>Sınıf Açıklaması</th>
        <th>İşlemler</th>
    </tr>
    <?php
    session_start();

    // Oturum kontrolü
    if (!isset($_SESSION["admin_id"])) {
        header("Location: admin_login.php"); // Giriş sayfasına yönlendir
        exit();
    }

    global$db;
    require_once "db_connection.php"; // Veritabanı bağlantısı

    $selectQuery = "SELECT * FROM classes";
    $stmt = $db->prepare($selectQuery);
    $stmt->execute();
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($classes as $class) {
        echo "<tr>";
        echo "<td>{$class['id']}</td>";
        echo "<td>{$class['class_name']}</td>";
        echo "<td>{$class['class_code']}</td>";
        echo "<td>{$class['class_description']}</td>";
        echo '<td>
                  <a href="edit_class.php?id='.$class['id'].'">Düzenle</a>
                  <a href="delete_class.php?id='.$class['id'].'">Sil</a>
                  </td>';
        echo "</tr>";
    }
    ?>
</table>
</body>
</html>
