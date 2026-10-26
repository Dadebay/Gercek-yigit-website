<?php
require_once './includes/functions.php';
$message = '';

// Form gönderildiğinde
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $langs = ['en', 'tm', 'ru'];
    $success = true;
    // Mevcut ortak resimleri veritabanından al (yeni seçilmezse koru)
    $stmtImg = $pdo->query("SELECT image1, image2 FROM about_us LIMIT 1");
    $existing = $stmtImg ? $stmtImg->fetch() : null;
    $image1 = $existing['image1'] ?? null;
    $image2 = $existing['image2'] ?? null;

    if (isset($_FILES["image1"]) && $_FILES["image1"]['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES["image1"]['name'], PATHINFO_EXTENSION);
        $image1 = "aboutus_1_" . time() . ".$ext";
        if (move_uploaded_file($_FILES["image1"]['tmp_name'], "./uploads/$image1")) {
            if (!empty($existing['image1']) && $existing['image1'] !== $image1) {
                $oldPath = "./uploads/" . $existing['image1'];
                if (is_file($oldPath)) { @unlink($oldPath); }
            }
        }
    }
    if (isset($_FILES["image2"]) && $_FILES["image2"]['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES["image2"]['name'], PATHINFO_EXTENSION);
        $image2 = "aboutus_2_" . time() . ".$ext";
        if (move_uploaded_file($_FILES["image2"]['tmp_name'], "./uploads/$image2")) {
            if (!empty($existing['image2']) && $existing['image2'] !== $image2) {
                $oldPath = "./uploads/" . $existing['image2'];
                if (is_file($oldPath)) { @unlink($oldPath); }
            }
        }
    }

    foreach ($langs as $lang) {
        $title = trim($_POST["title_$lang"] ?? '');
        $description = trim($_POST["description_$lang"] ?? '');
        $short_description = trim($_POST["short_description_$lang"] ?? '');

        // Kayıt var mı kontrol et
        $stmt = $pdo->prepare("SELECT id FROM about_us WHERE lang_code = ?");
        $stmt->execute([$lang]);
        $exists = $stmt->fetch();

        if ($exists) {
            // Güncelle (resimler seçilmediyse mevcut olanı korur)
            $stmt = $pdo->prepare("UPDATE about_us SET title = ?, description = ?, short_description = ?, image1 = ?, image2 = ? WHERE lang_code = ?");
            $result = $stmt->execute([$title, $description, $short_description, $image1, $image2, $lang]);
        } else {
            // Ekle
            $stmt = $pdo->prepare("INSERT INTO about_us (lang_code, title, description, short_description, image1, image2) VALUES (?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute([$lang, $title, $description, $short_description, $image1, $image2]);
        }
        if (!$result) $success = false;
    }

    $message = $success
        ? "<div class='alert alert-success'>All languages updated successfully.</div>"
        : "<div class='alert alert-danger'>Error updating records.</div>";
}

// Mevcut verileri çek
$about_us = [];
$stmt = $pdo->query("SELECT * FROM about_us");
while ($row = $stmt->fetch()) {
    $about_us[$row['lang_code']] = $row;
}
// Ortak resimleri ilk satırdan al
$first = reset($about_us);
$image1 = $first['image1'] ?? '';
$image2 = $first['image2'] ?? '';
?>

<div class="row">
    <div class="col-12">
        <?php echo $message; ?>
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">About Us Management (All Languages)</h5>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Image 1 (Images size must be : 500px-700px)</label>
                            <input type="file" class="form-control" name="image1">
                            <?php if (!empty($image1)): ?>
                                <img src="./uploads/<?php echo htmlspecialchars($image1); ?>" alt="Image 1" style="max-width:100px;max-height:100px;">
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Image 2 (Images size must be : 500px-700px)</label>
                            <input type="file" class="form-control" name="image2">
                            <?php if (!empty($image2)): ?>
                                <img src="./uploads/<?php echo htmlspecialchars($image2); ?>" alt="Image 2" style="max-width:100px;max-height:100px;">
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php foreach (['en'=>'English', 'tm'=>'Türkmençe', 'ru'=>'Русский'] as $lang => $label): ?>
                        <div class="mb-3 border-bottom pb-2">
                            <h6><?php echo $label; ?> (<?php echo strtoupper($lang); ?>)</h6>
                            <label for="title_<?php echo $lang; ?>" class="form-label">Title</label>
                            <input type="text" class="form-control mb-2" id="title_<?php echo $lang; ?>" name="title_<?php echo $lang; ?>"
                                   value="<?php echo htmlspecialchars($about_us[$lang]['title'] ?? ''); ?>" required>
                            <label for="description_<?php echo $lang; ?>" class="form-label">Description</label>
                            <textarea class="form-control" id="description_<?php echo $lang; ?>" name="description_<?php echo $lang; ?>" rows="10" required><?php echo htmlspecialchars($about_us[$lang]['description'] ?? ''); ?></textarea>
                            <label for="short_description_<?php echo $lang; ?>" class="form-label">Short Description</label>
                            <textarea class="form-control" id="short_description_<?php echo $lang; ?>" name="short_description_<?php echo $lang; ?>" rows="3" required><?php echo htmlspecialchars($about_us[$lang]['short_description'] ?? ''); ?></textarea>
                        </div>
                    <?php endforeach; ?>
                    <button type="submit" class="btn btn-primary">Save All</button>
                </form>
            </div>
        </div>
    </div>
</div>