<?php
// This file is a template for single-content, multi-language modules (e.g., About Us, Our Vision, Our Mission, Company Infos)

// The module name is passed via the 'module' variable from header.php
$table_name = $module;
$page_title = $t[$table_name];
$lang_id = get_current_lang_id($pdo);

// --- CRUD Operations ---

// 1. READ/LIST (Implicit, as it's a single-row content)
$stmt = $pdo->prepare("SELECT * FROM {$table_name} WHERE lang_id = ?");
$stmt->execute([$lang_id]);
$content = $stmt->fetch();

// 2. CREATE/UPDATE
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_content = trim($_POST['content']);
    $success = false;

    if ($content) {
        // UPDATE
        $stmt = $pdo->prepare("UPDATE {$table_name} SET content = ? WHERE lang_id = ?");
        $success = $stmt->execute([$new_content, $lang_id]);
    } else {
        // CREATE
        $stmt = $pdo->prepare("INSERT INTO {$table_name} (lang_id, content) VALUES (?, ?)");
        $success = $stmt->execute([$lang_id, $new_content]);
    }

    if ($success) {
        $message = "<div class='alert alert-success'>{$page_title} content updated successfully for language: " . strtoupper($_SESSION['lang_code']) . "</div>";
        // Re-fetch the content after update/create
        $stmt = $pdo->prepare("SELECT * FROM {$table_name} WHERE lang_id = ?");
        $stmt->execute([$lang_id]);
        $content = $stmt->fetch();
    } else {
        $message = "<div class='alert alert-danger'>Error updating {$page_title} content.</div>";
    }
}

// --- HTML Output ---
?>

<div class="row">
    <div class="col-12">
        <?php echo $message ?? ''; ?>
        <div class="card">
            <div class="card-header">
                <h5 class="card-title"><?php echo $page_title; ?> Management (Language: <?php echo strtoupper($_SESSION['lang_code']); ?>)</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="content" class="form-label">Content</label>
                        <textarea class="form-control" id="content" name="content" rows="10" required><?php echo htmlspecialchars($content['content'] ?? ''); ?></textarea>
                        <!-- <script src="http://js.nicedit.com/nicEdit-latest.js" type="text/javascript"></script>
                        <script type="text/javascript">bkLib.onDomLoaded(nicEditors.allTextAreas);</script> -->
                    </div>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>
