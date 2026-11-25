/* Grafik dashboard (SVG murni, tanpa library): sparkline, batang bertumpuk, donat, dan area. Data dari #dashData. */
(function () {
  'use strict';

  var raw = document.getElementById('dashData');
  if (!raw) return;
  var data;
  try { data = JSON.parse(raw.textContent); } catch (e) { return; }

  var NS = 'http://www.w3.org/2000/svg';
  var C = { primary: '#066fd1', azure: '#4299e1', green: '#2fb344', yellow: '#f59f00', purple: '#ae3ec9', red: '#d63939', gray: '#667382' };
  var PALET = [C.primary, C.azure, C.green, C.yellow, C.purple, C.red, C.gray];

  var labels = data.labels || [], dates = data.dates || [];
  var downloads = (data.downloads || []).map(Number), uploads = (data.uploads || []).map(Number);
  var n = labels.length;
  var activity = downloads.map(function (v, i) { return v + (uploads[i] || 0); });
  var series = { downloads: downloads, uploads: uploads, activity: activity };

  function el(name, attrs, parent) {
    var node = document.createElementNS(NS, name);
    for (var k in attrs) node.setAttribute(k, attrs[k]);
    if (parent) parent.appendChild(node);
    return node;
  }
  function fmt(v) { return Number(v).toLocaleString('id-ID'); }

  /* Kurva halus monoton (Fritsch-Carlson): tidak melewati batas data. */
  function smooth(pts) {
    var len = pts.length;
    if (len < 2) return 'M' + pts[0][0] + ',' + pts[0][1];
    var dx = [], m = [], tg = [], i;
    for (i = 0; i < len - 1; i++) { dx[i] = pts[i + 1][0] - pts[i][0]; m[i] = (pts[i + 1][1] - pts[i][1]) / dx[i]; }
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

  /* Langkah sumbu y berupa bilangan bulat "bulat" (1, 2, 5, 10, ...). */
  function niceScale(max) {
    var step = 1;
    if (max > 4) {
      var mag = Math.pow(10, Math.floor(Math.log10(max / 4)));
      var f = (max / 4) / mag;
      step = (f <= 1 ? 1 : f <= 2 ? 2 : f <= 5 ? 5 : 10) * mag;
    }
    var top = Math.max(4, Math.ceil(max / step)) * step;
    if (max <= 4) { step = 1; top = 4; }
    var ticks = [];
    for (var t = 0; t <= top + 1e-9; t += step) ticks.push(t);
    return { top: top, ticks: ticks };
  }

  /* ---------- Sparkline ---------- */
  function drawSpark(svg) {
    var vals = series[svg.getAttribute('data-series')] || [];
    var type = svg.getAttribute('data-spark');
    var W = svg.parentNode.clientWidth - (type === 'bar' ? 40 : 0), H = 40;
    if (W < 20 || !vals.length) return;
    while (svg.firstChild) svg.removeChild(svg.firstChild);
    svg.setAttribute('viewBox', '0 0 ' + W + ' ' + H);
    svg.setAttribute('height', H);
    var max = Math.max.apply(null, vals.concat([1]));
    var len = vals.length;

    if (type === 'bar') {
      var slot = W / len, bw = Math.max(2, slot * 0.55);
      vals.forEach(function (v, i) {
        var h = Math.max(v > 0 ? 3 : 2, v / max * (H - 2));
        el('rect', { x: i * slot + (slot - bw) / 2, y: H - h, width: bw, height: h, rx: 1, fill: C.primary, opacity: v > 0 ? 1 : .25 }, svg);
      });
      return;
    }

    var padY = 4;
    var pts = vals.map(function (v, i) { return [len === 1 ? W / 2 : W * i / (len - 1), H - padY - (v / max) * (H - padY * 2)]; });
    var line = smooth(pts);
    if (type === 'area') {
      el('path', { d: line + ' L' + W + ',' + H + ' L0,' + H + ' Z', fill: C.primary, 'fill-opacity': .12 }, svg);
    }
    el('path', { d: line, fill: 'none', stroke: C.primary, 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }, svg);
  }

  /* ---------- Batang bertumpuk ---------- */
  var tip = null;
  function ensureTip(host) {
    if (!tip) { tip = document.createElement('div'); tip.className = 'tb-tip'; tip.hidden = true; }
    host.appendChild(tip);
    return tip;
  }

  var firstBars = true;
  function drawBars(host) {
    var W = host.clientWidth, H = 290;
    if (W < 100 || !n) return;
    host.innerHTML = '';
    var padL = 34, padR = 4, padT = 8, padB = 30;
    var iw = W - padL - padR, ih = H - padT - padB;
    var max = Math.max.apply(null, activity.concat([1]));
    var sc = niceScale(max);
    var slot = iw / n, bw = Math.max(3, Math.min(14, slot * 0.62));
    var x = function (i) { return padL + slot * i + slot / 2; };
    var y = function (v) { return padT + ih - (v / sc.top) * ih; };

    var svg = el('svg', { viewBox: '0 0 ' + W + ' ' + H, width: W, height: H }, host);
    var axis = el('g', { 'class': 'tb-axis' }, svg);

    sc.ticks.forEach(function (tv) {
      el('line', { x1: padL, x2: W - padR, y1: y(tv), y2: y(tv), 'class': 'tb-grid' }, svg);
      var tx = el('text', { x: padL - 8, y: y(tv) + 4, 'text-anchor': 'end' }, axis); tx.textContent = tv;
    });

    // Label tanggal: tiap k hari, ditambah awal bulan (tebal).
    var k = Math.max(1, Math.ceil(n / Math.max(2, Math.floor(iw / 70))));
    var shown = [];
    for (var i = 0; i < n; i += k) shown.push(i);
    var bulan = [];
    dates.forEach(function (d, j) { if (d.slice(8, 10) === '01' && j > 0) bulan.push(j); });
    shown = shown.filter(function (j) { return bulan.every(function (b) { return Math.abs(b - j) > k / 2; }); });
    shown.forEach(function (j) {
      el('line', { x1: x(j), x2: x(j), y1: padT, y2: padT + ih, 'class': 'tb-grid' }, svg);
      var t = el('text', { x: x(j), y: H - 8, 'text-anchor': 'middle' }, axis); t.textContent = labels[j];
    });
    bulan.forEach(function (j) {
      el('line', { x1: x(j), x2: x(j), y1: padT, y2: padT + ih, 'class': 'tb-grid' }, svg);
      var parts = labels[j].split(' ');
      var t = el('text', { x: x(j), y: H - 8, 'text-anchor': 'middle', 'class': 'is-strong' }, axis);
      t.textContent = (parts[1] || '') + " \u2019" + dates[j].slice(2, 4);
    });

    el('line', { x1: padL, x2: W - padR, y1: y(0), y2: y(0), 'class': 'tb-grid solid' }, svg);

    for (var b = 0; b < n; b++) {
      var dv = downloads[b], uv = uploads[b] || 0;
      if (dv > 0) el('rect', { x: x(b) - bw / 2, y: y(dv), width: bw, height: y(0) - y(dv), fill: C.primary, 'data-bar': 1 }, svg);
      if (uv > 0) el('rect', { x: x(b) - bw / 2, y: y(dv + uv), width: bw, height: y(dv) - y(dv + uv), fill: C.green, 'data-bar': 1 }, svg);
    }

    if (!firstBars) svg.querySelectorAll('[data-bar]').forEach(function (r) { r.style.animation = 'none'; });
    firstBars = false;
    var hover = el('rect', { y: padT, width: slot, height: ih, fill: 'rgba(6,111,209,.06)', visibility: 'hidden' }, svg);
    var t = ensureTip(host);
    svg.addEventListener('mousemove', function (e) {
      var r = svg.getBoundingClientRect();
      var px = (e.clientX - r.left) * (W / r.width);
      var idx = Math.floor((px - padL) / slot);
      if (idx < 0 || idx >= n) { out(); return; }
      hover.setAttribute('x', padL + slot * idx); hover.setAttribute('visibility', 'visible');
      t.innerHTML = '<div class="t-head">' + labels[idx] + '</div>' +
        '<div class="t-row"><span class="t-dot" style="background:' + C.primary + '"></span>Download: <strong>' + fmt(downloads[idx]) + '</strong></div>' +
        '<div class="t-row"><span class="t-dot" style="background:' + C.green + '"></span>Unggahan: <strong>' + fmt(uploads[idx] || 0) + '</strong></div>';
      t.hidden = false;
      var top = y(downloads[idx] + (uploads[idx] || 0));
      var left = Math.max(70, Math.min(W - 70, x(idx)));
      t.style.left = left + 'px'; t.style.top = Math.max(top - 6, 70) + 'px';
    });
    function out() { hover.setAttribute('visibility', 'hidden'); t.hidden = true; }
    svg.addEventListener('mouseleave', out);
  }

  /* ---------- Donat ---------- */
  function drawDonut(host) {
    var items = (data.jenis || []).filter(function (j) { return j.count > 0; });
    var legend = document.getElementById('legendJenis');
    if (!host || !items.length) return;
    var total = items.reduce(function (a, j) { return a + j.count; }, 0);
    var S = 208, R = 80, SW = 28, CX = S / 2, CIRC = 2 * Math.PI * R;
    host.innerHTML = '';
    var svg = el('svg', { viewBox: '0 0 ' + S + ' ' + S, width: S, height: S }, host);
    el('circle', { cx: CX, cy: CX, r: R, fill: 'none', stroke: 'var(--tb-track)', 'stroke-width': SW }, svg);
    var off = 0;
    items.forEach(function (j, i) {
      var len = j.count / total * CIRC;
      var seg = el('circle', { cx: CX, cy: CX, r: R, fill: 'none', stroke: PALET[i % PALET.length], 'stroke-width': SW, 'stroke-dasharray': Math.max(0, len - (items.length > 1 ? 2 : 0)) + ' ' + CIRC, 'stroke-dashoffset': -off, transform: 'rotate(-90 ' + CX + ' ' + CX + ')' }, svg);
      var tt = el('title', {}, seg); tt.textContent = j.label + ': ' + fmt(j.count);
      off += len;
    });
    var big = el('text', { x: CX, y: CX + 4, 'text-anchor': 'middle', style: 'font:600 24px Inter,sans-serif;fill:var(--tb-text)' }, svg); big.textContent = fmt(total);
    var sm = el('text', { x: CX, y: CX + 24, 'text-anchor': 'middle', style: 'font:400 12px Inter,sans-serif;fill:var(--tb-muted)' }, svg); sm.textContent = 'arsip';
    if (legend) {
      legend.innerHTML = '';
      items.forEach(function (j, i) {
        var s = document.createElement('span');
        var left = document.createElement('span');
        var dot = document.createElement('i'); dot.style.background = PALET[i % PALET.length];
        left.appendChild(dot); left.appendChild(document.createTextNode(' ' + j.label));
        var b = document.createElement('b'); b.textContent = fmt(j.count);
        s.appendChild(left); s.appendChild(b);
        legend.appendChild(s);
      });
    }
  }

  /* ---------- Area kumulatif ---------- */
  function drawArea(host) {
    var W = host.clientWidth, H = Math.max(host.clientHeight, 192);
    if (W < 40 || !n) return;
    host.innerHTML = '';
    var cum = [], run = 0;
    downloads.forEach(function (v) { run += v; cum.push(run); });
    var max = Math.max(run, 1);
    var svg = el('svg', { viewBox: '0 0 ' + W + ' ' + H, width: W, height: H, style: 'height:' + H + 'px' }, host);
    var padT = 16;
    var pts = cum.map(function (v, i) { return [n === 1 ? W / 2 : W * i / (n - 1), H - 2 - (v / max) * (H - padT - 2)]; });
    var line = smooth(pts);
    el('path', { d: line + ' L' + W + ',' + H + ' L0,' + H + ' Z', fill: C.primary, 'fill-opacity': .12 }, svg);
    el('path', { d: line, fill: 'none', stroke: C.primary, 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }, svg);
  }

  function render() {
    document.querySelectorAll('svg[data-spark]').forEach(drawSpark);
    var a = document.getElementById('chartDaily'); if (a) drawBars(a);
    var b = document.getElementById('chartJenis'); if (b) drawDonut(b);
    var c = document.getElementById('chartTren'); if (c) drawArea(c);
  }

  render();
  var timer = 0;
  window.addEventListener('resize', function () { clearTimeout(timer); timer = setTimeout(render, 120); });
})();
