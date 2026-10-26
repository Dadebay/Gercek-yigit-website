<?php
// The homepage shares the existing PHP router, languages and CMS content.
$home_services = [];
$home_news = [];
if (isset($pdo)) {
    try {
        $rows = $pdo->query("SELECT s.* FROM services s LEFT JOIN menu_item_settings m ON m.type='service' AND m.item_id=s.id WHERE COALESCE(m.is_enabled,1)=1 ORDER BY s.id")->fetchAll();
        foreach ($rows as $row) $home_services[(int)$row['id']] = $row;
        $home_news = $pdo->query('SELECT * FROM posts ORDER BY date DESC, id DESC LIMIT 2')->fetchAll();
    } catch (Exception $e) { error_log('Homepage content unavailable: ' . $e->getMessage()); }
}
$sector_config = [
    2 => ['key'=>'textile', 'image'=>'img/generated/sector-textile.webp'],
    3 => ['key'=>'socks', 'image'=>'img/generated/sector-socks.webp'],
    4 => ['key'=>'construction', 'image'=>'img/generated/construction/hero.webp'],
    1 => ['key'=>'machinery', 'image'=>'img/generated/machinery/hero.webp'],
];
$home_sectors = [];
foreach ($sector_config as $id=>$sector) {
    if (isset($home_services[$id])) $home_sectors[$id] = $sector;
}
$home_poster = is_file(__DIR__ . '/../img/generated/textile-film-poster.webp') ? 'img/generated/textile-film-poster.webp' : 'img/generated/textile-hero.webp';
$home_films = array_values(array_filter([
 ['key'=>'textile','src'=>'media/textile-story-30s.mp4','poster'=>$home_poster,'duration'=>'00:30'],
 ['key'=>'construction','src'=>'media/construction-work.mp4','poster'=>'img/generated/construction-film-poster.webp','duration'=>'00:10'],
 ['key'=>'machinery','src'=>'media/construction-machinery.mp4','poster'=>'img/generated/machinery-film-poster.webp','duration'=>'00:10'],
], static fn($film)=>is_file(__DIR__.'/../'.$film['src']) && is_file(__DIR__.'/../'.$film['poster'])));
$has_home_video = count($home_films)>0;
if ($has_home_video) $home_poster=$home_films[0]['poster'];
?>
<section class="h-hero" aria-labelledby="hero-title">
 <div class="h-hero-media" aria-hidden="true">
  <img class="h-hero-poster" src="<?= $base_path . $home_poster ?>" alt="" width="1920" height="1080" fetchpriority="high">
  <?php if ($has_home_video): ?><video id="hero-film" class="h-hero-video" muted playsinline preload="none" poster="<?= $base_path . $home_poster ?>" data-src="<?= $base_path . $home_films[0]['src'] ?>" tabindex="-1"></video><?php endif; ?>
 </div>
 <div class="h-hero-shade" aria-hidden="true"></div>
 <div class="h-hero-body h-shell">
  <p class="h-eyebrow h-hero-eyebrow"><span class="h-dot" aria-hidden="true"></span><?= $he($home_copy['eyebrow']) ?></p>
  <h1 id="hero-title"><span class="h-line"><span><?= $he($home_copy['hero_line']) ?></span></span><span class="h-line"><span><?= $he($home_copy['hero_line2']) ?></span></span><span class="h-line"><em><?= $he($home_copy['hero_em']) ?></em></span></h1>
  <div class="h-hero-description"><p><?= $he($home_copy['hero_desc']) ?></p><a class="h-button h-button-light" href="#expertise"><span><?= $he($home_copy['discover']) ?></span><span class="h-button-arrow" aria-hidden="true">↗</span></a></div>
 </div>
 <div class="h-hero-bottom h-shell">
  <a href="#introduction" class="h-scroll-link"><span class="h-scroll-icon" aria-hidden="true">↓</span><?= $he($home_copy['scroll']) ?></a>
  <?php if ($has_home_video): ?>
  <div class="h-film-switcher" role="group" aria-label="<?= $he($home_copy['film_selector']) ?>" hidden>
   <?php foreach($home_films as $i=>$film): ?><button type="button" data-hero-film="<?= $he($film['key']) ?>" data-src="<?= $base_path . $film['src'] ?>" data-poster="<?= $base_path . $film['poster'] ?>" data-film-name="<?= $he($home_copy[$film['key']]) ?>" data-duration="<?= $he($film['duration']) ?>" aria-controls="hero-film" aria-pressed="<?= $i===0?'true':'false' ?>"><span class="h-film-number" aria-hidden="true"><?= str_pad($i+1,2,'0',STR_PAD_LEFT) ?></span><?= $he($home_copy['film_'.$film['key']]) ?></button><?php endforeach; ?>
  </div>
  <?php endif; ?>
  <div class="h-film-caption"><span class="h-film-line" aria-hidden="true"></span><span><span data-film-name><?= $he($has_home_video ? $home_copy[$home_films[0]['key']] : $home_copy['film']) ?></span><small><span data-film-duration><?= $has_home_video ? $he($home_films[0]['duration']) : '' ?></span> · <?= $he($home_copy['film_note']) ?></small></span><?php if ($has_home_video): ?><button class="h-film-toggle" hidden type="button" aria-controls="hero-film" aria-label="<?= $he($home_copy['pause']) ?>" data-pause-label="<?= $he($home_copy['pause']) ?>" data-play-label="<?= $he($home_copy['play']) ?>"><span aria-hidden="true">Ⅱ</span></button><?php endif; ?></div>
 </div>
</section>

<section class="h-introduction h-shell h-section" id="introduction" aria-labelledby="intro-title">
 <div class="h-intro-top"><p class="h-eyebrow h-reveal"><?= $he($home_copy['intro_label']) ?></p><div><h2 class="h-display h-reveal" id="intro-title"><?= $he($home_copy['intro']) ?><br><em><?= $he($home_copy['intro_em']) ?></em></h2><div class="h-intro-copy h-reveal"><p><?= $he($home_copy['intro_desc']) ?></p><a class="h-text-link" href="<?= $base_path ?>index.php?page=about"><?= $he($home_copy['story_link']) ?><span aria-hidden="true">↗</span></a></div></div></div>
 <div class="h-facts h-reveal"><div class="h-fact"><span class="h-fact-value">2020<span>↗</span></span><span class="h-eyebrow"><?= $he($home_copy['founded']) ?></span></div><div class="h-fact"><span class="h-fact-value"><?= str_pad((string)count($home_sectors),2,'0',STR_PAD_LEFT) ?><span>↗</span></span><span class="h-eyebrow"><?= $he($home_copy['fields']) ?></span></div><div class="h-fact h-fact-place"><span class="h-fact-value"><?= $he($home_copy['ashgabat']) ?><span>↗</span></span><span class="h-eyebrow"><?= $he($home_copy['turkmenistan']) ?> / <?= $he($home_copy['home']) ?></span></div></div>
</section>

<?php if ($home_sectors && (int)($menu_enabled['services'] ?? 1)): ?>
<section class="h-expertise h-section" id="expertise" aria-labelledby="expertise-title"><div class="h-shell">
 <div class="h-section-heading"><div><p class="h-eyebrow h-reveal"><?= $he($home_copy['expertise_label']) ?></p><h2 id="expertise-title" class="h-display h-reveal"><?= $he($home_copy['expertise_title']) ?><br><em><?= $he($home_copy['expertise_em']) ?></em></h2></div><p class="h-heading-aside h-reveal"><?= $he($home_copy['expertise_desc']) ?></p></div>
 <div class="h-sector-layout"><div class="h-sector-list">
 <?php $sector_index = 0; foreach ($home_sectors as $id=>$sector): $key=$sector['key']; ?>
  <a class="h-sector h-reveal<?= $sector_index === 0 ? ' is-active' : '' ?>" href="<?= $base_path ?>index.php?page=service_detail&amp;id=<?= $id ?>" data-sector="<?= $id ?>"><span class="h-sector-number"><?= str_pad((string)++$sector_index,2,'0',STR_PAD_LEFT) ?></span><div><h3><?= $he($home_copy[$key]) ?></h3><p><?= $he($home_copy[$key.'_desc']) ?></p></div><span class="h-sector-arrow" aria-hidden="true">↗</span></a>
 <?php endforeach; ?>
 </div><div class="h-sector-stage h-reveal" aria-hidden="true">
 <?php $sector_index = 0; foreach ($home_sectors as $id=>$sector): ?><div class="h-sector-visual<?= $sector_index++ === 0 ? ' is-active' : '' ?>" data-sector-image="<?= $id ?>"><img src="<?= $he(site_media_url($sector['image'])) ?>" alt="" width="900" height="1050" loading="lazy"><div class="h-sector-caption"><span><?= $he($home_copy[$sector['key']]) ?></span><span>0<?= $sector_index ?> / 0<?= count($home_sectors) ?></span></div></div><?php endforeach; ?>
 <span class="h-image-credit"><?= $he($home_copy['image_note']) ?></span>
 </div></div>
</div></section>
<?php endif; ?>

<section class="h-inside h-section h-shell" id="inside" aria-labelledby="inside-title">
 <div class="h-inside-grid"><figure class="h-inside-photo h-reveal"><img src="<?= $base_path ?>img/generated/textile-about.webp" alt="<?= $he($home_copy['image_note']) ?>" width="1024" height="1024" loading="lazy"><figcaption><span>GERÇEK ÝIGIT</span><span><?= $he($home_copy['craft']) ?> / 01</span></figcaption></figure><div class="h-inside-copy"><p class="h-eyebrow h-reveal"><?= $he($home_copy['inside_label']) ?></p><h2 class="h-display h-reveal" id="inside-title"><?= $he($home_copy['inside_title']) ?><br><em><?= $he($home_copy['inside_em']) ?></em></h2><p class="h-body-copy h-reveal"><?= $he($home_copy['inside_desc']) ?></p><a class="h-text-link h-reveal" href="<?= $base_path ?>index.php?page=gallery"><?= $he($home_copy['gallery_link']) ?><span aria-hidden="true">↗</span></a><div class="h-thread-mark" aria-hidden="true"><svg viewBox="0 0 360 140" fill="none"><?php for ($line=0;$line<16;$line++): ?><path d="M0 <?= 14+$line*5 ?> C90 <?= 150-$line*4 ?>, 220 <?= -30+$line*5 ?>, 360 <?= 40+$line*5 ?>" stroke="currentColor" stroke-width=".65"/><?php endfor; ?></svg></div></div></div>
 <div class="h-values"><?php foreach ($home_copy['values'] as $i=>$value): ?><article class="h-reveal"><span class="h-eyebrow">0<?= $i+1 ?> /</span><h3><?= $he($value) ?></h3><p><?= $he($home_copy['values_desc'][$i]) ?></p></article><?php endforeach; ?></div>
</section>

<section class="h-build" aria-labelledby="build-title"><div class="h-build-image" data-parallax><img src="<?= $base_path ?>img/generated/sector-construction.webp" alt="<?= $he($home_copy['image_note']) ?>" width="1536" height="1024" loading="lazy"></div><div class="h-build-shade"></div><div class="h-shell h-build-content"><p class="h-eyebrow h-reveal"><?= $he($home_copy['build_label']) ?></p><h2 class="h-reveal" id="build-title"><?= $he($home_copy['build_title']) ?><br><em><?= $he($home_copy['build_em']) ?></em></h2><div class="h-build-bottom"><p class="h-reveal"><?= $he($home_copy['build_desc']) ?></p><a class="h-button h-button-light h-reveal" href="<?= $base_path ?>index.php?page=service_detail&amp;id=4"><span><?= $he($home_copy['build_link']) ?></span><span class="h-button-arrow" aria-hidden="true">↗</span></a></div></div></section>

<?php if ($home_news && (int)($menu_enabled['news'] ?? 1)): ?>
<section class="h-journal h-section h-shell" id="journal" aria-labelledby="journal-title"><div class="h-section-heading"><div><p class="h-eyebrow h-reveal"><?= $he($home_copy['journal_label']) ?></p><h2 class="h-display h-reveal" id="journal-title"><?= $he($home_copy['journal_title']) ?> <em><?= $he($home_copy['journal_em']) ?></em></h2></div><a class="h-text-link h-reveal" href="<?= $base_path ?>index.php?page=news"><?= $he($home_copy['all_news']) ?><span aria-hidden="true">↗</span></a></div>
 <div class="h-news-grid"><?php foreach ($home_news as $news):
 $news_title=$news['title_'.$lang] ?? $news['title'] ?? '';
 $images=json_decode($news['images'] ?? '', true);
 $news_image=is_array($images) ? ($images[0] ?? '') : trim(explode(',', $news['images'] ?? '')[0]);
 $news_date=!empty($news['date']) ? date('d.m.Y', strtotime($news['date'])) : '';
 ?><article class="h-news-item h-reveal"><a href="<?= $base_path ?>index.php?page=news_detail&amp;id=<?= (int)$news['id'] ?>"><div class="h-news-image"><img src="<?= $he(site_media_url($news_image, 'img/generated/textile-quality.webp')) ?>" alt="<?= $he($news_title) ?>" width="800" height="520" loading="lazy"><span class="h-news-arrow" aria-hidden="true">↗</span></div><div class="h-news-meta"><span><?= $he(t('news')) ?></span><time><?= $he($news_date) ?></time></div><h3><?= $he($news_title) ?></h3></a></article><?php endforeach; ?></div>
</section>
<?php endif; ?>

<section class="h-contact h-section" aria-labelledby="contact-title"><div class="h-shell"><p class="h-eyebrow h-reveal"><?= $he($home_copy['contact_label']) ?></p><div class="h-contact-heading"><h2 id="contact-title" class="h-reveal"><?= $he($home_copy['contact_title']) ?><br><em><?= $he($home_copy['contact_em']) ?></em></h2><a class="h-contact-circle h-reveal" href="<?= $base_path ?>index.php?page=contact" aria-label="<?= $he($home_copy['contact_link']) ?>"><span aria-hidden="true">↗</span></a></div><div class="h-contact-bottom h-reveal"><p><?= $he($home_copy['contact_desc']) ?></p><a class="h-text-link" href="mailto:<?= $he($common_email) ?>"><?= $he($common_email) ?><span aria-hidden="true">↗</span></a></div></div></section>
