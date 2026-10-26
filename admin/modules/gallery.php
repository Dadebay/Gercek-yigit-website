<?php
require_once './includes/functions.php';
$message = '';

// Gallery item ekleme
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_gallery'])) {
    $title_en = trim($_POST["title_en"] ?? '');
    $title_tm = trim($_POST["title_tm"] ?? '');
    $title_ru = trim($_POST["title_ru"] ?? '');
    $description_en = trim($_POST["description_en"] ?? '');
    $description_tm = trim($_POST["description_tm"] ?? '');
    $description_ru = trim($_POST["description_ru"] ?? '');
    $images = '';
    $target_dir = "uploads/gallery/";
    $uploaded_images = [];

    if (!empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['name'] as $key => $name) {
            if ($_FILES['images']['error'][$key] == UPLOAD_ERR_OK) {
                if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
                $filename = uniqid() . "_" . basename($name);
                $target_file = $target_dir . $filename;
                move_uploaded_file($_FILES['images']['tmp_name'][$key], $target_file);
                $uploaded_images[] = $target_file;
            }
        }
        $images = json_encode($uploaded_images);
    }

    $stmt = $pdo->prepare("INSERT INTO gallery (title_en, title_tm, title_ru, description_en, description_tm, description_ru, images) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $result = $stmt->execute([$title_en, $title_tm, $title_ru, $description_en, $description_tm, $description_ru, $images]);

    $message = $result
        ? "<div class='alert alert-success'>Gallery item added successfully.</div>"
        : "<div class='alert alert-danger'>Error adding gallery item.</div>";
}

// Gallery resmi silme (tekil)
if (isset($_GET['delete_image']) && isset($_GET['gallery_id'])) {
    $gallery_id = intval($_GET['gallery_id']);
    $image_to_delete = $_GET['delete_image'];

    // Gallery item çek
    $stmt = $pdo->prepare("SELECT images FROM gallery WHERE id=?");
    $stmt->execute([$gallery_id]);
    $item = $stmt->fetch();

    if ($item) {
        $current_images = json_decode($item['images'], true) ?: [];
        $new_images = [];
        foreach ($current_images as $img) {
            if (trim($img) === $image_to_delete) {
                // Dosyayı sunucudan sil
                if (file_exists($img)) {
                    unlink($img);
                }
            } else {
                $new_images[] = trim($img);
            }
        }

        $updated_images_str = json_encode($new_images);
        $stmt = $pdo->prepare("UPDATE gallery SET images=? WHERE id=?");
        $result = $stmt->execute([$updated_images_str, $gallery_id]);

        $message = $result
            ? "<div class='alert alert-success'>Image deleted.</div>"
            : "<div class='alert alert-danger'>Error deleting image.</div>";
    }
}

// Gallery item silme
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = intval($_GET['delete']);
    // Delete all images from disk
    $gs = $pdo->prepare("SELECT images FROM gallery WHERE id=?");
    $gs->execute([$id]);
    $grow = $gs->fetch();
    if ($grow && !empty($grow['images'])) {
        $imgs = json_decode($grow['images'], true) ?: [];
        foreach ($imgs as $img) {
            $img = trim($img);
            if ($img && is_file($img)) { @unlink($img); }
        }
    }
    $stmt = $pdo->prepare("DELETE FROM gallery WHERE id=?");
    $result = $stmt->execute([$id]);
    $message = $result
        ? "<div class='alert alert-success'>Gallery item deleted.</div>"
        : "<div class='alert alert-danger'>Error deleting gallery item.</div>";
}

// Gallery item düzenleme
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_gallery'])) {
    $id = intval($_POST['gallery_id']);
    $title_en = trim($_POST["edit_title_en"] ?? '');
    $title_tm = trim($_POST["edit_title_tm"] ?? '');
    $title_ru = trim($_POST["edit_title_ru"] ?? '');
    $description_en = trim($_POST["edit_description_en"] ?? '');
    $description_tm = trim($_POST["edit_description_tm"] ?? '');
    $description_ru = trim($_POST["edit_description_ru"] ?? '');
    $old_images = json_decode($_POST["old_images"] ?? '[]', true) ?: [];
    $target_dir = "uploads/gallery/";
    $uploaded_images = [];

    if (!empty($_FILES['edit_images']['name'][0])) {
        foreach ($_FILES['edit_images']['name'] as $key => $name) {
            if ($_FILES['edit_images']['error'][$key] == UPLOAD_ERR_OK) {
                if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
                $filename = uniqid() . "_" . basename($name);
                $target_file = $target_dir . $filename;
                move_uploaded_file($_FILES['edit_images']['tmp_name'][$key], $target_file);
                $uploaded_images[] = $target_file;
            }
        }
        $images = array_merge($old_images, $uploaded_images);
    } else {
        $images = $old_images;
    }

    $images_json = json_encode($images);
    $stmt = $pdo->prepare("UPDATE gallery SET title_en=?, title_tm=?, title_ru=?, description_en=?, description_tm=?, description_ru=?, images=? WHERE id=?");
    $result = $stmt->execute([$title_en, $title_tm, $title_ru, $description_en, $description_tm, $description_ru, $images_json, $id]);

    $message = $result
        ? "<div class='alert alert-success'>Gallery item updated.</div>"
        : "<div class='alert alert-danger'>Error updating gallery item.</div>";
}

// Mevcut gallery itemleri çek
$gallery_items = [];
$stmt = $pdo->query("SELECT * FROM gallery ORDER BY id DESC");
while ($row = $stmt->fetch()) {
    $gallery_items[] = $row;
}

// Düzenlenecek gallery item çek
$edit_item = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM gallery WHERE id=?");
    $stmt->execute([$id]);
    $edit_item = $stmt->fetch();
}
?>

<div class="row">
    <div class="col-12">
        <?php if ($message): ?>
            <div id="alert-message">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Add Gallery Item Button -->
        <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addGalleryModal">
            Add Gallery Item
        </button>

        <!-- Gallery Items List -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Gallery Items List</h5>
            </div>
            <div class="card-body">
                <table id="galleryTable" class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Title (EN)</th>
                            <th>Images</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($gallery_items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['title_en']); ?></td>
                                <td>
                                    <?php
                                        $imgs = json_decode($item['images'] ?? '[]', true) ?: [];
                                        $imgCount = count($imgs);
                                    ?>
                                    <span class="badge bg-primary">Images: <?php echo $imgCount; ?></span>
                                </td>
                                <td>
                                    <a href="#" class="btn btn-sm btn-warning"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editGalleryModal"
                                    data-id="<?php echo $item['id']; ?>"
                                    data-title-en="<?php echo htmlspecialchars($item['title_en']); ?>"
                                    data-title-tm="<?php echo htmlspecialchars($item['title_tm']); ?>"
                                    data-title-ru="<?php echo htmlspecialchars($item['title_ru']); ?>"
                                    data-description-en="<?php echo htmlspecialchars($item['description_en']); ?>"
                                    data-description-tm="<?php echo htmlspecialchars($item['description_tm']); ?>"
                                    data-description-ru="<?php echo htmlspecialchars($item['description_ru']); ?>"
                                    data-images="<?php echo htmlspecialchars($item['images']); ?>"
                                    >Edit</a>
                                    <a href="?delete=<?php echo $item['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this gallery item?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add Gallery Modal -->
        <div class="modal fade" id="addGalleryModal" tabindex="-1" aria-labelledby="addGalleryModalLabel" aria-hidden="true">
          <div class="modal-dialog">
            <form method="POST" enctype="multipart/form-data" class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="addGalleryModalLabel">Add New Gallery Item</h5>
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
                    <textarea class="form-control" id="description_en" name="description_en" rows="3"></textarea>
                </div>
                <div class="mb-3">
                    <label for="description_tm" class="form-label">Description (Türkmençe)</label>
                    <textarea class="form-control" id="description_tm" name="description_tm" rows="3"></textarea>
                </div>
                <div class="mb-3">
                    <label for="description_ru" class="form-label">Description (Русский)</label>
                    <textarea class="form-control" id="description_ru" name="description_ru" rows="3"></textarea>
                </div>
                <div class="mb-3">
                    <label for="images" class="form-label">Images (Multiple)</label>
                    <input type="file" class="form-control" id="images" name="images[]" multiple required>
                </div>
              </div>
              <div class="modal-footer">
                <button type="submit" name="add_gallery" class="btn btn-primary">Add Gallery Item</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              </div>
            </form>
          </div>
        </div>

        <!-- Edit Gallery Modal -->
        <div class="modal fade" id="editGalleryModal" tabindex="-1" aria-labelledby="editGalleryModalLabel" aria-hidden="true">
          <div class="modal-dialog">
            <form method="POST" enctype="multipart/form-data" class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="editGalleryModalLabel">Edit Gallery Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <input type="hidden" name="gallery_id" id="edit_gallery_id">
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
                    <textarea class="form-control" id="edit_description_en" name="edit_description_en" rows="3"></textarea>
                </div>
                <div class="mb-3">
                    <label for="edit_description_tm" class="form-label">Description (Türkmençe)</label>
                    <textarea class="form-control" id="edit_description_tm" name="edit_description_tm" rows="3"></textarea>
                </div>
                <div class="mb-3">
                    <label for="edit_description_ru" class="form-label">Description (Русский)</label>
                    <textarea class="form-control" id="edit_description_ru" name="edit_description_ru" rows="3"></textarea>
                </div>
                <div class="mb-3">
                    <label for="edit_images" class="form-label">Add Images (Multiple)</label>
                    <input type="file" class="form-control" id="edit_images" name="edit_images[]" multiple>
                    <div id="edit_images_preview" class="mt-2"></div>
                    <input type="hidden" name="old_images" id="edit_old_images">
                </div>
              </div>
              <div class="modal-footer">
                <button type="submit" name="edit_gallery" class="btn btn-primary">Update Gallery Item</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              </div>
            </form>
          </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var editModal = document.getElementById('editGalleryModal');
    editModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        document.getElementById('edit_gallery_id').value = button.getAttribute('data-id');
        document.getElementById('edit_title_en').value = button.getAttribute('data-title-en');
        document.getElementById('edit_title_tm').value = button.getAttribute('data-title-tm');
        document.getElementById('edit_title_ru').value = button.getAttribute('data-title-ru');
        document.getElementById('edit_description_en').value = button.getAttribute('data-description-en');
        document.getElementById('edit_description_tm').value = button.getAttribute('data-description-tm');
        document.getElementById('edit_description_ru').value = button.getAttribute('data-description-ru');

        var images = button.getAttribute('data-images');
        document.getElementById('edit_old_images').value = images;
        var imgPreview = document.getElementById('edit_images_preview');
        imgPreview.innerHTML = '';
        var projectId = button.getAttribute('data-id');
        if (images) {
            JSON.parse(images).forEach(function(img) {
                if (img) {
                    var div = document.createElement('div');
                    div.className = 'd-inline-block position-relative me-2 mb-2';
                    div.innerHTML = `
                        <img src="${img}" style="max-width:100px;max-height:80px;" class="border rounded">
                        <a href="index.php?module=gallery&delete_image=${encodeURIComponent(img)}&gallery_id=${projectId}"
                           class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger text-white text-decoration-none"
                           onclick="return confirm('Delete this image?');"
                           style="cursor:pointer;">
                            &times;
                        </a>
                    `;
                    imgPreview.appendChild(div);
                }
            });
        }
    });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var alert = document.getElementById('alert-message');
    if (alert) {
        setTimeout(function() {
            alert.style.display = 'none';
        }, 3000);
    }
});
</script>
<script>
$(document).ready(function() {
    $('#galleryTable').DataTable({
        "paging": true,
        "searching": true,
        "ordering": true,
        "info": true,
        "lengthChange": true
    });
});
</script>