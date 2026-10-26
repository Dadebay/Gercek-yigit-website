<?php
if (!function_exists('check_auth')) {
    function check_auth() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION["user_id"])) {
            header("Location: login.php");
            exit;
        }
    }
}

if (!function_exists('get_languages')) {
    function get_languages($pdo) {
        $stmt = $pdo->query("SELECT id, name, code FROM languages");
        return $stmt->fetchAll();
    }
}

if (!function_exists('get_current_lang_id')) {
    function get_current_lang_id($pdo) {
        // Default to 'en' if not set
        $lang_code = $_SESSION['lang_code'] ?? 'en';
        $stmt = $pdo->prepare("SELECT id FROM languages WHERE code = ?");
        $stmt->execute([$lang_code]);
        $lang = $stmt->fetch();
        return $lang['id'] ?? 1; // Default to ID 1 (English)
    }
}

if (!function_exists('set_language')) {
    function set_language($code) {
        $_SESSION['lang_code'] = $code;
    }
}

// Upload helpers
if (!function_exists('sanitize_basename')) {
    function sanitize_basename($name) {
        $base = basename($name);
        // allow letters, numbers, dot, dash, underscore
        $base = preg_replace('/[^A-Za-z0-9._-]/', '_', $base);
        return $base;
    }
}

if (!function_exists('save_uploaded_file')) {
    /**
     * Save an uploaded file with basic validation
     * @param array $file $_FILES[...] entry
     * @param string $targetDir Directory relative to admin root, e.g., "uploads/partners/"
     * @param array $allowedExts e.g., ['jpg','jpeg','png','gif','webp']
     * @param int $maxBytes Maximum file size in bytes
     * @return string|false Relative saved path or false on failure
     */
    function save_uploaded_file(array $file, $targetDir, array $allowedExts, $maxBytes = 5242880) {
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) return false;
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExts, true)) return false;
        if (!is_dir($targetDir)) {
            // create with safer permissions than 0777
            mkdir($targetDir, 0755, true);
        }
        if (!isset($file['size']) || $file['size'] > $maxBytes) return false;
        $safeName = sanitize_basename($file['name']);
        $filename = uniqid('', true) . '_' . $safeName;
        $targetFile = rtrim($targetDir, '/\\') . '/' . $filename;
        // Optional: verify mime
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            $imageMimes = ['image/jpeg','image/png','image/gif','image/webp'];
            $pdfMimes = ['application/pdf'];
            if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                if (!in_array($mime, $imageMimes, true)) return false;
            } elseif ($ext === 'pdf') {
                if (!in_array($mime, $pdfMimes, true)) return false;
            }
        }
        return move_uploaded_file($file['tmp_name'], $targetFile) ? $targetFile : false;
    }
}
?>
