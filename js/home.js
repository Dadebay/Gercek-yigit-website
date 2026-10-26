(() => {
 'use strict';
 const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
 const nav = document.querySelector('.h-nav');
 const progress = document.querySelector('.h-reading-progress');
 const parallax = document.querySelector('[data-parallax]');
 const build = document.querySelector('.h-build');
 let framePending = false;
 const renderScroll = () => {
   framePending = false;
   nav?.classList.toggle('is-scrolled', window.scrollY > 56);
   const max = document.documentElement.scrollHeight - window.innerHeight;
   if (progress) progress.style.transform = `scaleX(${max > 0 ? Math.min(1, window.scrollY / max) : 0})`;
   if (parallax && build && !reduced.matches) {
     const rect = build.getBoundingClientRect();
     if (rect.bottom > 0 && rect.top < window.innerHeight) {
       const offset = Math.max(-50, Math.min(50, (window.innerHeight / 2 - rect.top - rect.height / 2) * .12));
       parallax.style.transform = `translateY(${offset}px)`;
     }
   }
 };
 const queueScroll = () => { if (!framePending) { framePending = true; requestAnimationFrame(renderScroll); } };
 window.addEventListener('scroll', queueScroll, {passive:true});
 window.addEventListener('resize', queueScroll, {passive:true});
 renderScroll();
 // Keep the native browser scroll and anchor history; no scroll hijacking.
 document.querySelectorAll('a[href^="#"]').forEach(link => link.addEventListener('click', event => {
   const target = document.getElementById(link.getAttribute('href').slice(1));
   if (!target) return;
   event.preventDefault();
   target.scrollIntoView({behavior:reduced.matches || event.detail === 0 ? 'instant' : 'smooth',block:'start'});
   history.replaceState(null, '', link.getAttribute('href'));
   target.setAttribute('tabindex','-1');
   target.focus({preventScroll:true});
 }));
 // Reveal each section once. No hidden content when JS or IntersectionObserver fails.
 if ('IntersectionObserver' in window && !reduced.matches) {
   document.body.classList.add('h-motion-ready');
   const reveal = new IntersectionObserver(entries => entries.forEach(entry => {
     if (entry.isIntersecting) { entry.target.classList.add('is-visible'); reveal.unobserve(entry.target); }
   }), {threshold:.08, rootMargin:'0px 0px -35px 0px'});
   document.querySelectorAll('.h-reveal').forEach(element => reveal.observe(element));
 }
 // The service links keep their normal click action; hover/focus previews imagery.
 const sectors = [...document.querySelectorAll('[data-sector]')];
 const visuals = [...document.querySelectorAll('[data-sector-image]')];
 const sectorLayout = document.querySelector('.h-sector-layout');
 const showSector = (id, instant = false) => {
   sectorLayout?.classList.toggle('is-instant',instant);
   sectors.forEach(element => element.classList.toggle('is-active',element.dataset.sector === id));
   visuals.forEach(element => element.classList.toggle('is-active',element.dataset.sectorImage === id));
 };
 sectors.forEach(element => {
   element.addEventListener('pointerenter',event => { if (event.pointerType === 'mouse') showSector(element.dataset.sector); });
   element.addEventListener('focus',() => showSector(element.dataset.sector,true));
 });
 // Native details provides keyboard support and a readable no-JS menu.
 const menus = [...document.querySelectorAll('.h-dropdown')];
 menus.forEach(menu => menu.addEventListener('toggle',() => {
   if (menu.open) menus.forEach(other => { if (other !== menu) other.open = false; });
 }));
 document.addEventListener('click',event => menus.forEach(menu => { if (!menu.contains(event.target)) menu.open = false; }));
 document.addEventListener('keydown',event => {
   if (event.key === 'Escape') menus.forEach(menu => { if (menu.open) { menu.open = false; menu.querySelector('summary').focus(); } });
 });
 // A single video element plays the selected local film; inactive films stay unloaded.
 const video = document.getElementById('hero-film');
 const videoControl = document.querySelector('.h-film-toggle');
 if (video && videoControl) {
   const hero = document.querySelector('.h-hero');
   const poster = document.querySelector('.h-hero-poster');
   const filmButtons = [...document.querySelectorAll('[data-hero-film]')];
   const filmSwitcher = document.querySelector('.h-film-switcher');
   const filmName = document.querySelector('.h-film-caption [data-film-name]');
   const filmDuration = document.querySelector('[data-film-duration]');
   let activeFilm = 0;
   let sourceVersion = 0;
   let userPaused = reduced.matches || Boolean(navigator.connection?.saveData);
   let inView = false;
   const updateControl = () => {
     videoControl.setAttribute('aria-label',video.paused ? videoControl.dataset.playLabel : videoControl.dataset.pauseLabel);
     videoControl.querySelector('span').textContent = video.paused ? '▶' : 'Ⅱ';
   };
   const syncPlayback = () => {
     if (!inView || document.hidden || userPaused) { video.pause(); return; }
     if (!video.getAttribute('src')) { video.src = video.dataset.src; video.load(); }
     const version=sourceVersion;
     video.play().catch(error => {
       if (version!==sourceVersion || error.name==='AbortError') return;
       userPaused=true; videoControl.hidden=false; updateControl();
     });
   };
   const selectFilm = (next,explicit=false,instant=false) => {
     if (!filmButtons.length) return;
     activeFilm=(next+filmButtons.length)%filmButtons.length;
     const choice=filmButtons[activeFilm];
     sourceVersion++;
     video.pause();
     hero.classList.toggle('is-film-instant',instant || reduced.matches);
     video.classList.remove('is-playing');
     video.removeAttribute('src');
     video.load();
     video.dataset.src=choice.dataset.src;
     video.poster=choice.dataset.poster;
     if (poster) poster.src=choice.dataset.poster;
     if (filmName) filmName.textContent=choice.dataset.filmName;
     if (filmDuration) filmDuration.textContent=choice.dataset.duration;
     hero.dataset.activeFilm=choice.dataset.heroFilm;
     filmButtons.forEach((button,index)=>button.setAttribute('aria-pressed',String(index===activeFilm)));
     if (explicit) userPaused=false;
     videoControl.hidden=false;
     updateControl();
     syncPlayback();
   };
   filmButtons.forEach((button,index)=>button.addEventListener('click',event=>selectFilm(index,true,event.detail===0)));
   if (filmSwitcher) filmSwitcher.hidden=filmButtons.length<2;
   if (hero && filmButtons.length) hero.dataset.activeFilm=filmButtons[0].dataset.heroFilm;
   video.loop=filmButtons.length<2;
   videoControl.hidden = false;
   videoControl.addEventListener('click',() => { userPaused = !video.paused; syncPlayback(); });
   video.addEventListener('playing',() => { video.classList.add('is-playing'); updateControl(); });
   video.addEventListener('pause',updateControl);
   video.addEventListener('ended',() => { if (!userPaused && filmButtons.length>1) selectFilm(activeFilm+1); });
   video.addEventListener('error',() => { video.classList.remove('is-playing'); videoControl.hidden = true; });
   if ('IntersectionObserver' in window) new IntersectionObserver(entries => { inView=entries[0].isIntersecting; syncPlayback(); }, {threshold:.05}).observe(hero);
   else inView=true;
   document.addEventListener('visibilitychange',syncPlayback);
   reduced.addEventListener('change',() => { userPaused=reduced.matches; if (parallax) parallax.style.transform=''; syncPlayback(); });
   updateControl();
   syncPlayback();
 }
})();
