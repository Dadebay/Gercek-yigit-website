<?php
$services=records("SELECT s.* FROM services s LEFT JOIN menu_item_settings m ON m.type='service' AND m.item_id=s.id WHERE COALESCE(m.is_enabled,1)=1 ORDER BY FIELD(s.id,2,3,4,1)");
page_intro(ui('our_world'),t('services'),ui('world_desc'));
?>
<section class="h-shell p-sector-list">
<?php foreach($services as $svc): $sector=sector_data((int)$svc['id']); ?>
 <a class="p-sector-row h-reveal" href="index.php?page=service_detail&amp;id=<?= (int)$svc['id'] ?>"><div class="p-sector-info"><p class="h-eyebrow">0<?= $sector['index'] ?> / <?= e(t('turkmenistan')) ?></p><h2><?= e(loc($svc,'title')) ?></h2><p class="p-lead"><?= e($sector['desc']) ?></p><span class="h-text-link"><?= e(t('explore')) ?> <span aria-hidden="true">↗</span></span></div><figure><img src="<?= e(site_media_url('', $sector['image'])) ?>" alt="<?= e(ui('representative')) ?>" loading="lazy"><figcaption><?= e(ui('representative')) ?></figcaption></figure></a>
<?php endforeach; ?>
<?php if(!$services): ?><p class="p-notice"><?= e(t('service_no')) ?></p><?php endif; ?>
</section>
<?php page_cta(); ?>
