<?php
require_once './includes/functions.php';
$message = '';

// Service ekleme
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_service'])) {
    $title_en = trim($_POST["title_en"] ?? '');
    $title_tm = trim($_POST["title_tm"] ?? '');
    $title_ru = trim($_POST["title_ru"] ?? '');
    $description_en = trim($_POST["description_en"] ?? '');
    $description_tm = trim($_POST["description_tm"] ?? '');
    $description_ru = trim($_POST["description_ru"] ?? '');
    $image = '';
    $service_brand = trim($_POST["service_brand"] ?? '');
    $target_dir = "uploads/services/";

    if (isset($_FILES["image"]) && $_FILES["image"]["error"] == UPLOAD_ERR_OK) {
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $filename = uniqid() . "_" . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $filename;
        move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);
        $image = $target_file;
    }

    // Insert with service_brand if column exists
    $colExists = $pdo->query("SHOW COLUMNS FROM services LIKE 'service_brand'")->fetch();
    if ($colExists) {
        $stmt = $pdo->prepare("INSERT INTO services (title_en, title_tm, title_ru, description_en, description_tm, description_ru, image, service_brand) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $result = $stmt->execute([$title_en, $title_tm, $title_ru, $description_en, $description_tm, $description_ru, $image, $service_brand ?: null]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO services (title_en, title_tm, title_ru, description_en, description_tm, description_ru, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $result = $stmt->execute([$title_en, $title_tm, $title_ru, $description_en, $description_tm, $description_ru, $image]);
    }

    $message = $result
        ? "<div class='alert alert-success'>Service added successfully.</div>"
        : "<div class='alert alert-danger'>Error adding service.</div>";
}

// Service silme
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM services WHERE id=?");
    $result = $stmt->execute([$id]);
    $message = $result
        ? "<div class='alert alert-success'>Service deleted.</div>"
        : "<div class='alert alert-danger'>Error deleting service.</div>";
}

// Service düzenleme
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_service'])) {
    $id = intval($_POST['service_id']);
    $title_en = trim($_POST["edit_title_en"] ?? '');
    $title_tm = trim($_POST["edit_title_tm"] ?? '');
    $title_ru = trim($_POST["edit_title_ru"] ?? '');
    $description_en = trim($_POST["edit_description_en"] ?? '');
    $description_tm = trim($_POST["edit_description_tm"] ?? '');
    $description_ru = trim($_POST["edit_description_ru"] ?? '');
    $image = $_POST["old_image"] ?? '';
    $service_brand = trim($_POST["edit_service_brand"] ?? '');
    $target_dir = "uploads/services/";

    if (isset($_FILES["edit_image"]) && $_FILES["edit_image"]["error"] == UPLOAD_ERR_OK) {
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $filename = uniqid() . "_" . basename($_FILES["edit_image"]["name"]);
        $target_file = $target_dir . $filename;
        if (move_uploaded_file($_FILES["edit_image"]["tmp_name"], $target_file)) {
            if (!empty($_POST["old_image"]) && $_POST["old_image"] !== $target_file) {
                $old = $_POST["old_image"];
                if (is_file($old)) { @unlink($old); }
            }
            $image = $target_file;
        }
    }

    // Update with service_brand if column exists
    $colExists = $pdo->query("SHOW COLUMNS FROM services LIKE 'service_brand'")->fetch();
    if ($colExists) {
        $stmt = $pdo->prepare("UPDATE services SET title_en=?, title_tm=?, title_ru=?, description_en=?, description_tm=?, description_ru=?, image=?, service_brand=? WHERE id=?");
        $result = $stmt->execute([$title_en, $title_tm, $title_ru, $description_en, $description_tm, $description_ru, $image, $service_brand ?: null, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE services SET title_en=?, title_tm=?, title_ru=?, description_en=?, description_tm=?, description_ru=?, image=? WHERE id=?");
        $result = $stmt->execute([$title_en, $title_tm, $title_ru, $description_en, $description_tm, $description_ru, $image, $id]);
    }

    $message = $result
        ? "<div class='alert alert-success'>Service updated.</div>"
        : "<div class='alert alert-danger'>Error updating service.</div>";
}

// Mevcut servisleri çek
$services = [];
$stmt = $pdo->query("SELECT * FROM services ORDER BY id DESC");
while ($row = $stmt->fetch()) {
    $services[] = $row;
}

// Her servis için resim sayısını çek
$imageCounts = [];
$stmtCounts = $pdo->query("SELECT service_id, COUNT(*) as cnt FROM service_images GROUP BY service_id");
while ($r = $stmtCounts->fetch()) {
    $imageCounts[(int)$r['service_id']] = (int)$r['cnt'];
}

// Düzenlenecek servisi çek
$edit_service = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM services WHERE id=?");
    $stmt->execute([$id]);
    $edit_service = $stmt->fetch();
}
?>

<div class="row">
    <div class="col-12">
        <?php if ($message): ?>
            <div id="alert-message">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Add Service Button -->
        <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addServiceModal">
            Add Service
        </button>
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Services List (Images size must be : 500px-700px)</h5>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Title (EN)</th>
                            <th>Title (TM)</th>
                            <th>Title (RU)</th>
                            <th>Brand</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($services as $service): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($service['image'])): ?>
                                        <img src="<?php echo $service['image']; ?>" style="max-width:80px;max-height:40px;">
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($service['title_en']); ?></td>
                                <td><?php echo htmlspecialchars($service['title_tm']); ?></td>
                                <td><?php echo htmlspecialchars($service['title_ru']); ?></td>
                                <td><?php echo htmlspecialchars($service['service_brand'] ?? ''); ?></td>
                                <td>
                                    <a href="#" class="btn btn-sm btn-warning" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#editServiceModal"
                                    data-id="<?php echo $service['id']; ?>"
                                    data-title-en="<?php echo htmlspecialchars($service['title_en']); ?>"
                                    data-title-tm="<?php echo htmlspecialchars($service['title_tm']); ?>"
                                    data-title-ru="<?php echo htmlspecialchars($service['title_ru']); ?>"
                                    data-description-en="<?php echo htmlspecialchars($service['description_en']); ?>"
                                    data-description-tm="<?php echo htmlspecialchars($service['description_tm']); ?>"
                                    data-description-ru="<?php echo htmlspecialchars($service['description_ru']); ?>"
                                    data-image="<?php echo $service['image']; ?>"
                                    data-brand="<?php echo htmlspecialchars($service['service_brand'] ?? ''); ?>"
                                    >
                                        Edit
                                    </a>
                                                <a href="index.php?module=service_images&service_id=<?php echo $service['id']; ?>" 
                                    class="btn btn-sm btn-primary ms-2">
                                    Images (<?php echo isset($imageCounts[$service['id']]) ? (int)$imageCounts[$service['id']] : 0; ?>)
                                </a>
                                    <a href="?delete=<?php echo $service['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this service?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Add Service Modal -->
        <div class="modal fade" id="addServiceModal" tabindex="-1" aria-labelledby="addServiceModalLabel" aria-hidden="true">
          <div class="modal-dialog">
            <form method="POST" enctype="multipart/form-data" class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="addServiceModalLabel">Add New Service</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <div class="mb-3">
                    <label for="title_en" class="form-label">Title (English)</label>
                    <input type="text" class="form-control" id="title_en" name="title_en" required>
                </div>
                <div class="mb-3">
                    <label for="title_tm" class="form-label">Title (Türkmençe)</label>
                    <input type="text" class="form-control" id="title_tm" name="title_tm" required>
                </div>
                <div class="mb-3">
                    <label for="title_ru" class="form-label">Title (Русский)</label>
                    <input type="text" class="form-control" id="title_ru" name="title_ru" required>
                </div>
                <div class="mb-3">
                    <label for="description_en" class="form-label">Description (English)</label>
                    <textarea class="form-control" id="description_en" name="description_en" rows="3" required></textarea>
                </div>
                <div class="mb-3">
                    <label for="description_tm" class="form-label">Description (Türkmençe)</label>
                    <textarea class="form-control" id="description_tm" name="description_tm" rows="3" required></textarea>
                </div>
                <div class="mb-3">
                    <label for="description_ru" class="form-label">Description (Русский)</label>
                    <textarea class="form-control" id="description_ru" name="description_ru" rows="3" required></textarea>
                </div>
                <div class="mb-3">
                    <label for="image" class="form-label">Image</label>
                    <input type="file" class="form-control" id="image" name="image" required>
                </div>
                <div class="mb-3">
                    <label for="service_brand" class="form-label">Brand Logo</label>
                    <select class="form-select" id="service_brand" name="service_brand">
                        <option value="">Default (Vision)</option>
                        <option value="media">Bent Media</option>
                        <option value="tech">Bent Tech</option>
                        <option value="vision">Bent Vision</option>
                    </select>
                </div>
              </div>
              <div class="modal-footer">
                <button type="submit" name="add_service" class="btn btn-primary">Add Service</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              </div>
            </form>
          </div>
        </div>

        <!-- Edit Service Modal -->
        <div class="modal fade" id="editServiceModal" tabindex="-1" aria-labelledby="editServiceModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" enctype="multipart/form-data" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editServiceModalLabel">Edit Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="service_id" id="edit_service_id">
                    <div class="mb-3">
                        <label for="edit_title_en" class="form-label">Title (English)</label>
                        <input type="text" class="form-control" id="edit_title_en" name="edit_title_en" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_title_tm" class="form-label">Title (Türkmençe)</label>
                        <input type="text" class="form-control" id="edit_title_tm" name="edit_title_tm" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_title_ru" class="form-label">Title (Русский)</label>
                        <input type="text" class="form-control" id="edit_title_ru" name="edit_title_ru" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description_en" class="form-label">Description (English)</label>
                        <textarea class="form-control" id="edit_description_en" name="edit_description_en" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description_tm" class="form-label">Description (Türkmençe)</label>
                        <textarea class="form-control" id="edit_description_tm" name="edit_description_tm" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description_ru" class="form-label">Description (Русский)</label>
                        <textarea class="form-control" id="edit_description_ru" name="edit_description_ru" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit_image" class="form-label">Image</label>
                        <input type="file" class="form-control" id="edit_image" name="edit_image">
                        <img id="edit_image_preview" src="" style="max-width:80px;max-height:40px;" class="mt-2">
                        <input type="hidden" name="old_image" id="edit_old_image">
                    </div>
                    <div class="mb-3">
                        <label for="edit_service_brand" class="form-label">Brand Logo</label>
                        <select class="form-select" id="edit_service_brand" name="edit_service_brand">
                            <option value="">Default (Vision)</option>
                            <option value="media">Bent Media</option>
                            <option value="tech">Bent Tech</option>
                            <option value="vision">Bent Vision</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="edit_service" class="btn btn-primary">Update Service</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
                </form>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var alert = document.getElementById('alert-message');
            if (alert) {
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 3000);
            }

            var editModal = document.getElementById('editServiceModal');
            editModal.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                document.getElementById('edit_service_id').value = button.getAttribute('data-id');
                document.getElementById('edit_title_en').value = button.getAttribute('data-title-en');
                document.getElementById('edit_title_tm').value = button.getAttribute('data-title-tm');
                document.getElementById('edit_title_ru').value = button.getAttribute('data-title-ru');
                document.getElementById('edit_description_en').value = button.getAttribute('data-description-en');
                document.getElementById('edit_description_tm').value = button.getAttribute('data-description-tm');
                document.getElementById('edit_description_ru').value = button.getAttribute('data-description-ru');
                document.getElementById('edit_old_image').value = button.getAttribute('data-image');
                document.getElementById('edit_image_preview').src = button.getAttribute('data-image');
                document.getElementById('edit_service_brand').value = button.getAttribute('data-brand') || '';
            });
        });
        </script>
    </div>
</div>
