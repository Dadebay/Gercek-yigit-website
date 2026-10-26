<?php
// Safe, one-off script to set company_info_common.logo_bent_tech to an uploaded file
// Usage (Windows): C:\xampp\php\php.exe -f C:\xampp\htdocs\gercekyigit\admin\tools\set_logo_bent_tech.php

require_once __DIR__ . '/../includes/db.php';
header('Content-Type: text/plain');

try {
    $uploadsDir = realpath(__DIR__ . '/../uploads/company_infos');
    if (!$uploadsDir) {
        throw new Exception('Uploads directory not found');
    }
    // Find a candidate file: prefer one containing "_bent_tech_"
    $files = scandir($uploadsDir);
    $candidate = null;
    foreach ($files as $f) {
        if ($f === '.' || $f === '..') continue;
        $path = $uploadsDir . DIRECTORY_SEPARATOR . $f;
        if (!is_file($path)) continue;
        if (stripos($f, '_bent_tech_') !== false) {
            $candidate = $f;
            break;
        }
    }
    
    // fallback: use favicon.svg in site img folder
    if (!$candidate) {
        $favSvg = realpath(__DIR__ . '/../../img/favicon.svg');
        if (!$favSvg) throw new Exception('No bent_tech logo found and favicon.svg missing');
        // Copy into uploads so admin path is consistent
        $destName = uniqid() . '_bent_tech_fallback.svg';
        $destPath = $uploadsDir . DIRECTORY_SEPARATOR . $destName;
        if (!copy($favSvg, $destPath)) throw new Exception('Failed to copy favicon.svg to uploads');
        $candidate = $destName;
    }

    // Build relative path from admin root
    $relative = 'uploads/company_infos/' . $candidate;
    
    // Ensure a row exists
    $row = $pdo->query('SELECT id FROM company_info_common LIMIT 1')->fetch();
    if ($row) {
        $stmt = $pdo->prepare('UPDATE company_info_common SET logo_bent_tech = ? WHERE id = ?');
        $stmt->execute([$relative, $row['id']]);
        echo "UPDATED\t" . $relative . "\n";
    } else {
        $stmt = $pdo->prepare('INSERT INTO company_info_common (phone, email, logo_bent_tech) VALUES (?, ?, ?)');
        $stmt->execute(['', '', $relative]);
        echo "INSERTED\t" . $relative . "\n";
    }
    echo "OK\n";
} catch (Exception $e) {
    echo 'ERR: ' . $e->getMessage() . "\n";
}
