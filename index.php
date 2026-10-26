<?php
require_once __DIR__ . '/inc/functions.php';
$lang = get_lang();
$page = $_GET['page'] ?? 'home';
// sanitize page name
$page = preg_replace('/[^a-z0-9_\-]/i','', $page);

// allowed pages map to files in pages/
$allowed = ['home','about','services','service_detail','projects','project_detail','certificates','news','news_detail','contact','search','subscribe','gallery','catalogues'];
if (!in_array($page, $allowed)) $page = 'home';

if ($page === 'services' && (int)($_GET['id'] ?? 0) > 0) $page = 'service_detail';

require_once __DIR__ . '/header.php';

$path = __DIR__ . '/pages/' . $page . '.php';
if (file_exists($path)) {
    include $path;
} else {
    echo "<div class='alert alert-warning'>Page not found: " . htmlspecialchars($page) . "</div>";
}

require_once __DIR__ . '/footer.php';
