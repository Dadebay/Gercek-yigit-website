<?php $certs=records('SELECT * FROM our_partners ORDER BY id');page_intro(ui('cert_title'),t('certificates'),ui('cert_desc')); ?>
<section class="h-shell p-documents"><?php $items=[];foreach($certs as $i=>$cert){$title=loc($cert,'title');if(is_numeric($title)||!$title)$title=ui('certificate').' '.str_pad($i+1,2,'0',STR_PAD_LEFT);$items[]=['src'=>site_media_url($cert['image']),'title'=>$title];}media_cards($items,'p-certificate-grid');if(!$certs): ?><p class="p-notice"><?= e(t('no_certificates')) ?></p><?php endif; ?></section>
<?php page_cta(); ?>
