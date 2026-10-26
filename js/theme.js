(() => {
 'use strict';
 const root = document.documentElement;
 const key = 'gercek-theme';
 const system = window.matchMedia('(prefers-color-scheme: dark)');
 const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
 let preference = null;
 let transition = null;
 let fadeTimer = null;
 let button = null;
 try {
  const saved = localStorage.getItem(key);
  if (saved === 'light' || saved === 'dark') preference = saved;
 } catch (_) { /* Private/storage-restricted browsing still supports the toggle. */ }
 const resolved = () => preference || (system.matches ? 'dark' : 'light');
 const apply = () => {
  const theme = resolved();
  root.dataset.theme = theme;
  document.querySelector('meta[name="theme-color"]')?.setAttribute('content',theme === 'dark' ? '#151e19' : '#f2f1e9');
  if (button) {
   const label = theme === 'dark' ? button.dataset.labelLight : button.dataset.labelDark;
   button.setAttribute('aria-label',label);
   button.title = label;
  }
 };
 // This small script runs in <head>, before CSS/body, to prevent a light flash.
 apply();
 document.addEventListener('DOMContentLoaded',() => {
  button = document.querySelector('[data-theme-toggle]');
  if (!button) return;
  apply();
  button.hidden = false;
  button.addEventListener('click',event => {
   preference = resolved() === 'dark' ? 'light' : 'dark';
   try { localStorage.setItem(key,preference); } catch (_) { /* Keep the current page usable. */ }
   transition?.skipTransition();
   clearTimeout(fadeTimer);
   root.classList.remove('theme-color-fade');
   const animate = event.detail > 0 && !reduced.matches;
   root.classList.toggle('theme-instant',!animate);
   // Progressive enhancement: https://developer.mozilla.org/en-US/docs/Web/API/Document/startViewTransition
   if (animate && typeof document.startViewTransition === 'function') {
    const current = document.startViewTransition(apply);
    transition = current;
    current.finished.catch(() => {}).finally(() => { if (transition === current) transition = null; });
   } else {
    if (animate) root.classList.add('theme-color-fade');
    apply();
    fadeTimer = setTimeout(() => root.classList.remove('theme-color-fade'),220);
   }
  });
 });
 system.addEventListener('change',() => {
  if (preference) return;
  transition?.skipTransition();
  root.classList.add('theme-instant');
  root.classList.remove('theme-color-fade');
  apply();
 });
 // Synchronize other open pages without overriding their form or scroll state.
 window.addEventListener('storage',event => {
  if (event.key !== key && event.key !== null) return;
  preference = event.newValue === 'light' || event.newValue === 'dark' ? event.newValue : null;
  transition?.skipTransition();
  root.classList.add('theme-instant');
  root.classList.remove('theme-color-fade');
  apply();
 });
})();
