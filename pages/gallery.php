<?php
$groups=records('SELECT * FROM gallery ORDER BY id DESC');
$construction_media=require __DIR__.'/../inc/gallery-construction-media.php';
page_intro(ui('gallery_title'),t('gallery'),ui('gallery_intro'));
?>
<section class="h-shell p-gallery"><div class="p-filters" aria-label="<?= e(t('gallery')) ?>"><button type="button" data-filter="all" aria-pressed="true"><?= e(ui('all')) ?></button><?php foreach($groups as $group): ?><button type="button" data-filter="<?= (int)$group['id'] ?>" aria-pressed="false"><?= e(loc($group,'title')) ?></button><?php endforeach; ?></div>
<?php foreach($groups as $group):
 $images=media_list($group['images']);
 $is_construction=(int)$group['id']===1;
 $items=array_map(function($src) use($group,$is_construction,$construction_media) {
  $replacement=$is_construction ? ($construction_media[$src] ?? null) : null;
  return [
   'src'=>site_media_url($replacement ? 'img/generated/gallery-construction/'.$replacement['image'] : $src),
   'title'=>$replacement ? loc($replacement,'title') : loc($group,'title'),
   'credit'=>$replacement ? ui('representative') : '',
  ];
 },$images);
?>
 <section class="p-gallery-group" id="gallery-<?= (int)$group['id'] ?>" data-category="<?= (int)$group['id'] ?>">
  <div class="p-gallery-heading"><h2><?= e(loc($group,'title')) ?></h2><p><?= e(loc($group,'description')) ?></p><span class="h-eyebrow"><?= count($images) ?> <?= e(t('images')) ?></span></div>
  <?php media_cards($items,$is_construction ? 'p-work-grid' : 'p-gallery-grid'); ?>
 </section>
<?php endforeach; if(!$groups): ?><p class="p-notice"><?= e(t('no_gallery_items')) ?></p><?php endif; ?>
</section>
<?php page_cta(); ?>
