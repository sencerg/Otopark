(function () {
  var form = document.getElementById('detayli-foto-form');
  if (!form) return;

  var panels = Array.prototype.slice.call(form.querySelectorAll('.detayli-foto-panel'));
  var thumbs = Array.prototype.slice.call(form.querySelectorAll('[data-foto-jump]'));
  var etiket = document.getElementById('foto-ix-etiket');
  var baslik = document.getElementById('foto-rehber-baslik');
  var ix = 0;

  function show(i) {
    if (i < 0 || i >= panels.length) return;
    ix = i;
    panels.forEach(function (p, n) {
      p.classList.toggle('is-active', n === ix);
    });
    thumbs.forEach(function (t, n) {
      t.classList.toggle('active', n === ix);
    });
    var p = panels[ix];
    if (etiket) etiket.textContent = (ix + 1) + ' / ' + panels.length;
    if (baslik && p) {
      baslik.textContent = (p.getAttribute('data-ad') || '') + ' — ' + (p.getAttribute('data-ipucu') || '');
    }
    var activeThumb = thumbs[ix];
    if (activeThumb && activeThumb.scrollIntoView) {
      activeThumb.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
    }
  }

  thumbs.forEach(function (t) {
    t.addEventListener('click', function () {
      show(parseInt(t.getAttribute('data-foto-jump'), 10) || 0);
    });
  });

  var onceki = document.getElementById('foto-onceki');
  var sonraki = document.getElementById('foto-sonraki');
  if (onceki) onceki.addEventListener('click', function () { show(ix - 1); });
  if (sonraki) sonraki.addEventListener('click', function () { show(ix + 1); });

  panels.forEach(function (panel) {
    var input = panel.querySelector('[data-foto-file]');
    var canvas = panel.querySelector('.detayli-foto-canvas');
    if (!input || !canvas) return;
    input.addEventListener('change', function () {
      var file = input.files && input.files[0];
      if (!file || !file.type || file.type.indexOf('image/') !== 0) return;
      var url = URL.createObjectURL(file);
      canvas.innerHTML = '';
      var img = document.createElement('img');
      img.className = 'detayli-foto-yuklenen-img';
      img.src = url;
      img.alt = '';
      canvas.appendChild(img);
      var jump = panel.getAttribute('data-foto-ix');
      var thumb = form.querySelector('[data-foto-jump="' + jump + '"]');
      if (thumb) {
        thumb.classList.add('has-photo');
        var tImg = thumb.querySelector('img');
        if (tImg) tImg.src = url;
      }
    });
  });

  show(0);
})();
