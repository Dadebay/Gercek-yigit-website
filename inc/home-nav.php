<?php $he = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); ?>
<a class="h-skip" href="#home-main"><?= $he($home_copy['skip']) ?></a>
<header class="h-nav" id="site-nav">
 <a class="h-brand" href="<?= $base_path ?>index.php" aria-label="<?= $he($site_title) ?>">
  <?php if ($logo_sm): ?><img src="<?= $he(site_media_url($logo_sm)) ?>" width="44" height="44" alt=""><?php endif; ?>
  <span>GERÇEK ÝIGIT<small><?= $he($home_copy['brand_line']) ?></small></span>
 </a>
 <nav class="h-nav-links" aria-label="<?= $he(t('menu')) ?>">
  <?php if ((int)($menu_enabled['about'] ?? 1)): ?><a href="<?= $base_path ?>index.php?page=about"<?= $current_page === 'about' ? ' aria-current="page"' : '' ?>><?= $he($home_copy['company']) ?></a><?php endif; ?>
  <?php if ((int)($menu_enabled['services'] ?? 1)): ?><a href="<?= $is_editorial_home ? '#expertise' : $base_path . 'index.php?page=services' ?>"<?= in_array($current_page,['services','service_detail'],true) ? ' aria-current="location"' : '' ?>><?= $he($home_copy['expertise']) ?></a><?php endif; ?>
  <a href="<?= $is_editorial_home ? '#inside' : $base_path . 'index.php?page=gallery' ?>"<?= $current_page === 'gallery' ? ' aria-current="page"' : '' ?>><?= $he($home_copy['inside']) ?></a>
  <?php if ((int)($menu_enabled['news'] ?? 1)): ?><a href="<?= $is_editorial_home ? '#journal' : $base_path . 'index.php?page=news' ?>"<?= in_array($current_page,['news','news_detail'],true) ? ' aria-current="location"' : '' ?>><?= $he($home_copy['journal']) ?></a><?php endif; ?>
  <details class="h-dropdown"><summary><?= $he($home_copy['more']) ?><svg class="h-nav-icon h-nav-chevron" width="20" height="20" viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5"/></svg></summary>
   <div class="h-popover">
    <?php foreach (['services','gallery','certificates','projects','catalogues'] as $nav_page): if (!(int)($menu_enabled[$nav_page] ?? 1)) continue; ?>
    <a href="<?= $base_path ?>index.php?page=<?= $nav_page ?>"<?= $current_page === $nav_page ? ' aria-current="page"' : '' ?>><?= $he(t($nav_page)) ?><svg class="h-nav-icon" width="20" height="20" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 17 17 7M7 7h10v10"/></svg></a>
    <?php endforeach; ?>
    <?php foreach ($custom_header_links as $custom): ?><a href="<?= $he($custom['url']) ?>"<?= ($custom['target'] ?? '') === '_blank' ? ' target="_blank" rel="noopener noreferrer"' : '' ?>><?= $he($custom['label_'.$lang] ?: $custom['label_en']) ?><svg class="h-nav-icon" width="20" height="20" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 17 17 7M7 7h10v10"/></svg></a><?php endforeach; ?>
   </div>
  </details>
 </nav>
 <div class="h-nav-actions">
  <?php require __DIR__ . '/language-switcher.php'; ?>
  <button class="h-nav-icon-button h-theme-toggle" type="button" data-theme-toggle data-label-dark="<?= e(ui('theme_dark')) ?>" data-label-light="<?= e(ui('theme_light')) ?>" aria-label="<?= e(ui('theme_dark')) ?>" title="<?= e(ui('theme_dark')) ?>" hidden>
   <svg class="h-nav-icon h-theme-sun" width="20" height="20" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3.75"/><path d="M12 2v2m0 16v2M2 12h2m16 0h2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/></svg>
   <svg class="h-nav-icon h-theme-moon" width="20" height="20" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.7 13.4A8.7 8.7 0 0 1 10.6 3.3a8.7 8.7 0 1 0 10.1 10.1Z"/></svg>
  </button>
  <a class="h-nav-icon-button h-search-link" href="<?= $base_path ?>index.php?page=search" aria-label="<?= $he(t('search')) ?>"><svg class="h-nav-icon" width="20" height="20" viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/></svg></a>
  <a class="h-nav-contact" href="<?= $base_path ?>index.php?page=contact"><?= $he($home_copy['talk']) ?><svg class="h-nav-icon" width="20" height="20" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 18 18 6M6 6h12v12"/></svg></a>
 </div>
 <div class="h-reading-progress" aria-hidden="true"></div>
</header>
