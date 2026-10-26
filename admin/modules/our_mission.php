<?php
require_once './includes/functions.php';
$message = '';

// Mevcut verileri çek
$our_mission = [];
$stmt = $pdo->query("SELECT * FROM our_mission");
while ($row = $stmt->fetch()) {
    $our_mission[$row['lang_code']] = $row;
}
$images = $our_mission['en']['images'] ?? ''; // Ortak images alanı

// Upload path helpers: store relative, move using absolute
$UPLOAD_REL = 'uploads/our_mission/';
$ADMIN_ROOT = rtrim(dirname(__DIR__), '/\\') . '/'; // .../admin/
$UPLOAD_ABS = $ADMIN_ROOT . $UPLOAD_REL;            // .../admin/uploads/our_mission/

// Form gönderildiğinde
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $langs = ['en', 'tm', 'ru'];
    $success = true;

    // Resim yükleme işlemi (çoklu dosya için)
    $target_dir_rel = $UPLOAD_REL;
    $target_dir_abs = $UPLOAD_ABS;
    $uploaded_images = [];
    if (!empty($_FILES['images']['name'][0])) {
        // ensure upload directory exists and is writable
        if (!is_dir($target_dir_abs)) { @mkdir($target_dir_abs, 0755, true); }
        // if replacing, delete old images
        if (!empty($_POST['old_images'])) {
            foreach (explode(',', $_POST['old_images']) as $old) {
                $old = trim($old);
                if ($old === '') continue;
                // build absolute path for deletion if stored as relative
                if (preg_match('~^([a-z]+:)?/|^https?://~i', $old)) {
                    // absolute path or URL -> skip deletion for URLs
                    continue;
                }
                $delAbs = $ADMIN_ROOT . ltrim($old, '/');
                if (is_file($delAbs)) { @unlink($delAbs); }
            }
        }
        foreach ($_FILES['images']['name'] as $key => $name) {
            if ($_FILES['images']['error'][$key] == UPLOAD_ERR_OK) {
                if (!is_dir($target_dir_abs)) { @mkdir($target_dir_abs, 0755, true); }
                if (!is_writable($target_dir_abs)) {
                    $message = "<div class='alert alert-danger'>Upload directory is not writable: " . htmlspecialchars($target_dir_abs) . "</div>";
                    continue;
                }
                $filename = uniqid() . "_" . basename($name);
                $target_file_abs = $target_dir_abs . $filename;
                $target_file_rel = $target_dir_rel . $filename;
                if (@move_uploaded_file($_FILES['images']['tmp_name'][$key], $target_file_abs)) {
                    $uploaded_images[] = $target_file_rel; // store relative in DB
                } else {
                    $message = "<div class='alert alert-danger'>Failed to move uploaded file. Check permissions.</div>";
                }
            }
        }
        $images = implode(',', $uploaded_images);
    } else {
        $images = $_POST['old_images'] ?? '';
    }

    // Her dil için description güncelle/ekle
    foreach ($langs as $lang) {
        $description = trim($_POST["description_$lang"] ?? '');

        $stmt = $pdo->prepare("SELECT id FROM our_mission WHERE lang_code = ?");
        $stmt->execute([$lang]);
        $exists = $stmt->fetch();

        if ($exists) {
            $stmt = $pdo->prepare("UPDATE our_mission SET description=?, images=? WHERE lang_code=?");
            $result = $stmt->execute([$description, $images, $lang]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO our_mission (lang_code, description, images) VALUES (?, ?, ?)");
            $result = $stmt->execute([$lang, $description, $images]);
        }
        if (!$result) $success = false;
    }

    $message = $success
        ? "<div class='alert alert-success'>Our Mission updated successfully.</div>"
        : "<div class='alert alert-danger'>Error updating records.</div>";
}

// Yeniden verileri çek
$our_mission = [];
$stmt = $pdo->query("SELECT * FROM our_mission");
while ($row = $stmt->fetch()) {
    $our_mission[$row['lang_code']] = $row;
}
$images = $our_mission['en']['images'] ?? '';
?>

<div class="row">
    <div class="col-12">
        <?php echo $message; ?>
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Our Mission Management (Description: All Languages, Images: Common)</h5>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <?php foreach (['en'=>'English', 'tm'=>'Türkmençe', 'ru'=>'Русский'] as $lang => $label): ?>
                        <div class="mb-3 border-bottom pb-2">
                            <h6><?php echo $label; ?> (<?php echo strtoupper($lang); ?>)</h6>
                            <label for="description_<?php echo $lang; ?>" class="form-label">Description</label>
                            <textarea class="form-control mb-2" id="description_<?php echo $lang; ?>" name="description_<?php echo $lang; ?>" rows="4" required><?php echo htmlspecialchars($our_mission[$lang]['description'] ?? ''); ?></textarea>
                        </div>
                    <?php endforeach; ?>
                    <div class="mb-3 border-bottom pb-2">
                        <label for="images" class="form-label">Images (Common)</label>
                        <input type="file" class="form-control mb-2" id="images" name="images[]" multiple>
                        <?php if (!empty($images)): ?>
                            <div class="mt-2">
                                <?php foreach (explode(',', $images) as $img): ?>
                                    <img src="<?php echo $img; ?>" style="max-width:120px;max-height:60px;" class="me-2 mb-2">
                                <?php endforeach; ?>
                                <input type="hidden" name="old_images" value="<?php echo $images; ?>">
                            </div>
                        <?php endif; ?>
                    </div>
                    <button type="submit" class="btn btn-primary">Save All</button>
                </form>
            </div>
        </div>
    </div>
</div>
