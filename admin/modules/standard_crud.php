<?php
// This file is a template for standard multi-language CRUD modules (e.g., Projects, Services, Posts, Sliders)

// The module name is passed via the 'module' variable from header.php
$table_name = $module;
$page_title = $t[$table_name];
$lang_id = get_current_lang_id($pdo);
$message = '';

// Define fields and upload folder based on the module
$fields = [];
$upload_folder = '';
switch ($table_name) {
    case 'projects':
        $fields = ['title' => 'Title', 'description' => 'Description', 'image' => 'Image'];
        $upload_folder = 'uploads/projects/';
        break;
    case 'services':
        $fields = ['title' => 'Title', 'description' => 'Description', 'image' => 'Image'];
        $upload_folder = 'uploads/services/';
        break;
    case 'posts':
        $fields = ['title' => 'Title', 'content' => 'Content', 'image' => 'Image'];
        $upload_folder = 'uploads/posts/';
        break;
    case 'sliders':
        $fields = ['title' => 'Title', 'description' => 'Description', 'image' => 'Image'];
        $upload_folder = 'uploads/sliders/';
        break;
    case 'users':
        $fields = ['name' => 'Name', 'email' => 'Email', 'image' => 'Image'];
        $upload_folder = 'uploads/users/';
        break;
    case 'company_infos':
        $fields = ['title' => 'Title', 'description' => 'Description', 'image' => 'Image'];
        $upload_folder = 'uploads/company_infos/';
        break;
    case 'our_partners':
        $fields = ['title' => 'Title', 'image' => 'Image'];
        $upload_folder = 'uploads/partners/';
        break;
    default:
        die("Invalid module: " . $table_name);
}

// --- CRUD Operations ---

// 1. CREATE/UPDATE
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && ($_POST['action'] == 'create' || $_POST['action'] == 'update')) {
    $data = [];
    $placeholders = [];
    $set_clauses = [];
    $id = $_POST['id'] ?? null;

    foreach ($fields as $field => $label) {
        if ($field == 'image') {
            // Dosya yükleme kontrolü
            if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
                if (!is_dir($upload_folder)) mkdir($upload_folder, 0777, true);
                $filename = uniqid() . '_' . basename($_FILES["image"]["name"]);
                $target_file = $upload_folder . $filename;
                if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    // delete old image on update
                    if (!empty($_POST['old_image']) && $_POST['old_image'] !== $target_file) {
                        $old = $_POST['old_image'];
                        if (is_file($old)) { @unlink($old); }
                    }
                    $data[$field] = $target_file;
                }
            } else {
                // Güncellemede eski resim
                $data[$field] = $_POST['old_image'] ?? '';
            }
        } else {
            $data[$field] = trim($_POST[$field] ?? '');
        }
        $placeholders[] = '?';
        $set_clauses[] = "{$field} = ?";
    }

    if ($_POST['action'] == 'create') {
        $sql = "INSERT INTO {$table_name} (lang_id, " . implode(', ', array_keys($fields)) . ") VALUES (?, " . implode(', ', $placeholders) . ")";
        $params = array_merge([$lang_id], array_values($data));
    } else { // update
        $sql = "UPDATE {$table_name} SET " . implode(', ', $set_clauses) . " WHERE id = ? AND lang_id = ?";
        $params = array_merge(array_values($data), [$id, $lang_id]);
    }

    $stmt = $pdo->prepare($sql);
    if ($stmt->execute($params)) {
        $message = "<div class='alert alert-success'>Record " . ($_POST['action'] == 'create' ? 'created' : 'updated') . " successfully.</div>";
    } else {
        $message = "<div class='alert alert-danger'>Error " . ($_POST['action'] == 'create' ? 'creating' : 'updating') . " record.</div>";
    }
    $action = 'list'; // Go back to list view after operation
}

// 2. DELETE
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    // try to delete image file if present
    $imgQ = $pdo->prepare("SELECT image FROM {$table_name} WHERE id = ? AND lang_id = ?");
    $imgQ->execute([$id, $lang_id]);
    $rec = $imgQ->fetch();
    if ($rec && !empty($rec['image']) && is_file($rec['image'])) { @unlink($rec['image']); }
    $stmt = $pdo->prepare("DELETE FROM {$table_name} WHERE id = ? AND lang_id = ?");
    if ($stmt->execute([$id, $lang_id])) {
        $message = "<div class='alert alert-success'>Record deleted successfully.</div>";
    } else {
        $message = "<div class='alert alert-danger'>Error deleting record.</div>";
    }
    $action = 'list'; // Go back to list view after operation
}

// --- HTML Output ---

if ($action == 'create' || $action == 'edit') {
    $record = [];
    $form_action = 'create';
    if ($action == 'edit' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stmt = $pdo->prepare("SELECT * FROM {$table_name} WHERE id = ? AND lang_id = ?");
        $stmt->execute([$id, $lang_id]);
        $record = $stmt->fetch();
        $form_action = 'update';
        if (!$record) {
            $message = "<div class='alert alert-warning'>Record not found.</div>";
            $action = 'list';
        }
    }

    if ($action != 'list'):
?>
<div class="row">
    <div class="col-12">
        <?php echo $message; ?>
        <div class="card">
            <div class="card-header">
                <h5 class="card-title"><?php echo $action == 'create' ? 'Create New' : 'Edit'; ?> <?php echo $page_title; ?> (Language: <?php echo isset($_SESSION['lang_code']) ? strtoupper($_SESSION['lang_code']) : 'N/A'; ?>)</h5>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" action="index.php?module=<?php echo $table_name; ?>">
                    <input type="hidden" name="action" value="<?php echo $form_action; ?>">
                    <?php if ($form_action == 'update'): ?>
                        <input type="hidden" name="id" value="<?php echo $record['id']; ?>">
                    <?php endif; ?>

                    <?php foreach ($fields as $field => $label): ?>
                        <div class="mb-3">
                            <label for="<?php echo $field; ?>" class="form-label"><?php echo $label; ?></label>
                            <?php if ($field == 'description' || $field == 'content'): ?>
                                <textarea class="form-control" id="<?php echo $field; ?>" name="<?php echo $field; ?>" rows="5"><?php echo htmlspecialchars($record[$field] ?? ''); ?></textarea>
                                <!-- <script src="assets/ckeditor.js"></script>
                                <script>
                                    CKEDITOR.replace('<?php echo $field; ?>');
                                </script> -->
                                <script src="http://js.nicedit.com/nicEdit-latest.js" type="text/javascript"></script>
                                <script type="text/javascript">bkLib.onDomLoaded(nicEditors.allTextAreas);</script>
                            <?php elseif ($field == 'image'): ?>
                                <input type="file" class="form-control" id="image" name="image" <?php echo $form_action == 'create' ? 'required' : ''; ?>>
                                <?php if ($form_action == 'update' && !empty($record['image'])): ?>
                                    <img src="<?php echo $record['image']; ?>" style="max-width:100px;">
                                    <input type="hidden" name="old_image" value="<?php echo $record['image']; ?>">
                                <?php endif; ?>
                            <?php else: ?>
                                <input type="text" class="form-control" id="<?php echo $field; ?>" name="<?php echo $field; ?>" value="<?php echo htmlspecialchars($record[$field] ?? ''); ?>" required>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                    <button type="submit" class="btn btn-primary"><?php echo $form_action == 'create' ? 'Create' : 'Update'; ?></button>
                    <a href="index.php?module=<?php echo $table_name; ?>" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>
<?php
    endif;
}

if ($action == 'list'):
    // 3. LIST
    $stmt = $pdo->prepare("SELECT * FROM {$table_name} WHERE lang_id = ? ORDER BY id DESC");
    $stmt->execute([$lang_id]);
    $records = $stmt->fetchAll();
?>
<div class="row">
    <div class="col-12">
        <?php echo $message; ?>
        <div class="d-flex justify-content-end mb-3">
            <a href="index.php?module=<?php echo $table_name; ?>&action=create" class="btn btn-success"><i class="bi bi-plus-circle"></i> Create New</a>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <?php foreach ($fields as $field => $label): ?>
                            <th><?php echo $label; ?></th>
                        <?php endforeach; ?>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($records)): ?>
                        <tr>
                            <td colspan="<?php echo count($fields) + 2; ?>" class="text-center">No records found for this language.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($records as $record): ?>
                            <tr>
                                <td><?php echo $record['id']; ?></td>
                                <?php foreach ($fields as $field => $label): ?>
                                    <td>
                                        <?php 
                                            $value = $record[$field];
                                            if ($field == 'image' || $field == 'image_url') {
                                                echo $value ? '<img src="' . htmlspecialchars($value) . '" alt="Image" style="max-width: 100px; max-height: 100px;">' : 'N/A';
                                            } elseif (strlen($value) > 50) {
                                                echo htmlspecialchars(substr($value, 0, 50)) . '...';
                                            } else {
                                                echo htmlspecialchars($value);
                                            }
                                        ?>
                                    </td>
                                <?php endforeach; ?>
                                <td>
                                    <a href="index.php?module=<?php echo $table_name; ?>&action=edit&id=<?php echo $record['id']; ?>" class="btn btn-sm btn-primary"><i class="bi bi-pencil"></i> Edit</a>
                                    <a href="index.php?module=<?php echo $table_name; ?>&action=delete&id=<?php echo $record['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this record?');"><i class="bi bi-trash"></i> Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php
endif;
?>
