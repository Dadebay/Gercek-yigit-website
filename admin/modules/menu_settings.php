<?php
require_once __DIR__ . '/../includes/header.php';

// Ensure table exists and seed defaults if missing
$pdo->exec("CREATE TABLE IF NOT EXISTS menu_settings (
  slug VARCHAR(32) PRIMARY KEY,
  is_enabled TINYINT(1) NOT NULL DEFAULT 1,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");
$defaults = [
  'home'=>1,
  'about'=>1,
  'services'=>1,
  'projects'=>1,
  'news'=>1,
  'contact'=>1,
  // Infos dropdown + subitems
  'infos'=>1,
  'catalogues'=>1,
  'gallery'=>1,
  'certificates'=>1
];
foreach ($defaults as $slug => $enabled) {
    $stmt = $pdo->prepare("INSERT INTO menu_settings (slug, is_enabled) VALUES (?, ?) ON DUPLICATE KEY UPDATE slug=slug");
    $stmt->execute([$slug, $enabled]);
}

$pdo->exec("CREATE TABLE IF NOT EXISTS menu_item_settings (
  type ENUM('service','project') NOT NULL,
  item_id INT NOT NULL,
  is_enabled TINYINT(1) NOT NULL DEFAULT 1,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (type, item_id)
)");

// Custom menu links (for header/footer)
$pdo->exec("CREATE TABLE IF NOT EXISTS custom_menu_links (
  id INT AUTO_INCREMENT PRIMARY KEY,
  position ENUM('header','footer') NOT NULL DEFAULT 'header',
  label_en VARCHAR(128) NOT NULL,
  label_tm VARCHAR(128) DEFAULT NULL,
  label_ru VARCHAR(128) DEFAULT NULL,
  url VARCHAR(512) NOT NULL,
  target VARCHAR(32) DEFAULT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_enabled TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

$services = $pdo->query("SELECT id, title_en, title_ru, title_tm FROM services ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$all_projects = $pdo->query("SELECT id, title_en, title_ru, title_tm, service_id, year FROM projects ORDER BY service_id ASC, year DESC, id DESC")->fetchAll(PDO::FETCH_ASSOC);

$alert = '';
// Handle deletion via GET param for simplicity
if (isset($_GET['delete_custom'])) {
  $delId = (int)$_GET['delete_custom'];
  if ($delId > 0) {
    $pdo->prepare("DELETE FROM custom_menu_links WHERE id=?")->execute([$delId]);
    $alert = '<div class="alert alert-success">Custom link deleted.</div>';
  }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Top-level flags
  $flags = [
    'home' => isset($_POST['home']) ? 1 : 0,
    'about' => isset($_POST['about']) ? 1 : 0,
    'services' => isset($_POST['services']) ? 1 : 0,
    'projects' => isset($_POST['projects']) ? 1 : 0,
    'news' => isset($_POST['news']) ? 1 : 0,
    'contact' => isset($_POST['contact']) ? 1 : 0,
    // Infos
    'infos' => isset($_POST['infos']) ? 1 : 0,
    'catalogues' => isset($_POST['catalogues']) ? 1 : 0,
    'gallery' => isset($_POST['gallery']) ? 1 : 0,
    'certificates' => isset($_POST['certificates']) ? 1 : 0,
  ];
  $upd = $pdo->prepare("UPDATE menu_settings SET is_enabled=? WHERE slug=?");
  foreach ($flags as $slug => $is_enabled) {
    $upd->execute([$is_enabled, $slug]);
  }

  // Services submenu toggles
  $service_ids = array_column($services, 'id');
  $upsert = $pdo->prepare("INSERT INTO menu_item_settings (type, item_id, is_enabled) VALUES ('service', ?, ?) ON DUPLICATE KEY UPDATE is_enabled=VALUES(is_enabled)");
  foreach ($service_ids as $sid) {
    $enabled = isset($_POST['service_'.$sid]) ? 1 : 0;
    $upsert->execute([$sid, $enabled]);
  }

  // Projects submenu toggles
  $project_ids = array_column($all_projects, 'id');
  $upsertP = $pdo->prepare("INSERT INTO menu_item_settings (type, item_id, is_enabled) VALUES ('project', ?, ?) ON DUPLICATE KEY UPDATE is_enabled=VALUES(is_enabled)");
  foreach ($project_ids as $pid) {
    $enabled = isset($_POST['project_'.$pid]) ? 1 : 0;
    $upsertP->execute([$pid, $enabled]);
  }

  $alert = '<div class="alert alert-success">Menu settings saved successfully.</div>';

  // Handle add custom link
  if (isset($_POST['action']) && $_POST['action'] === 'add_custom_link') {
    $label_en = trim($_POST['label_en'] ?? '');
    $label_tm = trim($_POST['label_tm'] ?? '');
    $label_ru = trim($_POST['label_ru'] ?? '');
    $url = trim($_POST['url'] ?? '');
    $target = trim($_POST['target'] ?? '');
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $position = in_array(($_POST['position'] ?? 'header'), ['header','footer']) ? $_POST['position'] : 'header';
    if ($label_en && $url) {
      $ins = $pdo->prepare("INSERT INTO custom_menu_links (position,label_en,label_tm,label_ru,url,target,sort_order,is_enabled) VALUES (?,?,?,?,?,?,?,1)");
      $ins->execute([$position,$label_en,$label_tm,$label_ru,$url,$target,$sort_order]);
      $alert = '<div class="alert alert-success">Custom link added.</div>';
    } else {
      $alert = '<div class="alert alert-warning">Please provide at least English label and URL.</div>';
    }
  }

  // Handle update toggles for custom links
  if (isset($_POST['custom_enabled']) && is_array($_POST['custom_enabled'])) {
    $updC = $pdo->prepare("UPDATE custom_menu_links SET is_enabled=? WHERE id=?");
    foreach ($_POST['custom_enabled'] as $id => $val) {
      $updC->execute([(int)$val ? 1 : 0, (int)$id]);
    }
    $alert = '<div class="alert alert-success">Custom links updated.</div>';
  }
}

// Fetch current values
$rows = $pdo->query("SELECT slug, is_enabled FROM menu_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$val = function($slug) use ($rows, $defaults) { return (int)($rows[$slug] ?? $defaults[$slug]); };
$custom_links = $pdo->query("SELECT * FROM custom_menu_links ORDER BY position ASC, sort_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card shadow-sm">
  <div class="card-body">
    <h5 class="card-title mb-2 d-flex align-items-center"><i class="bi bi-menu-button-wide me-2"></i> Menu Settings</h5>
    <p class="text-muted mb-3">Toggle which items appear in the site navbar. Changes apply immediately after saving.</p>
    <?php echo $alert; ?>
    <form method="post">
      <div class="row g-3">
      <div class="col-12 col-sm-6 col-lg-4">
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" id="home" name="home" <?php echo $val('home') ? 'checked' : ''; ?>>
        <label class="form-check-label" for="home">Home</label>
      </div>
      </div>
      <div class="col-12 col-sm-6 col-lg-4">
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" id="about" name="about" <?php echo $val('about') ? 'checked' : ''; ?>>
        <label class="form-check-label" for="about">About Us</label>
      </div>
      </div>
      <div class="col-12 col-sm-6 col-lg-4">
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" id="services" name="services" <?php echo $val('services') ? 'checked' : ''; ?>>
        <label class="form-check-label" for="services">Services</label>
      </div>
      </div>
      <div class="col-12 col-sm-6 col-lg-4">
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" id="projects" name="projects" <?php echo $val('projects') ? 'checked' : ''; ?>>
        <label class="form-check-label" for="projects">Projects</label>
      </div>
      </div>
      <div class="col-12 col-sm-6 col-lg-4">
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" id="news" name="news" <?php echo $val('news') ? 'checked' : ''; ?>>
        <label class="form-check-label" for="news">News</label>
      </div>
      </div>
      <div class="col-12 col-sm-6 col-lg-4">
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" id="contact" name="contact" <?php echo $val('contact') ? 'checked' : ''; ?>>
        <label class="form-check-label" for="contact">Contact</label>
      </div>
      </div>
      <div class="col-12 col-sm-6 col-lg-4">
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" id="infos" name="infos" <?php echo $val('infos') ? 'checked' : ''; ?>>
        <label class="form-check-label" for="infos">Infos (Dropdown)</label>
      </div>
      </div>
      <!-- calculation option removed -->
      </div>
    
    <hr class="my-4">
    <div class="d-flex align-items-center mb-2">
      <h6 class="m-0">Infos Submenu</h6>
      <span class="badge bg-secondary ms-2">toggle visibility of dropdown items</span>
    </div>
    <div class="row g-3">
      <div class="col-12 col-sm-6 col-lg-4">
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" id="catalogues" name="catalogues" <?php echo $val('catalogues') ? 'checked' : ''; ?>>
          <label class="form-check-label" for="catalogues">Catalogues</label>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-lg-4">
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" id="gallery" name="gallery" <?php echo $val('gallery') ? 'checked' : ''; ?>>
          <label class="form-check-label" for="gallery">Gallery</label>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-lg-4">
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" id="certificates" name="certificates" <?php echo $val('certificates') ? 'checked' : ''; ?>>
          <label class="form-check-label" for="certificates">Certificates</label>
        </div>
      </div>
    </div>

    <hr class="my-4">
    <div class="d-flex align-items-center mb-2">
      <h6 class="m-0">Services Submenu</h6>
      <span class="badge bg-secondary ms-2">toggle visibility per service</span>
      <div class="ms-auto" style="max-width:280px;">
        <input id="searchServices" type="text" class="form-control form-control-sm" placeholder="Search services...">
      </div>
    </div>
    <div class="row" id="servicesList">
    <?php
      // Fetch current service toggles
      $svcRows = $pdo->query("SELECT item_id, is_enabled FROM menu_item_settings WHERE type='service'")->fetchAll(PDO::FETCH_KEY_PAIR);
      foreach ($services as $svc):
        $sid = (int)$svc['id'];
        $enabled = isset($svcRows[$sid]) ? (int)$svcRows[$sid] : 1;
        $title = $svc['title_en'] ?? $svc['title_tm'] ?? $svc['title_ru'] ?? ('Service #'.$sid);
    ?>
      <div class="col-12 col-md-6 col-lg-4 mb-2 toggle-item" data-title="<?php echo htmlspecialchars(strtolower($title)); ?>">
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" id="service_<?php echo $sid; ?>" name="service_<?php echo $sid; ?>" <?php echo $enabled ? 'checked' : ''; ?>>
          <label class="form-check-label" for="service_<?php echo $sid; ?>"><?php echo htmlspecialchars($title); ?></label>
        </div>
      </div>
    <?php endforeach; ?>
    </div>

    <hr class="my-4">
    <div class="d-flex align-items-center mb-2">
      <h6 class="m-0">Projects Submenu</h6>
      <span class="badge bg-secondary ms-2">toggle visibility per project</span>
      <div class="ms-auto" style="max-width:280px;">
        <input id="searchProjects" type="text" class="form-control form-control-sm" placeholder="Search projects...">
      </div>
    </div>
    <div class="row" id="projectsList">
    <?php
      // Fetch current project toggles
      $projRows = $pdo->query("SELECT item_id, is_enabled FROM menu_item_settings WHERE type='project'")->fetchAll(PDO::FETCH_KEY_PAIR);
      foreach ($all_projects as $proj):
        $pid = (int)$proj['id'];
        $enabled = isset($projRows[$pid]) ? (int)$projRows[$pid] : 1;
        $title = $proj['title_en'] ?? $proj['title_tm'] ?? $proj['title_ru'] ?? ('Project #'.$pid);
    ?>
      <div class="col-12 col-md-6 col-lg-4 mb-2 toggle-item" data-title="<?php echo htmlspecialchars(strtolower($title)); ?>">
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" id="project_<?php echo $pid; ?>" name="project_<?php echo $pid; ?>" <?php echo $enabled ? 'checked' : ''; ?>>
          <label class="form-check-label" for="project_<?php echo $pid; ?>"><?php echo htmlspecialchars($title); ?></label>
        </div>
      </div>
    <?php endforeach; ?>
    </div>

      <div class="d-flex justify-content-end mt-3">
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-save"></i> Save Changes
        </button>
      </div>
    
    </form>
  </div>
</div>

<div class="card shadow-sm mt-4">
  <div class="card-body">
    <h5 class="card-title mb-2 d-flex align-items-center"><i class="bi bi-link-45deg me-2"></i> Custom Menu Links</h5>
    <p class="text-muted mb-3">Add your own links to the header or footer menus.</p>
    <?php echo $alert; ?>
    <form method="post" class="mb-4">
      <input type="hidden" name="action" value="add_custom_link">
      <div class="row g-3">
        <div class="col-12 col-md-6">
          <label class="form-label">Label (EN)</label>
          <input type="text" name="label_en" class="form-control" required>
        </div>
        <div class="col-12 col-md-6">
          <label class="form-label">Label (TM)</label>
          <input type="text" name="label_tm" class="form-control">
        </div>
        <div class="col-12 col-md-6">
          <label class="form-label">Label (RU)</label>
          <input type="text" name="label_ru" class="form-control">
        </div>
        <div class="col-12 col-md-6">
          <label class="form-label">URL</label>
          <input type="url" name="url" class="form-control" placeholder="https://example.com" required>
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label">Target</label>
          <select name="target" class="form-select">
            <option value="">Same tab</option>
            <option value="_blank">New tab</option>
          </select>
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label">Position</label>
          <select name="position" class="form-select">
            <option value="header">Header</option>
            <option value="footer">Footer</option>
          </select>
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label">Sort Order</label>
          <input type="number" name="sort_order" class="form-control" value="0">
        </div>
        <div class="col-12 col-md-3 d-flex align-items-end">
          <button type="submit" class="btn btn-success w-100"><i class="bi bi-plus-circle"></i> Add Link</button>
        </div>
      </div>
    </form>

    <form method="post">
      <div class="table-responsive">
        <table class="table table-striped align-middle">
          <thead>
            <tr>
              <th>ID</th>
              <th>Position</th>
              <th>Label (EN)</th>
              <th>URL</th>
              <th>Sort</th>
              <th>Enabled</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($custom_links as $cl): ?>
              <tr>
                <td><?php echo (int)$cl['id']; ?></td>
                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($cl['position']); ?></span></td>
                <td><?php echo htmlspecialchars($cl['label_en']); ?></td>
                <td><a href="<?php echo htmlspecialchars($cl['url']); ?>" target="_blank"><?php echo htmlspecialchars($cl['url']); ?></a></td>
                <td><?php echo (int)$cl['sort_order']; ?></td>
                <td>
                  <input type="checkbox" name="custom_enabled[<?php echo (int)$cl['id']; ?>]" value="1" <?php echo ((int)$cl['is_enabled'] ? 'checked' : ''); ?>>
                </td>
                <td>
                  <a href="?module=menu_settings&delete_custom=<?php echo (int)$cl['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this link?');"><i class="bi bi-trash"></i></a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="d-flex justify-content-end">
        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save Link Toggles</button>
      </div>
    </form>
  </div>
</div>

<script>
  (function(){
    function attachFilter(inputId, listId){
      var input = document.getElementById(inputId);
      var list = document.getElementById(listId);
      if(!input || !list) return;
      input.addEventListener('input', function(){
        var term = input.value.trim().toLowerCase();
        var items = list.querySelectorAll('.toggle-item');
        items.forEach(function(el){
          var title = el.getAttribute('data-title') || '';
          el.style.display = (!term || title.indexOf(term) !== -1) ? '' : 'none';
        });
      });
    }
    attachFilter('searchServices','servicesList');
    attachFilter('searchProjects','projectsList');
  })();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
