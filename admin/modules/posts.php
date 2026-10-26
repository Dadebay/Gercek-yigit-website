<?php
require_once './includes/functions.php';
$message = '';

// Post ekleme
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_post'])) {
    $title_en = trim($_POST["title_en"] ?? '');
    $title_tm = trim($_POST["title_tm"] ?? '');
    $title_ru = trim($_POST["title_ru"] ?? '');
    $short_description_en = trim($_POST["short_description_en"] ?? '');
    $short_description_tm = trim($_POST["short_description_tm"] ?? '');
    $short_description_ru = trim($_POST["short_description_ru"] ?? '');
    $description_en = trim($_POST["description_en"] ?? '');
    $description_tm = trim($_POST["description_tm"] ?? '');
    $description_ru = trim($_POST["description_ru"] ?? '');
    $date = trim($_POST["date"] ?? date('Y-m-d'));
    $images = '';
    $target_dir = "uploads/posts/";
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
        $images = implode(',', $uploaded_images);
    }

    $stmt = $pdo->prepare("INSERT INTO posts (title_en, title_tm, title_ru, short_description_en, short_description_tm, short_description_ru, description_en, description_tm, description_ru, date, images) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $result = $stmt->execute([$title_en, $title_tm, $title_ru, $short_description_en, $short_description_tm, $short_description_ru, $description_en, $description_tm, $description_ru, $date, $images]);

    $message = $result
        ? "<div class='alert alert-success'>Post added successfully.</div>"
        : "<div class='alert alert-danger'>Error adding post.</div>";
}

// Post silme
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = intval($_GET['delete']);
    // Fetch images to delete from disk
    $fs = $pdo->prepare("SELECT images FROM posts WHERE id=?");
    $fs->execute([$id]);
    $frow = $fs->fetch();
    if ($frow && !empty($frow['images'])) {
        foreach (explode(',', $frow['images']) as $img) {
            $img = trim($img);
            if ($img && is_file($img)) { @unlink($img); }
        }
    }
    $stmt = $pdo->prepare("DELETE FROM posts WHERE id=?");
    $result = $stmt->execute([$id]);
    $message = $result
        ? "<div class='alert alert-success'>Post deleted.</div>"
        : "<div class='alert alert-danger'>Error deleting post.</div>";
}

// Post görünürlük toggle (gizle/göster)
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $fs = $pdo->prepare("SELECT visible FROM posts WHERE id=?");
    $fs->execute([$id]);
    $row = $fs->fetch();
    $new = 1;
    if ($row) { $new = (isset($row['visible']) && $row['visible'] == 1) ? 0 : 1; }
    $stmt = $pdo->prepare("UPDATE posts SET visible=? WHERE id=?");
    $result = $stmt->execute([$new, $id]);
    $message = $result
        ? "<div class='alert alert-success'>Post " . ($new ? "shown" : "hidden") . ".</div>"
        : "<div class='alert alert-danger'>Error updating visibility.</div>";
}

// Post düzenleme
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_post'])) {
    $id = intval($_POST['post_id']);
    // Mevcut resimleri DB'den al (silinecekleri tespit etmek için)
    $fs = $pdo->prepare("SELECT images FROM posts WHERE id=?");
    $fs->execute([$id]);
    $frow = $fs->fetch();
    $current_images = [];
    if ($frow && !empty($frow['images'])) {
        $current_images = array_filter(array_map('trim', explode(',', $frow['images'])));
    }
    $title_en = trim($_POST["edit_title_en"] ?? '');
    $title_tm = trim($_POST["edit_title_tm"] ?? '');
    $title_ru = trim($_POST["edit_title_ru"] ?? '');
    $short_description_en = trim($_POST["edit_short_description_en"] ?? '');
    $short_description_tm = trim($_POST["edit_short_description_tm"] ?? '');
    $short_description_ru = trim($_POST["edit_short_description_ru"] ?? '');
    $description_en = trim($_POST["edit_description_en"] ?? '');
    $description_tm = trim($_POST["edit_description_tm"] ?? '');
    $description_ru = trim($_POST["edit_description_ru"] ?? '');
    $date = trim($_POST["edit_date"] ?? date('Y-m-d'));
    $visible = isset($_POST['edit_visible']) ? 1 : 0;
    $old_images = $_POST["old_images"] ?? '';
    $target_dir = "uploads/posts/";
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
        $images = implode(',', $uploaded_images);
        if ($old_images) $images = $old_images . ',' . $images;
    } else {
        $images = $old_images;
    }

    // DB'de olan ama formda gönderilmeyen (kullanıcı silmiş) resimleri diskte kaldır
    $kept_images = array_filter(array_map('trim', explode(',', $images)));
    $removed = array_diff($current_images, $kept_images);
    foreach ($removed as $r) {
        if ($r && is_file($r)) { @unlink($r); }
    }

    $stmt = $pdo->prepare("UPDATE posts SET title_en=?, title_tm=?, title_ru=?, short_description_en=?, short_description_tm=?, short_description_ru=?, description_en=?, description_tm=?, description_ru=?, date=?, images=?, visible=? WHERE id=?");
    $result = $stmt->execute([$title_en, $title_tm, $title_ru, $short_description_en, $short_description_tm, $short_description_ru, $description_en, $description_tm, $description_ru, $date, $images, $visible, $id]);

    $message = $result
        ? "<div class='alert alert-success'>Post updated.</div>"
        : "<div class='alert alert-danger'>Error updating post.</div>";
}

// Mevcut postları çek
$posts = [];
$stmt = $pdo->query("SELECT * FROM posts ORDER BY id DESC");
while ($row = $stmt->fetch()) {
    $posts[] = $row;
}
?>

<div class="row">
    <div class="col-12">
        <?php if ($message): ?>
            <div id="alert-message" class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Add Post Button -->
        <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addPostModal">
            Add Post
        </button>

        <!-- Posts List -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Posts List</h5>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Title (EN)</th>
                            <th>Short Desc (EN)</th>
                            <th>Images</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($posts as $post): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($post['date']); ?></td>
                                <td><?php echo htmlspecialchars($post['title_en'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($post['short_description_en'] ?? ''); ?></td>
                                <td>
                                    <?php foreach (explode(',', $post['images']) as $img): ?>
                                        <?php if ($img): ?>
                                            <img src="<?php echo $img; ?>" style="max-width:60px;max-height:40px;" class="me-1 mb-1">
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </td>
                                <td>
                                    <?php if (!empty($post['visible']) && $post['visible'] == 1): ?>
                                        <span class="badge bg-success">Visible</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Hidden</span>
                                    <?php endif; ?>
                                    <a href="?toggle=<?php echo $post['id']; ?>" class="btn btn-sm btn-secondary ms-1">
                                        <?php echo (!empty($post['visible']) && $post['visible'] == 1) ? 'Hide' : 'Show'; ?>
                                    </a>
                                    <a href="#" class="btn btn-sm btn-warning ms-1"
                                       data-bs-toggle="modal"
                                       data-bs-target="#editPostModal"
                                       data-id="<?php echo $post['id']; ?>"
                                       data-title-en="<?php echo htmlspecialchars($post['title_en'] ?? ''); ?>"
                                       data-title-tm="<?php echo htmlspecialchars($post['title_tm'] ?? ''); ?>"
                                       data-title-ru="<?php echo htmlspecialchars($post['title_ru'] ?? ''); ?>"
                                       data-short-description-en="<?php echo htmlspecialchars($post['short_description_en'] ?? ''); ?>"
                                       data-short-description-tm="<?php echo htmlspecialchars($post['short_description_tm'] ?? ''); ?>"
                                       data-short-description-ru="<?php echo htmlspecialchars($post['short_description_ru'] ?? ''); ?>"
                                       data-description-en="<?php echo htmlspecialchars($post['description_en'] ?? ''); ?>"
                                       data-description-tm="<?php echo htmlspecialchars($post['description_tm'] ?? ''); ?>"
                                       data-description-ru="<?php echo htmlspecialchars($post['description_ru'] ?? ''); ?>"
                                       data-date="<?php echo $post['date']; ?>"
                                       data-images="<?php echo $post['images']; ?>"
                                       data-visible="<?php echo isset($post['visible']) ? $post['visible'] : 1; ?>"
                                    >Edit</a>
                                    <a href="?delete=<?php echo $post['id']; ?>" class="btn btn-sm btn-danger ms-1" onclick="return confirm('Delete this post?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add Post Modal -->
        <div class="modal fade" id="addPostModal" tabindex="-1" aria-labelledby="addPostModalLabel" aria-hidden="true">
          <div class="modal-dialog">
            <form method="POST" enctype="multipart/form-data" class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="addPostModalLabel">Add New Post</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Title (EN)</label>
                    <input type="text" class="form-control" name="title_en" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Title (TM)</label>
                    <input type="text" class="form-control" name="title_tm">
                </div>
                <div class="mb-3">
                    <label class="form-label">Title (RU)</label>
                    <input type="text" class="form-control" name="title_ru">
                </div>
                <div class="mb-3">
                    <label class="form-label">Short Description (EN)</label>
                    <input type="text" class="form-control" name="short_description_en" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Short Description (TM)</label>
                    <input type="text" class="form-control" name="short_description_tm">
                </div>
                <div class="mb-3">
                    <label class="form-label">Short Description (RU)</label>
                    <input type="text" class="form-control" name="short_description_ru">
                </div>
                <div class="mb-3">
                    <label class="form-label">Description (EN)</label>
                    <textarea class="form-control" name="description_en" rows="4" required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description (TM)</label>
                    <textarea class="form-control" name="description_tm" rows="4"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description (RU)</label>
                    <textarea class="form-control" name="description_ru" rows="4"></textarea>
                </div>
                <div class="mb-3">
                    <label for="date" class="form-label">Date</label>
                    <input type="date" class="form-control" id="date" name="date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="images" class="form-label">Images (Multiple)</label>
                    <input type="file" class="form-control" id="images" name="images[]" multiple required>
                </div>
              </div>
              <div class="modal-footer">
                <button type="submit" name="add_post" class="btn btn-primary">Add Post</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              </div>
            </form>
          </div>
        </div>

        <!-- Edit Post Modal -->
        <div class="modal fade" id="editPostModal" tabindex="-1" aria-labelledby="editPostModalLabel" aria-hidden="true">
          <div class="modal-dialog">
            <form method="POST" enctype="multipart/form-data" class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="editPostModalLabel">Edit Post</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <input type="hidden" name="post_id" id="edit_post_id">
                <div class="mb-3">
                    <label class="form-label">Title (EN)</label>
                    <input type="text" class="form-control" id="edit_title_en" name="edit_title_en" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Title (TM)</label>
                    <input type="text" class="form-control" id="edit_title_tm" name="edit_title_tm">
                </div>
                <div class="mb-3">
                    <label class="form-label">Title (RU)</label>
                    <input type="text" class="form-control" id="edit_title_ru" name="edit_title_ru">
                </div>
                <div class="mb-3">
                    <label class="form-label">Short Description (EN)</label>
                    <input type="text" class="form-control" id="edit_short_description_en" name="edit_short_description_en" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Short Description (TM)</label>
                    <input type="text" class="form-control" id="edit_short_description_tm" name="edit_short_description_tm">
                </div>
                <div class="mb-3">
                    <label class="form-label">Short Description (RU)</label>
                    <input type="text" class="form-control" id="edit_short_description_ru" name="edit_short_description_ru">
                </div>
                <div class="mb-3">
                    <label class="form-label">Description (EN)</label>
                    <textarea class="form-control" id="edit_description_en" name="edit_description_en" rows="4" required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description (TM)</label>
                    <textarea class="form-control" id="edit_description_tm" name="edit_description_tm" rows="4"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description (RU)</label>
                    <textarea class="form-control" id="edit_description_ru" name="edit_description_ru" rows="4"></textarea>
                </div>
                <div class="mb-3">
                    <label for="edit_date" class="form-label">Date</label>
                    <input type="date" class="form-control" id="edit_date" name="edit_date" required>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="edit_visible" name="edit_visible" value="1">
                    <label class="form-check-label" for="edit_visible">Visible on site</label>
                </div>
                <div class="mb-3">
                    <label for="edit_images" class="form-label">Add Images (Multiple)</label>
                    <input type="file" class="form-control" id="edit_images" name="edit_images[]" multiple>
                    <div id="edit_images_preview" class="mt-2"></div>
                    <input type="hidden" name="old_images" id="edit_old_images">
                </div>
              </div>
              <div class="modal-footer">
                <button type="submit" name="edit_post" class="btn btn-primary">Update Post</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              </div>
            </form>
          </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var editModal = document.getElementById('editPostModal');
    editModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        document.getElementById('edit_post_id').value = button.getAttribute('data-id');
        document.getElementById('edit_title_en').value = button.getAttribute('data-title-en') || '';
        document.getElementById('edit_title_tm').value = button.getAttribute('data-title-tm') || '';
        document.getElementById('edit_title_ru').value = button.getAttribute('data-title-ru') || '';
        document.getElementById('edit_short_description_en').value = button.getAttribute('data-short-description-en') || '';
        document.getElementById('edit_short_description_tm').value = button.getAttribute('data-short-description-tm') || '';
        document.getElementById('edit_short_description_ru').value = button.getAttribute('data-short-description-ru') || '';
        document.getElementById('edit_description_en').value = button.getAttribute('data-description-en') || '';
        document.getElementById('edit_description_tm').value = button.getAttribute('data-description-tm') || '';
        document.getElementById('edit_description_ru').value = button.getAttribute('data-description-ru') || '';
        document.getElementById('edit_date').value = button.getAttribute('data-date');
        document.getElementById('edit_old_images').value = button.getAttribute('data-images');
        // visible checkbox
        var vis = button.getAttribute('data-visible');
        document.getElementById('edit_visible').checked = (vis == '1' || vis === '1');
        // Resim önizleme (her resim için silme butonu ile)
        var imagesPreview = document.getElementById('edit_images_preview');
        imagesPreview.innerHTML = '';
        var images = button.getAttribute('data-images');
        if (images) {
            images.split(',').forEach(function(img) {
                if (img) {
                    var safe = img;
                    imagesPreview.innerHTML += '<div class="existing-image d-inline-block me-1 mb-1" data-img="' + safe + '">'
                        + '<img src="' + safe + '" style="max-width:60px;max-height:40px;" class="border">'
                        + '<button type="button" class="btn btn-sm btn-danger remove-image-btn" style="position:relative;top:-8px;left:-8px;">&times;</button>'
                        + '</div>';
                }
            });
            // hidden input'u güncelle
            var kept = [];
            imagesPreview.querySelectorAll('.existing-image').forEach(function(el){ kept.push(el.getAttribute('data-img')); });
            document.getElementById('edit_old_images').value = kept.join(',');
        }
    });
    // Edit modal içindeki resim silme butonlarını yakala ve hidden input'u güncelle
    var imagesPreviewGlobal = document.getElementById('edit_images_preview');
    if (imagesPreviewGlobal) {
        imagesPreviewGlobal.addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('remove-image-btn')) {
                var container = e.target.closest('.existing-image');
                if (!container) return;
                container.remove();
                var kept = [];
                imagesPreviewGlobal.querySelectorAll('.existing-image').forEach(function(el){ kept.push(el.getAttribute('data-img')); });
                document.getElementById('edit_old_images').value = kept.join(',');
            }
        });
    }

    var alert = document.getElementById('alert-message');
    if (alert) {
        setTimeout(function() {
            alert.style.display = 'none';
        }, 3000);
    }
});
</script>
