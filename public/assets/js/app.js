(function ($) {
    'use strict';

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' } });

    const App = window.App = {};

    App.money = (v) => new Intl.NumberFormat('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(v || 0)) + ' ₺';
    App.num = (v, d = 0) => new Intl.NumberFormat('tr-TR', { minimumFractionDigits: d, maximumFractionDigits: d }).format(Number(v || 0));
    App.esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    App.toast = (message, icon = 'success') => Swal.fire({ toast: true, position: 'top-end', icon, title: message, showConfirmButton: false, timer: 2500, timerProgressBar: true });
    App.error = (message) => Swal.fire({ icon: 'error', title: 'İşlem başarısız', text: message || 'Beklenmeyen bir hata oluştu.', confirmButtonColor: '#1d4ed8' });

    /* Tema (klasik | vuexy) ve görünüm (light | dark | system) */
    const root = document.documentElement;
    const sistemKoyu = window.matchMedia('(prefers-color-scheme: dark)');
    App.tema = () => root.getAttribute('data-tema') || 'klasik';
    App.mod = () => localStorage.getItem('mod') || 'light';
    function temaUygula() {
        const mod = App.mod();
        const koyu = mod === 'dark' || (mod === 'system' && sistemKoyu.matches);
        root.setAttribute('data-tema', localStorage.getItem('tema') === 'vuexy' ? 'vuexy' : 'klasik');
        root.setAttribute('data-bs-theme', koyu ? 'dark' : 'light');
        $('[data-mod-ikon]').attr('class', 'mdi ' + (mod === 'system' ? 'mdi-monitor' : koyu ? 'mdi-weather-night' : 'mdi-weather-sunny'));
        $('[data-tema-sec]').each(function () { $(this).toggleClass('active', this.dataset.temaSec === App.tema()); });
        $('[data-mod-sec]').each(function () { $(this).toggleClass('active', this.dataset.modSec === mod); });
        if (window.Chart) {
            const css = getComputedStyle(root);
            Chart.defaults.color = css.getPropertyValue('--text').trim() || '#4a5a6b';
            Chart.defaults.borderColor = css.getPropertyValue('--border').trim() || '#e3e8f2';
            Object.values(Chart.instances || {}).forEach((c) => c.update('none'));
        }
        $(document).trigger('tema-degisti');
    }
    App.setTema = (tema) => { localStorage.setItem('tema', tema); temaUygula(); };
    App.setMod = (mod) => { localStorage.setItem('mod', mod); temaUygula(); };
    $(document).on('click', '[data-tema-sec]', function () { App.setTema(this.dataset.temaSec); });
    $(document).on('click', '[data-mod-sec]', function () { App.setMod(this.dataset.modSec); });
    sistemKoyu.addEventListener('change', () => { if (App.mod() === 'system') temaUygula(); });
    $(temaUygula);

    /* Sidebar */
    const layout = document.getElementById('layout');
    const isDesktop = () => window.matchMedia('(min-width: 1200px)').matches;
    if (layout && isDesktop() && localStorage.getItem('sidebar-collapsed') === '1') {
        layout.classList.add('sidebar-collapsed');
    }
    $(document).on('click', '[data-toggle-sidebar]', function () {
        if (isDesktop()) {
            layout.classList.toggle('sidebar-collapsed');
            localStorage.setItem('sidebar-collapsed', layout.classList.contains('sidebar-collapsed') ? '1' : '0');
        } else {
            layout.classList.toggle('sidebar-open');
        }
    });

    /* Select2 */
    App.initSelect2 = function (root) {
        $(root || document).find('select.select2').each(function () {
            if ($(this).data('select2')) return;
            const modal = $(this).closest('.modal');
            $(this).select2({
                theme: 'bootstrap-5',
                language: 'tr',
                width: '100%',
                allowClear: !this.multiple && !this.required,
                placeholder: $(this).data('placeholder') || 'Seçiniz',
                dropdownParent: modal.length ? modal : $(document.body),
            });
        });

        $(root || document).find('select.arac-select').each(function () {
            if ($(this).data('select2')) return;
            const modal = $(this).closest('.modal');
            $(this).select2({
                theme: 'bootstrap-5',
                language: 'tr',
                width: '100%',
                allowClear: true,
                placeholder: $(this).data('placeholder') || 'Şasi veya plaka yazın',
                dropdownParent: modal.length ? modal : $(document.body),
                minimumInputLength: 2,
                ajax: {
                    url: '/api/araclar',
                    delay: 250,
                    data: (p) => ({ q: p.term, stokta: $(this).data('stokta') || '' }),
                    processResults: (d) => ({ results: d.results }),
                },
            });
        });
    };

    /* Marka → Seri → Model */
    function fillSelect($select, items, placeholder) {
        const current = $select.data('selected');
        $select.empty().append(new Option(placeholder, ''));
        Object.entries(items).sort((a, b) => a[1].localeCompare(b[1], 'tr')).forEach(([id, ad]) => {
            $select.append(new Option(ad, id, false, String(current) === String(id)));
        });
        $select.data('selected', null).trigger('change.select2');
    }

    $(document).on('change', 'select[name="marka_id"]', function () {
        const $scope = $(this).closest('form, .modal, .filter-scope');
        const $seri = $scope.find('select[name="seri_id"]');
        const $model = $scope.find('select[name="model_id"]');
        fillSelect($model, {}, 'Önce Seri Seçiniz');
        if (!this.value) { fillSelect($seri, {}, 'Önce Marka Seçiniz'); return; }
        $.getJSON('/api/seriler', { marka_id: this.value }, (d) => { fillSelect($seri, d, 'Seçiniz'); $seri.trigger('change'); });
    });

    $(document).on('change', 'select[name="seri_id"]', function () {
        const $model = $(this).closest('form, .modal, .filter-scope').find('select[name="model_id"]');
        if (!$model.length) return;
        if (!this.value) { fillSelect($model, {}, 'Önce Seri Seçiniz'); return; }
        $.getJSON('/api/modeller', { seri_id: this.value }, (d) => fillSelect($model, d, 'Seçiniz'));
    });

    /* DataTables */
    $.extend(true, $.fn.dataTable.defaults, {
        language: {
            decimal: ',', thousands: '.', emptyTable: 'Kayıt bulunamadı', info: '_TOTAL_ kayıttan _START_ - _END_ arası',
            infoEmpty: 'Kayıt yok', infoFiltered: '(_MAX_ kayıt içinden)', lengthMenu: '_MENU_ kayıt göster',
            loadingRecords: 'Yükleniyor...', processing: '<div class="spinner-border spinner-border-sm text-primary"></div> Yükleniyor...',
            search: 'Ara:', zeroRecords: 'Eşleşen kayıt bulunamadı',
            paginate: { first: 'İlk', last: 'Son', next: '›', previous: '‹' },
        },
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, 500], [10, 25, 50, 100, 500]],
        processing: true,
        autoWidth: false,
    });

    /**
     * Sunucu taraflı tablo. Filtreler: data-filter="alan" özelliğine sahip inputlar.
     * opts.bulk: seçim kutuları + toplu işlem çubuğu.
     */
    App.table = function (selector, opts) {
        const $table = $(selector);
        const $filters = $(opts.filters || '[data-filter]');
        const filterData = () => {
            const d = {};
            $filters.each(function () {
                const v = $(this).val();
                if (v !== null && v !== '' && !(Array.isArray(v) && !v.length)) d[$(this).data('filter')] = v;
            });
            const q = $(opts.search || '#dt-search').val();
            if (q) d.q = q;
            return d;
        };

        const columns = opts.columns.slice();
        if (opts.bulk) {
            columns.unshift({
                data: 'id', orderable: false, searchable: false, width: 28,
                render: (id) => `<input type="checkbox" class="form-check-input row-check" value="${id}">`,
            });
        }

        const dt = $table.DataTable({
            serverSide: true,
            searching: false,
            ajax: { url: opts.url, type: 'GET', data: (d) => Object.assign(d, filterData()), dataSrc: (json) => { opts.onData && opts.onData(json); return json.data; } },
            columns,
            order: opts.order || [[opts.bulk ? 1 : 0, 'desc']],
            createdRow: opts.createdRow,
            drawCallback: () => { $table.find('.check-all').prop('checked', false); updateBulk(); },
        });

        let timer;
        $(opts.search || '#dt-search').on('input', () => { clearTimeout(timer); timer = setTimeout(() => dt.ajax.reload(), 350); });
        $filters.on('change', () => dt.ajax.reload());
        $(opts.clear || '#dt-clear').on('click', () => { $filters.val(null).trigger('change.select2'); $(opts.search || '#dt-search').val(''); dt.ajax.reload(); });

        $(opts.exportBtn || '#dt-export').on('click', function (e) {
            e.preventDefault();
            window.location = (opts.exportUrl || opts.url) + (opts.url.includes('?') ? '&' : '?') + $.param(Object.assign({ export: 1 }, filterData()));
        });

        const selectedIds = () => $table.find('.row-check:checked').map((_, el) => el.value).get();
        function updateBulk() {
            const n = selectedIds().length;
            $(opts.bulkBar || '#bulk-bar').toggleClass('show', n > 0).find('.bulk-count').text(n);
        }
        $table.on('change', '.row-check', updateBulk);
        $table.on('change', '.check-all', function () { $table.find('.row-check').prop('checked', this.checked); updateBulk(); });

        $(opts.bulkBar || '#bulk-bar').on('click', '[data-bulk]', function () {
            const ids = selectedIds();
            const $btn = $(this);
            if (!ids.length) return;
            Swal.fire({
                icon: 'warning', title: $btn.data('confirm') || 'Emin misiniz?', text: ids.length + ' kayıt seçildi.',
                showCancelButton: true, confirmButtonText: 'Evet', cancelButtonText: 'Vazgeç', confirmButtonColor: '#1d4ed8',
            }).then((r) => {
                if (!r.isConfirmed) return;
                if ($btn.data('bulk-open')) {
                    window.open($btn.data('bulk') + '?' + $.param({ ids }), '_blank');
                    return;
                }
                $.post($btn.data('bulk'), { ids, _csrf: csrf }).done((res) => {
                    App.toast(res.message || 'İşlem tamamlandı');
                    dt.ajax.reload(null, false);
                }).fail((x) => App.error(x.responseJSON?.message));
            });
        });

        return dt;
    };

    App.badge = (text, color) => `<span class="badge badge-soft-${color || 'secondary'}">${App.esc(text)}</span>`;
    App.actions = (buttons) => '<div class="table-actions">' + buttons.filter(Boolean).map((b) =>
        `<a href="${b.url}" class="btn btn-sm btn-${b.color || 'light'} btn-icon" title="${App.esc(b.title)}" ${b.attrs || ''}><i class="mdi ${b.icon}"></i></a>`
    ).join('') + '</div>';

    /* Onaylı formlar */
    $(document).on('submit', 'form[data-confirm]', function (e) {
        if (this.dataset.confirmed) return;
        e.preventDefault();
        const form = this;
        Swal.fire({
            icon: 'warning', title: form.dataset.confirm, showCancelButton: true,
            confirmButtonText: 'Evet', cancelButtonText: 'Vazgeç', confirmButtonColor: '#dc2626',
        }).then((r) => { if (r.isConfirmed) { form.dataset.confirmed = '1'; form.submit(); } });
    });

    /* AJAX formlar (modallar) */
    $(document).on('submit', 'form[data-ajax]', function (e) {
        e.preventDefault();
        const form = this;
        const $btn = $(form).find('[type="submit"]').prop('disabled', true);
        $.ajax({ url: form.action, method: 'POST', data: new FormData(form), processData: false, contentType: false })
            .done((res) => {
                if (!res.success) { App.error(res.message); return; }
                Swal.fire({ icon: 'success', title: res.message || 'Kaydedildi', confirmButtonColor: '#1d4ed8', timer: 2200 });
                form.reset();
                $(form).find('select').val(null).trigger('change.select2');
                $(form).closest('.modal').each(function () { bootstrap.Modal.getInstance(this)?.hide(); });
                $('table.dataTable').each(function () { $(this).DataTable().ajax.reload(null, false); });
                $(document).trigger('ajax-form-saved', [form, res]);
            })
            .fail((x) => App.error(x.responseJSON?.message))
            .always(() => $btn.prop('disabled', false));
    });

    /* Maliyet tipi seçilince varsayılan tutarı getir */
    $(document).on('change', 'select[data-tutar-target]', function () {
        const tutar = $(this).find(':selected').data('tutar');
        const $target = $($(this).data('tutar-target'));
        if (tutar !== undefined && Number(tutar) > 0 && !$target.val()) $target.val(tutar);
    });

    /* Dinamik satır ekleme (dosya/araç listeleri) */
    $(document).on('click', '[data-add-row]', function () {
        const $tpl = $($(this).data('add-row'));
        const $row = $($tpl.html());
        $($(this).data('target')).append($row);
        App.initSelect2($row);
    });
    $(document).on('click', '[data-remove-row]', function () { $(this).closest('tr, .row-item').remove(); });

    $(function () {
        App.initSelect2();
        $('.modal').on('shown.bs.modal', function () { App.initSelect2(this); });
        document.querySelectorAll('[title]').forEach((el) => { if (el.closest('.table-actions')) new bootstrap.Tooltip(el); });
        $(document).on('draw.dt', () => document.querySelectorAll('.table-actions [title]').forEach((el) => bootstrap.Tooltip.getOrCreateInstance(el)));
        $('input[type="date"][data-default-today]').each(function () { if (!this.value) this.valueAsDate = new Date(); });
    });
})(jQuery);
