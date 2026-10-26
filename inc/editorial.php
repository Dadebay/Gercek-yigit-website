<?php
function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function ui($key) { global $lang; static $copy; $copy ??= require __DIR__.'/page-copy.php'; return $copy[$key][$lang] ?? $copy[$key]['en'] ?? $key; }
function loc($row,$field) { global $lang; foreach (array_unique([$lang,'en','tm','ru']) as $lc) { if (trim((string)($row[$field.'_'.$lc] ?? '')) !== '') return $row[$field.'_'.$lc]; } return $row[$field] ?? ''; }
function records($sql,$params=[]) { global $pdo; if (!isset($pdo)) return []; try { $q=$pdo->prepare($sql); $q->execute($params); return $q->fetchAll(PDO::FETCH_ASSOC); } catch (Exception $e) { error_log('Public page content: '.$e->getMessage()); return []; } }
function media_list($value) { if (is_array($value)) return $value; $a=json_decode($value ?: '[]',true); return is_array($a) ? array_values($a) : array_values(array_filter(array_map('trim',explode(',', $value ?? '')))); }
function sector_data($id) { global $home_copy; $names=[2=>'textile',3=>'socks',4=>'construction',1=>'machinery']; $key=$names[$id] ?? 'textile'; return ['key'=>$key,'title'=>$home_copy[$key],'desc'=>$home_copy[$key.'_desc'],'image'=>in_array($key,['construction','machinery'],true) ? 'img/generated/'.$key.'/hero.webp' : 'img/generated/sector-'.$key.'.webp','index'=>array_search($id,array_keys($names),true)+1]; }
function page_intro($title,$label,$description='') { ?>
 <header class="p-heading h-shell"><nav class="p-breadcrumb" aria-label="<?= e(ui('breadcrumb')) ?>"><a href="index.php"><?= e(t('home')) ?></a><span aria-hidden="true">/</span><span><?= e($label) ?></span></nav><div class="p-heading-row"><div><p class="h-eyebrow"><?= e($label) ?> / GERÇEK ÝIGIT</p><h1><?= e($title) ?></h1></div><?php if($description): ?><p class="p-lead"><?= e($description) ?></p><?php endif; ?></div></header>
<?php }
function page_cta() { global $home_copy; ?>
 <section class="p-cta"><div class="h-shell"><p class="h-eyebrow"><?= e($home_copy['contact_label']) ?></p><div class="p-cta-row"><h2><?= e(ui('together')) ?></h2><a class="h-button" href="index.php?page=contact"><?= e(t('contact_us')) ?><span class="h-button-arrow" aria-hidden="true">↗</span></a></div></div></section>
<?php }
function media_cards($items,$variant='') { ?>
 <div class="p-media-grid <?= e($variant) ?>"><?php foreach($items as $i=>$item): ?>
  <article class="p-media-card h-reveal"><a class="p-photo-link" href="<?= e($item['src']) ?>" data-viewer data-title="<?= e($item['title']) ?>" data-description="<?= e((!empty($item['credit']) ? $item['credit']."\n\n" : '').($item['desc'] ?? '')) ?>"><div class="p-photo"><img src="<?= e($item['src']) ?>" alt="<?= e($item['title'].(!empty($item['credit']) ? ' — '.$item['credit'] : '')) ?>" loading="lazy" decoding="async"><span class="p-photo-open" aria-hidden="true">↗</span></div><?php if(!empty($item['credit'])): ?><span class="p-image-note"><?= e($item['credit']) ?></span><?php endif; ?><div class="p-card-heading"><h3><?= e($item['title']) ?></h3><span class="p-index"><?= str_pad($i+1,2,'0',STR_PAD_LEFT) ?></span></div></a><?php if(!empty($item['desc'])): ?><p class="p-card-description"><?= nl2br(e(preview_text($item['desc'],180))) ?></p><?php endif; ?></article>
 <?php endforeach; ?></div>
<?php }
function empty_panel($title,$description,$image,$link='index.php?page=contact',$label='') { ?>
 <section class="p-empty h-shell"><figure><img src="<?= e(site_media_url('', $image)) ?>" alt="<?= e(ui('representative')) ?>"><figcaption><?= e(ui('representative')) ?></figcaption></figure><div><p class="h-eyebrow">GERÇEK ÝIGIT</p><h2><?= e($title) ?></h2><p class="p-lead"><?= e($description) ?></p><a class="h-button" href="<?= e($link) ?>"><?= e($label ?: t('contact_us')) ?><span class="h-button-arrow" aria-hidden="true">↗</span></a></div></section>
<?php }
