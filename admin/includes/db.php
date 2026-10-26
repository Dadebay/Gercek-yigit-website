<?php
// Load configuration
if (file_exists(__DIR__ . '/../../config.php')) {
    require_once __DIR__ . '/../../config.php';
}

// Local preview may override the deployed configuration through environment variables.
$host = getenv('GERCEK_DB_HOST') ?: (defined('DB_HOST') ? DB_HOST : '127.0.0.1');
$port = getenv('GERCEK_DB_PORT') ?: '3306';
$db   = getenv('GERCEK_DB_NAME') ?: (defined('DB_NAME') ? DB_NAME : 'gercekyigit');
$user = getenv('GERCEK_DB_USER') ?: (defined('DB_USER') ? DB_USER : '');
$pass = getenv('GERCEK_DB_PASS') !== false ? getenv('GERCEK_DB_PASS') : (defined('DB_PASS') ? DB_PASS : '');
$charset = getenv('GERCEK_DB_CHARSET') ?: ((defined('DB_CHARSET') && DB_CHARSET !== '') ? DB_CHARSET : 'utf8mb4');

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     // Frontend should not hard-fail on DB connection; admin can.
     if (defined('IN_ADMIN') && IN_ADMIN) {
        die("Database connection failed: " . $e->getMessage());
     } else {
        // Log error in production but allow page to render
        if (defined('PRODUCTION_MODE') && PRODUCTION_MODE) {
            error_log('DB Connection Failed: ' . $e->getMessage());
        }
        $pdo = null; // allow pages to render without DB-dependent sections
     }
}
?>
