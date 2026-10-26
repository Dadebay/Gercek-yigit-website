<?php $items=records('SELECT * FROM catalogues ORDER BY id DESC');page_intro(ui('catalogue_title'),t('catalogues'),ui('catalogue_desc')); ?>
<?php if(!$items): empty_panel(ui('catalogue_empty'),ui('catalogue_empty_desc'),'img/generated/sector-socks.webp');else: ?>
<section class="h-shell p-downloads"><?php foreach($items as $i=>$item): $clean=ltrim($item['pdf_path'] ?? '','/');$file='';if($clean && !str_contains($clean,'..') && is_file(__DIR__.'/../admin/'.$clean)) $file=$base_path.'admin/'.implode('/',array_map('rawurlencode',explode('/',$clean))); ?>
 <article class="p-download h-reveal"><span class="p-download-num"><?= str_pad($i+1,2,'0',STR_PAD_LEFT) ?></span><div><p class="h-eyebrow">PDF / GERÇEK ÝIGIT</p><h2><?= e(loc($item,'title')) ?></h2><p><?= e(loc($item,'description')) ?></p></div><?php if($file): ?><a class="h-button" href="<?= e($file) ?>" target="_blank" rel="noopener noreferrer"><?= e(t('download')) ?><span class="h-button-arrow">↓</span></a><?php else: ?><span class="p-lead"><?= e(ui('no_file')) ?></span><?php endif; ?></article>
<?php endforeach; ?></section><?php endif; ?>
<?php page_cta(); ?>
