(function () {
  var root = document.getElementById('arac-sema');
  if (!root || root.classList.contains('readonly')) return;

  var form = root.closest('form') || document.querySelector('form[data-hasar-form]');
  if (!form) return;

  function sevClass(sev) {
    switch (sev) {
      case 'Çizik': return 'sev-cizik';
      case 'Göçük': return 'sev-gocuk';
      case 'Boyalı': return 'sev-boyali';
      case 'Değişen': return 'sev-degisen';
      case 'Hasarlı': return 'sev-hasarli';
      default: return 'sev-yok';
    }
  }

  function updatePanel(kod, sev) {
    var g = root.querySelector('.sema-panel[data-panel="' + kod + '"]');
    if (!g) return;
    g.className = 'sema-panel ' + sevClass(sev);
    var text = g.querySelector('text.sema-etiket');
    if (sev && sev !== 'Yok') {
      if (!text) {
        var rect = g.querySelector('rect');
        text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        text.setAttribute('class', 'sema-etiket');
        text.setAttribute('text-anchor', 'middle');
        var x = parseFloat(rect.getAttribute('x')) + parseFloat(rect.getAttribute('width')) / 2;
        var y = parseFloat(rect.getAttribute('y')) + parseFloat(rect.getAttribute('height')) / 2 + 3;
        text.setAttribute('x', String(x));
        text.setAttribute('y', String(y));
        g.appendChild(text);
      }
      text.textContent = sev.substring(0, 6);
    } else if (text) {
      text.remove();
    }
  }

  root.querySelectorAll('.sema-panel[data-panel]').forEach(function (g) {
    g.addEventListener('click', function () {
      var kod = g.getAttribute('data-panel');
      var sel = form.querySelector('select[name="hasar_sev_' + kod + '"]');
      var row = sel && sel.closest('tr');
      if (row) {
        row.scrollIntoView({ behavior: 'smooth', block: 'center' });
        row.classList.add('hasar-row-focus');
        setTimeout(function () { row.classList.remove('hasar-row-focus'); }, 1200);
      }
      if (sel) sel.focus();
    });
  });

  form.querySelectorAll('select[name^="hasar_sev_"]').forEach(function (sel) {
    sel.addEventListener('change', function () {
      var kod = sel.name.replace('hasar_sev_', '');
      updatePanel(kod, sel.value);
    });
  });
})();
