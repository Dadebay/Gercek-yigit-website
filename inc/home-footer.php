 </main>
 <footer class="h-footer">
  <div class="h-shell">
   <div class="h-footer-grid">
    <div class="h-footer-intro"><a class="h-brand" href="<?= $base_path ?>index.php"><span>GERÇEK ÝIGIT<small><?= $he($home_copy['brand_line']) ?></small></span></a><p><?= $he($home_copy['footer_line']) ?></p></div>
    <div><span class="h-eyebrow"><?= $he($home_copy['navigation']) ?></span><div class="h-footer-links"><?php foreach (['about','services','projects','gallery','certificates','news','catalogues','contact'] as $footer_page): if (!(int)($menu_enabled[$footer_page] ?? 1)) continue; ?><a href="<?= $base_path ?>index.php?page=<?= $footer_page ?>"><?= $he(t($footer_page === 'about' ? 'about_us' : $footer_page)) ?></a><?php endforeach; ?></div></div>
    <div><span class="h-eyebrow"><?= $he($home_copy['find']) ?></span><p class="h-address"><?= $he($company_address) ?></p><a class="h-text-link" href="https://www.google.com/maps/search/?api=1&amp;query=<?= rawurlencode($company_address) ?>" target="_blank" rel="noopener noreferrer"><?= $he($home_copy['ashgabat'] . ', ' . $home_copy['turkmenistan']) ?> <span aria-hidden="true">↗</span></a></div>
    <div><span class="h-eyebrow"><?= $he($home_copy['follow']) ?></span><div class="h-footer-links"><a href="mailto:<?= $he($common_email) ?>"><?= $he($common_email) ?></a><?php if ($common_phone): ?><a href="tel:<?= $he(preg_replace('/[^+0-9]/', '', $common_phone)) ?>"><?= $he($common_phone) ?></a><?php endif; ?><?php foreach (['Instagram'=>$social_instagram,'LinkedIn'=>$social_linkedin,'Facebook'=>$social_facebook] as $social_name=>$social_url): if (!filter_var($social_url, FILTER_VALIDATE_URL)) continue; ?><a href="<?= $he($social_url) ?>" target="_blank" rel="noopener noreferrer"><?= $social_name ?> ↗</a><?php endforeach; ?></div></div>
   </div>
   <div class="h-footer-wordmark" aria-hidden="true">GERÇEK ÝIGIT</div>
   <div class="h-footer-bottom"><span>© <?= date('Y') ?> <?= $he($site_title) ?></span><span><?= $he(t('all_rights_reserved')) ?></span><a href="#home-main"><?= $he($home_copy['back']) ?> ↑</a></div>
  </div>
 </footer>
 <?php require __DIR__ . '/media-dialog.php'; ?>
 <script src="<?= $base_path ?>js/pages.js?v=20261014" defer></script>
 <script src="<?= $base_path ?>js/home.js?v=20261014-films" defer></script>
</body>
</html>
