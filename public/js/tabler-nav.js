/* Navigasi Tabler + Turbo Drive.
 * Semua event klik memakai delegation agar tetap berfungsi setelah Turbo mengganti <body>.
 */
(function () {
  'use strict';

  if (window.__simpanAwasTablerNavBound) return;
  window.__simpanAwasTablerNavBound = true;

  var KEY = 'tbInkIdx';
  var desktop = window.matchMedia('(min-width: 992px)');
  var resizeTimer = 0;

  function navItems(ul) {
    return Array.prototype.slice.call(ul.querySelectorAll(':scope > .tb-nav-item'));
  }

  function closeAll(except) {
    document.querySelectorAll('.tb-nav-item[data-dropdown]').forEach(function (item) {
      if (item === except) return;
      item.classList.remove('open');
      var button = item.querySelector(':scope > button');
      if (button) button.setAttribute('aria-expanded', 'false');
    });
  }

  function setInk(index, immediate) {
    var ul = document.querySelector('.tb-nav');
    var ink = document.getElementById('tbInk');
    if (!ul || !ink) return;
    var items = navItems(ul);
    if (!desktop.matches || index < 0 || !items[index]) {
      ink.classList.remove('is-on');
      return;
    }

    var rect = items[index].getBoundingClientRect();
    var parent = ul.getBoundingClientRect();
    var x = rect.left - parent.left + 12;
    var width = Math.max(0, rect.width - 24);

    if (immediate) ink.classList.add('no-anim');
    ink.style.transform = 'translateX(' + x + 'px)';
    ink.style.width = width + 'px';
    ink.classList.add('is-on');
    if (immediate) {
      void ink.offsetWidth;
      requestAnimationFrame(function () { ink.classList.remove('no-anim'); });
    }
  }

  function activeIndex() {
    var ul = document.querySelector('.tb-nav');
    if (!ul) return -1;
    var items = navItems(ul);
    return items.findIndex(function (item) { return item.classList.contains('active'); });
  }

  function syncInkAfterVisit() {
    var target = activeIndex();
    var ul = document.querySelector('.tb-nav');
    if (!ul) return;

    if (!desktop.matches) {
      setInk(-1, true);
      return;
    }

    var from = -1;
    try { from = parseInt(sessionStorage.getItem(KEY), 10); } catch (e) { from = -1; }
    if (!Number.isInteger(from) || from < 0 || from >= navItems(ul).length) from = target;

    setInk(from, true);
    requestAnimationFrame(function () {
      requestAnimationFrame(function () {
        setInk(target, false);
        try { sessionStorage.setItem(KEY, String(target)); } catch (e) { /* abaikan */ }
      });
    });
  }

  document.addEventListener('click', function (event) {
    var mobileToggle = event.target.closest && event.target.closest('#tbNavToggle');
    if (mobileToggle) {
      var nav = document.getElementById('tbNav');
      if (!nav) return;
      var open = !nav.classList.contains('open');
      nav.classList.toggle('open', open);
      mobileToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      return;
    }

    var dropdownButton = event.target.closest && event.target.closest('.tb-nav-item[data-dropdown] > button');
    if (dropdownButton) {
      event.preventDefault();
      event.stopPropagation();
      var item = dropdownButton.parentElement;
      var openDropdown = !item.classList.contains('open');
      closeAll(item);
      item.classList.toggle('open', openDropdown);
      dropdownButton.setAttribute('aria-expanded', openDropdown ? 'true' : 'false');
      return;
    }

    if (!event.target.closest || !event.target.closest('.tb-nav')) {
      closeAll(null);
    }

    var link = event.target.closest && event.target.closest('.tb-nav a[href]');
    if (link) {
      var parentItem = link.closest('.tb-nav-item');
      var ul = link.closest('.tb-nav');
      if (parentItem && ul) {
        var index = navItems(ul).indexOf(parentItem);
        if (index >= 0) {
          setInk(index, false);
          try { sessionStorage.setItem(KEY, String(index)); } catch (e) { /* abaikan */ }
        }
      }
    }
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') closeAll(null);
  });

  document.addEventListener('mouseover', function (event) {
    if (!desktop.matches) return;
    var item = event.target.closest && event.target.closest('.tb-nav > .tb-nav-item');
    if (!item || item.id === 'tbInk') return;
    var ul = item.parentElement;
    var index = navItems(ul).indexOf(item);
    if (index >= 0) setInk(index, false);
  });

  document.addEventListener('mouseout', function (event) {
    if (!desktop.matches) return;
    var ul = event.target.closest && event.target.closest('.tb-nav');
    if (!ul || (event.relatedTarget && ul.contains(event.relatedTarget))) return;
    setInk(activeIndex(), false);
  });

  document.addEventListener('turbo:before-cache', function () {
    closeAll(null);
    var nav = document.getElementById('tbNav');
    var toggle = document.getElementById('tbNavToggle');
    if (nav) nav.classList.remove('open');
    if (toggle) toggle.setAttribute('aria-expanded', 'false');
  });

  document.addEventListener('turbo:load', syncInkAfterVisit);
  document.addEventListener('DOMContentLoaded', syncInkAfterVisit, { once: true });
  window.addEventListener('resize', function () {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function () { setInk(activeIndex(), true); }, 100);
  });
  if (document.fonts && document.fonts.ready) document.fonts.ready.then(function () { setInk(activeIndex(), true); });
})();
