<?php
require_once 'includes/header.php';
$module_file = __DIR__ . "/modules/{$module}.php";
if (preg_match('/\A[a-z0-9_]+\z/i', $module) && is_file($module_file)) {
    require_once $module_file;
} else {
    echo '<div class="alert alert-info">Welcome to the Admin Panel Dashboard. Select a module from the sidebar to manage content.</div>';
}
require_once 'includes/footer.php';
?>
