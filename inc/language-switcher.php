<nav class="site-language-switch" aria-label="<?= htmlspecialchars(t('language')) ?>">
<?php foreach (['en'=>'EN', 'ru'=>'RU', 'tm'=>'TKM'] as $language_code=>$language_label): ?>
 <a href="<?= htmlspecialchars(current_url_with_lang($language_code)) ?>" lang="<?= html_lang($language_code) ?>" hreflang="<?= html_lang($language_code) ?>" title="<?= htmlspecialchars(t($language_code.'_lang')) ?>"<?= $lang === $language_code ? ' aria-current="true"' : '' ?>><?= $language_label ?></a>
<?php endforeach; ?>
</nav>
