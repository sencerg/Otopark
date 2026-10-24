(function () {
  function toast(msg) {
    var el = document.getElementById('vis-toast');
    if (!el) {
      el = document.createElement('div');
      el.id = 'vis-toast';
      el.className = 'vis-toast';
      document.body.appendChild(el);
    }
    el.hidden = false;
    el.textContent = msg;
    clearTimeout(el._t);
    el._t = setTimeout(function () { el.hidden = true; }, 2200);
  }

  var toggle = document.getElementById('vis-menu-toggle');
  var side = document.getElementById('vis-side');
  if (toggle && side) {
    toggle.addEventListener('click', function () {
      side.classList.toggle('open');
    });
  }

  document.querySelectorAll('form[data-vis-save]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var id = form.getAttribute('data-id');
      var fd = new FormData(form);
      fd.append('modul', form.getAttribute('data-modul') || '');
      if (form.getAttribute('data-alan')) {
        fd.append('alan', form.getAttribute('data-alan'));
      }
      var csrf = document.querySelector('meta[name="csrf-token"]');
      if (csrf && !fd.get('_csrf')) {
        fd.append('_csrf', csrf.getAttribute('content'));
      }

      fetch('/vis/kayit/' + encodeURIComponent(id) + '/kaydet', {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        credentials: 'same-origin'
      }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
        .then(function (res) {
          if (!res.ok || !res.j || !res.j.success) {
            toast((res.j && res.j.message) || 'Kayıt başarısız');
            return;
          }
          toast(res.j.message || 'Kaydedildi');
          if (res.j.sonraki) {
            setTimeout(function () { window.location.href = res.j.sonraki; }, 350);
          }
        })
        .catch(function () { toast('Bağlantı hatası'); });
    });
  });
})();
