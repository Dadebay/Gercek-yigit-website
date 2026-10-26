<?php $project=records('SELECT * FROM projects WHERE id=? LIMIT 1',[(int)($_GET['id'] ?? 0)])[0] ?? null;
if(!$project){page_intro(t('project_not_found'),t('projects'));empty_panel(t('project_not_found'),ui('projects_desc'),'img/generated/sector-construction.webp','index.php?page=projects',t('our_projects'));return;}
page_intro(loc($project,'title'),t('projects'),(string)($project['year'] ?? ''));$images=media_list($project['images'] ?? ''); ?>
<section class="h-shell p-documents"><?php media_cards(array_map(fn($src)=>['src'=>site_media_url($src,'img/generated/sector-construction.webp'),'title'=>loc($project,'title')],$images),'p-work-grid'); ?><div class="p-article-body"><?= nl2br(e(loc($project,'description'))) ?></div><a class="h-text-link" href="index.php?page=projects"><?= e(t('our_projects')) ?><span>↖</span></a></section>
<?php page_cta(); ?>
