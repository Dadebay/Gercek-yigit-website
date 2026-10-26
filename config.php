<?php
// Private connection details live outside the web root. Environment values take precedence.
$projectPrivateConfigPath = dirname(__DIR__) . '/private-backups/custom-site-db.json';
$privateConfigPath = is_file($projectPrivateConfigPath)
    ? $projectPrivateConfigPath
    : dirname(__DIR__, 2) . '/gercekyigit-private-backups/custom-site-db.json';
$usePrivateConfig = PHP_SAPI !== 'cli-server' || getenv('GERCEK_USE_PRIVATE_DB') === '1';
$privateConfig = $usePrivateConfig && is_file($privateConfigPath) ? json_decode((string)file_get_contents($privateConfigPath), true) : [];
if (!is_array($privateConfig)) $privateConfig = [];
foreach (['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'DB_CHARSET'] as $key) {
    $env = getenv('GERCEK_' . $key);
    define($key, $env !== false ? $env : ($privateConfig[$key] ?? ''));
}
define('PRODUCTION_MODE', getenv('GERCEK_PRODUCTION_MODE') !== '0' && PHP_SAPI !== 'cli-server');
define('BASE_URL', getenv('GERCEK_BASE_URL') ?: '/');
if (PRODUCTION_MODE) {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
// Secure session cookies only on HTTPS requests; otherwise the contact form and
// admin login cannot keep a session while the site is still reachable over HTTP.
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
if (PRODUCTION_MODE && $isHttps) ini_set('session.cookie_secure', '1');
