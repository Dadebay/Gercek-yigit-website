<?php
// Admin module to manage frontend translations stored in inc/lang.php

// Load existing translations
$langFile = realpath(__DIR__ . '/../../inc/lang.php');
if (!$langFile || !is_readable($langFile)) {
    echo "<div class='alert alert-danger m-3'>Translation file not found or unreadable.</div>";
    return;
}
$all = include $langFile;
$langs = ['en' => 'English', 'tm' => 'Türkmençe', 'ru' => 'Русский'];

// Helper: sanitize key and escape values for PHP file
function sanitize_key_name($key) {
    $key = trim($key);
    // allow letters, numbers, underscore, dash
    return preg_replace('/[^A-Za-z0-9_-]/', '_', $key);
}

function php_string($str) {
    // Normalize line breaks and escape quotes/backslashes
    $str = str_replace(["\r\n", "\r"], "\n", $str);
    return str_replace(['\\', "'"], ['\\\\', "\\'"], $str);
}

function write_lang_file($path, array $data) {
    // Build PHP file content deterministically
    $out = "<?php\n// Basic translation container — fill texts as needed\nreturn [\n";
    foreach (['en','tm','ru'] as $lc) {
        $out .= "    '" . $lc . "' => [\n";
        $entries = $data[$lc] ?? [];
        // sort keys for stable ordering
        ksort($entries);
        foreach ($entries as $k => $v) {
            $out .= "        '" . php_string($k) . "' => '" . php_string((string)$v) . "',\n";
        }
        $out .= "    ],\n";
    }
    $out .= "];\n";
    return (bool)file_put_contents($path, $out);
}

$msg = '';

// Handle Add
if (isset($_POST['add'])) {
    $key = sanitize_key_name($_POST['key'] ?? '');
    if ($key === '') {
        $msg = "<div class='alert alert-danger'>Key is required.</div>";
    } else {
        foreach ($langs as $lc => $label) {
            $val = trim($_POST['value_' . $lc] ?? '');
            if (!isset($all[$lc])) $all[$lc] = [];
            $all[$lc][$key] = $val;
        }
        if (write_lang_file($langFile, $all)) {
            $msg = "<div class='alert alert-success'>Added and saved.</div>";
        } else {
            $msg = "<div class='alert alert-danger'>Failed to write translations file.</div>";
        }
    }
}

// Handle Edit
if (isset($_POST['edit'])) {
    $key = sanitize_key_name($_POST['key'] ?? '');
    if ($key === '') {
        $msg = "<div class='alert alert-danger'>Key is required.</div>";
    } else {
        foreach ($langs as $lc => $label) {
            $val = trim($_POST['value_' . $lc] ?? '');
            if (!isset($all[$lc])) $all[$lc] = [];
            $all[$lc][$key] = $val;
        }
        if (write_lang_file($langFile, $all)) {
            $msg = "<div class='alert alert-success'>Updated and saved.</div>";
        } else {
            $msg = "<div class='alert alert-danger'>Failed to write translations file.</div>";
        }
    }
}

// Handle Delete
if (isset($_GET['delete_key'])) {
    $dk = sanitize_key_name($_GET['delete_key']);
    foreach ($langs as $lc => $label) {
        if (isset($all[$lc][$dk])) {
            unset($all[$lc][$dk]);
        }
    }
    if (write_lang_file($langFile, $all)) {
        $msg = "<div class='alert alert-warning'>Deleted and saved.</div>";
    } else {
        $msg = "<div class='alert alert-danger'>Failed to write translations file.</div>";
    }
}

// Build key list
$keys = [];
foreach ($langs as $lc => $label) {
    $keys = array_merge($keys, array_keys($all[$lc] ?? []));
}
$keys = array_values(array_unique($keys));
sort($keys);

// Edit prefill
$edit = null;
if (isset($_GET['edit_key'])) {
    $ek = sanitize_key_name($_GET['edit_key']);
    $edit = [
        'key' => $ek,
        'values' => [
            'en' => $all['en'][$ek] ?? '',
            'tm' => $all['tm'][$ek] ?? '',
            'ru' => $all['ru'][$ek] ?? '',
        ]
    ];
}
?>

<div class="container-fluid mt-4">
    <?php if (!empty($msg)) echo $msg; ?>
    <div class="card shadow-sm">
        <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
            <h4 class="mb-0">Translations (lang.php)</h4>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">Add Key</button>
        </div>
        <div class="card-body p-3">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Key</th>
                            <th>EN</th>
                            <th>TM</th>
                            <th>RU</th>
                            <th style="width:160px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($keys as $k): ?>
                        <tr>
                            <td class="align-middle"><?php echo htmlspecialchars($k); ?></td>
                            <td><?php echo htmlspecialchars($all['en'][$k] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($all['tm'][$k] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($all['ru'][$k] ?? ''); ?></td>
                            <td class="align-middle">
                                <a href="index.php?module=translations&edit_key=<?php echo urlencode($k); ?>" class="btn btn-sm btn-warning mb-1">Edit</a>
                                <a href="index.php?module=translations&delete_key=<?php echo urlencode($k); ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this key from all languages?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Translation Key</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2">
            <label>Key (letters, numbers, underscore)</label>
            <input type="text" name="key" class="form-control" required pattern="[A-Za-z0-9_\-]+">
        </div>
        <?php foreach ($langs as $lc => $label): ?>
        <div class="border rounded p-2 mb-2">
            <strong><?php echo $label; ?> (<?php echo strtoupper($lc); ?>)</strong>
            <div class="mb-2">
                <label>Value</label>
                <textarea name="value_<?php echo $lc; ?>" class="form-control" rows="2"></textarea>
            </div>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="modal-footer">
        <button type="submit" name="add" class="btn btn-primary">Save</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<?php if ($edit): ?>
<script>
window.addEventListener('DOMContentLoaded', function() {
    var editModal = new bootstrap.Modal(document.getElementById('editModal'));
    editModal.show();
});
</script>
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Translation Key</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2">
            <label>Key</label>
            <input type="text" name="key" class="form-control" value="<?php echo htmlspecialchars($edit['key']); ?>" required readonly>
        </div>
        <?php foreach ($langs as $lc => $label): ?>
        <div class="border rounded p-2 mb-2">
            <strong><?php echo $label; ?> (<?php echo strtoupper($lc); ?>)</strong>
            <div class="mb-2">
                <label>Value</label>
                <textarea name="value_<?php echo $lc; ?>" class="form-control" rows="2"><?php echo htmlspecialchars($edit['values'][$lc] ?? ''); ?></textarea>
            </div>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="modal-footer">
        <button type="submit" name="edit" class="btn btn-primary">Update</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php
// End of module
?>
