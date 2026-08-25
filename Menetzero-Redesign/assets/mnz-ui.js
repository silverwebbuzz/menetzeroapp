/* ============================================================
   mnz-ui.js — MeNetZero portal behaviour, one file, no framework
   Drop in public/js/mnz-ui.js, load with <script src defer>.
   Replaces Alpine.js (43 KB) and the inline scripts in the
   Blade layout. ~3 KB gzipped. No dependencies.

   Everything is delegated from document, so Blade partials
   rendered later (or swapped over AJAX) need no re-init.
   ============================================================ */
(function () {
  'use strict';

  var d = document;
  var on = function (evt, sel, fn) {
    d.addEventListener(evt, function (e) {
      var t = e.target.closest(sel);
      if (t) fn(e, t);
    });
  };
  var $$ = function (sel, root) { return Array.prototype.slice.call((root || d).querySelectorAll(sel)); };

  /* ---------- 1. Sidebar drawer (mobile) ---------- */
  on('click', '[data-mnz-drawer]', function () {
    var side = d.querySelector('.mnz-side');
    if (side) side.classList.toggle('is-open');
  });
  d.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      var side = d.querySelector('.mnz-side.is-open');
      if (side) side.classList.remove('is-open');
      $$('[data-mnz-menu].is-open').forEach(function (m) { m.classList.remove('is-open'); });
    }
  });

  /* ---------- 2. Dropdown menus (company switcher, user menu) ---------- */
  on('click', '[data-mnz-menu-toggle]', function (e, btn) {
    e.preventDefault();
    var menu = btn.closest('[data-mnz-menu]');
    var open = menu.classList.contains('is-open');
    $$('[data-mnz-menu].is-open').forEach(function (m) { m.classList.remove('is-open'); });
    if (!open) menu.classList.add('is-open');
    btn.setAttribute('aria-expanded', String(!open));
  });
  d.addEventListener('click', function (e) {
    if (!e.target.closest('[data-mnz-menu]')) {
      $$('[data-mnz-menu].is-open').forEach(function (m) { m.classList.remove('is-open'); });
    }
  });

  /* ---------- 3. Collapsible sections (disclosure editors, scope groups) ----------
     Server renders .mnz-sec, adds .is-open to the ones that must start open.
     State persists per page so a reload keeps the reviewer's place. */
  var KEY = 'mnz.sec.' + location.pathname;
  var openSet = null;
  try { openSet = new Set(JSON.parse(sessionStorage.getItem(KEY) || '[]')); } catch (err) { openSet = new Set(); }

  function persist() {
    try { sessionStorage.setItem(KEY, JSON.stringify(Array.prototype.slice.call(openSet))); } catch (err) {}
  }
  $$('.mnz-sec[data-sec-id]').forEach(function (sec) {
    if (openSet.has(sec.dataset.secId)) sec.classList.add('is-open');
  });
  on('click', '.mnz-sec__btn', function (e, btn) {
    var sec = btn.closest('.mnz-sec');
    var open = sec.classList.toggle('is-open');
    btn.setAttribute('aria-expanded', String(open));
    if (sec.dataset.secId) { open ? openSet.add(sec.dataset.secId) : openSet.delete(sec.dataset.secId); persist(); }
  });

  /* ---------- 4. Switches ---------- */
  on('click', '.mnz-switch', function (e, sw) {
    var next = sw.getAttribute('aria-checked') !== 'true';
    sw.setAttribute('aria-checked', String(next));
    var input = sw.nextElementSibling;
    if (input && input.type === 'hidden') input.value = next ? '1' : '0';
    sw.dispatchEvent(new CustomEvent('mnz:change', { bubbles: true, detail: { checked: next } }));
  });

  /* ---------- 5. Dirty-cell tracking + unsaved guard (Quick Input grids) ---------- */
  var dirty = 0;
  on('input', '.mnz-input--cell', function (e, el) {
    if (!el.classList.contains('is-dirty')) { el.classList.add('is-dirty'); dirty++; }
    var badge = d.querySelector('[data-mnz-dirty-count]');
    if (badge) badge.textContent = dirty;
  });
  window.addEventListener('beforeunload', function (e) {
    if (dirty > 0) { e.preventDefault(); e.returnValue = ''; }
  });
  d.addEventListener('submit', function () { dirty = 0; });

  /* ---------- 6. Client-side row filter (index pages) ---------- */
  on('input', '[data-mnz-filter]', function (e, input) {
    var scope = d.querySelector(input.dataset.mnzFilter);
    if (!scope) return;
    var q = input.value.trim().toLowerCase();
    $$('.mnz-table__row, tbody tr', scope).forEach(function (row) {
      row.hidden = q !== '' && row.textContent.toLowerCase().indexOf(q) === -1;
    });
  });

  /* ---------- 7. Charts: load Chart.js ONLY when a chart is on screen ----------
     Saves ~70 KB on every page without a canvas, and defers the parse cost
     until the chart scrolls into view.
     Markup: <div class="mnz-chart"><canvas data-mnz-chart='{...chart.js config}'></canvas></div> */
  var chartLibPromise = null;
  function chartLib() {
    if (!chartLibPromise) {
      chartLibPromise = new Promise(function (res, rej) {
        if (window.Chart) return res(window.Chart);
        var s = d.createElement('script');
        s.src = (window.MNZ_CHART_SRC || '/js/chart.umd.min.js');
        s.onload = function () { res(window.Chart); };
        s.onerror = rej;
        d.head.appendChild(s);
      });
    }
    return chartLibPromise;
  }
  var DEFAULTS = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { labels: { usePointStyle: true, boxWidth: 6, font: { family: 'Inter Tight', size: 11 }, padding: 12 } } },
    scales: { y: { beginAtZero: true, grid: { color: 'rgba(20,22,26,.06)' }, border: { display: false } }, x: { grid: { display: false } } }
  };
  function draw(canvas) {
    var cfg;
    try { cfg = JSON.parse(canvas.dataset.mnzChart); } catch (err) { return; }
    if (cfg.type === 'doughnut' || cfg.type === 'pie') { cfg.options = Object.assign({ cutout: '64%' }, cfg.options); delete DEFAULTS.scales; }
    cfg.options = Object.assign({}, DEFAULTS, cfg.options || {});
    chartLib().then(function (Chart) {
      if (canvas.__mnzChart) canvas.__mnzChart.destroy();
      canvas.__mnzChart = new Chart(canvas, cfg);
    });
  }
  var io = 'IntersectionObserver' in window ? new IntersectionObserver(function (entries) {
    entries.forEach(function (en) { if (en.isIntersecting) { io.unobserve(en.target); draw(en.target); } });
  }, { rootMargin: '120px' }) : null;

  function initCharts(root) {
    $$('canvas[data-mnz-chart]', root).forEach(function (c) { io ? io.observe(c) : draw(c); });
  }
  initCharts(d);

  /* ---------- 8. Public API for AJAX-swapped fragments ---------- */
  window.MnzUI = { initCharts: initCharts, drawChart: draw };
})();
