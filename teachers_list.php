<?php
// Veritabanı bağlantısını sağlayın
global $siteName, $siteShortName, $siteUrl, $db;
require_once "db_connection.php";

// Öğretmenler tablosunu öğrenciler ve dersler tablolarıyla birleştirerek çekin
$query = "
    SELECT 
        teachers.id,
        teachers.first_name,
        teachers.tc_identity,
        teachers.last_name,
        teachers.birth_date,
        teachers.phone,
        teachers.email,
        classes.class_name,
        courses.course_name
    FROM teachers
    LEFT JOIN teacher_courses ON teachers.id = teacher_courses.teacher_id
    LEFT JOIN classes ON teacher_courses.class_id = classes.id
    LEFT JOIN courses ON teacher_courses.course_id = courses.id
";



$stmt = $db->query($query);
$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
require_once "config.php";
?>
<?php
require_once "admin_panel_header.php";
?>
<div class="container-fluid">
    <div class="row">
        <?php require_once "admin_panel_sidebar.php"; ?>
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
                <h2>Öğretmen Listesi</h2>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Ad</th>
                        <th>Soyad</th>
                        <th>T.C. Kimlik No</th>
                        <th>Doğum Tarihi</th>
                        <th>Telefon</th>
                        <th>E-posta</th>
                        <th>Sınıf</th>
                        <th>Ders</th>
                        <th>İşlemler</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($teachers as $teacher): ?>
                        <tr>
                            <td><?php echo $teacher['id']; ?></td>
                            <td><?php echo $teacher['first_name']; ?></td>
                            <td><?php echo $teacher['last_name']; ?></td>
                            <td><?php echo $teacher['tc_identity']; ?></td>
                            <td><?php echo $teacher['birth_date']; ?></td>
                            <td><?php echo $teacher['phone']; ?></td>
                            <td><?php echo $teacher['email']; ?></td>
                            <td><?php echo $teacher['class_name']; ?></td>
                            <td><?php echo $teacher['course_name']; ?></td>
                            <td>
                                <a href="edit_teacher.php?id=<?php echo $teacher['id']; ?>" class="btn btn-warning btn-sm">
                                    <i class="fas fa-pencil-alt"></i>
                                </a>
                                <a href="delete_teacher.php?id=<?php echo $teacher['id']; ?>" class="btn btn-danger btn-sm">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <button onclick="location.href='add_teacher.php'" type="button" class="btn btn-success">
                <i class="fas fa-plus"></i> Yeni Öğretmen Ekle
            </button>

        </main>
    </div>
</div>

<?php
require_once "footer.php";
?>
