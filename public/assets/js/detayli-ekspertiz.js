(function () {
  var form = document.getElementById('detayli-hasar-form');
  if (!form) return;

  var metaEl = document.getElementById('hasar-meta');
  var meta = { tipler: [], seviyeler: ['C0', 'C1', 'C2', 'C3', 'C4', 'C5'] };
  try {
    meta = JSON.parse(metaEl ? metaEl.textContent : '{}') || meta;
  } catch (e) {}

  var jsonInput = document.getElementById('hasar-json');
  var state = {};
  try {
    state = JSON.parse(jsonInput && jsonInput.value ? jsonInput.value : '{}') || {};
  } catch (e) {
    state = {};
  }

  var items = Array.prototype.slice.call(document.querySelectorAll('.detayli-parca-item'));
  var panel = document.getElementById('hasar-panel');
  var bos = document.getElementById('hasar-bos');
  var tipWrap = document.getElementById('hasar-tip-satirlari');
  var baslik = document.getElementById('hasar-panel-baslik');
  var breadcrumb = document.getElementById('hasar-breadcrumb');
  var notInput = document.getElementById('hasar-parca-not');
  var sayac = document.getElementById('hasar-dolu-sayac');
  var aktifKod = null;
  var aktifIx = -1;

  function syncJson() {
    if (jsonInput) jsonInput.value = JSON.stringify(state);
  }

  function doluMu(rec) {
    return rec && rec.tipler && Object.keys(rec.tipler).length > 0;
  }

  function updateSayac() {
    var n = 0;
    Object.keys(state).forEach(function (k) {
      if (doluMu(state[k])) n++;
    });
    if (sayac) sayac.textContent = String(n);
    items.forEach(function (btn) {
      var kod = btn.getAttribute('data-kod');
      var rec = state[kod];
      var cnt = btn.querySelector('.cnt');
      if (doluMu(rec)) {
        btn.classList.add('has-data');
        if (cnt) cnt.textContent = String(Object.keys(rec.tipler).length);
      } else {
        btn.classList.remove('has-data');
        if (cnt) cnt.textContent = '';
      }
    });
  }

  function ensureFotolar(rec) {
    if (!rec.fotolar || typeof rec.fotolar !== 'object') rec.fotolar = {};
    return rec.fotolar;
  }

  function renderThumbs(zone, list) {
    var wrap = zone.querySelector('.detayli-hasar-thumbs');
    if (!wrap) return;
    wrap.innerHTML = '';
    (list || []).forEach(function (f) {
      if (!f || !f.path) return;
      var a = document.createElement('a');
      a.href = f.path;
      a.target = '_blank';
      a.className = 'detayli-hasar-thumb';
      var img = document.createElement('img');
      img.src = f.path;
      img.alt = '';
      a.appendChild(img);
      wrap.appendChild(a);
    });
  }

  function toggleFotoZone(row, tipKod, open) {
    var zone = row.querySelector('.detayli-hasar-foto-zone');
    if (!zone) return;
    zone.classList.toggle('d-none', !open);
    if (!open) return;
    var rec = state[aktifKod] || {};
    var fotolar = ensureFotolar(rec);
    renderThumbs(zone, fotolar[tipKod] || []);
  }

  function saveAktif() {
    if (!aktifKod) return;
    var btn = items[aktifIx];
    var tipler = {};
    tipWrap.querySelectorAll('[data-tip]').forEach(function (row) {
      var tip = row.getAttribute('data-tip');
      var aktif = row.querySelector('.detayli-c-btn.active');
      if (aktif) tipler[tip] = aktif.getAttribute('data-sev');
    });
    var not = (notInput.value || '').trim();
    var prev = state[aktifKod] || {};
    var fotolar = ensureFotolar(prev);
    // Seçimi kalkmış tiplerin fotoğraflarını da temizle
    Object.keys(fotolar).forEach(function (tip) {
      if (!tipler[tip]) delete fotolar[tip];
    });

    if (Object.keys(tipler).length === 0 && not === '' && Object.keys(fotolar).length === 0) {
      delete state[aktifKod];
    } else {
      state[aktifKod] = {
        ad: btn ? btn.getAttribute('data-ad') : aktifKod,
        ana: btn ? btn.getAttribute('data-ana-ad') : '',
        tipler: tipler,
        fotolar: fotolar,
        not: not
      };
    }
    syncJson();
    updateSayac();
  }

  function buildFotoZone(tipKod) {
    var zone = document.createElement('div');
    zone.className = 'detayli-hasar-foto-zone d-none';
    zone.setAttribute('data-tip-foto', tipKod);

    var label = document.createElement('div');
    label.className = 'small text-muted mb-2';
    label.textContent = 'Görsel ekle (isteğe bağlı)';
    zone.appendChild(label);

    var thumbs = document.createElement('div');
    thumbs.className = 'detayli-hasar-thumbs mb-2';
    zone.appendChild(thumbs);

    var input = document.createElement('input');
    input.type = 'file';
    input.className = 'form-control form-control-sm';
    input.accept = 'image/*';
    input.multiple = true;
    input.name = 'hf__' + aktifKod + '__' + tipKod + '[]';
    input.addEventListener('change', function () {
      // Yerel önizleme (kayıt submit’te sunucuya gider)
      var wrap = zone.querySelector('.detayli-hasar-thumbs');
      Array.prototype.forEach.call(input.files || [], function (file) {
        if (!file.type || file.type.indexOf('image/') !== 0) return;
        var url = URL.createObjectURL(file);
        var span = document.createElement('span');
        span.className = 'detayli-hasar-thumb is-local';
        var img = document.createElement('img');
        img.src = url;
        img.alt = file.name;
        span.appendChild(img);
        wrap.appendChild(span);
      });
    });
    zone.appendChild(input);
    return zone;
  }

  function renderPanel(ix) {
    if (ix < 0 || ix >= items.length) return;
    if (aktifKod) saveAktif();

    aktifIx = ix;
    var btn = items[ix];
    aktifKod = btn.getAttribute('data-kod');
    var ad = btn.getAttribute('data-ad') || '';
    var ana = btn.getAttribute('data-ana-ad') || '';

    items.forEach(function (b) { b.classList.toggle('active', b === btn); });
    bos.classList.add('d-none');
    panel.classList.remove('d-none');
    baslik.textContent = ad;
    breadcrumb.textContent = ana && ana !== ad ? (ana + ' · ' + ad) : ad;

    var rec = state[aktifKod] || { tipler: {}, fotolar: {}, not: '' };
    notInput.value = rec.not || '';

    tipWrap.innerHTML = '';
    (meta.tipler || []).forEach(function (t) {
      var row = document.createElement('div');
      row.className = 'detayli-hasar-tip-satir';
      row.setAttribute('data-tip', t.kod);

      var head = document.createElement('div');
      head.className = 'detayli-hasar-tip-head';

      var lab = document.createElement('div');
      lab.className = 'tip-ad';
      lab.textContent = t.ad;
      head.appendChild(lab);

      var btns = document.createElement('div');
      btns.className = 'detayli-c-btns';
      var selected = rec.tipler && rec.tipler[t.kod];
      (meta.seviyeler || []).forEach(function (sev) {
        var b = document.createElement('button');
        b.type = 'button';
        b.className = 'detayli-c-btn' + (selected === sev ? ' active' : '');
        b.setAttribute('data-sev', sev);
        b.textContent = sev;
        b.addEventListener('click', function () {
          var cur = row.querySelector('.detayli-c-btn.active');
          if (cur === b) {
            b.classList.remove('active');
            toggleFotoZone(row, t.kod, false);
          } else {
            row.querySelectorAll('.detayli-c-btn').forEach(function (x) { x.classList.remove('active'); });
            b.classList.add('active');
            toggleFotoZone(row, t.kod, true);
          }
          saveAktif();
        });
        btns.appendChild(b);
      });
      head.appendChild(btns);
      row.appendChild(head);

      var zone = buildFotoZone(t.kod);
      row.appendChild(zone);
      if (selected) toggleFotoZone(row, t.kod, true);

      tipWrap.appendChild(row);
    });
  }

  items.forEach(function (btn, ix) {
    btn.addEventListener('click', function () {
      renderPanel(ix);
    });
  });

  var onceki = document.getElementById('hasar-onceki');
  var sonraki = document.getElementById('hasar-sonraki');
  if (onceki) onceki.addEventListener('click', function () {
    if (aktifIx > 0) renderPanel(aktifIx - 1);
  });
  if (sonraki) sonraki.addEventListener('click', function () {
    if (aktifIx < items.length - 1) renderPanel(aktifIx + 1);
    else saveAktif();
  });

  if (notInput) {
    notInput.addEventListener('change', saveAktif);
    notInput.addEventListener('blur', saveAktif);
  }

  form.addEventListener('submit', function () {
    saveAktif();
    syncJson();
  });

  updateSayac();
  if (items.length) renderPanel(0);
})();
