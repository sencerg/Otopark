<?php
use App\Core\Csrf;

$sekmeler = [
    'musteri' => ['Müşteriler', 'mdi-domain'],
    'lokasyon' => ['Otoparklar (Lokasyonlar)', 'mdi-parking'],
    'hizmet' => ['Hizmetler', 'mdi-cash-register'],
    'personel' => ['Personel', 'mdi-account-hard-hat'],
    'arac' => ['Marka / Seri / Model', 'mdi-car-info'],
];
$aciklama = [
    'musteri' => 'Araç girişinde seçilen firmalar. Firmaya özel otopark ve hizmet ücretleri müşteri kartındaki ilgili sekmelerden yönetilir. Pasif müşteri yeni girişlerde listelenmez.',
    'lokasyon' => 'Otopark yalnızca standart günlük depolama fiyatı ve çarpanını tutar. Firmaya özel günlük ücret müşteri kartından girilir; girildiyse çarpan uygulanmadan o tutar kullanılır.',
    'hizmet' => 'Genel hizmet tanımları. Varsayılan ücret zorunludur. “Bayi fiyatı değiştirebilir” kapalıysa otopark kullanıcıları hizmeti uygularken tutarı değiştiremez (firma özel / varsayılan fiyat kilitlenir). Açıkysa bayi kendi tutarını yazabilir. Firmaya özel fiyat müşteri kartından girilir.',
    'personel' => 'Her personel bir otoparka bağlıdır. Bayi kullanıcısı yalnızca kendi otoparkındaki personeli görür ve ekler. Pasif personel seçim listelerinde görünmez.',
];
?>
<ul class="nav nav-tabs mb-3" role="tablist">
    <?php $ilk = true; foreach ($sekmeler as $kod => [$ad, $ikon]): ?>
        <li class="nav-item"><button class="nav-link <?= $ilk ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#sekme-<?= $kod ?>" type="button"><i class="mdi <?= $ikon ?> me-1"></i><?= e($ad) ?> <span class="badge badge-soft-secondary ms-1" data-sayi="<?= $kod ?>"></span></button></li>
    <?php $ilk = false; endforeach; ?>
</ul>

<div class="tab-content">
    <?php $ilk = true; foreach (['musteri', 'lokasyon', 'hizmet', 'personel'] as $kod): $t = $tipler[$kod]; $yaz = !$t['admin'] || $admin; ?>
        <div class="tab-pane fade <?= $ilk ? 'show active' : '' ?>" id="sekme-<?= $kod ?>">
            <div class="card">
                <div class="card-header">
                    <h5><?= e($sekmeler[$kod][0]) ?></h5>
                    <?php if ($yaz && $kod === 'musteri'): ?>
                        <a class="btn btn-primary btn-sm" href="/tanimlamalar/musteri/yeni"><i class="mdi mdi-plus me-1"></i>Yeni Müşteri</a>
                    <?php elseif ($yaz): ?>
                        <button class="btn btn-primary btn-sm" data-yeni="<?= $kod ?>"><i class="mdi mdi-plus me-1"></i>Yeni <?= e($t['baslik']) ?></button>
                    <?php else: ?>
                        <span class="badge badge-soft-secondary">Sadece görüntüleme (yönetici değiştirebilir)</span>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="alert alert-info py-2 small"><i class="mdi mdi-information-outline me-1"></i><?= e($aciklama[$kod]) ?></div>
                    <input type="search" class="form-control mb-2" placeholder="Ara..." data-ara="<?= $kod ?>">
                    <div class="table-responsive"><table class="table table-hover align-middle" id="tablo-<?= $kod ?>"><thead></thead><tbody></tbody></table></div>
                </div>
            </div>
        </div>
    <?php $ilk = false; endforeach; ?>

    <div class="tab-pane fade" id="sekme-arac">
        <div class="row g-3">
            <?php foreach (['marka' => 'Markalar', 'seri' => 'Seriler', 'model' => 'Modeller'] as $kod => $ad): ?>
                <div class="col-lg-4">
                    <div class="card h-100">
                        <div class="card-header"><h5><?= $ad ?> <span class="text-muted small" data-ust-ad="<?= $kod ?>"></span></h5></div>
                        <div class="card-body">
                            <form class="input-group mb-2" data-hizli-ekle="<?= $kod ?>">
                                <input type="text" class="form-control" placeholder="Yeni <?= mb_strtolower($tipler[$kod]['baslik']) ?> adı" required <?= $kod !== 'marka' ? 'disabled' : '' ?>>
                                <button class="btn btn-primary" type="submit" <?= $kod !== 'marka' ? 'disabled' : '' ?>><i class="mdi mdi-plus"></i> Ekle</button>
                            </form>
                            <input type="search" class="form-control form-control-sm mb-2" placeholder="Ara..." data-ara="<?= $kod ?>">
                            <div class="list-group tanim-liste" id="liste-<?= $kod ?>" style="max-height: 60vh; overflow-y: auto;">
                                <?php if ($kod !== 'marka'): ?><div class="text-muted small p-2">Önce <?= $kod === 'seri' ? 'marka' : 'seri' ?> seçin.</div><?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php foreach (['lokasyon', 'hizmet', 'personel'] as $kod): $t = $tipler[$kod]; if (!empty($t['admin']) && !$admin) continue; ?>
<div class="modal fade" id="modal-<?= $kod ?>" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="post" action="/tanimlamalar/<?= $kod ?>/kaydet" data-ajax data-tip="<?= $kod ?>">
            <?= Csrf::field() ?>
            <input type="hidden" name="id">
            <div class="modal-header"><h5 class="modal-title"><?= e($t['baslik']) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <?php foreach ($t['alanlar'] as $alan => [$etiket, $tur, $zorunlu]): ?>
                    <div class="mb-3">
                        <?php if ($tur === 'bool'): ?>
                            <div class="form-check form-switch"><input type="checkbox" class="form-check-input" name="<?= $alan ?>" value="1" id="f-<?= $kod ?>-<?= $alan ?>" checked><label class="form-check-label" for="f-<?= $kod ?>-<?= $alan ?>"><?= e($etiket) ?></label></div>
                        <?php else: ?>
                            <label class="form-label <?= $zorunlu ? 'required' : '' ?>"><?= e($etiket) ?></label>
                            <?php if ($tur === 'textarea'): ?>
                                <textarea name="<?= $alan ?>" class="form-control" rows="2"></textarea>
                            <?php else: ?>
                                <input type="<?= $tur === 'decimal' ? 'number' : 'text' ?>" <?= $tur === 'decimal' ? 'step="0.001" min="0"' : '' ?> name="<?= $alan ?>" class="form-control" <?= $zorunlu ? 'required' : '' ?>>
                            <?php endif; ?>
                            <?php if ($alan === 'fiyat_carpani'): ?><div class="form-text">Yalnızca depolama için. 1 = değişiklik yok, 1,25 = %25 fazla. Hizmet ücretlerine uygulanmaz.</div><?php endif; ?>
                            <?php if ($alan === 'varsayilan_tutar'): ?><div class="form-text">Zorunlu genel ücret. Firmaya özel fiyat yoksa bu tutar kullanılır.</div><?php endif; ?>
                            <?php if ($alan === 'bayi_fiyat_degistirebilir'): ?><div class="form-text">Kapalı = bayi hizmet seçince ücreti değiştiremez (kilitli). Açık = bayi kendi fiyatını girebilir.</div><?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <?php if ($kod === 'lokasyon'): ?><div class="alert alert-secondary py-2 small mb-0">Etkin günlük depolama: <b data-etkin-fiyat>-</b></div><?php endif; ?>
                <?php if ($kod === 'personel' && $admin): ?>
                    <div class="mb-3">
                        <label class="form-label required">Otopark</label>
                        <select name="bayi_id" class="form-select" required><?= \App\Core\Tanim::options(\App\Core\Tanim::liste('bayiler'), null, 'Seçiniz') ?></select>
                        <div class="form-text">Personel yalnızca seçilen otoparkta görünür. Bayi kullanıcısı otomatik kendi otoparkına kaydeder.</div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary">Kaydet</button></div>
        </form>
    </div>
</div>
<?php endforeach; ?>

<?php ob_start(); ?>
<script>
$(function () {
    const ADMIN = <?= $admin ? 'true' : 'false' ?>;
    const TIPLER = <?= json_encode($tipler, JSON_UNESCAPED_UNICODE) ?>;
    const yazabilir = (tip) => !TIPLER[tip].admin || ADMIN;
    const veri = {};
    const secili = { marka: null, seri: null };
    const durum = (v) => v ? App.badge('Aktif', 'success') : App.badge('Pasif', 'secondary');

    const islem = (tip, id) => {
        if (tip === 'musteri') {
            const links = [{ url: `/tanimlamalar/musteri/${id}`, icon: 'mdi-pencil', color: 'primary', title: 'Düzenle' }];
            if (yazabilir(tip)) links.push({ url: '#', icon: 'mdi-delete', color: 'danger', title: 'Sil', attrs: `data-sil="${tip}" data-id="${id}"` });
            return App.actions(links);
        }
        return yazabilir(tip) ? App.actions([
            { url: '#', icon: 'mdi-pencil', color: 'primary', title: 'Düzenle', attrs: `data-duzenle="${tip}" data-id="${id}"` },
            { url: '#', icon: 'mdi-delete', color: 'danger', title: 'Sil', attrs: `data-sil="${tip}" data-id="${id}"` },
        ]) : '';
    };

    const KOLONLAR = {
        musteri: [
            ['Firma Adı', (r) => `<a href="/tanimlamalar/musteri/${r.id}" class="fw-bold text-decoration-none">${App.esc(r.ad)}</a>`],
            ['Vergi No', (r) => App.esc(r.vergi_no || '-')], ['Yetkili', (r) => App.esc(r.yetkili || '-')],
            ['Telefon', (r) => App.esc(r.telefon || '-')], ['E-posta', (r) => App.esc(r.eposta || '-')],
            ['Stoktaki Araç', (r) => r.stoktaki, 'text-end'],
            ['Depolama', (r) => Number(r.ozel_fiyat) > 0
                ? `<a href="/tanimlamalar/musteri/${r.id}?sekme=otopark">${App.badge(r.ozel_fiyat + ' otopark', 'info')}</a>`
                : App.badge('Standart', 'secondary')],
            ['Hizmet Fiyatı', (r) => Number(r.hizmet_fiyat) > 0
                ? `<a href="/tanimlamalar/musteri/${r.id}?sekme=fiyat">${App.badge(r.hizmet_fiyat + ' hizmet', 'primary')}</a>`
                : App.badge('Varsayılan', 'secondary')],
            ['Durum', (r) => durum(r.aktif)],
        ],
        lokasyon: [
            ['Otopark', (r) => `<b>${App.esc(r.ad)}</b>`], ['Şehir', (r) => App.esc(r.sehir || '-')],
            ['Standart Günlük', (r) => Number(r.gunluk_fiyat) > 0 ? App.money(r.gunluk_fiyat) : App.badge('Tanımsız', 'warning'), 'text-end'],
            ['Çarpan', (r) => '× ' + App.num(r.fiyat_carpani, 2), 'text-end'],
            ['Etkin Günlük', (r) => `<b>${App.money(r.gunluk_fiyat * r.fiyat_carpani)}</b>`, 'text-end'],
            ['Stoktaki Araç', (r) => r.stoktaki, 'text-end'], ['Durum', (r) => durum(r.aktif)],
        ],
        hizmet: [
            ['Hizmet', (r) => `<b>${App.esc(r.ad)}</b>`],
            ['Varsayılan Ücret', (r) => App.money(r.varsayilan_tutar), 'text-end'],
            ['Bayi fiyatı', (r) => r.bayi_fiyat_degistirebilir ? App.badge('Değiştirilebilir', 'success') : App.badge('Kilitli', 'warning')],
            ['Firma Özel', (r) => Number(r.firma_fiyat) > 0 ? App.badge(r.firma_fiyat + ' firmada', 'secondary') : '—'],
            ['Kullanım', (r) => r.kullanim + ' kayıt', 'text-end'],
            ['Durum', (r) => durum(r.aktif)],
        ],
        personel: [
            ['Ad Soyad', (r) => `<b>${App.esc(r.ad_soyad)}</b>`],
            ['Otopark', (r) => App.esc(r.otopark || '—')],
            ['Telefon', (r) => App.esc(r.telefon || '-')],
            ['Kullanım', (r) => r.kullanim + ' hareket', 'text-end'],
            ['Durum', (r) => durum(r.aktif)],
        ],
    };

    function tabloCiz(tip) {
        const q = ($(`[data-ara="${tip}"]`).val() || '').toLocaleLowerCase('tr');
        const rows = (veri[tip] || []).filter((r) => !q || Object.values(r).join(' ').toLocaleLowerCase('tr').includes(q));
        const $t = $('#tablo-' + tip);
        $t.find('thead').html('<tr>' + KOLONLAR[tip].map((k) => `<th class="${k[2] || ''}">${k[0]}</th>`).join('') + '<th>İşlem</th></tr>');
        $t.find('tbody').html(rows.length ? rows.map((r) => '<tr>' + KOLONLAR[tip].map((k) => `<td class="${k[2] || ''}">${k[1](r)}</td>`).join('')
            + `<td>${islem(tip, r.id)}</td></tr>`).join('') : `<tr><td colspan="9" class="text-center text-muted py-4">Kayıt yok.</td></tr>`);
    }

    function listeCiz(tip) {
        const q = ($(`[data-ara="${tip}"]`).val() || '').toLocaleLowerCase('tr');
        const rows = (veri[tip] || []).filter((r) => !q || r.ad.toLocaleLowerCase('tr').includes(q));
        const altAd = { marka: 'seri', seri: 'model', model: 'araç' }[tip];
        $('#liste-' + tip).html(rows.length ? rows.map((r) => `
            <div class="list-group-item list-group-item-action d-flex align-items-center gap-2 ${secili[tip] === r.id ? 'active' : ''}" data-sec="${tip}" data-id="${r.id}" role="button">
                <span class="flex-grow-1">${App.esc(r.ad)}</span>
                <span class="badge badge-soft-secondary">${r.alt_sayi} ${altAd}</span>
                <a href="#" class="text-reset" title="Yeniden adlandır" data-yeniden="${tip}" data-id="${r.id}"><i class="mdi mdi-pencil"></i></a>
                <a href="#" class="text-danger" title="Sil" data-sil="${tip}" data-id="${r.id}"><i class="mdi mdi-delete"></i></a>
            </div>`).join('') : '<div class="text-muted small p-2">Kayıt yok.</div>');
    }

    function yukle(tip) {
        const ust = tip === 'seri' ? secili.marka : tip === 'model' ? secili.seri : null;
        return $.getJSON(`/tanimlamalar/${tip}/liste`, ust ? { ust_id: ust } : {}).done((r) => {
            veri[tip] = r.data;
            if (KOLONLAR[tip]) tabloCiz(tip); else listeCiz(tip);
            if (tip === 'marka') $('[data-sayi="arac"]').text(r.data.length + ' marka');
            else if (KOLONLAR[tip]) $(`[data-sayi="${tip}"]`).text(r.data.length);
        });
    }

    function altlariSifirla(tip) {
        const altlar = tip === 'marka' ? ['seri', 'model'] : tip === 'seri' ? ['model'] : [];
        altlar.forEach((a) => {
            if (a === 'model' || tip === 'marka') secili[a] = null;
            veri[a] = [];
            $('#liste-' + a).html(`<div class="text-muted small p-2">Önce ${a === 'seri' ? 'marka' : 'seri'} seçin.</div>`);
            $(`[data-hizli-ekle="${a}"]`).find('input, button').prop('disabled', true);
            $(`[data-ust-ad="${a}"]`).text('');
        });
    }

    ['musteri', 'lokasyon', 'hizmet', 'personel', 'marka'].forEach(yukle);
    $(document).on('input', '[data-ara]', function () { const tip = this.dataset.ara; KOLONLAR[tip] ? tabloCiz(tip) : listeCiz(tip); });

    /* Otopark / hizmet modalı (müşteri ayrı sayfada) */
    const modalAc = (tip, kayit) => {
        const $f = $('#modal-' + tip + ' form');
        $f[0].reset();
        $f.find('[name=id]').val(kayit?.id || '');
        $f.find(':checkbox').prop('checked', true);
        if (tip === 'lokasyon' && !kayit) $f.find('[name=fiyat_carpani]').val('1');
        if (kayit) Object.entries(kayit).forEach(([k, v]) => {
            const $el = $f.find(`[name="${k}"]`);
            if ($el.is(':checkbox')) $el.prop('checked', !!v); else $el.val(v ?? '');
        });
        $f.find('input').first().trigger('input');
        bootstrap.Modal.getOrCreateInstance($('#modal-' + tip)[0]).show();
    };
    $('[data-yeni]').on('click', function () { modalAc(this.dataset.yeni, null); });
    $(document).on('click', '[data-duzenle]', function (e) {
        e.preventDefault();
        modalAc(this.dataset.duzenle, veri[this.dataset.duzenle].find((r) => r.id == this.dataset.id));
    });
    $('#modal-lokasyon form').on('input', 'input', function () {
        const $f = $(this).closest('form');
        const f = Number($f.find('[name=gunluk_fiyat]').val() || 0), c = Number($f.find('[name=fiyat_carpani]').val() || 0);
        $f.find('[data-etkin-fiyat]').text(App.money(f * c) + ` (${App.money(f)} × ${App.num(c, 2)})`);
    });
    $(document).on('ajax-form-saved', (_, form) => { if (form.dataset.tip) yukle(form.dataset.tip); });

    $(document).on('click', '[data-sil]', function (e) {
        e.preventDefault(); e.stopPropagation();
        const tip = this.dataset.sil, id = this.dataset.id;
        Swal.fire({ icon: 'warning', title: TIPLER[tip].baslik + ' silinsin mi?', showCancelButton: true, confirmButtonText: 'Sil', cancelButtonText: 'Vazgeç', confirmButtonColor: '#dc2626' }).then((r) => {
            if (!r.isConfirmed) return;
            $.post(`/tanimlamalar/${tip}/sil/${id}`).done((res) => {
                App.toast(res.message);
                if (secili[tip] == id) { secili[tip] = null; altlariSifirla(tip); }
                yukle(tip);
            }).fail((x) => App.error(x.responseJSON?.message));
        });
    });

    $(document).on('click', '[data-sec]', function () {
        const tip = this.dataset.sec;
        if (tip === 'model') return;
        secili[tip] = Number(this.dataset.id);
        altlariSifirla(tip);
        const alt = tip === 'marka' ? 'seri' : 'model';
        const ad = veri[tip].find((r) => r.id === secili[tip])?.ad || '';
        $(`[data-ust-ad="${alt}"]`).text('— ' + (tip === 'seri' ? (veri.marka.find((r) => r.id === secili.marka)?.ad + ' / ') : '') + ad);
        $(`[data-hizli-ekle="${alt}"]`).find('input, button').prop('disabled', false);
        listeCiz(tip);
        yukle(alt);
    });
    const ustParam = (tip) => tip === 'seri' ? { marka_id: secili.marka } : tip === 'model' ? { seri_id: secili.seri } : {};
    $('[data-hizli-ekle]').on('submit', function (e) {
        e.preventDefault();
        const tip = this.dataset.hizliEkle, $in = $(this).find('input');
        $.post(`/tanimlamalar/${tip}/kaydet`, Object.assign({ ad: $in.val() }, ustParam(tip))).done((res) => {
            App.toast(res.message); $in.val('');
            yukle(tip);
            if (tip !== 'marka') yukle(tip === 'seri' ? 'marka' : 'seri');
        }).fail((x) => App.error(x.responseJSON?.message));
    });
    $(document).on('click', '[data-yeniden]', function (e) {
        e.preventDefault(); e.stopPropagation();
        const tip = this.dataset.yeniden, id = this.dataset.id;
        const kayit = veri[tip].find((r) => r.id == id);
        Swal.fire({ title: TIPLER[tip].baslik + ' adı', input: 'text', inputValue: kayit.ad, showCancelButton: true, confirmButtonText: 'Kaydet', cancelButtonText: 'Vazgeç' }).then((r) => {
            if (!r.isConfirmed || !r.value) return;
            $.post(`/tanimlamalar/${tip}/kaydet`, Object.assign({ id, ad: r.value }, ustParam(tip))).done((res) => { App.toast(res.message); yukle(tip); })
                .fail((x) => App.error(x.responseJSON?.message));
        });
    });

    if (location.hash && location.hash.startsWith('#sekme-')) {
        const btn = document.querySelector(`[data-bs-target="${location.hash}"]`);
        if (btn) bootstrap.Tab.getOrCreateInstance(btn).show();
    }
});
</script>
<?php $scripts = ob_get_clean(); ?>
