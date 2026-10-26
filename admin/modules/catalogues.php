<?php
require_once __DIR__ . '/../includes/header.php';

// Ensure upload directory exists
$uploadDir = __DIR__ . '/../uploads/catalogues';
if (!is_dir($uploadDir)) {
  @mkdir($uploadDir, 0775, true);
}

// Ensure catalogues table exists
// Ensure catalogues table exists and has image_path column
$pdo->exec("CREATE TABLE IF NOT EXISTS catalogues (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title_en VARCHAR(255) NOT NULL,
  title_tm VARCHAR(255) DEFAULT NULL,
  title_ru VARCHAR(255) DEFAULT NULL,
  description_en TEXT DEFAULT NULL,
  description_tm TEXT DEFAULT NULL,
  description_ru TEXT DEFAULT NULL,
  pdf_path VARCHAR(512) NOT NULL,
  image_path VARCHAR(512) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");
// Backfill column if table existed previously without image_path
try {
  $col = $pdo->query("SHOW COLUMNS FROM catalogues LIKE 'image_path'")->fetch();
  if (!$col) {
    $pdo->exec("ALTER TABLE catalogues ADD COLUMN image_path VARCHAR(512) DEFAULT NULL AFTER pdf_path");
  }
} catch (Throwable $e) {
  // ignore
}

$alert = '';

// Handle create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
  $title_en = trim($_POST['title_en'] ?? '');
  $title_tm = trim($_POST['title_tm'] ?? '');
  $title_ru = trim($_POST['title_ru'] ?? '');
  $description_en = trim($_POST['description_en'] ?? '');
  $description_tm = trim($_POST['description_tm'] ?? '');
  $description_ru = trim($_POST['description_ru'] ?? '');

  $pdf_path = '';
  $image_path = '';
  if (!empty($_FILES['pdf']['name'])) {
    $fname = $_FILES['pdf']['name'];
    $ext = strtolower(pathinfo($fname, PATHINFO_EXTENSION));
    if ($ext !== 'pdf') {
      $alert = '<div class="alert alert-warning">Only PDF files are allowed.</div>';
    } else {
      $safeBase = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', pathinfo($fname, PATHINFO_FILENAME));
      $newName = $safeBase . '_' . time() . '.pdf';
      $dest = $uploadDir . '/' . $newName;
      if (move_uploaded_file($_FILES['pdf']['tmp_name'], $dest)) {
        $pdf_path = 'uploads/catalogues/' . $newName;
      } else {
        $alert = '<div class="alert alert-danger">Failed to upload PDF.</div>';
      }
    }
  }

  // Optional cover image upload
  if (!$alert && !empty($_FILES['image']['name'])) {
    $imgName = $_FILES['image']['name'];
    $imgExt = strtolower(pathinfo($imgName, PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','webp'];
    if (!in_array($imgExt, $allowed, true)) {
      $alert = '<div class="alert alert-warning">Image must be JPG, PNG, or WEBP.</div>';
    } else {
      $safeImgBase = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', pathinfo($imgName, PATHINFO_FILENAME));
      $imgNew = $safeImgBase . '_' . time() . '.' . $imgExt;
      $imgDest = $uploadDir . '/' . $imgNew;
      if (move_uploaded_file($_FILES['image']['tmp_name'], $imgDest)) {
        // Store relative path (same scheme as pdf_path)
        $image_path = 'uploads/catalogues/' . $imgNew;
      } else {
        $alert = '<div class="alert alert-danger">Failed to upload image.</div>';
      }
    }
  }

  if (!$alert) {
    if ($title_en && $pdf_path) {
      $stmt = $pdo->prepare("INSERT INTO catalogues (title_en,title_tm,title_ru,description_en,description_tm,description_ru,pdf_path,image_path) VALUES (?,?,?,?,?,?,?,?)");
      $stmt->execute([$title_en,$title_tm,$title_ru,$description_en,$description_tm,$description_ru,$pdf_path,$image_path ?: null]);
      $alert = '<div class="alert alert-success">Catalogue saved.</div>';
    } else {
      $alert = '<div class="alert alert-warning">Please provide English title and a PDF file.</div>';
    }
  }
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
  $id = (int)($_POST['id'] ?? 0);
  if ($id > 0) {
    $rowQ = $pdo->prepare("SELECT * FROM catalogues WHERE id=?");
    $rowQ->execute([$id]);
    $row = $rowQ->fetch(PDO::FETCH_ASSOC);
    if ($row) {
      $title_en = trim($_POST['title_en'] ?? $row['title_en']);
      $title_tm = trim($_POST['title_tm'] ?? $row['title_tm']);
      $title_ru = trim($_POST['title_ru'] ?? $row['title_ru']);
      $description_en = trim($_POST['description_en'] ?? $row['description_en']);
      $description_tm = trim($_POST['description_tm'] ?? $row['description_tm']);
      $description_ru = trim($_POST['description_ru'] ?? $row['description_ru']);

      $pdf_path = $row['pdf_path'];
      $image_path = $row['image_path'];

      // Optional new PDF
      if (!empty($_FILES['pdf']['name'])) {
        $fname = $_FILES['pdf']['name'];
        $ext = strtolower(pathinfo($fname, PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
          $alert = '<div class="alert alert-warning">Only PDF files are allowed.</div>';
        } else {
          $safeBase = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', pathinfo($fname, PATHINFO_FILENAME));
          $newName = $safeBase . '_' . time() . '.pdf';
          $dest = $uploadDir . '/' . $newName;
          if (move_uploaded_file($_FILES['pdf']['tmp_name'], $dest)) {
            // delete old PDF
            if (!empty($pdf_path)) {
              $oldFull = __DIR__ . '/../' . ltrim($pdf_path, '/');
              @unlink($oldFull);
            }
            $pdf_path = 'uploads/catalogues/' . $newName;
          } else {
            $alert = '<div class="alert alert-danger">Failed to upload PDF.</div>';
          }
        }
      }

      // Optional new cover image
      if (!$alert && !empty($_FILES['image']['name'])) {
        $imgName = $_FILES['image']['name'];
        $imgExt = strtolower(pathinfo($imgName, PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp'];
        if (!in_array($imgExt, $allowed, true)) {
          $alert = '<div class="alert alert-warning">Image must be JPG, PNG, or WEBP.</div>';
        } else {
          $safeImgBase = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', pathinfo($imgName, PATHINFO_FILENAME));
          $imgNew = $safeImgBase . '_' . time() . '.' . $imgExt;
          $imgDest = $uploadDir . '/' . $imgNew;
          if (move_uploaded_file($_FILES['image']['tmp_name'], $imgDest)) {
            // delete old image
            if (!empty($image_path)) {
              $oldImgFull = __DIR__ . '/../' . ltrim($image_path, '/');
              @unlink($oldImgFull);
            }
            $image_path = 'uploads/catalogues/' . $imgNew;
          } else {
            $alert = '<div class="alert alert-danger">Failed to upload image.</div>';
          }
        }
      }

      if (!$alert) {
        $upd = $pdo->prepare("UPDATE catalogues SET title_en=?, title_tm=?, title_ru=?, description_en=?, description_tm=?, description_ru=?, pdf_path=?, image_path=? WHERE id=?");
        $upd->execute([$title_en,$title_tm,$title_ru,$description_en,$description_tm,$description_ru,$pdf_path,$image_path,$id]);
        $alert = '<div class="alert alert-success">Catalogue updated.</div>';
      }
    }
  }
}

// Handle delete
if (isset($_GET['delete'])) {
  $id = (int)$_GET['delete'];
  if ($id > 0) {
    $row = $pdo->prepare("SELECT pdf_path, image_path FROM catalogues WHERE id=?");
    $row->execute([$id]);
    $r = $row->fetch();
    if ($r) {
      $pdf = $r['pdf_path'];
      $full = __DIR__ . '/../' . ltrim($pdf, '/');
      @unlink($full);
      if (!empty($r['image_path'])) {
        $imgFull = __DIR__ . '/../' . ltrim($r['image_path'], '/');
        @unlink($imgFull);
      }
      $pdo->prepare("DELETE FROM catalogues WHERE id=?")->execute([$id]);
      $alert = '<div class="alert alert-success">Catalogue deleted.</div>';
    }
  }
}

$items = $pdo->query("SELECT * FROM catalogues ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card shadow-sm">
  <div class="card-body">
    <h5 class="card-title mb-2 d-flex align-items-center"><i class="bi bi-journal-richtext me-2"></i> Catalogues</h5>
    <p class="text-muted mb-3">Upload PDF catalogues with multilingual titles and descriptions. Optionally add a cover image to represent the catalogue on the website.</p>
    <?php echo $alert; ?>

    <form method="post" enctype="multipart/form-data" class="mb-4">
      <input type="hidden" name="action" value="create">
      <div class="row g-3">
        <div class="col-12 col-md-6">
          <label class="form-label">Title (EN) *</label>
          <input type="text" name="title_en" class="form-control" required>
        </div>
        <div class="col-12 col-md-6">
          <label class="form-label">Title (TM)</label>
          <input type="text" name="title_tm" class="form-control">
        </div>
        <div class="col-12 col-md-6">
          <label class="form-label">Title (RU)</label>
          <input type="text" name="title_ru" class="form-control">
        </div>
        <div class="col-12 col-md-4">
          <label class="form-label">PDF File *</label>
          <input type="file" name="pdf" accept="application/pdf" class="form-control" required>
        </div>
        <div class="col-12 col-md-4">
          <label class="form-label">Cover Image (optional)</label>
          <input type="file" name="image" accept="image/png,image/jpeg,image/webp" class="form-control">
        </div>
        <div class="col-12">
          <label class="form-label">Description (EN)</label>
          <textarea name="description_en" class="form-control" rows="3"></textarea>
        </div>
        <div class="col-12">
          <label class="form-label">Description (TM)</label>
          <textarea name="description_tm" class="form-control" rows="3"></textarea>
        </div>
        <div class="col-12">
          <label class="form-label">Description (RU)</label>
          <textarea name="description_ru" class="form-control" rows="3"></textarea>
        </div>
        <div class="col-12 d-flex justify-content-end">
          <button type="submit" class="btn btn-success"><i class="bi bi-plus-circle"></i> Add Catalogue</button>
        </div>
      </div>
    </form>

    <div class="table-responsive">
      <table class="table table-striped align-middle" id="tblCatalogues">
        <thead>
          <tr>
            <th>ID</th>
            <th>Title (EN)</th>
            <th>PDF</th>
            <th>Image</th>
            <th>Created</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $it): ?>
            <tr>
              <td><?php echo (int)$it['id']; ?></td>
              <td><?php echo htmlspecialchars($it['title_en']); ?></td>
              <td><a href="<?php echo htmlspecialchars($it['pdf_path']); ?>" target="_blank">View</a></td>
              <td>
                <?php if (!empty($it['image_path'])): ?>
                  <img src="<?php echo htmlspecialchars($it['image_path']); ?>" alt="cover" style="height:40px;width:auto;object-fit:cover;border-radius:4px;">
                <?php else: ?>
                  <span class="text-muted">None</span>
                <?php endif; ?>
              </td>
              <td><?php echo htmlspecialchars($it['created_at']); ?></td>
              <td>
                <a href="#" class="btn btn-sm btn-warning me-1"
                   data-bs-toggle="modal"
                   data-bs-target="#editCatalogueModal"
                   data-id="<?php echo (int)$it['id']; ?>"
                   data-title-en="<?php echo htmlspecialchars($it['title_en']); ?>"
                   data-title-tm="<?php echo htmlspecialchars($it['title_tm'] ?? ''); ?>"
                   data-title-ru="<?php echo htmlspecialchars($it['title_ru'] ?? ''); ?>"
                   data-desc-en="<?php echo htmlspecialchars($it['description_en'] ?? ''); ?>"
                   data-desc-tm="<?php echo htmlspecialchars($it['description_tm'] ?? ''); ?>"
                   data-desc-ru="<?php echo htmlspecialchars($it['description_ru'] ?? ''); ?>"
                   data-pdf="<?php echo htmlspecialchars($it['pdf_path']); ?>"
                   data-image="<?php echo htmlspecialchars($it['image_path'] ?? ''); ?>">
                   <i class="bi bi-pencil"></i> Edit
                </a>
                <a href="index.php?module=catalogues&delete=<?php echo (int)$it['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this item?');"><i class="bi bi-trash"></i></a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
  (function(){
    if (window.jQuery && jQuery.fn.DataTable) {
      jQuery('#tblCatalogues').DataTable({pageLength:10});
    }
  })();
</script>

<!-- Edit Catalogue Modal -->
<div class="modal fade" id="editCatalogueModal" tabindex="-1" aria-labelledby="editCatalogueModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form method="post" enctype="multipart/form-data" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editCatalogueModalLabel">Edit Catalogue</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" id="cat_edit_id">
        <div class="row g-3">
          <div class="col-12 col-md-6">
            <label class="form-label">Title (EN) *</label>
            <input type="text" name="title_en" id="cat_title_en" class="form-control" required>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">Title (TM)</label>
            <input type="text" name="title_tm" id="cat_title_tm" class="form-control">
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">Title (RU)</label>
            <input type="text" name="title_ru" id="cat_title_ru" class="form-control">
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label">Replace PDF</label>
            <input type="file" name="pdf" accept="application/pdf" class="form-control">
            <div class="mt-1" id="cat_pdf_link"></div>
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label">Replace Cover Image</label>
            <input type="file" name="image" accept="image/png,image/jpeg,image/webp" class="form-control">
            <div class="mt-1"><img id="cat_img_preview" src="" alt="cover" style="height:40px;width:auto;object-fit:cover;border-radius:4px;display:none;"></div>
          </div>
          <div class="col-12">
            <label class="form-label">Description (EN)</label>
            <textarea name="description_en" id="cat_desc_en" class="form-control" rows="3"></textarea>
          </div>
          <div class="col-12">
            <label class="form-label">Description (TM)</label>
            <textarea name="description_tm" id="cat_desc_tm" class="form-control" rows="3"></textarea>
          </div>
          <div class="col-12">
            <label class="form-label">Description (RU)</label>
            <textarea name="description_ru" id="cat_desc_ru" class="form-control" rows="3"></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save Changes</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>
    </form>
  </div>
 </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var editModal = document.getElementById('editCatalogueModal');
  editModal.addEventListener('show.bs.modal', function (event) {
    var btn = event.relatedTarget;
    document.getElementById('cat_edit_id').value = btn.getAttribute('data-id');
    document.getElementById('cat_title_en').value = btn.getAttribute('data-title-en') || '';
    document.getElementById('cat_title_tm').value = btn.getAttribute('data-title-tm') || '';
    document.getElementById('cat_title_ru').value = btn.getAttribute('data-title-ru') || '';
    document.getElementById('cat_desc_en').value = btn.getAttribute('data-desc-en') || '';
    document.getElementById('cat_desc_tm').value = btn.getAttribute('data-desc-tm') || '';
    document.getElementById('cat_desc_ru').value = btn.getAttribute('data-desc-ru') || '';
    var pdf = btn.getAttribute('data-pdf');
    var img = btn.getAttribute('data-image');
    var pdfLink = document.getElementById('cat_pdf_link');
    var imgPrev = document.getElementById('cat_img_preview');
    if (pdf) {
      pdfLink.innerHTML = '<a href="' + pdf + '" target="_blank" class="btn btn-sm btn-outline-info"><i class="bi bi-file-pdf"></i> View current PDF</a>';
    } else {
      pdfLink.innerHTML = '<span class="text-muted">No PDF</span>';
    }
    if (img) {
      imgPrev.src = img; imgPrev.style.display = 'inline-block';
    } else {
      imgPrev.style.display = 'none';
    }
  });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
