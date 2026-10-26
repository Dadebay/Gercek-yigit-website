<?php
// page_headers.php
if (!isset($pdo)) {
    require_once __DIR__ . '/../includes/db.php';
}

$pages = ['about', 'services', 'projects', 'certificates', 'news', 'gallery', 'contact', 'catalogues', 'search'];

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($pages as $page) {
        if (isset($_FILES[$page]) && $_FILES[$page]['error'] == UPLOAD_ERR_OK) {
            $target_dir = __DIR__ . '/../uploads/';
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            $filename = uniqid() . '_' . $page . '_' . basename($_FILES[$page]['name']);
            $target_file = $target_dir . $filename;
            // fetch old image for this page
            $prevQ = $pdo->prepare("SELECT header_image FROM page_headers WHERE page_name = ? LIMIT 1");
            $prevQ->execute([$page]);
            $prev = $prevQ->fetch(PDO::FETCH_ASSOC);
            if (move_uploaded_file($_FILES[$page]['tmp_name'], $target_file)) {
                // delete old physical file
                if ($prev && !empty($prev['header_image']) && $prev['header_image'] !== $filename) {
                    $oldFull = $target_dir . $prev['header_image'];
                    if (is_file($oldFull)) { @unlink($oldFull); }
                }
                // update existing row; if none updated, insert new row
                $stmt = $pdo->prepare("UPDATE page_headers SET header_image = ? WHERE page_name = ?");
                $stmt->execute([$filename, $page]);
                if ($stmt->rowCount() === 0) {
                    // ensure no existing row then insert
                    $check = $pdo->prepare("SELECT COUNT(*) FROM page_headers WHERE page_name = ?");
                    $check->execute([$page]);
                    if ((int)$check->fetchColumn() === 0) {
                        $ins = $pdo->prepare("INSERT INTO page_headers (page_name, header_image) VALUES (?, ?)");
                        $ins->execute([$page, $filename]);
                    }
                }
                $message = 'Images updated successfully.';
            } else {
                $message = 'Error uploading file for ' . $page;
            }
        }
    }
}

$stmt = $pdo->query("SELECT page_name, header_image FROM page_headers WHERE page_name IN ('" . implode("','", $pages) . "')");
$current_images = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h2>Page Header Images</h2>
            <?php if ($message): ?>
                <div class="alert alert-info"><?php echo $message; ?></div>
            <?php endif; ?>
            <form method="post" enctype="multipart/form-data">
                <?php foreach ($pages as $page): ?>
                    <div class="mb-3">
                        <label for="<?php echo $page; ?>" class="form-label"><?php echo ucfirst($page); ?> Header Image</label>
                        <input type="file" class="form-control" id="<?php echo $page; ?>" name="<?php echo $page; ?>">
                        <?php if (!empty($current_images[$page])): ?>
                            <?php $preview = str_starts_with($current_images[$page], 'img/') ? '../' . $current_images[$page] : 'uploads/' . $current_images[$page]; ?>
                            <img src="<?php echo htmlspecialchars($preview); ?>" style="max-width:200px;max-height:100px;" class="mt-2" alt="<?php echo htmlspecialchars(ucfirst($page)); ?>">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <button type="submit" class="btn btn-primary">Update Images</button>
            </form>
        </div>
    </div>
</div>
