/* Sidebar kaca: ciut/lebar, pil kaca yang meluncur, tooltip, grup menu, pencarian menu, mode gelap. */
(function () {
  'use strict';

  var root = document.documentElement;
  var aside = document.getElementById('sidebar');
  if (!aside) return;

  var nav = document.getElementById('gsNav');
  var pill = document.getElementById('gsPill');
  var tip = document.getElementById('gsTip');
  var toggle = document.getElementById('gsToggle');
  var search = document.getElementById('gsSearch');
  var searchBtn = document.getElementById('gsSearchBtn');
  var theme = document.getElementById('gsTheme');
  var empty = document.getElementById('gsEmpty');
  var desktop = window.matchMedia('(min-width: 1024px)');

  function isCompact() { return root.classList.contains('gs-compact') && desktop.matches; }
  function store(key, val) { try { localStorage.setItem(key, val); } catch (e) { /* diblokir: berlaku sementara */ } }

  /* ---------- Pil kaca ---------- */
  var hovered = null;
  var raf = 0;

  function visible(el) { return el && el.offsetParent !== null; }

  function activeEl() {
    var sub = nav.querySelector('.gs-sublink.is-active');
    if (sub && visible(sub)) return sub; // submenu terbuka: pil di jenis yang dipilih
    var item = nav.querySelector('.gs-item.is-active');
    return item && visible(item) ? item : null;
  }

  function place(instant) {
    var target = hovered && visible(hovered) ? hovered : activeEl();
    if (!target || !visible(target)) { pill.style.opacity = '0'; return; }

    var n = nav.getBoundingClientRect();
    var r = target.getBoundingClientRect();
    var first = pill.style.opacity !== '1';
    var snap = instant || first;

    if (snap) pill.classList.add('is-instant');
    pill.style.width = r.width + 'px';
    pill.style.height = r.height + 'px';
    pill.style.transform = 'translate(' + (r.left - n.left + nav.scrollLeft) + 'px,' + (r.top - n.top + nav.scrollTop) + 'px)';
    pill.style.opacity = '1';
    pill.classList.toggle('is-active', target.classList.contains('is-active'));
    if (snap) { void pill.offsetWidth; pill.classList.remove('is-instant'); }
  }

  // Selama panel/submenu beranimasi, ikuti posisinya tiap frame agar pil tidak tertinggal.
  function track(ms) {
    cancelAnimationFrame(raf);
    var end = performance.now() + ms;
    (function loop(now) {
      place(true);
      if (now < end) raf = requestAnimationFrame(loop);
    })(performance.now());
  }

  nav.addEventListener('pointerover', function (e) {
    var el = e.target.closest('.gs-item, .gs-sublink');
    if (el && nav.contains(el) && el !== hovered) { hovered = el; place(false); }
  });
  nav.addEventListener('pointerleave', function () { hovered = null; place(false); });
  nav.addEventListener('focusin', function (e) {
    var el = e.target.closest('.gs-item, .gs-sublink');
    if (el) { hovered = el; place(false); }
  });
  nav.addEventListener('focusout', function () { hovered = null; place(false); });
  nav.addEventListener('scroll', function () { place(true); }, { passive: true });
  window.addEventListener('resize', function () { place(true); });

  /* ---------- Ciut / lebar ---------- */
  function setCompact(on, save) {
    root.classList.toggle('gs-compact', on);
    if (save !== false) store('sidebarCompact', on ? '1' : '0');
    if (toggle) {
      toggle.setAttribute('aria-expanded', on ? 'false' : 'true');
      toggle.setAttribute('aria-label', on ? 'Lebarkan menu' : 'Ciutkan menu');
    }
    hideTip();
    track(620);
  }

  if (toggle) toggle.addEventListener('click', function () { setCompact(!root.classList.contains('gs-compact')); });
  setCompact(root.classList.contains('gs-compact'), false);

  /* ---------- Grup menu (submenu kategori) ---------- */
  nav.querySelectorAll('[data-group-toggle]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var group = btn.closest('[data-group]');
      if (isCompact()) { // saat ciut: lebarkan dulu agar submenu terlihat
        setCompact(false);
        group.classList.add('is-open');
      } else {
        group.classList.toggle('is-open');
      }
      btn.setAttribute('aria-expanded', group.classList.contains('is-open') ? 'true' : 'false');
      track(520);
    });
  });

  /* ---------- Pencarian menu ---------- */
  function filter() {
    var q = search.value.trim().toLowerCase();
    var any = false;

    nav.querySelectorAll('.gs-label').forEach(function (l) { l.hidden = q !== ''; });
    nav.querySelectorAll(':scope > .gs-item').forEach(function (a) {
      var ok = q === '' || a.textContent.toLowerCase().indexOf(q) !== -1;
      a.hidden = !ok; any = any || ok;
    });
    nav.querySelectorAll('[data-group]').forEach(function (g) {
      var head = g.querySelector('.gs-item');
      var subs = Array.prototype.slice.call(g.querySelectorAll('.gs-sublink'));
      var headOk = q === '' || head.textContent.toLowerCase().indexOf(q) !== -1;
      var subOk = false;
      subs.forEach(function (s) {
        var ok = q === '' || headOk || s.textContent.toLowerCase().indexOf(q) !== -1;
        s.hidden = !ok; subOk = subOk || ok;
      });
      g.hidden = !(headOk || subOk);
      if (q !== '' && subOk) g.classList.add('is-open');
      any = any || !g.hidden;
    });
    empty.hidden = any;
    track(450);
  }

  if (search) {
    search.addEventListener('input', filter);
    search.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') { search.value = ''; filter(); search.blur(); }
      if (e.key === 'Enter') {
        var first = nav.querySelector('.gs-item:not([hidden]):not(button), .gs-group:not([hidden]) .gs-sublink:not([hidden])');
        if (first && search.value.trim() !== '' && first.href) window.location.href = first.href;
      }
    });
  }
  if (searchBtn) searchBtn.addEventListener('click', function () {
    if (isCompact()) { setCompact(false); setTimeout(function () { search.focus(); }, 220); }
    else search.focus();
  });
  document.addEventListener('keydown', function (e) {
    if (e.key !== '/' || e.ctrlKey || e.metaKey || e.altKey) return;
    var t = e.target;
    if (t && (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA' || t.tagName === 'SELECT' || t.isContentEditable)) return;
    if (!desktop.matches) return;
    e.preventDefault();
    if (isCompact()) setCompact(false);
    search.focus();
  });

  /* ---------- Mode gelap ---------- */
  function syncTheme() {
    if (!theme) return;
    theme.setAttribute('aria-checked', root.classList.contains('dark') ? 'true' : 'false');
  }
  if (theme) {
    theme.addEventListener('click', function () {
      var dark = !root.classList.contains('dark');
      // Simpan pilihan (kunci 'tema' sama dengan yang dibaca head.blade.php) lalu terapkan langsung,
      // tanpa bergantung pada fungsi lain di halaman.
      try { localStorage.setItem('tema', dark ? 'gelap' : 'terang'); } catch (e) { /* diblokir: berlaku sementara */ }
      root.classList.toggle('dark', dark);
      root.style.colorScheme = dark ? 'dark' : 'light';
      syncTheme();
    });
    new MutationObserver(syncTheme).observe(root, { attributes: true, attributeFilter: ['class'] });
    syncTheme();
  }

  /* ---------- Tooltip saat ciut ---------- */
  function hideTip() { tip.classList.remove('is-show'); tip.hidden = true; }
  function showTip(el) {
    if (!isCompact()) return;
    var text = el.getAttribute('data-tip');
    if (!text) return;
    tip.textContent = text;
    tip.hidden = false;
    var r = el.getBoundingClientRect();
    var host = el.closest('.gs-search-box, .gs-brand, .gs-item, .gs-theme, .gs-user-link, .gs-logout-btn') || el;
    var hr = host.getBoundingClientRect();
    tip.style.left = (hr.right + 12) + 'px';
    tip.style.top = (r.top + r.height / 2) + 'px';
    void tip.offsetWidth;
    tip.classList.add('is-show');
  }
  aside.addEventListener('pointerover', function (e) {
    var el = e.target.closest('[data-tip]');
    if (el && aside.contains(el)) showTip(el); else hideTip();
  });
  aside.addEventListener('pointerleave', hideTip);
  aside.addEventListener('focusin', function (e) { var el = e.target.closest('[data-tip]'); if (el) showTip(el); });
  aside.addEventListener('focusout', hideTip);
  nav.addEventListener('scroll', hideTip, { passive: true });

  /* ---------- Mulai ---------- */
  function start() {
    place(true);
    requestAnimationFrame(function () { root.classList.add('gs-ready'); });
    // Ikon lucide baru tergambar sesudah DOMContentLoaded; hitung ulang posisi pil.
    setTimeout(function () { place(true); }, 120);
    var active = nav.querySelector('.gs-sublink.is-active, .gs-item.is-active');
    if (active && nav.scrollHeight > nav.clientHeight) {
      var d = active.getBoundingClientRect().top - nav.getBoundingClientRect().top;
      nav.scrollTop += d - nav.clientHeight / 2;
    }
  }
  if (document.readyState === 'complete') start(); else window.addEventListener('load', start);
  if (desktop.addEventListener) desktop.addEventListener('change', function () { hideTip(); track(300); });
})();
