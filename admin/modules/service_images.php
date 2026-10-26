<?php
// No need to require db.php or functions.php as they are included by index.php
// $pdo is available from index.php -> header.php

// Get service ID from query
$service_id = isset($_GET['service_id']) ? intval($_GET['service_id']) : 0;
if ($service_id <= 0) {
    echo "<div class='alert alert-danger'>Invalid service ID. <a href='index.php?module=services'>Go back</a></div>";
    return;
}

// Handle add image
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_image'])) {
    $title_en = trim($_POST['title_en'] ?? '');
    $title_tm = trim($_POST['title_tm'] ?? '');
    $title_ru = trim($_POST['title_ru'] ?? '');
    $description_en = trim($_POST['description_en'] ?? '');
    $description_tm = trim($_POST['description_tm'] ?? '');
    $description_ru = trim($_POST['description_ru'] ?? '');
    $image_path = '';
    $target_dir = 'uploads/service_images/';
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $filename = uniqid() . '_' . basename($_FILES['image']['name']);
        $target_file = $target_dir . $filename;
        move_uploaded_file($_FILES['image']['tmp_name'], $target_file);
        $image_path = $target_file;
    }
    $stmt = $pdo->prepare("INSERT INTO service_images (service_id, image, title_en, title_tm, title_ru, description_en, description_tm, description_ru) VALUES (?,?,?,?,?,?,?,?)");
    $result = $stmt->execute([$service_id, $image_path, $title_en, $title_tm, $title_ru, $description_en, $description_tm, $description_ru]);
    $message = $result ? "<div class='alert alert-success'>Image added.</div>" : "<div class='alert alert-danger'>Error adding image.</div>";
}

  // Handle add multiple images
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_images'])) {
    $title_en = trim($_POST['title_en_multi'] ?? '');
    $title_tm = trim($_POST['title_tm_multi'] ?? '');
    $title_ru = trim($_POST['title_ru_multi'] ?? '');
    $description_en = trim($_POST['description_en_multi'] ?? '');
    $description_tm = trim($_POST['description_tm_multi'] ?? '');
    $description_ru = trim($_POST['description_ru_multi'] ?? '');

    // Default titles to the service's title if not provided
    $lang = $_SESSION['lang_code'] ?? 'en';
    $service_title_default = $service['title_' . $lang] ?? ($service['title_en'] ?? '');
    if ($title_en === '') $title_en = $service_title_default;
    if ($title_tm === '') $title_tm = $service_title_default;
    if ($title_ru === '') $title_ru = $service_title_default;

    $target_dir = 'uploads/service_images/';
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

    $added = 0; $errors = 0;
    if (!empty($_FILES['images']) && is_array($_FILES['images']['name'])) {
      $count = count($_FILES['images']['name']);
      for ($i = 0; $i < $count; $i++) {
        if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
          $name = basename($_FILES['images']['name'][$i]);
          $filename = uniqid() . '_' . $name;
          $target_file = $target_dir . $filename;
          if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $target_file)) {
            $stmt = $pdo->prepare("INSERT INTO service_images (service_id, image, title_en, title_tm, title_ru, description_en, description_tm, description_ru) VALUES (?,?,?,?,?,?,?,?)");
            $ok = $stmt->execute([$service_id, $target_file, $title_en, $title_tm, $title_ru, $description_en, $description_tm, $description_ru]);
            if ($ok) { $added++; } else { $errors++; }
          } else {
            $errors++;
          }
        } else {
          $errors++;
        }
      }
    }
    if ($added > 0 && $errors === 0) {
      $message = "<div class='alert alert-success'>Uploaded $added image(s) successfully.</div>";
    } elseif ($added > 0 && $errors > 0) {
      $message = "<div class='alert alert-warning'>Uploaded $added image(s), $errors failed.</div>";
    } else {
      $message = "<div class='alert alert-danger'>No images were uploaded.</div>";
    }
  }

// Handle delete image
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
  $img_id = intval($_GET['delete']);
  // Fetch image path first
  $s = $pdo->prepare("SELECT image FROM service_images WHERE id=? AND service_id=?");
  $s->execute([$img_id, $service_id]);
  $row = $s->fetch(PDO::FETCH_ASSOC);
  if ($row && !empty($row['image']) && is_file($row['image'])) {
    @unlink($row['image']);
  }
  // Then delete DB row
  $stmt = $pdo->prepare("DELETE FROM service_images WHERE id=? AND service_id=?");
  $stmt->execute([$img_id, $service_id]);
  $message = "<div class='alert alert-success'>Image deleted.</div>";
}

// Fetch service info
$stmt = $pdo->prepare("SELECT * FROM services WHERE id=?");
$stmt->execute([$service_id]);
$service = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$service) {
    echo "<div class='alert alert-danger'>Service not found.</div>";
    return;
}

// Fetch images for this service
$stmt = $pdo->prepare("SELECT * FROM service_images WHERE service_id=? ORDER BY id DESC");
$stmt->execute([$service_id]);
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);

$lang = $_SESSION['lang_code'] ?? 'en';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Images for Service: <?= htmlspecialchars($service['title_' . $lang] ?? $service['title_en']) ?></h2>
            <a href="index.php?module=services" class="btn btn-secondary">Back to Services</a>
        </div>

        <?= $message ?? '' ?>
        
        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addImageModal">Add Image</button>
        <button class="btn btn-outline-primary mb-3 ms-2" data-bs-toggle="modal" data-bs-target="#addImagesModal">Add Multiple Images</button>
        
        <div class="card">
            <div class="card-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Title (EN)</th>
                            <th>Title (TM)</th>
                            <th>Title (RU)</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($images as $img): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($img['image'])): ?>
                                        <img src="<?= htmlspecialchars($img['image']) ?>" style="max-width:120px; max-height:80px;">
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($img['title_en']) ?></td>
                                <td><?= htmlspecialchars($img['title_tm']) ?></td>
                                <td><?= htmlspecialchars($img['title_ru']) ?></td>
                                <td>
                                    <a href="index.php?module=service_images&service_id=<?= $service_id ?>&delete=<?= $img['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Image Modal -->
<div class="modal fade" id="addImageModal" tabindex="-1" aria-labelledby="addImageModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" enctype="multipart/form-data" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addImageModalLabel">Add Image</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Image</label>
          <input type="file" name="image" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Title (EN)</label>
          <input type="text" name="title_en" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Title (TM)</label>
          <input type="text" name="title_tm" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Title (RU)</label>
          <input type="text" name="title_ru" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Description (EN)</label>
          <textarea name="description_en" class="form-control" rows="2" required></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label">Description (TM)</label>
          <textarea name="description_tm" class="form-control" rows="2" required></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label">Description (RU)</label>
          <textarea name="description_ru" class="form-control" rows="2" required></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" name="add_image" class="btn btn-primary">Add</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Add Multiple Images Modal -->
<div class="modal fade" id="addImagesModal" tabindex="-1" aria-labelledby="addImagesModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" enctype="multipart/form-data" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addImagesModalLabel">Add Multiple Images</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Images</label>
          <input type="file" name="images[]" class="form-control" multiple required>
          <small class="text-muted">You can select multiple image files.</small>
        </div>
        <hr>
        <p class="small text-muted">Optional: titles/descriptions will be applied to all uploaded images. If left blank, titles default to the service title.</p>
        <div class="mb-3">
          <label class="form-label">Title (EN)</label>
          <input type="text" name="title_en_multi" class="form-control">
        </div>
        <div class="mb-3">
          <label class="form-label">Title (TM)</label>
          <input type="text" name="title_tm_multi" class="form-control">
        </div>
        <div class="mb-3">
          <label class="form-label">Title (RU)</label>
          <input type="text" name="title_ru_multi" class="form-control">
        </div>
        <div class="mb-3">
          <label class="form-label">Description (EN)</label>
          <textarea name="description_en_multi" class="form-control" rows="2"></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label">Description (TM)</label>
          <textarea name="description_tm_multi" class="form-control" rows="2"></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label">Description (RU)</label>
          <textarea name="description_ru_multi" class="form-control" rows="2"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" name="add_images" class="btn btn-primary">Upload</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>
    </form>
  </div>
</div>
<!-- Bootstrap JS is already included in footer.php, but if modals don't work, we might need it. 
     However, footer.php usually includes scripts. 
     Let's check footer.php content if needed. For now, I'll assume it's there. -->
