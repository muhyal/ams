<?php
global $db, $showErrors, $siteName, $siteShortName, $siteUrl, $userType, $selectedLanguage;

// Oturum kontrolü
session_start();
session_regenerate_id(true);

if (!isset($_SESSION["admin_id"])) {
    header("Location: index.php"); // Giriş sayfasına yönlendir
    exit();
}

require_once(__DIR__ . '/../config/db_connection.php');
require_once('../config/config.php');

$_SESSION['user_type'] = $userType; // Set $userType with the actual user type value

// Yetki kontrolü fonksiyonu
function checkPermission() {
    if ($_SESSION["admin_type"] != 1) {
        // Yetki hatası
        echo "Bu işlemi gerçekleştirmek için yetkiniz yok!";
        exit();
    }
}

// Hata mesajlarını göster veya gizle ve ilgili işlemleri gerçekleştir
$showErrors ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
$showErrors ? ini_set('display_startup_errors', 1) : ini_set('display_startup_errors', 0);

// Fetch default values from the options table
$query = "SELECT * FROM options";
$result = $db->query($query);
$options = $result->fetchAll(PDO::FETCH_ASSOC);

$query = "SELECT option_name, option_value FROM options WHERE option_name LIKE 'user_agreement_%'";
$result = $db->query($query);
$userTypeAgreementTexts = $result->fetchAll(PDO::FETCH_KEY_PAIR);

// CSRF token oluşturma
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkPermission();

    // CSRF kontrolü
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token doğrulaması başarısız.');
    }

    $success = true;
    $message = '';

    try {
        // Loop through the submitted data and update the options
        foreach ($_POST as $optionName => $optionValue) {
            if ($optionName === 'csrf_token') continue;

            // Input validasyonu
            if (empty($optionValue) && $optionName !== 'course_fee') {
                throw new Exception("Lütfen tüm alanları doldurun.");
            }

            // Sanitize input
            $optionValue = htmlspecialchars($optionValue, ENT_QUOTES, 'UTF-8');

            // Update the options in the database
            $updateQuery = "UPDATE options SET option_value = :optionValue WHERE option_name = :optionName";
            $stmt = $db->prepare($updateQuery);
            $stmt->bindParam(':optionValue', $optionValue);
            $stmt->bindParam(':optionName', $optionName);
            $stmt->execute();

            // Update the options array
            foreach ($options as &$option) {
                if ($option['option_name'] === $optionName) {
                    $option['option_value'] = $optionValue;
                    break;
                }
            }
        }
        $message = 'Seçenekler başarıyla güncellendi.';
    } catch (Exception $e) {
        $success = false;
        $message = 'Hata: ' . $e->getMessage();
    }
}

// Fetch the course_fee from the options table
$query = "SELECT option_value FROM options WHERE option_name = 'course_fee' LIMIT 1";
$stmt = $db->prepare($query);
$stmt->execute();
$courseFee = $stmt->fetchColumn();

// Eğer değer bulunamazsa, placeholder olarak "yoksa siz belirleyin" mesajını ayarla
if (!$courseFee) {
    $courseFee = ""; // Değeri boş bırak
    $placeholderText = "Varsayılan değer bulunamadı, elle belirleyebilirsiniz";
} else {
    $placeholderText = $courseFee; // Değer bulunduysa placeholder aynı olur
}

checkPermission();
?>

<?php require_once('../admin/partials/header.php'); ?>
<div class="container-fluid">
    <div class="row">
        <?php
        require_once(__DIR__ . '/partials/sidebar.php');
        ?>
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
                <h2>Site Seçenekleri</h2>
            </div>

            <?php if (isset($message)): ?>
                <div class="alert alert-<?php echo $success ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form action="" method="post" class="container-fluid">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <?php foreach ($options as $option): ?>
                    <?php
                    // Unique ID oluşturma
                    $textareaId = 'ckeditor_' . $option['option_name'];

                    // Label metnini belirleme
                    $labelText = translate($option['option_name'], $selectedLanguage);

                    // Textarea içeriğini doğru şekilde işleme
                    $optionValue = htmlspecialchars_decode($option['option_value'], ENT_QUOTES);
                    $isRequired = ($option['option_name'] !== 'course_fee') ? 'required' : '';
                    ?>
                    <div class="mb-3">
                        <label for="<?php echo $textareaId; ?>" class="form-label"><?php echo $labelText; ?>:</label>
                        <?php if (strpos($option['option_name'], 'user_agreement_') !== false): ?>
                            <textarea class="form-control" id="<?php echo $textareaId; ?>" name="<?php echo $option['option_name']; ?>" rows="10" <?php echo $isRequired; ?>><?php echo $optionValue; ?></textarea>
                        <?php else: ?>
                            <input type="text" class="form-control" id="<?php echo $option['option_name']; ?>" name="<?php echo $option['option_name']; ?>" value="<?php echo $optionValue; ?>" <?php echo $isRequired; ?>>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <button type="submit" class="btn btn-primary">Seçenekleri Kaydet</button>
            </form>

        </main>
    </div>
</div>

<?php require_once('../admin/partials/footer.php'); ?>
