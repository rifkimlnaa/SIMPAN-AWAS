/* Grafik download per hari: area halus bergradasi, garis bidik, tooltip kaca. Tanpa library (SVG murni). */
(function () {
  'use strict';

  var host = document.getElementById('chartDownload');
  if (!host) return;

  var NS = 'http://www.w3.org/2000/svg';
  var labels, values;
  try { labels = JSON.parse(host.dataset.labels || '[]'); values = JSON.parse(host.dataset.values || '[]').map(Number); }
  catch (e) { labels = []; values = []; }
  var n = Math.min(labels.length, values.length);
  labels = labels.slice(0, n); values = values.slice(0, n);

  function el(name, attrs, parent) {
    var node = document.createElementNS(NS, name);
    for (var k in attrs) node.setAttribute(k, attrs[k]);
    if (parent) parent.appendChild(node);
    return node;
  }

  /* ---------- Ringkasan (total, rata-rata, puncak) ---------- */
  var total = values.reduce(function (a, b) { return a + b; }, 0);
  var peak = 0, peakI = -1;
  values.forEach(function (v, i) { if (v > peak) { peak = v; peakI = i; } });
  var stats = document.getElementById('chartStats');
  if (stats) {
    var avg = n ? total / n : 0;
    var set = function (key, text) { var s = stats.querySelector('[data-stat="' + key + '"]'); if (s) s.textContent = text; };
    set('total', String(total));
    set('avg', avg.toLocaleString('id-ID', { maximumFractionDigits: 1 }));
    set('peak', peakI >= 0 ? peak + ' · ' + labels[peakI] : '–');
  }

  if (!n) { host.innerHTML = '<p class="gc-empty">Belum ada data download.</p>'; return; }

  /* ---------- Skala ---------- */
  // Langkah sumbu y berupa bilangan bulat "bulat" (1, 2, 5, 10, ...) agar angkanya rata.
  var step = 1;
  if (peak > 4) {
    var mag = Math.pow(10, Math.floor(Math.log10(peak / 4)));
    var f = (peak / 4) / mag;
    step = (f <= 1 ? 1 : f <= 2 ? 2 : f <= 5 ? 5 : 10) * mag;
  }
  var top = Math.max(2, Math.ceil(peak / step)) * step;
  var ticks = [];
  for (var tv0 = 0; tv0 <= top + 1e-9; tv0 += step) ticks.push(tv0);

  /* Interpolasi monoton (Fritsch–Carlson): kurva halus tanpa "meluncur" di bawah nol. */
  function smoothPath(pts) {
    var len = pts.length;
    if (len < 2) return 'M' + pts[0][0] + ',' + pts[0][1];
    var dx = [], dy = [], m = [], tg = [];
    for (var i = 0; i < len - 1; i++) { dx[i] = pts[i + 1][0] - pts[i][0]; dy[i] = pts[i + 1][1] - pts[i][1]; m[i] = dy[i] / dx[i]; }
    tg[0] = m[0]; tg[len - 1] = m[len - 2];
    for (i = 1; i < len - 1; i++) tg[i] = m[i - 1] * m[i] <= 0 ? 0 : (m[i - 1] + m[i]) / 2;
    for (i = 0; i < len - 1; i++) {
      if (m[i] === 0) { tg[i] = 0; tg[i + 1] = 0; continue; }
      var a = tg[i] / m[i], b = tg[i + 1] / m[i], s = a * a + b * b;
      if (s > 9) { var k = 3 / Math.sqrt(s); tg[i] = k * a * m[i]; tg[i + 1] = k * b * m[i]; }
    }
    var d = 'M' + pts[0][0] + ',' + pts[0][1];
    for (i = 0; i < len - 1; i++) {
      var h = dx[i] / 3;
      d += ' C' + (pts[i][0] + h) + ',' + (pts[i][1] + tg[i] * h) + ' ' + (pts[i + 1][0] - h) + ',' + (pts[i + 1][1] - tg[i + 1] * h) + ' ' + pts[i + 1][0] + ',' + pts[i + 1][1];
    }
    return d;
  }

  /* ---------- Gambar ---------- */
  var tip = document.createElement('div');
  tip.className = 'gc-tip'; tip.hidden = true;
  host.classList.add('gc');
  var uid = 'gc' + Math.random().toString(36).slice(2, 7);
  var drawn = false;

  function render() {
    var W = host.clientWidth, H = host.clientHeight;
    if (W < 40 || H < 40) return;
    host.innerHTML = '';

    var padL = 30, padR = 12, padT = 12, padB = 26;
    var iw = W - padL - padR, ih = H - padT - padB;
    var x = function (i) { return padL + (n === 1 ? iw / 2 : iw * i / (n - 1)); };
    var y = function (v) { return padT + ih - (v / top) * ih; };

    var svg = el('svg', { width: W, height: H, viewBox: '0 0 ' + W + ' ' + H, role: 'img', 'aria-label': host.getAttribute('aria-label') || 'Grafik download per hari' }, host);
    var defs = el('defs', {}, svg);
    var gArea = el('linearGradient', { id: uid + 'a', x1: 0, y1: 0, x2: 0, y2: 1 }, defs);
    el('stop', { offset: '0%', 'stop-color': 'var(--gc-accent)', 'stop-opacity': '.38' }, gArea);
    el('stop', { offset: '100%', 'stop-color': 'var(--gc-accent)', 'stop-opacity': '0' }, gArea);
    var gLine = el('linearGradient', { id: uid + 'l', x1: 0, y1: 0, x2: 1, y2: 0 }, defs);
    el('stop', { offset: '0%', 'stop-color': 'var(--gc-accent-2)' }, gLine);
    el('stop', { offset: '100%', 'stop-color': 'var(--gc-accent)' }, gLine);
    var glow = el('filter', { id: uid + 'g', x: '-50%', y: '-50%', width: '200%', height: '200%' }, defs);
    el('feGaussianBlur', { stdDeviation: 3 }, glow);

    ticks.forEach(function (tv) {
      el('line', { x1: padL, x2: W - padR, y1: y(tv), y2: y(tv), class: 'gc-grid' + (tv === 0 ? ' is-base' : '') }, svg);
      var tx = el('text', { x: padL - 8, y: y(tv) + 3.5, class: 'gc-ytick', 'text-anchor': 'end' }, svg); tx.textContent = tv;
    });

    var every = Math.max(1, Math.ceil(n / Math.max(2, Math.floor(iw / 62))));
    for (var i = 0; i < n; i += every) {
      var lx = el('text', { x: x(i), y: H - 7, class: 'gc-xtick', 'text-anchor': i === 0 ? 'start' : (i + every >= n && i === n - 1 ? 'end' : 'middle') }, svg);
      lx.textContent = labels[i];
    }

    var pts = values.map(function (v, i) { return [x(i), y(v)]; });
    var line = smoothPath(pts);
    var area = el('path', { d: line + ' L' + x(n - 1) + ',' + y(0) + ' L' + x(0) + ',' + y(0) + ' Z', fill: 'url(#' + uid + 'a)', class: 'gc-area' + (drawn ? '' : ' is-intro') }, svg);
    var glowLine = el('path', { d: line, class: 'gc-line-glow', stroke: 'url(#' + uid + 'l)', filter: 'url(#' + uid + 'g)' }, svg);
    var path = el('path', { d: line, class: 'gc-line' + (drawn ? '' : ' is-intro'), stroke: 'url(#' + uid + 'l)' }, svg);
    if (!drawn) {
      var L = path.getTotalLength ? path.getTotalLength() : 1000;
      path.style.strokeDasharray = L; path.style.strokeDashoffset = L;
      void path.getBoundingClientRect();
      path.style.transition = 'stroke-dashoffset 1.1s cubic-bezier(.4,0,.2,1)';
      path.style.strokeDashoffset = 0;
      glowLine.style.opacity = 0; setTimeout(function () { glowLine.style.opacity = ''; }, 900);
      drawn = true;
    }

    values.forEach(function (v, i) { if (v > 0) el('circle', { cx: x(i), cy: y(v), r: 3, class: 'gc-dot' }, svg); });

    var cross = el('line', { y1: padT, y2: y(0), class: 'gc-cross' }, svg);
    var halo = el('circle', { r: 9, class: 'gc-halo' }, svg);
    var point = el('circle', { r: 4.5, class: 'gc-point' }, svg);
    cross.style.opacity = halo.style.opacity = point.style.opacity = 0;
    host.appendChild(tip);

    function show(clientX) {
      var r = host.getBoundingClientRect();
      var rel = clientX - r.left;
      var idx = n === 1 ? 0 : Math.round((rel - padL) / iw * (n - 1));
      idx = Math.max(0, Math.min(n - 1, idx));
      var px = x(idx), py = y(values[idx]);
      cross.setAttribute('x1', px); cross.setAttribute('x2', px);
      halo.setAttribute('cx', px); halo.setAttribute('cy', py);
      point.setAttribute('cx', px); point.setAttribute('cy', py);
      cross.style.opacity = halo.style.opacity = point.style.opacity = 1;
      tip.innerHTML = '<small>' + labels[idx] + '</small><strong>' + values[idx] + '</strong> download';
      tip.hidden = false;
      var tw = tip.offsetWidth, th = tip.offsetHeight;
      var left = Math.max(4, Math.min(W - tw - 4, px - tw / 2));
      var topPos = py - th - 14; if (topPos < 0) topPos = py + 14;
      tip.style.transform = 'translate(' + left + 'px,' + topPos + 'px)';
      tip.classList.add('is-show');
    }
    function hide() { cross.style.opacity = halo.style.opacity = point.style.opacity = 0; tip.classList.remove('is-show'); }
    svg.addEventListener('pointermove', function (e) { show(e.clientX); });
    svg.addEventListener('pointerdown', function (e) { show(e.clientX); });
    svg.addEventListener('pointerleave', hide);
  }

  render();
  var lastW = host.clientWidth;
  if (window.ResizeObserver) {
    new ResizeObserver(function () { if (host.clientWidth !== lastW) { lastW = host.clientWidth; render(); } }).observe(host);
  }
})();
