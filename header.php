<?php
// Load configuration
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}

// Calculate base path for assets (works in root or subfolder)
$base_path = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
if ($base_path === '') { $base_path = '/'; }
if ($base_path !== '/') { $base_path .= '/'; } else { $base_path = '/'; }

require_once __DIR__ . '/inc/functions.php';
$lang = get_lang();

// Ensure session and CSRF token are available across header/footer/forms
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } catch (Exception $e) {
        $_SESSION['csrf_token'] = sha1(uniqid('', true));
    }
}

// Reuse admin PDO connection
// adjust path if your setup differs
try {
  require_once __DIR__ . '/admin/includes/db.php';
} catch (Exception $e) {
  // ignore - $pdo may be unavailable in some dev setups
}
// Load menu_settings flags and custom links if DB available
$menu_enabled = [];
$custom_header_links = [];
if (isset($pdo)) {
    try {
        $menu_enabled = $pdo->query("SELECT slug, is_enabled FROM menu_settings")->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
    } catch (Exception $e) {}
    try {
        $stmtCL = $pdo->query("SELECT id, label_en, label_tm, label_ru, url, target, sort_order FROM custom_menu_links WHERE position='header' AND is_enabled=1 ORDER BY sort_order ASC, id ASC");
        $custom_header_links = $stmtCL ? $stmtCL->fetchAll(PDO::FETCH_ASSOC) : [];
    } catch (Exception $e) {}
}
// determine current page for nav active state
$current_page = 'home';
if (isset($page) && $page) {
    $current_page = $page;
} elseif (!empty($_GET['page'])) {
    $current_page = preg_replace('/[^a-z0-9_\-]/i','', $_GET['page']);
} else {
    // fallback to request URI detection for static pages
    $req = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($req, 'contact') !== false) $current_page = 'contact';
}

$company_name = 'DEMO';
$company_address = 'Demo';
$social_facebook = '';
$social_twitter = '';
$social_instagram = '';
$social_linkedin = '';
$common_email = 'example@gmail.com';
$common_phone = '';
$secondary_email = '';
$secondary_phone = '';
$logo_sm = '';
$page_header_url = site_media_url('', 'img/generated/project-site.webp');
$nav_services = [];

if (isset($pdo)) {
  try {
    // try language-specific company info first
    $stmt = $pdo->prepare("SELECT name, address FROM company_infos WHERE lang_code = ? LIMIT 1");
    $stmt->execute([$lang]);
    $row = $stmt->fetch();
    if ($row) {
      if (!empty($row['name'])) $company_name = $row['name'];
      if (!empty($row['address'])) $company_address = $row['address'];
    } else {
      // fallback to any company info
      $stmt2 = $pdo->query("SELECT name, address FROM company_infos LIMIT 1");
      $r2 = $stmt2->fetch();
      if ($r2) {
        if (!empty($r2['name'])) $company_name = $r2['name'];
        if (!empty($r2['address'])) $company_address = $r2['address'];
      }
    }

        // load social links
        $s = $pdo->query("SELECT * FROM social_media LIMIT 1")->fetch();
    if ($s) {
      $social_facebook = $s['facebook'] ?? '';
      $social_twitter = $s['twitter'] ?? '';
      $social_instagram = $s['instagram'] ?? '';
      $social_linkedin = $s['linkedin'] ?? '';
    }

        // company common (email, small logo)
        $c = $pdo->query("SELECT email,email1,phone,phone1, logo_bent_tech FROM company_info_common LIMIT 1")->fetch();
        if ($c) {
            if (!empty($c['email'])) $common_email = $c['email'];
            if (!empty($c['phone'])) $common_phone = $c['phone'];
            $secondary_email = $c['email1'] ?? '';
            $secondary_phone = $c['phone1'] ?? '';
            if (!empty($c['logo_bent_tech'])) 
                $logo_sm = $c['logo_bent_tech'];
        }
                // load services for navbar
                $nav_services = [];
                try {
                        $sstmt = $pdo->query("SELECT s.id, s.title_en, s.title_tm, s.title_ru FROM services s LEFT JOIN menu_item_settings m ON m.type='service' AND m.item_id=s.id WHERE COALESCE(m.is_enabled, 1)=1 ORDER BY s.id ASC");
                        $nav_services = $sstmt->fetchAll();
                } catch (Exception $e) {
                        $nav_services = [];
                }
                // resolve page header image for current page (absolute URL so CSS doesn't prefix with css/)
                try {
                    $ph = $pdo->prepare("SELECT header_image FROM page_headers WHERE page_name = ? LIMIT 1");
                    $ph->execute([$current_page]);
                    $hi = $ph->fetchColumn();
                    if (!empty($hi)) {
                        $basePath = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
                        if ($basePath === '') { $basePath = '/'; }
                        $header_asset = str_starts_with(ltrim($hi, '/'), 'img/') ? $hi : 'uploads/' . ltrim($hi, '/');
                        $page_header_url = site_media_url($header_asset, 'img/generated/project-site.webp');
                    }
                } catch (Exception $e) {
                    // keep default
                }
  } catch (Exception $e) {
    // ignore DB errors in header to avoid breaking the frontend
  }
}
$page_titles = [
  'home' => t('home'), 'about' => t('about_us'), 'services' => t('services'),
  'service_detail' => t('services'), 'projects' => t('projects'),
  'project_detail' => t('projects'), 'certificates' => t('certificates'),
  'news' => t('news'), 'news_detail' => t('news'), 'contact' => t('contact_us'),
  'search' => t('search'), 'subscribe' => t('subscribe'), 'gallery' => t('gallery'),
  'catalogues' => t('catalogues')
];
$page_title = trim((string)($page_titles[$current_page] ?? t('site_name')));
if (isset($pdo) && in_array($current_page, ['service_detail', 'project_detail', 'news_detail'], true)) {
    $detail_id = max(0, (int)($_GET['id'] ?? 0));
    if ($detail_id > 0) {
        $detail_queries = [
            'service_detail' => "SELECT COALESCE(NULLIF(title_{$lang}, ''), title_en) FROM services WHERE id = ?",
            'project_detail' => "SELECT COALESCE(NULLIF(title_{$lang}, ''), title_en) FROM projects WHERE id = ?",
            'news_detail' => "SELECT COALESCE(NULLIF(title_{$lang}, ''), NULLIF(title_en, ''), title) FROM posts WHERE id = ?"
        ];
        try {
            $detail_stmt = $pdo->prepare($detail_queries[$current_page]);
            $detail_stmt->execute([$detail_id]);
            $detail_title = trim((string)$detail_stmt->fetchColumn());
            if ($detail_title !== '') $page_title = $detail_title;
        } catch (Exception $e) { /* keep section title */ }
    }
}
$site_title = trim((string)$company_name) ?: t('site_name');
$meta_description = $site_title . ' — ' . $page_title . '. ' . t('about_heading');
$is_editorial_home = $current_page === 'home';
$home_copy = require __DIR__ . '/inc/home-content.php';
require_once __DIR__ . '/inc/editorial.php';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(html_lang($lang)) ?>">
<head>
 <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
 <title><?= htmlspecialchars($current_page === 'home' ? $site_title : $page_title . ' | ' . $site_title) ?></title>
 <meta name="description" content="<?= htmlspecialchars($meta_description) ?>">
 <?php if (in_array($current_page,['search','subscribe'],true)): ?><meta name="robots" content="noindex,follow"><?php endif; ?>
 <meta property="og:title" content="<?= htmlspecialchars($page_title . ' | ' . $site_title) ?>">
 <meta property="og:description" content="<?= htmlspecialchars($meta_description) ?>">
 <meta name="theme-color" content="#1b241f">
 <script src="<?= $base_path ?>js/theme.js?v=20261014-2"></script>
 <meta property="og:image" content="<?= htmlspecialchars(site_media_url('','img/generated/sector-textile.webp')) ?>">
 <link rel="icon" href="<?= htmlspecialchars(site_media_url($logo_sm, 'img/favicon.svg')) ?>">
 <link rel="stylesheet" href="<?= $base_path ?>css/home.css?v=20261014-films">
 <link rel="stylesheet" href="<?= $base_path ?>css/typography.css?v=20261014">
 <link rel="stylesheet" href="<?= $base_path ?>css/pages.css?v=20261014-construction">
 <link rel="stylesheet" href="<?= $base_path ?>css/theme.css?v=20261014-nav-icons">
 <?php if ($is_editorial_home): ?><link rel="preload" as="image" href="<?= $base_path ?>img/generated/textile-film-poster.webp"><?php endif; ?>
</head>
<body class="editorial-home<?= $is_editorial_home ? '' : ' editorial-inner' ?>">
<?php require __DIR__ . '/inc/home-nav.php'; ?>
<main id="home-main">
