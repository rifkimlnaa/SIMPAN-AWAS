/* Menu atas: dropdown (klik, klik di luar, Esc) dan tombol menu pada layar kecil. */
(function () {
  'use strict';

  var items = document.querySelectorAll('.tb-nav-item[data-dropdown]');
  var toggle = document.getElementById('tbNavToggle');
  var nav = document.getElementById('tbNav');

  function closeAll(except) {
    items.forEach(function (li) {
      if (li === except) return;
      li.classList.remove('open');
      var b = li.querySelector('button');
      if (b) b.setAttribute('aria-expanded', 'false');
    });
  }

  items.forEach(function (li) {
    var btn = li.querySelector('button');
    if (!btn) return;
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      var open = !li.classList.contains('open');
      closeAll(li);
      li.classList.toggle('open', open);
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  });

  document.addEventListener('click', function () { closeAll(null); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeAll(null); });

  if (toggle && nav) {
    toggle.addEventListener('click', function () {
      var open = !nav.classList.contains('open');
      nav.classList.toggle('open', open);
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }
})();

/* Garis biru penggeser di bawah menu.
   Disimpan sebagai nomor menu (bukan piksel) supaya tetap pas walau font berganti. Saat pindah halaman, garis
   mulai dari menu tempat ia terakhir berada (menu yang diklik) dan baru meluncur setelah halaman baru tampil. */
(function () {
  'use strict';

  var ul = document.querySelector('.tb-nav');
  var ink = document.getElementById('tbInk');
  if (!ul || !ink) return;
  var KEY = 'tbInkIdx';
  var desktop = window.matchMedia('(min-width: 992px)');
  var items = Array.prototype.slice.call(ul.querySelectorAll(':scope > .tb-nav-item'));
  var active = ul.querySelector(':scope > .tb-nav-item.active');
  var activeIdx = items.indexOf(active);
  var curIdx = activeIdx; // menu tempat garis berada sekarang

  function box(li) {
    var r = li.getBoundingClientRect(), u = ul.getBoundingClientRect();
    return { x: r.left - u.left + 12, w: Math.max(0, r.width - 24) };
  }
  function put(idx) {
    if (idx < 0 || !items[idx]) { ink.classList.remove('is-on'); curIdx = -1; return; }
    var b = box(items[idx]);
    ink.style.transform = 'translateX(' + b.x + 'px)';
    ink.style.width = b.w + 'px';
    ink.classList.add('is-on');
    curIdx = idx;
  }
  function instant(fn) {
    ink.classList.add('no-anim'); fn(); void ink.offsetWidth; ink.classList.remove('no-anim');
  }
  function remember() {
    try { sessionStorage.setItem(KEY, String(curIdx)); } catch (e) { /* abaikan */ }
  }

  var from = -1;
  try { from = parseInt(sessionStorage.getItem(KEY), 10); } catch (e) { from = -1; }
  if (isNaN(from)) from = -1;

  var started = false;
  function settle() {
    if (started) return;
    started = true;
    if (!desktop.matches) return;
    if (from >= 0 && from !== activeIdx) put(activeIdx); // meluncur dari menu asal ke menu aktif
    else instant(function () { put(activeIdx); });
  }

  if (desktop.matches) {
    // Titik awal: menu asal (atau langsung menu aktif bila tak ada perpindahan). Tanpa animasi.
    instant(function () { put(from >= 0 && from !== activeIdx && items[from] ? from : activeIdx); });

    // Mulai meluncur setelah transisi halaman siap, sehingga gerakannya terlihat dan sejalan dengan isi halaman.
    var go = function () { setTimeout(function () { requestAnimationFrame(settle); }, 60); };
    window.addEventListener('pagereveal', function (e) {
      if (e.viewTransition) e.viewTransition.ready.then(go, go); else go();
    });
    setTimeout(go, 500); // cadangan bila peristiwa pagereveal tidak ada
  }

  items.forEach(function (li, i) {
    li.addEventListener('mouseenter', function () { if (desktop.matches && started) put(i); });
    li.addEventListener('click', function () { if (desktop.matches) put(i); });
  });
  ul.addEventListener('mouseleave', function () { if (desktop.matches && started) put(activeIdx); });

  // Simpan tempat garis berada tepat sebelum meninggalkan halaman.
  document.addEventListener('click', function (e) {
    var a = e.target.closest && e.target.closest('a[href]');
    if (a && !a.closest('.tb-nav')) curIdx = activeIdx; // tautan di luar menu: garis tetap di menu aktif
    remember();
  }, true);
  window.addEventListener('pagehide', remember);

  // Font web mengubah lebar menu setelah termuat: pasang ulang tanpa animasi.
  function refit() { if (desktop.matches && started) instant(function () { put(curIdx); }); }
  if (document.fonts && document.fonts.ready) document.fonts.ready.then(refit);
  window.addEventListener('load', refit);
  var t = 0;
  window.addEventListener('resize', function () { clearTimeout(t); t = setTimeout(refit, 100); });
})();

/* Bilah muat tipis di atas layar saat berpindah halaman atau mengirim form. */
(function () {
  'use strict';

  var bar = document.createElement('div');
  bar.className = 'tb-loadbar';
  bar.setAttribute('aria-hidden', 'true');
  document.body.appendChild(bar);

  var vt = 'onpagereveal' in window;
  function go() { if (vt) return; bar.classList.remove('is-on'); void bar.offsetWidth; bar.classList.add('is-on'); }

  document.addEventListener('click', function (e) {
    var a = e.target.closest && e.target.closest('a[href]');
    if (!a || e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    if (a.target && a.target !== '_self') return;
    if (a.hasAttribute('download')) return;
    var u;
    try { u = new URL(a.href, location.href); } catch (err) { return; }
    if (u.origin !== location.origin) return;
    if (u.pathname === location.pathname && u.search === location.search) return;
    if (/\/(unduh|formulir|download)/.test(u.pathname)) return; // unduhan berkas tidak memuat halaman baru
    go();
  });
  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (f && !e.defaultPrevented && !(f.classList && f.classList.contains('js-confirm-delete'))) go();
  });
  window.addEventListener('pageshow', function () { bar.classList.remove('is-on'); });
})();
