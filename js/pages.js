(() => {
 'use strict';
 const dialog=document.getElementById('media-viewer');
 let items=[],index=0,trigger=null;
 const photo=document.getElementById('viewer-image');
 const title=document.getElementById('viewer-title');
 const description=document.getElementById('viewer-description');
 const count=document.getElementById('viewer-count');
 function show(i) {
  index=(i+items.length)%items.length;
  const item=items[index];
  photo.src=item.href; photo.alt=item.dataset.title || '';
  title.textContent=item.dataset.title || ''; description.textContent=item.dataset.description || '';
  count.textContent=String(index+1).padStart(2,'0')+' / '+String(items.length).padStart(2,'0');
  dialog.querySelectorAll('[data-viewer-prev],[data-viewer-next]').forEach(button=>button.hidden=items.length<2);
 }
 document.querySelectorAll('[data-viewer]').forEach(link=>link.addEventListener('click',event=>{
  if (!dialog?.showModal || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
  event.preventDefault();trigger=link;
  const group=link.closest('[data-media-group],.p-media-grid') || document;
  items=[...group.querySelectorAll('[data-viewer]')].filter(el=>!el.closest('[hidden]'));
  show(items.indexOf(link));dialog.showModal();document.body.classList.add('viewer-open');
 }));
 dialog?.querySelector('[data-viewer-close]').addEventListener('click',()=>dialog.close());
 dialog?.querySelector('[data-viewer-prev]').addEventListener('click',()=>show(index-1));
 dialog?.querySelector('[data-viewer-next]').addEventListener('click',()=>show(index+1));
 dialog?.addEventListener('click',event=>{ if(event.target===dialog) dialog.close(); });
 dialog?.addEventListener('keydown',event=>{ if(event.key==='ArrowRight'){event.preventDefault();show(index+1);} if(event.key==='ArrowLeft'){event.preventDefault();show(index-1);} });
 dialog?.addEventListener('close',()=>{document.body.classList.remove('viewer-open');trigger?.focus({preventScroll:true});});
 const filters=[...document.querySelectorAll('[data-filter]')];
 filters.forEach(button=>button.addEventListener('click',()=>{
  filters.forEach(item=>item.setAttribute('aria-pressed',String(item===button)));
  document.querySelectorAll('[data-category]').forEach(item=>{item.hidden=button.dataset.filter!=='all' && item.dataset.category!==button.dataset.filter;});
 }));
})();
