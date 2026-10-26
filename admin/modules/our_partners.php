<?php
require_once './includes/functions.php';
$message = '';

// Partner ekleme
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_partner'])) {
    $title_en = trim($_POST["title_en"] ?? '');
    $title_tm = trim($_POST["title_tm"] ?? '');
    $title_ru = trim($_POST["title_ru"] ?? '');
    $image = '';
    $target_dir = "uploads/partners/";

    if (isset($_FILES["image"]) && $_FILES["image"]["error"] == UPLOAD_ERR_OK) {
        $allowed = ['jpg','jpeg','png','gif','webp'];
        $saved = save_uploaded_file($_FILES["image"], $target_dir, $allowed, 5*1024*1024);
        if ($saved) { $image = $saved; }
    }

    $stmt = $pdo->prepare("INSERT INTO our_partners (title_en, title_tm, title_ru, image) VALUES (?, ?, ?, ?)");
    $result = $stmt->execute([$title_en, $title_tm, $title_ru, $image]);

    $message = $result
        ? "<div class='alert alert-success'>Partner added successfully.</div>"
        : "<div class='alert alert-danger'>Error adding partner.</div>";
}

// Partner silme
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM our_partners WHERE id=?");
    $result = $stmt->execute([$id]);
    $message = $result
        ? "<div class='alert alert-success'>Partner deleted.</div>"
        : "<div class='alert alert-danger'>Error deleting partner.</div>";
}

// Partner düzenleme
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_partner'])) {
    $id = intval($_POST['partner_id']);
    $title_en = trim($_POST["edit_title_en"] ?? '');
    $title_tm = trim($_POST["edit_title_tm"] ?? '');
    $title_ru = trim($_POST["edit_title_ru"] ?? '');
    $image = $_POST["old_image"] ?? '';
    $target_dir = "uploads/partners/";

    if (isset($_FILES["edit_image"]) && $_FILES["edit_image"]["error"] == UPLOAD_ERR_OK) {
        $allowed = ['jpg','jpeg','png','gif','webp'];
        $saved = save_uploaded_file($_FILES["edit_image"], $target_dir, $allowed, 5*1024*1024);
        if ($saved) { $image = $saved; }
    }

    $stmt = $pdo->prepare("UPDATE our_partners SET title_en=?, title_tm=?, title_ru=?, image=? WHERE id=?");
    $result = $stmt->execute([$title_en, $title_tm, $title_ru, $image, $id]);

    $message = $result
        ? "<div class='alert alert-success'>Partner updated.</div>"
        : "<div class='alert alert-danger'>Error updating partner.</div>";
}

// Mevcut partnerleri çek
$partners = [];
$stmt = $pdo->query("SELECT * FROM our_partners ORDER BY id DESC");
while ($row = $stmt->fetch()) {
    $partners[] = $row;
}
?>

<div class="row">
    <div class="col-12">
        <?php if ($message): ?>
            <div id="alert-message" class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Add Partner Button -->
        <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addPartnerModal">
            Add Partner
        </button>

        <!-- Partners List -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Partners List (Images size must be : 400px-110px)</h5>
            </div>
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
                        <?php foreach ($partners as $partner): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($partner['image'])): ?>
                                        <img src="<?php echo $partner['image']; ?>" style="max-width:80px;max-height:40px;">
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($partner['title_en']); ?></td>
                                <td><?php echo htmlspecialchars($partner['title_tm']); ?></td>
                                <td><?php echo htmlspecialchars($partner['title_ru']); ?></td>
                                <td>
                                    <a href="#" class="btn btn-sm btn-warning"
                                       data-bs-toggle="modal"
                                       data-bs-target="#editPartnerModal"
                                       data-id="<?php echo $partner['id']; ?>"
                                       data-title-en="<?php echo htmlspecialchars($partner['title_en']); ?>"
                                       data-title-tm="<?php echo htmlspecialchars($partner['title_tm']); ?>"
                                       data-title-ru="<?php echo htmlspecialchars($partner['title_ru']); ?>"
                                       data-image="<?php echo $partner['image']; ?>"
                                    >Edit</a>
                                    <a href="?delete=<?php echo $partner['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this partner?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add Partner Modal -->
        <div class="modal fade" id="addPartnerModal" tabindex="-1" aria-labelledby="addPartnerModalLabel" aria-hidden="true">
          <div class="modal-dialog">
            <form method="POST" enctype="multipart/form-data" class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="addPartnerModalLabel">Add New Partner</h5>
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
                    <label for="image" class="form-label">Image</label>
                    <input type="file" class="form-control" id="image" name="image" required>
                </div>
              </div>
              <div class="modal-footer">
                <button type="submit" name="add_partner" class="btn btn-primary">Add Partner</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              </div>
            </form>
          </div>
        </div>

      <!-- Edit Partner Modal -->
      <div class="modal fade" id="editPartnerModal" tabindex="-1" aria-labelledby="editPartnerModalLabel" aria-hidden="true">
          <div class="modal-dialog">
            <form method="POST" enctype="multipart/form-data" class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="editPartnerModalLabel">Edit Partner</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <input type="hidden" name="partner_id" id="edit_partner_id">
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
                    <label for="edit_image" class="form-label">Image</label>
                    <input type="file" class="form-control" id="edit_image" name="edit_image">
                    <img id="edit_image_preview" src="" style="max-width:80px;max-height:40px;" class="mt-2">
                    <input type="hidden" name="old_image" id="edit_old_image">
                </div>
              </div>
              <div class="modal-footer">
                <button type="submit" name="edit_partner" class="btn btn-primary">Update Partner</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              </div>
            </form>
          </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var editModal = document.getElementById('editPartnerModal');
    editModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        document.getElementById('edit_partner_id').value = button.getAttribute('data-id');
        document.getElementById('edit_title_en').value = button.getAttribute('data-title-en');
        document.getElementById('edit_title_tm').value = button.getAttribute('data-title-tm');
        document.getElementById('edit_title_ru').value = button.getAttribute('data-title-ru');
        document.getElementById('edit_old_image').value = button.getAttribute('data-image');
        document.getElementById('edit_image_preview').src = button.getAttribute('data-image');
    });

    var alert = document.getElementById('alert-message');
    if (alert) {
        setTimeout(function() {
            alert.style.display = 'none';
        }, 3000);
    }
});
</script>