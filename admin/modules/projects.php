<?php
require_once './includes/functions.php';
$message = '';

// Servisleri çek
$services = [];
$stmt = $pdo->query("SELECT id, title_en FROM services ORDER BY id DESC");
while ($row = $stmt->fetch()) {
    $services[$row['id']] = $row['title_en'];
}

// Proje ekleme
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_project'])) {
    $service_id = intval($_POST["service_id"] ?? 0);
    $year = intval($_POST["year"] ?? date('Y'));
    $title_en = trim($_POST["title_en"] ?? '');
    $title_tm = trim($_POST["title_tm"] ?? '');
    $title_ru = trim($_POST["title_ru"] ?? '');
    $description_en = trim($_POST["description_en"] ?? '');
    $description_tm = trim($_POST["description_tm"] ?? '');
    $description_ru = trim($_POST["description_ru"] ?? '');
    $images = '';
    $videos = '';
    $target_dir = "uploads/projects/";
    $uploaded_images = [];
    $uploaded_videos = [];

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

    // Videoları yükle
    if (!empty($_FILES['videos']['name'][0])) {
        foreach ($_FILES['videos']['name'] as $key => $name) {
            if ($_FILES['videos']['error'][$key] == UPLOAD_ERR_OK) {
                if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
                $filename = uniqid() . "_" . basename($name);
                $target_file = $target_dir . $filename;
                move_uploaded_file($_FILES['videos']['tmp_name'][$key], $target_file);
                $uploaded_videos[] = $target_file;
            }
        }
        $videos = implode(',', $uploaded_videos);
    }

    $stmt = $pdo->prepare("INSERT INTO projects (service_id, year, title_en, title_tm, title_ru, description_en, description_tm, description_ru, images, videos) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $result = $stmt->execute([$service_id, $year, $title_en, $title_tm, $title_ru, $description_en, $description_tm, $description_ru, $images, $videos]);

    $message = $result
        ? "<div class='alert alert-success'>Project added successfully.</div>"
        : "<div class='alert alert-danger'>Error adding project.</div>";
}

// Proje resmi silme (tekil)
if (isset($_GET['delete_image']) && isset($_GET['project_id'])) {
    $project_id = intval($_GET['project_id']);
    $image_to_delete = $_GET['delete_image'];
    
    // Projeyi çek
    $stmt = $pdo->prepare("SELECT images FROM projects WHERE id=?");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch();
    
    if ($project) {
        $current_images = explode(',', $project['images']);
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
        
        $updated_images_str = implode(',', $new_images);
        $stmt = $pdo->prepare("UPDATE projects SET images=? WHERE id=?");
        $result = $stmt->execute([$updated_images_str, $project_id]);
        
        $message = $result
            ? "<div class='alert alert-success'>Image deleted.</div>"
            : "<div class='alert alert-danger'>Error deleting image.</div>";
            
        // Redirect to avoid re-execution on refresh (optional but good practice)
        // header("Location: index.php?module=projects&edit=$project_id"); 
        // But since we are in a module included by index.php, simple redirect might be tricky without full URL.
        // For now, we just show the message.
    }
}

// Proje videosu silme (tekil)
if (isset($_GET['delete_video']) && isset($_GET['project_id'])) {
    $project_id = intval($_GET['project_id']);
    $video_to_delete = $_GET['delete_video'];
    $stmt = $pdo->prepare("SELECT videos FROM projects WHERE id=?");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch();
    if ($project) {
        $current_videos = explode(',', $project['videos']);
        $new_videos = [];
        foreach ($current_videos as $vid) {
            if (trim($vid) === $video_to_delete) {
                if (file_exists($vid)) { unlink($vid); }
            } else {
                $new_videos[] = trim($vid);
            }
        }
        $updated_videos_str = implode(',', $new_videos);
        $stmt = $pdo->prepare("UPDATE projects SET videos=? WHERE id=?");
        $result = $stmt->execute([$updated_videos_str, $project_id]);
        $message = $result
            ? "<div class='alert alert-success'>Video deleted.</div>"
            : "<div class='alert alert-danger'>Error deleting video.</div>";
    }
}

// Proje silme
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = intval($_GET['delete']);
    // Remove media files before delete
    $ps = $pdo->prepare("SELECT images, videos FROM projects WHERE id=?");
    $ps->execute([$id]);
    $prow = $ps->fetch();
    if ($prow) {
        if (!empty($prow['images'])) {
            foreach (explode(',', $prow['images']) as $img) { $img = trim($img); if ($img && is_file($img)) { @unlink($img); } }
        }
        if (!empty($prow['videos'])) {
            foreach (explode(',', $prow['videos']) as $vid) { $vid = trim($vid); if ($vid && is_file($vid)) { @unlink($vid); } }
        }
    }
    $stmt = $pdo->prepare("DELETE FROM projects WHERE id=?");
    $result = $stmt->execute([$id]);
    $message = $result
        ? "<div class='alert alert-success'>Project deleted.</div>"
        : "<div class='alert alert-danger'>Error deleting project.</div>";
}

// Proje düzenleme
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_project'])) {
    $id = intval($_POST['project_id']);
    $service_id = intval($_POST["edit_service_id"] ?? 0);
    $year = intval($_POST["edit_year"] ?? date('Y'));
    $title_en = trim($_POST["edit_title_en"] ?? '');
    $title_tm = trim($_POST["edit_title_tm"] ?? '');
    $title_ru = trim($_POST["edit_title_ru"] ?? '');
    $description_en = trim($_POST["edit_description_en"] ?? '');
    $description_tm = trim($_POST["edit_description_tm"] ?? '');
    $description_ru = trim($_POST["edit_description_ru"] ?? '');
    $old_images = $_POST["old_images"] ?? '';
    $old_videos = $_POST["old_videos"] ?? '';
    $target_dir = "uploads/projects/";
    $uploaded_images = [];
    $uploaded_videos = [];

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

    // Yeni videoları yükle
    if (!empty($_FILES['edit_videos']['name'][0])) {
        foreach ($_FILES['edit_videos']['name'] as $key => $name) {
            if ($_FILES['edit_videos']['error'][$key] == UPLOAD_ERR_OK) {
                if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
                $filename = uniqid() . "_" . basename($name);
                $target_file = $target_dir . $filename;
                move_uploaded_file($_FILES['edit_videos']['tmp_name'][$key], $target_file);
                $uploaded_videos[] = $target_file;
            }
        }
        $videos = implode(',', $uploaded_videos);
        if ($old_videos) $videos = $old_videos . ',' . $videos;
    } else {
        $videos = $old_videos;
    }

    $stmt = $pdo->prepare("UPDATE projects SET service_id=?, year=?, title_en=?, title_tm=?, title_ru=?, description_en=?, description_tm=?, description_ru=?, images=?, videos=? WHERE id=?");
    $result = $stmt->execute([$service_id, $year, $title_en, $title_tm, $title_ru, $description_en, $description_tm, $description_ru, $images, $videos, $id]);

    $message = $result
        ? "<div class='alert alert-success'>Project updated.</div>"
        : "<div class='alert alert-danger'>Error updating project.</div>";
}

// Mevcut projeleri çek
$projects = [];
$stmt = $pdo->query("SELECT * FROM projects ORDER BY id DESC");
while ($row = $stmt->fetch()) {
    $projects[] = $row;
}

// Düzenlenecek projeyi çek
$edit_project = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id=?");
    $stmt->execute([$id]);
    $edit_project = $stmt->fetch();
}
?>

<div class="row">
    <div class="col-12">
        <?php if ($message): ?>
            <div id="alert-message">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Add Project Button -->
        <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addProjectModal">
            Add Project
        </button>

        <!-- Projects List -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Projects List (Images size must be : 500px-600px)</h5>
            </div>
            <div class="card-body">
                <!-- <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Service</th>
                            <th>Title (EN)</th>
                            <th>Images</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projects as $project): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($services[$project['service_id']] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($project['title_en']); ?></td>
                                <td>
                                    <?php foreach (explode(',', $project['images']) as $img): ?>
                                        <?php if ($img): ?>
                                            <div class="d-inline-block position-relative me-1 mb-1">
                                                <img src="<?php echo $img; ?>" style="max-width:60px;max-height:40px;">
                                                <a href="index.php?module=projects&delete_image=<?php echo urlencode($img); ?>&project_id=<?php echo $project['id']; ?>" 
                                                   class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                                                   onclick="return confirm('Delete this image?');"
                                                   style="font-size: 0.6rem; padding: 0.2em 0.4em; text-decoration: none;">&times;</a>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </td>
                                <td>
                                    <a href="#" class="btn btn-sm btn-warning"
                                       data-bs-toggle="modal"
                                       data-bs-target="#editProjectModal"
                                       data-id="<?php echo $project['id']; ?>"
                                       data-service-id="<?php echo $project['service_id']; ?>"
                                       data-title-en="<?php echo htmlspecialchars($project['title_en']); ?>"
                                       data-title-tm="<?php echo htmlspecialchars($project['title_tm']); ?>"
                                       data-title-ru="<?php echo htmlspecialchars($project['title_ru']); ?>"
                                       data-description-en="<?php echo htmlspecialchars($project['description_en']); ?>"
                                       data-description-tm="<?php echo htmlspecialchars($project['description_tm']); ?>"
                                       data-description-ru="<?php echo htmlspecialchars($project['description_ru']); ?>"
                                       data-images="<?php echo $project['images']; ?>"
                                    >Edit</a>
                                    <a href="?delete=<?php echo $project['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this project?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table> -->
                <table id="projectsTable" class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Service</th>
                            <th>Title (EN)</th>
                            <th>Images</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projects as $project): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($services[$project['service_id']] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($project['title_en']); ?></td>
                                <td>
                                    <?php 
                                        $imgs = array_filter(array_map('trim', explode(',', $project['images'] ?? '')));
                                        $vids = array_filter(array_map('trim', explode(',', $project['videos'] ?? '')));
                                        $imgCount = count($imgs);
                                        $vidCount = count($vids);
                                    ?>
                                    <span class="badge bg-primary">Images: <?php echo $imgCount; ?></span>
                                    <span class="badge bg-secondary ms-1">Videos: <?php echo $vidCount; ?></span>
                                </td>
                                <td>
                                    <a href="#" class="btn btn-sm btn-warning"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editProjectModal"
                                    data-id="<?php echo $project['id']; ?>"
                                    data-service-id="<?php echo $project['service_id']; ?>"
                                    data-year="<?php echo $project['year'] ?? date('Y'); ?>"
                                    data-title-en="<?php echo htmlspecialchars($project['title_en']); ?>"
                                    data-title-tm="<?php echo htmlspecialchars($project['title_tm']); ?>"
                                    data-title-ru="<?php echo htmlspecialchars($project['title_ru']); ?>"
                                    data-description-en="<?php echo htmlspecialchars($project['description_en']); ?>"
                                    data-description-tm="<?php echo htmlspecialchars($project['description_tm']); ?>"
                                    data-description-ru="<?php echo htmlspecialchars($project['description_ru']); ?>"
                                    data-images="<?php echo $project['images']; ?>"
                                    data-videos="<?php echo $project['videos'] ?? ''; ?>"
                                    >Edit</a>
                                    <a href="?delete=<?php echo $project['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this project?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add Project Modal -->
        <div class="modal fade" id="addProjectModal" tabindex="-1" aria-labelledby="addProjectModalLabel" aria-hidden="true">
          <div class="modal-dialog">
            <form method="POST" enctype="multipart/form-data" class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="addProjectModalLabel">Add New Project</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <div class="mb-3">
                    <label for="service_id" class="form-label">Service Type</label>
                    <select class="form-select" id="service_id" name="service_id" required>
                        <option value="">Select Service</option>
                        <?php foreach ($services as $sid => $stitle): ?>
                            <option value="<?php echo $sid; ?>"><?php echo htmlspecialchars($stitle); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="year" class="form-label">Project Year</label>
                    <input type="number" class="form-control" id="year" name="year" min="2000" max="2100" value="<?php echo date('Y'); ?>" required>
                    <small class="text-muted">Enter the year of the project</small>
                </div>
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
                    <label for="images" class="form-label">Images (Multiple)</label>
                    <input type="file" class="form-control" id="images" name="images[]" multiple required>
                </div>
                <div class="mb-3">
                    <label for="videos" class="form-label">Videos (Multiple, mp4/webm/mov)</label>
                    <input type="file" class="form-control" id="videos" name="videos[]" multiple accept="video/*">
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" checked>
                    <label class="form-check-label" for="is_active">Show on Frontend</label>
                </div>
              </div>
              <div class="modal-footer">
                <button type="submit" name="add_project" class="btn btn-primary">Add Project</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              </div>
            </form>
          </div>
        </div>

        <!-- Edit Project Modal -->
        <div class="modal fade" id="editProjectModal" tabindex="-1" aria-labelledby="editProjectModalLabel" aria-hidden="true">
          <div class="modal-dialog">
            <form method="POST" enctype="multipart/form-data" class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="editProjectModalLabel">Edit Project</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <input type="hidden" name="project_id" id="edit_project_id">
                <div class="mb-3">
                    <label for="edit_service_id" class="form-label">Service Type</label>
                    <select class="form-select" id="edit_service_id" name="edit_service_id" required>
                        <option value="">Select Service</option>
                        <?php foreach ($services as $sid => $stitle): ?>
                            <option value="<?php echo $sid; ?>"><?php echo htmlspecialchars($stitle); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="edit_year" class="form-label">Project Year</label>
                    <input type="number" class="form-control" id="edit_year" name="edit_year" min="2000" max="2100" required>
                    <small class="text-muted">Enter the year of the project</small>
                </div>
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
                    <label for="edit_images" class="form-label">Add Images (Multiple)</label>
                    <input type="file" class="form-control" id="edit_images" name="edit_images[]" multiple>
                    <div id="edit_images_preview" class="mt-2"></div>
                    <input type="hidden" name="old_images" id="edit_old_images">
                </div>
                <div class="mb-3">
                    <label for="edit_videos" class="form-label">Add Videos (Multiple)</label>
                    <input type="file" class="form-control" id="edit_videos" name="edit_videos[]" multiple accept="video/*">
                    <div id="edit_videos_preview" class="mt-2"></div>
                    <input type="hidden" name="old_videos" id="edit_old_videos">
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="edit_is_active" name="edit_is_active" value="1">
                    <label class="form-check-label" for="edit_is_active">Show on Frontend</label>
                </div>
              </div>
            <script>
            // Populate edit modal fields from data attributes
            document.addEventListener('DOMContentLoaded', function(){
                var editModal = document.getElementById('editProjectModal');
                editModal.addEventListener('show.bs.modal', function (event) {
                    var button = event.relatedTarget;
                    if (!button) return;
                    document.getElementById('edit_project_id').value = button.getAttribute('data-id') || '';
                    document.getElementById('edit_service_id').value = button.getAttribute('data-service-id') || '';
                    document.getElementById('edit_year').value = button.getAttribute('data-year') || '';
                    document.getElementById('edit_title_en').value = button.getAttribute('data-title-en') || '';
                    document.getElementById('edit_title_tm').value = button.getAttribute('data-title-tm') || '';
                    document.getElementById('edit_title_ru').value = button.getAttribute('data-title-ru') || '';
                    document.getElementById('edit_description_en').value = button.getAttribute('data-description-en') || '';
                    document.getElementById('edit_description_tm').value = button.getAttribute('data-description-tm') || '';
                    document.getElementById('edit_description_ru').value = button.getAttribute('data-description-ru') || '';

                    var images = button.getAttribute('data-images') || '';
                    document.getElementById('edit_old_images').value = images;
                    var imgPreview = document.getElementById('edit_images_preview');
                    imgPreview.innerHTML = '';
                    if (images) {
                        images.split(',').forEach(function(img){
                            if (!img) return;
                            var div = document.createElement('div');
                            div.className = 'd-inline-block position-relative me-1 mb-1';
                            div.innerHTML = '<img src="'+img+'" style="max-width:60px;max-height:40px;">';
                            imgPreview.appendChild(div);
                        });
                    }

                    var videos = button.getAttribute('data-videos') || '';
                    document.getElementById('edit_old_videos').value = videos;
                    var vidPreview = document.getElementById('edit_videos_preview');
                    vidPreview.innerHTML = '';
                    if (videos) {
                        videos.split(',').forEach(function(vid){
                            if (!vid) return;
                            var div = document.createElement('div');
                            div.className = 'd-inline-block position-relative me-1 mb-1';
                            div.innerHTML = '<span class="badge bg-secondary">Video</span>';
                            vidPreview.appendChild(div);
                        });
                    }
                });
            });
            </script>
              <div class="modal-footer">
                <button type="submit" name="edit_project" class="btn btn-primary">Update Project</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              </div>
            </form>
          </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var editModal = document.getElementById('editProjectModal');
        editModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            document.getElementById('edit_project_id').value = button.getAttribute('data-id');
            document.getElementById('edit_service_id').value = button.getAttribute('data-service-id');
            document.getElementById('edit_year').value = button.getAttribute('data-year');
            document.getElementById('edit_title_en').value = button.getAttribute('data-title-en');
            document.getElementById('edit_title_tm').value = button.getAttribute('data-title-tm');
            document.getElementById('edit_title_ru').value = button.getAttribute('data-title-ru');
            document.getElementById('edit_description_en').value = button.getAttribute('data-description-en');
            document.getElementById('edit_description_tm').value = button.getAttribute('data-description-tm');
            document.getElementById('edit_description_ru').value = button.getAttribute('data-description-ru');
            document.getElementById('edit_old_images').value = button.getAttribute('data-images');
            // Resim önizleme
            var imagesPreview = document.getElementById('edit_images_preview');
            imagesPreview.innerHTML = '';
            var images = button.getAttribute('data-images');
            var projectId = button.getAttribute('data-id');
            if (images) {
                images.split(',').forEach(function(img) {
                    if (img) {
                        var div = document.createElement('div');
                        div.className = 'd-inline-block position-relative me-2 mb-2';
                        div.innerHTML = `
                            <img src="${img}" style="max-width:100px;max-height:80px;" class="border rounded">
                            <a href="index.php?module=projects&delete_image=${encodeURIComponent(img)}&project_id=${projectId}" 
                               class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger text-white text-decoration-none"
                               onclick="return confirm('Delete this image?');"
                               style="cursor:pointer;">
                                &times;
                            </a>
                        `;
                        imagesPreview.appendChild(div);
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
        $('#projectsTable').DataTable({
            "paging": true,       // Sayfalama
            "searching": true,    // Arama
            "ordering": true,     // Sıralama
            "info": true,         // Tablo bilgisi
            "lengthChange": true  // Sayfa uzunluğu seçici
        });
    });
</script>