<?php
$id=max(0,(int)($_GET['id'] ?? 0));
$service=records('SELECT * FROM services WHERE id=? LIMIT 1',[$id])[0] ?? null;
if(!$service){page_intro(t('service_not_found'),t('services'));empty_panel(t('service_not_found'),ui('world_desc'),'img/generated/sector-textile.webp','index.php?page=services',ui('back_services'));return;}
$sector=sector_data($id);$rows=records('SELECT * FROM service_images WHERE service_id=? ORDER BY id',[$id]);
$media_family=[4=>'construction',1=>'machinery'][$id] ?? null;
$media_overrides=$media_family ? require __DIR__.'/../inc/'.$media_family.'-media.php' : [];
$items=array_map(function($r) use($sector,$media_family,$media_overrides) {
 $replacement=$media_overrides[$r['image']] ?? null;
 $credit=$replacement ? ui('representative') : '';
 return [
  'src'=>site_media_url($replacement ? 'img/generated/'.$media_family.'/'.$replacement : $r['image'],$sector['image']),
  'title'=>loc($r,'title'), 'desc'=>loc($r,'description'), 'credit'=>$credit,
 ];
},$rows);
$focus=[2=>['cotton','blankets','weaving'],3=>['everyday','families','knitting'],4=>['civil','steel','projects_delivery'],1=>['excavation','lifting','equipment']][$id] ?? ['cotton','blankets','weaving'];
?>
<header class="p-sector-hero p-sector-<?= e($sector['key']) ?>">
 <img class="p-sector-cover" src="<?= e(site_media_url('', $sector['image'])) ?>" alt="<?= e(ui('representative')) ?>" fetchpriority="high">
 <div class="p-sector-shade"></div><div class="h-shell p-sector-hero-content"><nav class="p-breadcrumb" aria-label="<?= e(ui('breadcrumb')) ?>"><a href="index.php?page=services"><?= e(t('services')) ?></a><span>/</span><span>0<?= $sector['index'] ?></span></nav><p class="h-eyebrow">GERÇEK ÝIGIT / <?= e(t('turkmenistan')) ?></p><h1><?= e(loc($service,'title')) ?></h1><div class="p-sector-hero-bottom"><p><?= e($sector['desc']) ?></p><a class="h-button h-button-light" href="#collection"><?= e(ui('collection')) ?><span class="h-button-arrow" aria-hidden="true">↓</span></a></div><p class="p-media-credit"><?= e(ui('representative')) ?></p></div>
</header>
<nav class="p-section-nav h-shell" aria-label="<?= e(t('menu')) ?>"><a href="#overview"><?= e(ui('overview')) ?></a><a href="#collection"><?= e(ui('products_work')) ?> <span><?= str_pad(count($items),2,'0',STR_PAD_LEFT) ?></span></a><a href="index.php?page=contact"><?= e(t('contact_us')) ?> ↗</a></nav>
<section class="h-shell h-section p-overview" id="overview"><p class="h-eyebrow">01 / <?= e(ui('our_approach')) ?></p><div><h2 class="h-display h-reveal"><?= e(ui($sector['key'].'_head')) ?></h2><p class="p-overview-copy h-reveal"><?= e($sector['desc']) ?></p><div class="p-focus-list"><?php foreach($focus as $i=>$key): ?><div class="h-reveal"><span>0<?= $i+1 ?></span><h3><?= e(ui($key)) ?></h3><span aria-hidden="true">↗</span></div><?php endforeach; ?></div><?php if(strlen(loc($service,'description'))>100): ?><details class="p-details"><summary><?= e(ui('our_approach')) ?><span aria-hidden="true">+</span></summary><p><?= nl2br(e(loc($service,'description'))) ?></p></details><?php endif; ?></div></section>
<section class="p-collection" id="collection"><div class="h-shell"><div class="h-section-heading"><div><p class="h-eyebrow">02 / <?= e(ui('products_work')) ?></p><h2 class="h-display"><?= e(ui('collection')) ?></h2></div><p class="h-heading-aside"><?= e(ui('collection_desc')) ?></p></div><?php if($items) media_cards($items,$id===4?'p-work-grid':($id===1?'p-equipment-grid':'p-product-grid')); else echo '<p class="p-notice">'.e(t('no_images')).'</p>'; ?></div></section>
<section class="h-shell p-other-sectors"><p class="h-eyebrow"><?= e(ui('next_sector')) ?></p><div><?php foreach([2,3,4,1] as $other): if($other===$id)continue; $next=sector_data($other); ?><a href="index.php?page=service_detail&amp;id=<?= $other ?>"><span>0<?= $next['index'] ?></span><h3><?= e($next['title']) ?></h3><span aria-hidden="true">↗</span></a><?php endforeach; ?></div></section>
<?php page_cta(); ?>
