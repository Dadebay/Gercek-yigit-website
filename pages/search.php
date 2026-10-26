<?php
require_once __DIR__ . '/../inc/functions.php';
$lang = get_lang();

if (!isset($pdo)) {
    @include_once __DIR__ . '/../admin/includes/db.php';
}

$q = trim($_GET['q'] ?? '');
$hasQuery = $q !== '';

function localized_value_search($row, $base, $lang) {
  $key = $base . '_' . $lang;
  if (!empty($row[$key])) return $row[$key];
  foreach (['en','tm','ru'] as $fallback) {
    $fk = $base . '_' . $fallback;
    if (!empty($row[$fk])) return $row[$fk];
  }
  return $row[$base] ?? '';
}

$results = [
  'services' => [],
  'news' => [],
  'catalogues' => [],
  'certificates' => [],
  'gallery' => [],
  'projects' => []
];

if ($hasQuery && isset($pdo)) {
  $term = '%' . $q . '%';

  // Services
  try {
    $stmt = $pdo->prepare(
      "SELECT id, title_en, title_tm, title_ru FROM services
       WHERE title_en LIKE ? OR title_tm LIKE ? OR title_ru LIKE ?
          OR description_en LIKE ? OR description_tm LIKE ? OR description_ru LIKE ?
       ORDER BY id DESC LIMIT 50"
    );
    $stmt->execute([$term,$term,$term,$term,$term,$term]);
    $rows = $stmt->fetchAll();
    foreach ($rows as $r) {
      $results['services'][] = [
        'id' => (int)$r['id'],
        'title' => localized_value_search($r, 'title', $lang) ?: 'Service',
        'link' => 'index.php?page=service_detail&id=' . (int)$r['id']
      ];
    }
  } catch (Exception $e) {}

  // News (posts)
  try {
    $stmt = $pdo->prepare(
      "SELECT id, title_en, title_tm, title_ru, short_description_en, short_description_tm, short_description_ru, description_en, description_tm, description_ru
       FROM posts
       WHERE title_en LIKE ? OR title_tm LIKE ? OR title_ru LIKE ?
          OR short_description_en LIKE ? OR short_description_tm LIKE ? OR short_description_ru LIKE ?
          OR description_en LIKE ? OR description_tm LIKE ? OR description_ru LIKE ?
       ORDER BY date DESC, id DESC LIMIT 50"
    );
    $stmt->execute([$term,$term,$term,$term,$term,$term,$term,$term,$term]);
    $rows = $stmt->fetchAll();
    foreach ($rows as $r) {
      $results['news'][] = [
        'id' => (int)$r['id'],
        'title' => localized_value_search($r, 'title', $lang) ?: 'News',
        'link' => 'index.php?page=news_detail&id=' . (int)$r['id']
      ];
    }
  } catch (Exception $e) {}

  // Catalogues
  try {
    $stmt = $pdo->prepare(
      "SELECT id, title_en, title_tm, title_ru, description_en, description_tm, description_ru
       FROM catalogues
       WHERE title_en LIKE ? OR title_tm LIKE ? OR title_ru LIKE ?
          OR description_en LIKE ? OR description_tm LIKE ? OR description_ru LIKE ?
       ORDER BY id DESC LIMIT 50"
    );
    $stmt->execute([$term,$term,$term,$term,$term,$term]);
    $rows = $stmt->fetchAll();
    foreach ($rows as $r) {
      $results['catalogues'][] = [
        'id' => (int)$r['id'],
        'title' => localized_value_search($r, 'title', $lang) ?: 'Catalogue',
        'link' => 'index.php?page=catalogues'
      ];
    }
  } catch (Exception $e) {}

  // Certificates (using our_partners as source)
  try {
    $stmt = $pdo->prepare(
      "SELECT id, title_en, title_tm, title_ru FROM our_partners
       WHERE title_en LIKE ? OR title_tm LIKE ? OR title_ru LIKE ?
       ORDER BY id DESC LIMIT 50"
    );
    $stmt->execute([$term,$term,$term]);
    $rows = $stmt->fetchAll();
    foreach ($rows as $r) {
      $results['certificates'][] = [
        'id' => (int)$r['id'],
        'title' => localized_value_search($r, 'title', $lang) ?: 'Certificate',
        'link' => 'index.php?page=certificates'
      ];
    }
  } catch (Exception $e) {}

  // Gallery
  try {
    $stmt = $pdo->prepare(
      "SELECT id, title_en, title_tm, title_ru, description_en, description_tm, description_ru FROM gallery
       WHERE title_en LIKE ? OR title_tm LIKE ? OR title_ru LIKE ?
          OR description_en LIKE ? OR description_tm LIKE ? OR description_ru LIKE ?
       ORDER BY id DESC LIMIT 50"
    );
    $stmt->execute([$term,$term,$term,$term,$term,$term]);
    $rows = $stmt->fetchAll();
    foreach ($rows as $r) {
      $results['gallery'][] = [
        'id' => (int)$r['id'],
        'title' => localized_value_search($r, 'title', $lang) ?: 'Gallery',
        'link' => 'index.php?page=gallery'
      ];
    }
  } catch (Exception $e) {}

  // Projects
  try {
    $stmt = $pdo->prepare(
      "SELECT id, title_en, title_tm, title_ru, description_en, description_tm, description_ru FROM projects
       WHERE title_en LIKE ? OR title_tm LIKE ? OR title_ru LIKE ?
          OR description_en LIKE ? OR description_tm LIKE ? OR description_ru LIKE ?
       ORDER BY id DESC LIMIT 50"
    );
    $stmt->execute([$term,$term,$term,$term,$term,$term]);
    $rows = $stmt->fetchAll();
    foreach ($rows as $r) {
      $results['projects'][] = [
        'id' => (int)$r['id'],
        'title' => localized_value_search($r, 'title', $lang) ?: 'Project',
        'link' => 'index.php?page=project_detail&id=' . (int)$r['id']
      ];
    }
  } catch (Exception $e) {}
}
?><?php page_intro(ui('search_title'),t('search'),ui('search_desc')); ?>
<section class="h-shell"><form class="p-search" method="get" action="index.php" role="search"><input type="hidden" name="page" value="search"><input type="hidden" name="lang" value="<?= $lang==='tm'?'tkm':e($lang) ?>"><label for="page-search"><?= e(t('search')) ?><input id="page-search" name="q" type="search" value="<?= e($q) ?>" placeholder="<?= e(t('search_placeholder')) ?>" required></label><button class="h-button" type="submit"><?= e(t('search')) ?><span class="h-button-arrow">↗</span></button></form>
<div class="p-search-results"><?php if($hasQuery):$total=array_sum(array_map('count',$results)); ?><p class="h-eyebrow"><?= e(ui('results')) ?> / <?= $total ?></p><?php if(!$total): ?><p class="p-notice"><?= e(t('no_results_found')) ?> “<?= e($q) ?>”</p><?php endif; foreach($results as $key=>$rows):if(!$rows)continue; ?><section class="p-result-group"><h2><?= e(t($key)) ?></h2><?php foreach($rows as $item): ?><a class="p-result" href="<?= e($item['link']) ?>"><span><?= e($item['title']) ?></span><span aria-hidden="true">↗</span></a><?php endforeach; ?></section><?php endforeach;else: ?><div class="p-filters"><?php foreach([2,3,4,1] as $sector_id):$sector=sector_data($sector_id); ?><a class="h-text-link" href="index.php?page=service_detail&amp;id=<?= $sector_id ?>"><?= e($sector['title']) ?><span>↗</span></a><?php endforeach; ?></div><?php endif; ?></div></section>
