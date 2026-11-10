(function () {
  function ready(fn) { if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn); else fn(); }
  ready(function () {
    var type = document.getElementById('rm-type');
    function syncFields() {
      if (!type) return;
      var v = type.value;
      document.querySelectorAll('[data-reminder-field]').forEach(function (el) {
        var key = el.getAttribute('data-reminder-field');
        var show = (key === 'date' || key === 'amount') ? v === 'bill_payment' : key === 'threshold' ? v === 'budget_limit' : key === 'time' ? v !== 'budget_limit' : true;
        el.classList.toggle('hidden', !show);
        el.querySelectorAll('input,select').forEach(function (input) { input.disabled = !show; });
      });
      var title = document.getElementById('rm-title-input');
      if (title && !title.value) title.value = v === 'daily_expense' ? 'Catat pengeluaran harian' : v === 'bill_payment' ? 'Bayar tagihan' : 'Batas anggaran';
    }
    if (type) { type.addEventListener('change', syncFields); syncFields(); }
    var btn = document.getElementById('enable-notifications');
    if (btn) btn.addEventListener('click', function () {
      if (!('Notification' in window)) { alert('Browser ini belum mendukung notifikasi.'); return; }
      Notification.requestPermission().then(function (permission) {
        alert(permission === 'granted' ? 'Notifikasi browser berhasil diaktifkan.' : 'Izin notifikasi belum diberikan. Pengingat tetap terlihat saat halaman dibuka.');
      });
    });
    var node = document.getElementById('reminder-events');
    if (!node) return;
    var events = [];
    try { events = JSON.parse(node.textContent || '[]'); } catch (e) {}
    function checkEvents() {
      var now = new Date();
      var date = [now.getFullYear(), String(now.getMonth()+1).padStart(2,'0'), String(now.getDate()).padStart(2,'0')].join('-');
      var time = String(now.getHours()).padStart(2,'0') + ':' + String(now.getMinutes()).padStart(2,'0');
      events.forEach(function (ev) {
        var due = ev.type === 'daily_expense' ? time >= ev.time : ev.type === 'bill_payment' ? ((date === (ev.notify_date || ev.date) || date === ev.date) && time >= ev.time) : true;
        if (!due) return;
        var key = 'sb-reminder-' + ev.key + '-' + date;
        if (localStorage.getItem(key)) return;
        localStorage.setItem(key, '1');
        if ('Notification' in window && Notification.permission === 'granted') {
          new Notification(ev.title, { body: ev.type === 'bill_payment' && date === ev.notify_date ? ev.body_before : ev.body, icon: '../assets/img/sakubijak-icon.jpg' });
        } else {
          var toast = document.getElementById('toasts');
          if (toast) { var el = document.createElement('div'); el.className = 'toast warning'; el.textContent = ev.title + ': ' + (ev.type === 'bill_payment' && date === ev.notify_date ? ev.body_before : ev.body); toast.appendChild(el); }
        }
      });
    }
    checkEvents();
    window.setInterval(checkEvents, 60000);
  });
})();
