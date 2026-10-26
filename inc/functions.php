<?php
// Language helper functions
function get_supported_langs() { return ['en','tm','ru']; }

function normalize_site_lang($value) {
    if (!is_string($value)) return null;
    $value = strtolower($value);
    if (in_array($value, ['tk', 'tkm'], true)) $value = 'tm';
    return in_array($value, get_supported_langs(), true) ? $value : null;
}

function html_lang($value) { return $value === 'tm' ? 'tk' : $value; }

function get_lang() {
    $supported = get_supported_langs();
    // Requested language via query string
    if (isset($_GET['lang']) && ($requested = normalize_site_lang($_GET['lang'])) !== null) {
        // Only set cookie if value differs and headers are not yet sent
        if (!isset($_COOKIE['site_lang']) || $_COOKIE['site_lang'] !== $requested) {
            if (!headers_sent()) {
                $expires = time()+60*60*24*30;
                $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
                setcookie('site_lang', $requested, [
                    'expires' => $expires,
                    'path' => '/',
                    'secure' => $secure,
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
            }
        }
        return $requested;
    }
    // Fallback to cookie if valid
    if (isset($_COOKIE['site_lang']) && ($saved = normalize_site_lang($_COOKIE['site_lang'])) !== null) {
        return $saved;
    }
    // Default language
    return 'en';
}

function load_translations() {
    $all = include __DIR__ . '/lang.php';
    $lang = get_lang();
    return $all[$lang] ?? $all['en'];
}

function t($key) {
    static $trans = null;
    if ($trans === null) $trans = load_translations();
    return $trans[$key] ?? $key;
}

// Resolve CMS uploads and local fallback assets from either / or a subfolder.
// Missing upload files use a real project image instead of a broken <img>.
function site_media_url($path, $fallback = 'img/generated/textile-about.webp') {
    $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    $prefix = $base === '' ? '/' : $base . '/';
    $candidate = trim((string)$path);
    if (preg_match('#^https?://#i', $candidate)) return $candidate;
    $candidate = ltrim($candidate, '/');
    if ($candidate !== '' && !str_starts_with($candidate, 'admin/') && !str_starts_with($candidate, 'img/')) {
        $candidate = 'admin/' . $candidate;
    }
    if ($candidate !== '' && !str_contains($candidate, '..') && is_file(__DIR__ . '/../' . $candidate)) {
        return $prefix . implode('/', array_map('rawurlencode', explode('/', $candidate)));
    }
    return $prefix . ltrim($fallback, '/');
}

// Build a URL to the current page but with a specific language code.
// Preserves existing query parameters like page, id, q, etc.
function current_url_with_lang($newLang) {
    $newLang = normalize_site_lang($newLang) ?? 'en';

    $path = $_SERVER['REQUEST_URI'] ?? '/';
    $parts = parse_url($path);
    $basePath = $parts['path'] ?? '/';
    // Start from current GET params, then override 'lang'
    $params = $_GET;
    $params['lang'] = $newLang === 'tm' ? 'tkm' : $newLang;
    // Remove empty values to keep URL tidy
    foreach ($params as $k => $v) {
        if ($v === '' || $v === null) unset($params[$k]);
    }
    $query = http_build_query($params);
    return $basePath . ($query ? ('?' . $query) : '');
}

// Safe preview text helper: trims text to a width with fallback when mbstring is missing
// Usage: preview_text($text, 160, '...')
if (!function_exists('preview_text')) {
    function preview_text($text, $width = 160, $marker = '...') {
        $t = (string)$text;
        if (function_exists('mb_strimwidth')) {
            return mb_strimwidth($t, 0, $width, $marker);
        }
        // Fallback without mbstring
        if (strlen($t) <= $width) return $t;
        return substr($t, 0, $width) . $marker;
    }
}
