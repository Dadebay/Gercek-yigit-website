<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Dil seçili değilse varsayılan ata
if (!isset($_SESSION['lang_code'])) {
    $_SESSION['lang_code'] = 'en'; // veya 'tr'
}
$current_lang_code = $_SESSION['lang_code'];

require_once 'db.php';
require_once 'functions.php';
check_auth();

$languages = get_languages($pdo);

if (isset($_GET['lang'])) {
    $new_lang_code = $_GET['lang'];
    $valid_lang = false;
    foreach ($languages as $lang) {
        if ($lang['code'] === $new_lang_code) {
            set_language($new_lang_code);
            $valid_lang = true;
            break;
        }
    }
    if ($valid_lang) {
        header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
        exit;
    }
}

// Simple translation array for the admin panel
$lang_text = [
    'en' => [
        'dashboard' => 'Dashboard',
        'contact' => 'Contact Messages',
        'about_us' => 'About Us',
        // 'our_vision' removed
        // 'our_mission' removed
        'projects' => 'Projects',
        'services' => 'Services',
        'menu_settings' => 'Menu Settings',
        'company_infos' => 'Company Infos',
        'page_headers' => 'Page Headers',
        'smtp_settings' => 'SMTP Settings',
        'posts' => 'Posts',
        'sliders' => 'Sliders',
        'users' => 'Users',
        'logout' => 'Logout',
        'welcome' => 'Welcome, ',
        'admin_panel' => 'Gerçek Ýigit Admin Panel',
        'our_partners' => 'Certificates',
        'our_mission' => 'Our partners',
        'social_media' => 'Social Media',
        'service_images' => 'Service Images',
        'gallery' => 'Gallery',
        'catalogues' => 'Catalogues',
        'translations' => 'Translations',
    ]
];

$t = $lang_text[$current_lang_code] ?? $lang_text['en'];

// Determine which module to load
$module = $_GET['module'] ?? 'dashboard';
$action = $_GET['action'] ?? 'list';
?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang_code; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['admin_panel']; ?> - <?php echo $t[$module] ?? ucfirst($module); ?></title>
    <link href="./assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./assets/icons/bootstrap-icons.min.css">
    <link rel="stylesheet" href="./assets/css/breeze-custom.css">
    <!-- <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script> -->
    <link rel="stylesheet" href="assets/css/jquery.dataTables.min.css">
    <script src="assets/js/jquery-3.6.0.min.js"></script>
    <script src="assets/js/jquery.dataTables.min.js"></script>
</head>
<body>

<?php
// Şirket ismi veritabanından
$stmt = $pdo->query("SELECT name FROM company_infos WHERE lang_code='en' LIMIT 1");
$company_row = $stmt->fetch();
$company_name = $company_row['name'] ?? 'Company';

// Kullanıcı bilgisi veritabanından
$current_user_id = $_SESSION['user_id'] ?? 0;
$stmt = $pdo->prepare("SELECT username, image FROM users WHERE id=? LIMIT 1");
$stmt->execute([$current_user_id]);
$user_row = $stmt->fetch();
$current_user = [
    'username' => $user_row['username'] ?? 'User',
    'image' => !empty($user_row['image']) ? $user_row['image'] : './uploads/users/default.png'
];
?>

<header class="sticky-top">
    <nav class="navbar topbar py-2">
        <div class="container-fluid align-items-center">
            <button class="btn btn-outline-secondary me-2 border-0 d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-label="Toggle navigation">
                <i class="bi bi-list fs-4"></i>
            </button>
            <a class="app-brand fw-bold text-dark text-decoration-none" href="index.php"><?php echo $company_name; ?></a>
            <div class="ms-auto d-flex align-items-center gap-2">
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php if (!empty($current_user['image'])): ?>
                            <img src="<?php echo $current_user['image']; ?>" class="rounded-circle me-2 avatar-thumb">
                        <?php else: ?>
                            <span class="bi bi-person-circle fs-4 text-muted me-2"></span>
                        <?php endif; ?>
                        <span class="fw-semibold text-dark d-none d-sm-inline"><?php echo htmlspecialchars($current_user['username']); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                        <li><a class="dropdown-item" href="index.php?module=profile"><i class="bi bi-person"></i> Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right"></i> <?php echo $t['logout']; ?></a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
</header>

<div class="container-fluid">
    <div class="row">
        <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block sidebar collapse">
            <div class="position-sticky pt-3 mt-md-0 mt-4 sidebar-sticky">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $module === 'dashboard' ? 'active' : ''; ?>" aria-current="page" href="index.php?module=dashboard">
                            <i class="bi bi-house-door"></i> &nbsp;
                            <?php echo $t['dashboard']; ?>
                        </a>
                    </li>
                    <?php
                        // Menu groups for collapsible sections
                        $content_group = ['about_us','our_partners','our_mission','posts','sliders','gallery','catalogues'];
                        $services_group = ['services','service_images'];
                        $settings_group = ['menu_settings','company_infos','social_media','page_headers','smtp_settings'];
                        $portfolio_group = ['projects'];
                        $users_group = ['users'];

                        $content_show = in_array($module, $content_group) ? 'show' : '';
                        $services_show = in_array($module, $services_group) ? 'show' : '';
                        $settings_show = in_array($module, $settings_group) ? 'show' : '';
                        $portfolio_show = in_array($module, $portfolio_group) ? 'show' : '';
                        $users_show = in_array($module, $users_group) ? 'show' : '';
                    ?>

                    <li class="nav-item mt-2">
                        <a class="nav-link d-flex justify-content-between align-items-center <?php echo $content_show ? '' : 'collapsed'; ?>" data-bs-toggle="collapse" href="#grpContent" role="button" aria-expanded="<?php echo $content_show ? 'true' : 'false'; ?>" aria-controls="grpContent">
                            <span><i class="bi bi-layout-text-window-reverse"></i> &nbsp; Content</span>
                            <i class="bi bi-chevron-down small"></i>
                        </a>
                        <div class="collapse <?php echo $content_show; ?>" id="grpContent">
                            <ul class="nav flex-column ms-3">
                                <li class="nav-item"><a class="nav-link <?php echo $module === 'about_us' ? 'active' : ''; ?>" href="index.php?module=about_us"><i class="bi bi-info-circle"></i> &nbsp; <?php echo $t['about_us']; ?></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo $module === 'our_mission' ? 'active' : ''; ?>" href="index.php?module=our_mission"><i class="bi bi-bullseye"></i> &nbsp; <?php echo $t['our_mission']; ?></a></li>
                                
                                <li class="nav-item"><a class="nav-link <?php echo $module === 'our_partners' ? 'active' : ''; ?>" href="index.php?module=our_partners"><i class="bi bi-people"></i> &nbsp; <?php echo $t['our_partners'] ?? 'Certificates'; ?></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo $module === 'posts' ? 'active' : ''; ?>" href="index.php?module=posts"><i class="bi bi-file-earmark-text"></i> &nbsp; <?php echo $t['posts']; ?></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo $module === 'sliders' ? 'active' : ''; ?>" href="index.php?module=sliders"><i class="bi bi-images"></i> &nbsp; <?php echo $t['sliders']; ?></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo $module === 'gallery' ? 'active' : ''; ?>" href="index.php?module=gallery"><i class="bi bi-image"></i> &nbsp; <?php echo $t['gallery'] ?? 'Gallery'; ?></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo $module === 'catalogues' ? 'active' : ''; ?>" href="index.php?module=catalogues"><i class="bi bi-journal-richtext"></i> &nbsp; <?php echo $t['catalogues'] ?? 'Catalogues'; ?></a></li>
                            </ul>
                        </div>
                    </li>

                    <li class="nav-item mt-2">
                        <a class="nav-link d-flex justify-content-between align-items-center <?php echo $portfolio_show ? '' : 'collapsed'; ?>" data-bs-toggle="collapse" href="#grpPortfolio" role="button" aria-expanded="<?php echo $portfolio_show ? 'true' : 'false'; ?>" aria-controls="grpPortfolio">
                            <span><i class="bi bi-kanban"></i> &nbsp; Portfolio</span>
                            <i class="bi bi-chevron-down small"></i>
                        </a>
                        <div class="collapse <?php echo $portfolio_show; ?>" id="grpPortfolio">
                            <ul class="nav flex-column ms-3">
                                <li class="nav-item"><a class="nav-link <?php echo $module === 'projects' ? 'active' : ''; ?>" href="index.php?module=projects"><i class="bi bi-kanban"></i> &nbsp; <?php echo $t['projects']; ?></a></li>
                            </ul>
                        </div>
                    </li>

                    <li class="nav-item mt-2">
                        <a class="nav-link d-flex justify-content-between align-items-center <?php echo $services_show ? '' : 'collapsed'; ?>" data-bs-toggle="collapse" href="#grpServices" role="button" aria-expanded="<?php echo $services_show ? 'true' : 'false'; ?>" aria-controls="grpServices">
                            <span><i class="bi bi-gear"></i> &nbsp; Services</span>
                            <i class="bi bi-chevron-down small"></i>
                        </a>
                        <div class="collapse <?php echo $services_show; ?>" id="grpServices">
                            <ul class="nav flex-column ms-3">
                                <li class="nav-item"><a class="nav-link <?php echo $module === 'services' ? 'active' : ''; ?>" href="index.php?module=services"><i class="bi bi-gear"></i> &nbsp; <?php echo $t['services']; ?></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo $module === 'service_images' ? 'active' : ''; ?>" href="index.php?module=service_images"><i class="bi bi-image"></i> &nbsp; <?php echo $t['service_images']; ?></a></li>
                            </ul>
                        </div>
                    </li>

                    <li class="nav-item mt-2">
                        <a class="nav-link d-flex justify-content-between align-items-center <?php echo $settings_show ? '' : 'collapsed'; ?>" data-bs-toggle="collapse" href="#grpSettings" role="button" aria-expanded="<?php echo $settings_show ? 'true' : 'false'; ?>" aria-controls="grpSettings">
                            <span><i class="bi bi-sliders"></i> &nbsp; Settings</span>
                            <i class="bi bi-chevron-down small"></i>
                        </a>
                        <div class="collapse <?php echo $settings_show; ?>" id="grpSettings">
                            <ul class="nav flex-column ms-3">
                                <li class="nav-item"><a class="nav-link <?php echo $module === 'menu_settings' ? 'active' : ''; ?>" href="index.php?module=menu_settings"><i class="bi bi-menu-button-wide"></i> &nbsp; <?php echo $t['menu_settings']; ?></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo $module === 'company_infos' ? 'active' : ''; ?>" href="index.php?module=company_infos"><i class="bi bi-building"></i> &nbsp; <?php echo $t['company_infos']; ?></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo $module === 'social_media' ? 'active' : ''; ?>" href="index.php?module=social_media"><i class="bi bi-share"></i> &nbsp; <?php echo $t['social_media']; ?></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo $module === 'page_headers' ? 'active' : ''; ?>" href="index.php?module=page_headers"><i class="bi bi-card-image"></i> &nbsp; <?php echo $t['page_headers']; ?></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo $module === 'smtp_settings' ? 'active' : ''; ?>" href="index.php?module=smtp_settings"><i class="bi bi-envelope-at"></i> &nbsp; <?php echo $t['smtp_settings']; ?></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo $module === 'translations' ? 'active' : ''; ?>" href="index.php?module=translations"><i class="bi bi-translate"></i> &nbsp; <?php echo $t['translations'] ?? 'Translations'; ?></a></li>
                                <li class="nav-item"><hr class="dropdown-divider my-1"></li>
                                <!-- calculation menu items removed -->
                            </ul>
                        </div>
                    </li>

                    <li class="nav-item mt-2">
                        <a class="nav-link d-flex justify-content-between align-items-center <?php echo $users_show ? '' : 'collapsed'; ?>" data-bs-toggle="collapse" href="#grpUsers" role="button" aria-expanded="<?php echo $users_show ? 'true' : 'false'; ?>" aria-controls="grpUsers">
                            <span><i class="bi bi-people"></i> &nbsp; Users</span>
                            <i class="bi bi-chevron-down small"></i>
                        </a>
                        <div class="collapse <?php echo $users_show; ?>" id="grpUsers">
                            <ul class="nav flex-column ms-3">
                                <li class="nav-item"><a class="nav-link <?php echo $module === 'users' ? 'active' : ''; ?>" href="index.php?module=users"><i class="bi bi-people"></i> &nbsp; <?php echo $t['users']; ?></a></li>
                            </ul>
                        </div>
                    </li>
                </ul>
            </div>
        </nav>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-1 pb-2 mb-2 border-bottom">
                <h1 class="h2"><?php echo $t[$module] ?? ucfirst($module); ?></h1>
            </div>