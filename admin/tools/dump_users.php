<?php
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: text/plain');
try {
    $stmt = $pdo->query('SELECT id, username, password, updated_at FROM users');
    $rows = $stmt->fetchAll();
    if (!$rows) {
        echo "NO_USERS\n";
    } else {
        foreach ($rows as $r) {
            echo $r['id'] . "\t" . $r['username'] . "\t" . $r['password'] . "\t" . ($r['updated_at'] ?? '') . "\n";
        }
    }
} catch (Exception $e) {
    echo 'ERR: ' . $e->getMessage();
}
