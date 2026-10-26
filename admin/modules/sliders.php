<?php
require_once './includes/functions.php';
$message = '';

// Migrate sliders table to support link targets
$pdo->exec("ALTER TABLE sliders ADD COLUMN IF NOT EXISTS link_type VARCHAR(32) NULL");
$pdo->exec("ALTER TABLE sliders ADD COLUMN IF NOT EXISTS link_id INT NULL");
$pdo->exec("ALTER TABLE sliders ADD COLUMN IF NOT EXISTS link_url VARCHAR(512) NULL");

// Slider ekleme
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_slider'])) {
    $title_en = trim($_POST["title_en"] ?? '');
    $title_tm = trim($_POST["title_tm"] ?? '');
    $title_ru = trim($_POST["title_ru"] ?? '');
    $subtitle_en = trim($_POST["subtitle_en"] ?? '');
    $subtitle_tm = trim($_POST["subtitle_tm"] ?? '');
    $subtitle_ru = trim($_POST["subtitle_ru"] ?? '');
    $project_id = !empty($_POST["project_id"]) ? intval($_POST["project_id"]) : null; // legacy
    $link_type = trim($_POST['link_type'] ?? '');
    $link_id = !empty($_POST['link_id']) ? intval($_POST['link_id']) : null;
    $link_url = trim($_POST['link_url'] ?? '');
    $image = '';
    $target_dir = "uploads/sliders/";

    if (isset($_FILES["image"]) && $_FILES["image"]["error"] == UPLOAD_ERR_OK) {
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $filename = uniqid() . "_" . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $filename;
        move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);
        $image = $target_file;
    }

    $stmt = $pdo->prepare("INSERT INTO sliders (title_en, title_tm, title_ru, subtitle_en, subtitle_tm, subtitle_ru, image, project_id, link_type, link_id, link_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $result = $stmt->execute([$title_en, $title_tm, $title_ru, $subtitle_en, $subtitle_tm, $subtitle_ru, $image, $project_id, $link_type ?: null, $link_id, $link_url ?: null]);

    $message = $result
        ? "<div class='alert alert-success'>Slider added successfully.</div>"
        : "<div class='alert alert-danger'>Error adding slider.</div>";
}

// Slider silme
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM sliders WHERE id=?");
    $result = $stmt->execute([$id]);
    $message = $result
        ? "<div class='alert alert-success'>Slider deleted.</div>"
        : "<div class='alert alert-danger'>Error deleting slider.</div>";
}

// Slider düzenleme
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_slider'])) {
    $id = intval($_POST['slider_id']);
    $title_en = trim($_POST["edit_title_en"] ?? '');
    $title_tm = trim($_POST["edit_title_tm"] ?? '');
    $title_ru = trim($_POST["edit_title_ru"] ?? '');
    $subtitle_en = trim($_POST["edit_subtitle_en"] ?? '');
    $subtitle_tm = trim($_POST["edit_subtitle_tm"] ?? '');
    $subtitle_ru = trim($_POST["edit_subtitle_ru"] ?? '');
    $project_id = !empty($_POST["edit_project_id"]) ? intval($_POST["edit_project_id"]) : null; // legacy
    $link_type = trim($_POST['edit_link_type'] ?? '');
    $link_id = !empty($_POST['edit_link_id']) ? intval($_POST['edit_link_id']) : null;
    $link_url = trim($_POST['edit_link_url'] ?? '');
    $image = $_POST["old_image"] ?? '';
    $target_dir = "uploads/sliders/";

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

    $stmt = $pdo->prepare("UPDATE sliders SET title_en=?, title_tm=?, title_ru=?, subtitle_en=?, subtitle_tm=?, subtitle_ru=?, image=?, project_id=?, link_type=?, link_id=?, link_url=? WHERE id=?");
    $result = $stmt->execute([$title_en, $title_tm, $title_ru, $subtitle_en, $subtitle_tm, $subtitle_ru, $image, $project_id, $link_type ?: null, $link_id, $link_url ?: null, $id]);

    $message = $result
        ? "<div class='alert alert-success'>Slider updated.</div>"
        : "<div class='alert alert-danger'>Error updating slider.</div>";
}

// Mevcut sliderları çek
$sliders = [];
$stmt = $pdo->query("SELECT * FROM sliders ORDER BY id DESC");
while ($row = $stmt->fetch()) {
    $sliders[] = $row;
}

// Projeleri çek (dropdown için)
$projects = [];
$stmt_projects = $pdo->query("SELECT id, title_en, title_ru, title_tm FROM projects ORDER BY id DESC");
while ($row = $stmt_projects->fetch()) {
    $projects[] = $row;
}

// Services, Posts (News), Catalogues for link targets
$services = [];
$stmt_services = $pdo->query("SELECT id, title_en, title_tm, title_ru FROM services ORDER BY id DESC");
while ($row = $stmt_services->fetch()) { $services[] = $row; }

$news_posts = [];
$stmt_posts = $pdo->query("SELECT id, title_en, title_tm, title_ru FROM posts ORDER BY id DESC");
while ($row = $stmt_posts->fetch()) { $news_posts[] = $row; }

$catalogues = [];
$stmt_cats = $pdo->query("SELECT id, title_en, title_tm, title_ru FROM catalogues ORDER BY id DESC");
while ($row = $stmt_cats->fetch()) { $catalogues[] = $row; }
?>

<div class="row">
    <div class="col-12">
        <?php if ($message): ?>
            <div id="alert-message" class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Add Slider Button -->
        <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addSliderModal">
            Add Slider
        </button>

        <!-- Sliders List -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Sliders List (Images size must be : 1920px-1080px)</h5>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Title (EN)</th>
                            <th>Subtitle (EN)</th>
                            <th>Target</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sliders as $slider): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($slider['image'])): ?>
                                        <img src="<?php echo $slider['image']; ?>" style="max-width:80px;max-height:40px;">
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($slider['title_en']); ?></td>
                                <td><?php echo htmlspecialchars($slider['subtitle_en']); ?></td>
                                <td>
                                    <?php
                                      $lt = $slider['link_type'] ?? '';
                                      $lid = (int)($slider['link_id'] ?? 0);
                                      if ($lt === 'project' && $lid) {
                                          echo '<span class="badge bg-info">Project</span> #' . $lid;
                                      } elseif ($lt === 'service' && $lid) {
                                          echo '<span class="badge bg-info">Service</span> #' . $lid;
                                      } elseif ($lt === 'news' && $lid) {
                                          echo '<span class="badge bg-info">News</span> #' . $lid;
                                      } elseif ($lt === 'catalogue' && $lid) {
                                          echo '<span class="badge bg-info">Catalogue</span> #' . $lid;
                                      } elseif (!empty($slider['link_url'])) {
                                          echo '<span class="badge bg-secondary">URL</span> ' . htmlspecialchars($slider['link_url']);
                                      } else {
                                          echo '<span class="text-muted">None</span>';
                                      }
                                    ?>
                                </td>
                                <td>
                                    <a href="#" class="btn btn-sm btn-warning"
                                       data-bs-toggle="modal"
                                       data-bs-target="#editSliderModal"
                                       data-id="<?php echo $slider['id']; ?>"
                                       data-title-en="<?php echo htmlspecialchars($slider['title_en']); ?>"
                                       data-title-tm="<?php echo htmlspecialchars($slider['title_tm']); ?>"
                                       data-title-ru="<?php echo htmlspecialchars($slider['title_ru']); ?>"
                                       data-subtitle-en="<?php echo htmlspecialchars($slider['subtitle_en']); ?>"
                                       data-subtitle-tm="<?php echo htmlspecialchars($slider['subtitle_tm']); ?>"
                                       data-subtitle-ru="<?php echo htmlspecialchars($slider['subtitle_ru']); ?>"
                                       data-image="<?php echo $slider['image']; ?>"
                                       data-project-id="<?php echo $slider['project_id'] ?? ''; ?>"
                                       data-link-type="<?php echo htmlspecialchars($slider['link_type'] ?? ''); ?>"
                                       data-link-id="<?php echo htmlspecialchars($slider['link_id'] ?? ''); ?>"
                                       data-link-url="<?php echo htmlspecialchars($slider['link_url'] ?? ''); ?>"
                                    >Edit</a>
                                    <a href="?delete=<?php echo $slider['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this slider?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add Slider Modal -->
        <div class="modal fade" id="addSliderModal" tabindex="-1" aria-labelledby="addSliderModalLabel" aria-hidden="true">
          <div class="modal-dialog">
            <form method="POST" enctype="multipart/form-data" class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="addSliderModalLabel">Add New Slider</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <!-- Language Tabs -->
                <ul class="nav nav-tabs" id="addSliderTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="add-en-tab" data-bs-toggle="tab" data-bs-target="#add-en" type="button">English</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="add-tm-tab" data-bs-toggle="tab" data-bs-target="#add-tm" type="button">Turkmen</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="add-ru-tab" data-bs-toggle="tab" data-bs-target="#add-ru" type="button">Russian</button>
                    </li>
                </ul>
                <div class="tab-content mt-3" id="addSliderTabContent">
                    <!-- English -->
                    <div class="tab-pane fade show active" id="add-en" role="tabpanel">
                        <div class="mb-3">
                            <label for="title_en" class="form-label">Title (EN)</label>
                            <input type="text" class="form-control" id="title_en" name="title_en" required>
                        </div>
                        <div class="mb-3">
                            <label for="subtitle_en" class="form-label">Subtitle (EN)</label>
                            <input type="text" class="form-control" id="subtitle_en" name="subtitle_en" required>
                        </div>
                    </div>
                    <!-- Turkmen -->
                    <div class="tab-pane fade" id="add-tm" role="tabpanel">
                        <div class="mb-3">
                            <label for="title_tm" class="form-label">Title (TM)</label>
                            <input type="text" class="form-control" id="title_tm" name="title_tm" required>
                        </div>
                        <div class="mb-3">
                            <label for="subtitle_tm" class="form-label">Subtitle (TM)</label>
                            <input type="text" class="form-control" id="subtitle_tm" name="subtitle_tm" required>
                        </div>
                    </div>
                    <!-- Russian -->
                    <div class="tab-pane fade" id="add-ru" role="tabpanel">
                        <div class="mb-3">
                            <label for="title_ru" class="form-label">Title (RU)</label>
                            <input type="text" class="form-control" id="title_ru" name="title_ru" required>
                        </div>
                        <div class="mb-3">
                            <label for="subtitle_ru" class="form-label">Subtitle (RU)</label>
                            <input type="text" class="form-control" id="subtitle_ru" name="subtitle_ru" required>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Click Target</label>
                    <div class="row g-2">
                        <div class="col-12 col-md-4">
                            <select class="form-select" id="link_type" name="link_type">
                                <option value="">None</option>
                                <option value="project">Project</option>
                                <option value="service">Service</option>
                                <option value="news">News</option>
                                <option value="catalogue">Catalogue</option>
                                <option value="url">Custom URL</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-8" id="link_id_wrap">
                            <select class="form-select" id="link_id" name="link_id">
                                <option value="">-- Select Item --</option>
                                <!-- dynamically populated via JS -->
                            </select>
                        </div>
                        <div class="col-12 d-none" id="link_url_wrap">
                            <input type="url" class="form-control" id="link_url" name="link_url" placeholder="https://example.com">
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="image" class="form-label">Image</label>
                    <input type="file" class="form-control" id="image" name="image" required>
                </div>
              </div>
              <div class="modal-footer">
                <button type="submit" name="add_slider" class="btn btn-primary">Add Slider</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              </div>
            </form>
          </div>
        </div>

        <!-- Edit Slider Modal -->
        <div class="modal fade" id="editSliderModal" tabindex="-1" aria-labelledby="editSliderModalLabel" aria-hidden="true">
          <div class="modal-dialog">
            <form method="POST" enctype="multipart/form-data" class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="editSliderModalLabel">Edit Slider</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <input type="hidden" name="slider_id" id="edit_slider_id">
                <!-- Language Tabs -->
                <ul class="nav nav-tabs" id="editSliderTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="edit-en-tab" data-bs-toggle="tab" data-bs-target="#edit-en" type="button">English</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="edit-tm-tab" data-bs-toggle="tab" data-bs-target="#edit-tm" type="button">Turkmen</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="edit-ru-tab" data-bs-toggle="tab" data-bs-target="#edit-ru" type="button">Russian</button>
                    </li>
                </ul>
                <div class="tab-content mt-3" id="editSliderTabContent">
                    <!-- English -->
                    <div class="tab-pane fade show active" id="edit-en" role="tabpanel">
                        <div class="mb-3">
                            <label for="edit_title_en" class="form-label">Title (EN)</label>
                            <input type="text" class="form-control" id="edit_title_en" name="edit_title_en" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_subtitle_en" class="form-label">Subtitle (EN)</label>
                            <input type="text" class="form-control" id="edit_subtitle_en" name="edit_subtitle_en" required>
                        </div>
                    </div>
                    <!-- Turkmen -->
                    <div class="tab-pane fade" id="edit-tm" role="tabpanel">
                        <div class="mb-3">
                            <label for="edit_title_tm" class="form-label">Title (TM)</label>
                            <input type="text" class="form-control" id="edit_title_tm" name="edit_title_tm" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_subtitle_tm" class="form-label">Subtitle (TM)</label>
                            <input type="text" class="form-control" id="edit_subtitle_tm" name="edit_subtitle_tm" required>
                        </div>
                    </div>
                    <!-- Russian -->
                    <div class="tab-pane fade" id="edit-ru" role="tabpanel">
                        <div class="mb-3">
                            <label for="edit_title_ru" class="form-label">Title (RU)</label>
                            <input type="text" class="form-control" id="edit_title_ru" name="edit_title_ru" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_subtitle_ru" class="form-label">Subtitle (RU)</label>
                            <input type="text" class="form-control" id="edit_subtitle_ru" name="edit_subtitle_ru" required>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Click Target</label>
                    <div class="row g-2">
                        <div class="col-12 col-md-4">
                            <select class="form-select" id="edit_link_type" name="edit_link_type">
                                <option value="">None</option>
                                <option value="project">Project</option>
                                <option value="service">Service</option>
                                <option value="news">News</option>
                                <option value="catalogue">Catalogue</option>
                                <option value="url">Custom URL</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-8" id="edit_link_id_wrap">
                            <select class="form-select" id="edit_link_id" name="edit_link_id">
                                <option value="">-- Select Item --</option>
                            </select>
                        </div>
                        <div class="col-12 d-none" id="edit_link_url_wrap">
                            <input type="url" class="form-control" id="edit_link_url" name="edit_link_url" placeholder="https://example.com">
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="edit_image" class="form-label">Image</label>
                    <input type="file" class="form-control" id="edit_image" name="edit_image">
                    <img id="edit_image_preview" src="" style="max-width:80px;max-height:40px;" class="mt-2">
                    <input type="hidden" name="old_image" id="edit_old_image">
                </div>
              </div>
              <div class="modal-footer">
                <button type="submit" name="edit_slider" class="btn btn-primary">Update Slider</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              </div>
            </form>
          </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var editModal = document.getElementById('editSliderModal');
    editModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        document.getElementById('edit_slider_id').value = button.getAttribute('data-id');
        document.getElementById('edit_title_en').value = button.getAttribute('data-title-en');
        document.getElementById('edit_title_tm').value = button.getAttribute('data-title-tm');
        document.getElementById('edit_title_ru').value = button.getAttribute('data-title-ru');
        document.getElementById('edit_subtitle_en').value = button.getAttribute('data-subtitle-en');
        document.getElementById('edit_subtitle_tm').value = button.getAttribute('data-subtitle-tm');
        document.getElementById('edit_subtitle_ru').value = button.getAttribute('data-subtitle-ru');
        document.getElementById('edit_old_image').value = button.getAttribute('data-image');
        document.getElementById('edit_image_preview').src = button.getAttribute('data-image');
        document.getElementById('edit_project_id').value = button.getAttribute('data-project-id') || '';

        // Prefill link target
        var lt = button.getAttribute('data-link-type') || '';
        var lid = button.getAttribute('data-link-id') || '';
        var lurl = button.getAttribute('data-link-url') || '';
        document.getElementById('edit_link_type').value = lt;
        populateTargetSelect('edit_link_type','edit_link_id','edit_link_url',<?php echo json_encode($projects); ?>,<?php echo json_encode($services); ?>,<?php echo json_encode($news_posts); ?>,<?php echo json_encode($catalogues); ?>);
        if (lt === 'url') {
            document.getElementById('edit_link_url').value = lurl;
        } else {
            document.getElementById('edit_link_id').value = lid;
        }
    });
    // Link target population for Add
    var addLinkType = document.getElementById('link_type');
    if (addLinkType) {
        addLinkType.addEventListener('change', function(){
            populateTargetSelect('link_type','link_id','link_url',<?php echo json_encode($projects); ?>,<?php echo json_encode($services); ?>,<?php echo json_encode($news_posts); ?>,<?php echo json_encode($catalogues); ?>);
        });
        populateTargetSelect('link_type','link_id','link_url',<?php echo json_encode($projects); ?>,<?php echo json_encode($services); ?>,<?php echo json_encode($news_posts); ?>,<?php echo json_encode($catalogues); ?>);
    }

    // Link target population for Edit
    var editLinkType = document.getElementById('edit_link_type');
    if (editLinkType) {
        editLinkType.addEventListener('change', function(){
            populateTargetSelect('edit_link_type','edit_link_id','edit_link_url',<?php echo json_encode($projects); ?>,<?php echo json_encode($services); ?>,<?php echo json_encode($news_posts); ?>,<?php echo json_encode($catalogues); ?>);
        });
    }

    function populateTargetSelect(typeId, selectId, urlId, projects, services, news, catalogues) {
        var typeEl = document.getElementById(typeId);
        var selEl = document.getElementById(selectId);
        var urlEl = document.getElementById(urlId);
        var selWrap = selEl.parentElement;
        var urlWrap = urlEl.parentElement;
        var type = typeEl.value;
        // reset
        selEl.innerHTML = '<option value="">-- Select Item --</option>';
        selWrap.classList.remove('d-none');
        urlWrap.classList.add('d-none');
        if (!type) return;
        if (type === 'project') {
            projects.forEach(function(p){ selEl.innerHTML += '<option value="'+p.id+'">'+(p.title_en||('Project #'+p.id))+'</option>'; });
        } else if (type === 'service') {
            services.forEach(function(s){ selEl.innerHTML += '<option value="'+s.id+'">'+(s.title_en||('Service #'+s.id))+'</option>'; });
        } else if (type === 'news') {
            news.forEach(function(n){ selEl.innerHTML += '<option value="'+n.id+'">'+(n.title_en||('News #'+n.id))+'</option>'; });
        } else if (type === 'catalogue') {
            catalogues.forEach(function(c){ selEl.innerHTML += '<option value="'+c.id+'">'+(c.title_en||('Catalogue #'+c.id))+'</option>'; });
        } else if (type === 'url') {
            selWrap.classList.add('d-none');
            urlWrap.classList.remove('d-none');
        }
    }

    var alert = document.getElementById('alert-message');
    if (alert) {
        setTimeout(function() {
            alert.style.display = 'none';
        }, 3000);
    }
});
</script>
