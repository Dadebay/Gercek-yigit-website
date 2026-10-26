<?php
require_once './includes/functions.php';
$message = '';

// Ensure contact_settings table exists for map embed src
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS contact_settings (\n        id INT AUTO_INCREMENT PRIMARY KEY,\n        map_embed_src TEXT NULL\n    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {
    // ignore if DB user lacks permission; module will still work without map
}

// Load current map embed src
$map_embed_src = '';
try {
    $row = $pdo->query("SELECT map_embed_src FROM contact_settings ORDER BY id ASC LIMIT 1")->fetch();
    if ($row && !empty($row['map_embed_src'])) $map_embed_src = $row['map_embed_src'];
} catch (Exception $e) { /* ignore */ }

// Mevcut verileri çek
$stmt = $pdo->query("SELECT * FROM consultation_settings ORDER BY lang_code ASC");
$consultation_settings = [];
while ($row = $stmt->fetch()) {
    $consultation_settings[$row['lang_code']] = $row;
}

// Form gönderildiğinde
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $langs = ['en', 'tm', 'ru'];
    $success = true;

    foreach ($langs as $lang) {
        $title = trim($_POST["title_$lang"] ?? '');
        $description = trim($_POST["description_$lang"] ?? '');
        $email_to = trim($_POST["email_to"] ?? 'info@bentvision.tech');

        // Kontrol et - varsa güncelle, yoksa ekle
        $stmt = $pdo->prepare("SELECT id FROM consultation_settings WHERE lang_code = ?");
        $stmt->execute([$lang]);
        $exists = $stmt->fetch();

        if ($exists) {
            $stmt = $pdo->prepare("UPDATE consultation_settings SET title=?, description=?, email_to=? WHERE lang_code=?");
            $result = $stmt->execute([$title, $description, $email_to, $lang]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO consultation_settings (lang_code, title, description, email_to) VALUES (?, ?, ?, ?)");
            $result = $stmt->execute([$lang, $title, $description, $email_to]);
        }
        
        if (!$result) $success = false;
    }

    // Handle map embed input (accept iframe HTML or direct URL; store only src)
    $map_input = trim($_POST['map_embed_iframe'] ?? '');
    if ($map_input !== '') {
        // Try to extract src from iframe HTML
        $src = '';
        if (stripos($map_input, '<iframe') !== false) {
            if (preg_match('/src\s*=\s*\"([^\"]+)\"/i', $map_input, $m)) {
                $src = $m[1];
            } elseif (preg_match("/src\s*=\s*'([^']+)'/i", $map_input, $m)) {
                $src = $m[1];
            }
        } else {
            $src = $map_input;
        }
        // Basic allow-list: only http/https URLs
        if ($src && preg_match('#^https?://#i', $src)) {
            try {
                $exists = $pdo->query("SELECT id FROM contact_settings ORDER BY id ASC LIMIT 1")->fetchColumn();
                if ($exists) {
                    $stmt = $pdo->prepare("UPDATE contact_settings SET map_embed_src = ? WHERE id = ?");
                    $stmt->execute([$src, (int)$exists]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO contact_settings (map_embed_src) VALUES (?)");
                    $stmt->execute([$src]);
                }
            } catch (Exception $e) {
                // ignore errors
            }
            $map_embed_src = $src;
        }
    }

    $message = $success
        ? "<div class='alert alert-success'>Consultation settings updated successfully.</div>"
        : "<div class='alert alert-danger'>Error updating records.</div>";
    
    // Verileri tekrar çek
    $stmt = $pdo->query("SELECT * FROM consultation_settings ORDER BY lang_code ASC");
    $consultation_settings = [];
    while ($row = $stmt->fetch()) {
        $consultation_settings[$row['lang_code']] = $row;
    }
}
?>

<div class="row">
    <div class="col-12">
        <?php if ($message): ?>
            <div id="alert-message">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Contact Page - Consultation Section Settings</h5>
                <p class="text-muted mb-0">Manage the "Schedule a Free Consultation" section on contact page</p>
            </div>
            <div class="card-body">
                <form method="POST">
                    <!-- Map Embed (Google Maps iframe) -->
                    <div class="mb-4 p-3 bg-light border rounded">
                        <label for="map_embed_iframe" class="form-label fw-bold">
                            <i class="bi bi-geo-alt"></i> Contact Page Map Embed (iframe or URL)
                        </label>
                        <textarea class="form-control mb-2" id="map_embed_iframe" name="map_embed_iframe" rows="3" placeholder="Paste Google Maps iframe HTML or the embed URL here..."><?php echo htmlspecialchars($map_embed_src); ?></textarea>
                        <small class="text-muted">Paste the Google Maps embed iframe, or just the iframe's <code>src</code> URL. We store only the URL for security.</small>
                        <?php if (!empty($map_embed_src)): ?>
                            <div class="mt-3">
                                <label class="form-label">Preview:</label>
                                <div class="ratio ratio-16x9 border rounded">
                                    <iframe src="<?php echo htmlspecialchars($map_embed_src); ?>" style="border:0;" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <!-- Email alıcı adresi (tüm diller için ortak) -->
                    <div class="mb-4 p-3 bg-light border rounded">
                        <label for="email_to" class="form-label fw-bold">
                            <i class="bi bi-envelope-at"></i> Email Recipient Address (All Languages)
                        </label>
                        <input type="email" class="form-control" id="email_to" name="email_to" 
                               value="<?php echo htmlspecialchars($consultation_settings['en']['email_to'] ?? 'info@bentvision.tech'); ?>" 
                               required>
                        <small class="text-muted">This email address will receive all consultation form submissions</small>
                    </div>

                    <hr>

                    <!-- Her dil için başlık ve açıklama -->
                    <?php foreach (['en'=>'English', 'tm'=>'Türkmençe', 'ru'=>'Русский'] as $lang => $label): ?>
                        <div class="mb-4 border rounded p-3">
                            <h6 class="text-primary mb-3">
                                <i class="bi bi-translate"></i> <?php echo $label; ?> (<?php echo strtoupper($lang); ?>)
                            </h6>
                            
                            <label for="title_<?php echo $lang; ?>" class="form-label">Section Title</label>
                            <input type="text" class="form-control mb-3" id="title_<?php echo $lang; ?>" name="title_<?php echo $lang; ?>"
                                   value="<?php echo htmlspecialchars($consultation_settings[$lang]['title'] ?? ''); ?>" required>
                            
                            <label for="description_<?php echo $lang; ?>" class="form-label">Section Description</label>
                            <textarea class="form-control" id="description_<?php echo $lang; ?>" name="description_<?php echo $lang; ?>" rows="4" required><?php echo htmlspecialchars($consultation_settings[$lang]['description'] ?? ''); ?></textarea>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary btn-lg px-5">
                            <i class="bi bi-save"></i> Save All Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
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
});
</script>
