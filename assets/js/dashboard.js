(function () {
  'use strict';
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };

  /* ---------- Pesan (toast) dari server hilang otomatis ---------- */
  $$('#toasts .toast').forEach(function (t) {
    setTimeout(function () { t.style.transition = 'opacity .3s'; t.style.opacity = '0'; setTimeout(function () { t.remove(); }, 300); }, 5000);
  });

  /* ---------- Modal ---------- */
  var lastFocus = null;
  function openModal(m) {
    lastFocus = document.activeElement;
    m.classList.add('open'); m.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    var f = $('input:not([type=hidden]):not([type=radio]), select:not(.hidden)', m); if (f) setTimeout(function () { f.focus(); }, 50);
  }
  function closeModal(m) {
    m.classList.remove('open'); m.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    var er = $('.form-error', m); if (er) er.remove();
    if (lastFocus) { try { lastFocus.focus(); } catch (e) {} }
  }
  // Modal yang dibuka server (mis. karena salah input) langsung aktif
  if ($('.modal.open')) { document.body.style.overflow = 'hidden'; }

  // Isi modal dari atribut data-fill-*, mis. data-fill-id="3" -> input name="id"
  function fillModal(m, ds) {
    Object.keys(ds).forEach(function (k) {
      if (k.indexOf('fill') !== 0) return;
      var n = k.slice(4); n = n.charAt(0).toLowerCase() + n.slice(1);
      n = n.replace(/[A-Z]/g, function (m) { return '_' + m.toLowerCase(); }); // fillCategoryId -> category_id
      var v = ds[k];
      $$('[name="' + n + '"]', m).forEach(function (inp) {
        if (inp.type === 'radio') { inp.checked = (inp.value === v); if (inp.checked) inp.dispatchEvent(new Event('change', { bubbles: true })); }
        else { inp.value = v; }
      });
      $$('[data-text="' + n + '"]', m).forEach(function (el) { el.textContent = v; });
    });
  }

  document.addEventListener('click', function (e) {
    var o = e.target.closest('[data-modal-open]');
    if (o) {
      e.preventDefault();
      var m = document.getElementById(o.getAttribute('data-modal-open'));
      if (m) {
        var fm = $('form', m);
        if (fm) {
          fm.reset();
          var chk = $('input[name=tipe]:checked', fm);
          if (chk) chk.dispatchEvent(new Event('change', { bubbles: true }));
        }
        fillModal(m, o.dataset);
        openModal(m);
      }
      return;
    }
    var c = e.target.closest('[data-modal-close]');
    if (c) { var mm = c.closest('.modal'); if (mm) closeModal(mm); return; }

    var dd = e.target.closest('[data-dropdown-toggle]');
    $$('.dropdown').forEach(function (d) {
      var isThis = dd && d.id === dd.getAttribute('data-dropdown-toggle');
      if (isThis) { d.classList.toggle('hidden'); dd.setAttribute('aria-expanded', String(!d.classList.contains('hidden'))); }
      else if (!d.contains(e.target)) { d.classList.add('hidden'); }
    });
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      $$('.modal.open').forEach(closeModal);
      $$('.dropdown').forEach(function (d) { d.classList.add('hidden'); });
    }
  });

  /* ---------- Input nominal (format 1.500.000) ---------- */
  document.addEventListener('input', function (e) {
    var el = e.target;
    if (!el.classList || !el.classList.contains('money-input')) return;
    var digits = el.value.replace(/\D/g, '').replace(/^0+(?=\d)/, '');
    el.value = digits ? Number(digits).toLocaleString('id-ID') : '';
  });

  /* ---------- Quick Add: pilihan kategori mengikuti jenis transaksi ---------- */
  function syncCategories(form) {
    var type = (form.querySelector('input[name=tipe]:checked') || {}).value || 'pengeluaran';
    $$('select[data-cat-for]', form).forEach(function (sel) {
      var on = sel.getAttribute('data-cat-for') === type;
      sel.classList.toggle('hidden', !on);
      sel.disabled = !on; // hanya satu kategori yang ikut terkirim
    });
  }
  $$('form').forEach(function (f) {
    if (!f.querySelector('select[data-cat-for]')) return;
    $$('input[name=tipe]', f).forEach(function (r) { r.addEventListener('change', function () { syncCategories(f); }); });
    syncCategories(f);
  });

  /* ---------- Cegah kirim ganda ---------- */
  document.addEventListener('submit', function (e) {
    var b = $('button[type=submit]', e.target);
    if (b) setTimeout(function () { b.disabled = true; b.textContent = 'Menyimpan...'; }, 0);
  });

  /* ---------- Sembunyikan saldo ---------- */
  var tgl = $('[data-toggle-balance]');
  if (tgl) {
    var hidden = false;
    var targets = $$('[data-sensitive]');
    var apply = function () {
      targets.forEach(function (el) {
        if (!el.dataset.real) el.dataset.real = el.textContent;
        el.textContent = hidden ? 'Rp•••••••' : el.dataset.real;
      });
      tgl.setAttribute('aria-pressed', String(hidden));
      $$('[data-eye]', tgl).forEach(function (s) { s.classList.toggle('hidden', (s.dataset.eye === 'off') !== hidden); });
    };
    try { hidden = localStorage.getItem('sb_hide') === '1'; } catch (x) {}
    apply();
    tgl.addEventListener('click', function () {
      hidden = !hidden;
      try { localStorage.setItem('sb_hide', hidden ? '1' : '0'); } catch (x) {}
      apply();
    });
  }

  /* ---------- Kata sandi: tampilkan/sembunyikan ---------- */
  $$('[data-toggle-password]').forEach(function (b) {
    b.addEventListener('click', function () {
      var inp = document.getElementById(b.getAttribute('data-toggle-password'));
      var show = inp.type === 'password';
      inp.type = show ? 'text' : 'password';
      b.textContent = show ? 'Sembunyikan' : 'Tampilkan';
    });
  });

  /* ---------- Grafik (data dibaca dari atribut HTML #chart-data) ---------- */
  function initCharts() {
    var src = $('#chart-data');
    if (!src || typeof Chart === 'undefined') return;
    var labels = src.dataset.labels.split('|');
    var values = src.dataset.values.split(',').map(Number);
    var colors = src.dataset.colors.split(',');
    Chart.defaults.font.family = "'DM Sans', sans-serif";
    Chart.defaults.color = '#344B3D';
    var rp = function (v) { return 'Rp' + Number(v).toLocaleString('id-ID'); };
    var bar = $('#chart-bar'), don = $('#chart-donut');
    if (bar) new Chart(bar, {
      type: 'bar',
      data: { labels: labels, datasets: [{ data: values, backgroundColor: colors, borderRadius: 10, borderSkipped: false, maxBarThickness: 36 }] },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false }, tooltip: { callbacks: { label: function (c) { return rp(c.parsed.y); } } } },
        scales: {
          x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkip: false, font: { size: 11 } } },
          y: { beginAtZero: true, grid: { color: 'rgba(52,75,61,.08)' }, border: { display: false }, ticks: { callback: function (v) { return v >= 1000000 ? (v / 1000000) + ' jt' : v >= 1000 ? (v / 1000) + ' rb' : v; }, font: { size: 11 } } }
        }
      }
    });
    if (don) new Chart(don, {
      type: 'doughnut',
      data: { labels: labels, datasets: [{ data: values, backgroundColor: colors, borderColor: '#fff', borderWidth: 3, hoverOffset: 6 }] },
      options: {
        responsive: true, maintainAspectRatio: false, cutout: '68%',
        plugins: { legend: { display: false }, tooltip: { callbacks: { label: function (c) {
          var tot = values.reduce(function (a, b) { return a + b; }, 0);
          return c.label + ': ' + rp(c.parsed) + ' (' + Math.round(c.parsed / tot * 100) + '%)';
        } } } }
      }
    });
  }
  window.addEventListener('load', function () {
    initCharts();
    $$('.skeleton-cover').forEach(function (s) { s.remove(); });
  });
})();
