<?php
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: text/plain');
try {
    $newHash = '$2y$10$4igfPPuJ1G2TvrcBBiwsLOk2.UEA3bvezWtE/o5bGLTXXR4J6nAai'; // password: admin-admin
    $stmt = $pdo->prepare('UPDATE users SET password = ?, updated_at = NOW() WHERE username = ?');
    $stmt->execute([$newHash, 'admin']);
    echo "UPDATED_ROWS\t" . $stmt->rowCount() . "\n";
    $check = $pdo->prepare('SELECT id, username, password, updated_at FROM users WHERE username = ?');
    $check->execute(['admin']);
    $row = $check->fetch();
    if ($row) {
        echo $row['id'] . "\t" . $row['username'] . "\t" . $row['password'] . "\t" . $row['updated_at'] . "\n";
    } else {
        echo "ADMIN_NOT_FOUND\n";
    }
} catch (Exception $e) {
    echo 'ERR: ' . $e->getMessage() . "\n";
}
