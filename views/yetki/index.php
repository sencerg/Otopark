<?php
use App\Core\Csrf;

$tipEtiket = ['admin' => 'Admin', 'bayi' => 'Otopark', 'musteri' => 'Müşteri'];
$kapsamEtiket = ['genel' => 'Otopark', 'kisisel' => 'Otopark personel'];

$yetkiEtiket = static function (array $u) use ($kapsamEtiket): string {
    if (($u['role'] ?? '') === 'admin') {
        return 'Admin';
    }
    if (($u['role'] ?? '') === 'musteri') {
        return 'Müşteri';
    }
    if (($u['role'] ?? '') === 'bayi') {
        return $kapsamEtiket[$u['bayi_kapsam'] ?? 'genel'] ?? 'Otopark';
    }

    return (string) ($u['role'] ?? '—');
};

$yetkiTipiKod = static function (array $u): string {
    if (($u['role'] ?? '') === 'admin') {
        return 'admin';
    }
    if (($u['role'] ?? '') === 'musteri') {
        return 'musteri';
    }
    if (($u['bayi_kapsam'] ?? 'genel') === 'kisisel') {
        return 'otopark_personel';
    }

    return 'otopark';
};
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
        <h4 class="page-title mb-1">Yetki Yönetimi</h4>
        <p class="text-muted mb-0 small">Kullanıcı ekleyin, Admin / Otopark / Otopark personel yetkisi verin; e-posta, telefon, kullanıcı adı ve şifreyi yönetin.</p>
    </div>
</div>

<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#sekme-kullanici" type="button">
            <i class="mdi mdi-account-cog-outline me-1"></i>Kullanıcılar
            <span class="badge badge-soft-secondary ms-1"><?= count($kullanicilar) ?></span>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#sekme-izin" type="button">
            <i class="mdi mdi-shield-key-outline me-1"></i>Profiller ve izinler
        </button>
    </li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="sekme-kullanici">
        <div class="card mb-3">
            <div class="card-body py-3">
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <span class="small text-muted me-1">Hızlı ekle:</span>
                    <button type="button" class="btn btn-sm btn-primary kullanici-hizli" data-tip="admin"><i class="mdi mdi-shield-account me-1"></i>Admin</button>
                    <button type="button" class="btn btn-sm btn-success kullanici-hizli" data-tip="otopark"><i class="mdi mdi-parking me-1"></i>Otopark</button>
                    <button type="button" class="btn btn-sm btn-outline-success kullanici-hizli" data-tip="otopark_personel"><i class="mdi mdi-account-hard-hat me-1"></i>Otopark personel</button>
                    <button type="button" class="btn btn-sm btn-outline-warning kullanici-hizli" data-tip="musteri"><i class="mdi mdi-domain me-1"></i>Müşteri</button>
                    <button type="button" class="btn btn-sm btn-outline-primary ms-auto" id="kullanici-yeni"><i class="mdi mdi-account-plus-outline me-1"></i>Yeni kullanıcı</button>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Tüm kullanıcılar</h5></div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0" id="kullanici-tablo">
                    <thead>
                    <tr>
                        <th>Ad</th>
                        <th>Kullanıcı adı</th>
                        <th>E-posta</th>
                        <th>Telefon</th>
                        <th>Yetki</th>
                        <th>Otopark / Firma</th>
                        <th>Durum</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($kullanicilar as $u): ?>
                        <tr data-user="<?= e(json_encode([
                            'id' => (int) $u['id'],
                            'name' => $u['name'],
                            'email' => $u['email'],
                            'kullanici_adi' => $u['kullanici_adi'] ?? '',
                            'telefon' => $u['telefon'] ?? '',
                            'role' => $u['role'],
                            'yetki_tipi' => $yetkiTipiKod($u),
                            'bayi_id' => $u['bayi_id'] ? (int) $u['bayi_id'] : null,
                            'musteri_id' => $u['musteri_id'] ? (int) $u['musteri_id'] : null,
                            'bayi_kapsam' => $u['bayi_kapsam'] ?? 'genel',
                            'yetki_profil_id' => $u['yetki_profil_id'] ? (int) $u['yetki_profil_id'] : null,
                            'kullanici_grubu_id' => $u['kullanici_grubu_id'] ? (int) $u['kullanici_grubu_id'] : null,
                            'is_active' => filter_var($u['is_active'], FILTER_VALIDATE_BOOLEAN),
                        ], JSON_UNESCAPED_UNICODE)) ?>">
                            <td class="fw-semibold"><?= e($u['name']) ?></td>
                            <td class="small"><code><?= e($u['kullanici_adi'] ?: '—') ?></code></td>
                            <td class="small"><?= e($u['email']) ?></td>
                            <td class="small"><?= e($u['telefon'] ?: '—') ?></td>
                            <td>
                                <?php
                                $yt = $yetkiEtiket($u);
                                $badge = $yt === 'Admin' ? 'primary' : ($yt === 'Müşteri' ? 'warning' : ($yt === 'Otopark personel' ? 'info' : 'success'));
                                ?>
                                <span class="badge badge-soft-<?= $badge ?>"><?= e($yt) ?></span>
                                <div class="text-muted" style="font-size:11px"><?= e($u['profil_adi'] ?? '') ?></div>
                            </td>
                            <td class="small"><?= e($u['bayi_adi'] ?? $u['musteri_adi'] ?? '—') ?></td>
                            <td><?= !empty($u['is_active']) ? '<span class="badge badge-soft-success">Aktif</span>' : '<span class="badge badge-soft-secondary">Pasif</span>' ?></td>
                            <td class="text-end"><button type="button" class="btn btn-sm btn-outline-primary kullanici-duzenle">Düzenle</button></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="sekme-izin">
        <div class="row g-3">
            <div class="col-lg-3">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Yetki profilleri</h5>
                        <button type="button" class="btn btn-sm btn-primary" id="profil-yeni"><i class="mdi mdi-plus"></i></button>
                    </div>
                    <div class="list-group list-group-flush" id="profil-liste">
                        <?php foreach ($profiller as $p): ?>
                            <button type="button"
                                    class="list-group-item list-group-item-action profil-sec <?= (int) $p['id'] === (int) $seciliProfil ? 'active' : '' ?>"
                                    data-id="<?= (int) $p['id'] ?>"
                                    data-ad="<?= e($p['ad']) ?>"
                                    data-tip="<?= e($p['tip']) ?>"
                                    data-aciklama="<?= e($p['aciklama'] ?? '') ?>"
                                    data-sistem="<?= !empty($p['sistem_mi']) ? '1' : '0' ?>">
                                <div class="d-flex justify-content-between gap-2">
                                    <strong class="small"><?= e($p['ad']) ?></strong>
                                    <?php if (!empty($p['sistem_mi'])): ?><span class="badge badge-soft-info">Sistem</span><?php endif; ?>
                                </div>
                                <div class="small opacity-75 mt-1"><?= e($tipEtiket[$p['tip']] ?? $p['tip']) ?> · <?= (int) $p['izin_sayisi'] ?> izin · <?= (int) $p['kullanici_sayisi'] ?> kullanıcı</div>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-9">
                <div class="card mb-3">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <h5 class="mb-0" id="profil-baslik">Profil</h5>
                            <div class="small text-muted" id="profil-aciklama-goster"></div>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="profil-duzenle"><i class="mdi mdi-pencil me-1"></i>Profil bilgisi</button>
                            <button type="button" class="btn btn-sm btn-outline-danger d-none" id="profil-sil"><i class="mdi mdi-delete-outline me-1"></i>Sil</button>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="izin-tumunu-sec">Tümünü seç</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="izin-temizle">Temizle</button>
                            <button type="button" class="btn btn-sm btn-primary" id="izin-kaydet"><i class="mdi mdi-content-save-outline me-1"></i>İzinleri kaydet</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-light border small mb-3">
                            <i class="mdi mdi-information-outline me-1"></i>
                            <b>Admin</b> tam yetki. <b>Otopark</b> lokasyon operasyon hesabı.
                            <b>Otopark personel</b> aynı otoparka bağlı dar yetkili kullanıcı.
                            Detaylı sayfa izinlerini burada profil bazında açıp kapatabilirsiniz.
                        </div>
                        <form id="izin-form">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="profil_id" id="profil_id" value="<?= (int) $seciliProfil ?>">
                            <?php foreach ($katalog as $grup): ?>
                                <div class="izin-grup mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="mb-0 text-uppercase text-muted" style="letter-spacing:.04em;font-size:12px"><?= e($grup['grup']) ?></h6>
                                        <button type="button" class="btn btn-link btn-sm p-0 grup-toggle" data-grup="<?= e($grup['grup']) ?>">Grup seç</button>
                                    </div>
                                    <div class="row g-2">
                                        <?php foreach ($grup['izinler'] as $izin): ?>
                                            <div class="col-md-6 col-xl-4">
                                                <label class="izin-kart border rounded px-2 py-2 d-flex gap-2 align-items-start h-100">
                                                    <input type="checkbox" class="form-check-input mt-1 izin-cb" name="izinler[]" value="<?= e($izin['kod']) ?>" data-grup="<?= e($grup['grup']) ?>">
                                                    <span>
                                                        <span class="d-block fw-semibold small"><?= e($izin['ad']) ?></span>
                                                        <?php if (!empty($izin['aciklama'])): ?>
                                                            <span class="d-block text-muted" style="font-size:11px"><?= e($izin['aciklama']) ?></span>
                                                        <?php endif; ?>
                                                        <code class="text-muted" style="font-size:10px"><?= e($izin['kod']) ?></code>
                                                    </span>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="profilModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" id="profil-form">
            <?= Csrf::field() ?>
            <input type="hidden" name="id" id="pf-id">
            <div class="modal-header"><h5 class="modal-title">Yetki profili</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body row g-3">
                <div class="col-12">
                    <label class="form-label required">Profil adı</label>
                    <input type="text" name="ad" id="pf-ad" class="form-control" required maxlength="120">
                </div>
                <div class="col-12">
                    <label class="form-label required">Tip</label>
                    <select name="tip" id="pf-tip" class="form-select" required>
                        <option value="bayi">Otopark</option>
                        <option value="musteri">Müşteri</option>
                        <option value="admin">Admin</option>
                    </select>
                    <div class="form-text">Sistem profillerinde tip değiştirilemez.</div>
                </div>
                <div class="col-12">
                    <label class="form-label">Açıklama</label>
                    <textarea name="aciklama" id="pf-aciklama" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Vazgeç</button>
                <button type="submit" class="btn btn-primary">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="kullaniciModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" id="kullanici-form">
            <?= Csrf::field() ?>
            <input type="hidden" name="id" id="ku-id">
            <input type="hidden" name="role" id="ku-role">
            <input type="hidden" name="bayi_kapsam" id="ku-kapsam">
            <div class="modal-header"><h5 class="modal-title" id="ku-baslik">Kullanıcı</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body row g-3">
                <div class="col-12">
                    <label class="form-label required">Yetki</label>
                    <select name="yetki_tipi" id="ku-yetki-tipi" class="form-select" required>
                        <option value="admin">Admin — tam yetki</option>
                        <option value="otopark">Otopark — lokasyon operasyon hesabı</option>
                        <option value="otopark_personel">Otopark personel — kısıtlı bireysel hesap</option>
                        <option value="musteri">Müşteri — firma portalı</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label required">Ad soyad</label>
                    <input type="text" name="name" id="ku-name" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label required">Kullanıcı adı</label>
                    <input type="text" name="kullanici_adi" id="ku-kullanici-adi" class="form-control" required pattern="[a-zA-Z0-9._\-]{3,80}" autocomplete="off">
                    <div class="form-text">Girişte e-posta yerine kullanılabilir.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label required">E-posta</label>
                    <input type="email" name="email" id="ku-email" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Telefon</label>
                    <input type="text" name="telefon" id="ku-telefon" class="form-control" maxlength="30">
                </div>
                <div class="col-md-6">
                    <label class="form-label required">Yetki profili</label>
                    <select name="yetki_profil_id" id="ku-profil" class="form-select" required>
                        <?php foreach ($profiller as $p): ?>
                            <option value="<?= (int) $p['id'] ?>" data-tip="<?= e($p['tip']) ?>" data-ad="<?= e(mb_strtolower($p['ad'])) ?>"><?= e($p['ad']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 ku-bayi-alan">
                    <label class="form-label required">Otopark</label>
                    <select name="bayi_id" id="ku-bayi" class="form-select">
                        <option value="">Seçiniz</option>
                        <?php foreach ($bayiler as $b): ?>
                            <option value="<?= (int) $b['id'] ?>"><?= e($b['ad']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 ku-musteri-alan d-none">
                    <label class="form-label required">Müşteri (firma)</label>
                    <select name="musteri_id" id="ku-musteri" class="form-select">
                        <option value="">Seçiniz</option>
                        <?php foreach ($musteriler as $m): ?>
                            <option value="<?= (int) $m['id'] ?>"><?= e($m['ad']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Kullanıcı grubu (eski etiket)</label>
                    <select name="kullanici_grubu_id" id="ku-grup" class="form-select">
                        <option value="">—</option>
                        <?php foreach ($gruplar as $g): ?>
                            <option value="<?= (int) $g['id'] ?>"><?= e($g['ad']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" id="ku-sifre-label">Şifre</label>
                    <input type="password" name="password" id="ku-password" class="form-control" autocomplete="new-password">
                </div>
                <div class="col-md-6">
                    <label class="form-label" id="ku-sifre-tekrar-label">Şifre tekrarı</label>
                    <input type="password" name="password_tekrar" id="ku-password-tekrar" class="form-control" autocomplete="new-password">
                    <div class="form-text">Düzenlemede boş bırakırsanız şifre değişmez.</div>
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="ku-aktif" value="1" checked>
                        <label class="form-check-label" for="ku-aktif">Aktif hesap</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Vazgeç</button>
                <button type="submit" class="btn btn-primary">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<style>
.izin-kart { cursor: pointer; background: #fff; transition: border-color .15s, background .15s; }
.izin-kart:hover { border-color: var(--primary) !important; background: #f8faff; }
.izin-kart:has(.izin-cb:checked) { border-color: var(--primary) !important; background: #eef4ff; }
#profil-liste .list-group-item.active { background: #eaf0fe; color: inherit; border-color: #d7e3ff; }
</style>

<?php ob_start(); ?>
<script>
$(function () {
    let secili = <?= (int) $seciliProfil ?>;
    const profilModal = new bootstrap.Modal('#profilModal');
    const kullaniciModal = new bootstrap.Modal('#kullaniciModal');
    const tipRole = { admin: 'admin', otopark: 'bayi', otopark_personel: 'bayi', musteri: 'musteri' };
    const tipKapsam = { admin: '', otopark: 'genel', otopark_personel: 'kisisel', musteri: '' };

    function profilBul(id) {
        return $('#profil-liste .profil-sec').filter(function () { return Number($(this).data('id')) === Number(id); }).first();
    }

    function profilYukle(id) {
        secili = id;
        const $b = profilBul(id);
        $('#profil-liste .profil-sec').removeClass('active');
        $b.addClass('active');
        $('#profil_id').val(id);
        $('#profil-baslik').text($b.data('ad') || 'Profil');
        $('#profil-aciklama-goster').text($b.data('aciklama') || '');
        $('#profil-sil').toggleClass('d-none', String($b.data('sistem')) === '1');
        $('.izin-cb').prop('checked', false);
        $.getJSON('/yetki_yonetimi/profil_izinleri', { id }).done(function (r) {
            if (!r.success) return App.toast(r.message || 'Yüklenemedi', 'error');
            (r.izinler || []).forEach(function (kod) {
                $('.izin-cb[value="' + kod + '"]').prop('checked', true);
            });
        }).fail(function (x) { App.error(x.responseJSON?.message || 'İzinler yüklenemedi'); });
    }

    $('#profil-liste').on('click', '.profil-sec', function () {
        profilYukle($(this).data('id'));
    });

    $('#izin-tumunu-sec').on('click', function () { $('.izin-cb').prop('checked', true); });
    $('#izin-temizle').on('click', function () { $('.izin-cb').prop('checked', false); });
    $('.grup-toggle').on('click', function () {
        const g = $(this).data('grup');
        const $cb = $('.izin-cb[data-grup="' + g + '"]');
        const all = $cb.length === $cb.filter(':checked').length;
        $cb.prop('checked', !all);
    });

    $('#izin-kaydet').on('click', function () {
        const data = $('#izin-form').serializeArray();
        $.post('/yetki_yonetimi/izin_kaydet', data).done(function (r) {
            App.toast(r.message, r.success ? 'success' : 'error');
            if (r.success) {
                const $b = profilBul(secili);
                $b.find('.small').first().html(function (_, html) {
                    return html.replace(/\d+ izin/, (r.izin_sayisi || 0) + ' izin');
                });
            }
        }).fail(function (x) { App.error(x.responseJSON?.message || 'Kaydedilemedi'); });
    });

    function profilFormAc(data) {
        $('#pf-id').val(data.id || '');
        $('#pf-ad').val(data.ad || '');
        $('#pf-tip').val(data.tip || 'bayi').prop('disabled', !!data.sistem);
        $('#pf-aciklama').val(data.aciklama || '');
        profilModal.show();
    }
    $('#profil-yeni').on('click', function () { profilFormAc({}); });
    $('#profil-duzenle').on('click', function () {
        const $b = profilBul(secili);
        profilFormAc({
            id: secili,
            ad: $b.data('ad'),
            tip: $b.data('tip'),
            aciklama: $b.data('aciklama'),
            sistem: String($b.data('sistem')) === '1',
        });
    });
    $('#profil-sil').on('click', function () {
        if (!confirm('Bu profil silinsin mi?')) return;
        $.post('/yetki_yonetimi/profil_sil', { id: secili }).done(function (r) {
            App.toast(r.message, r.success ? 'success' : 'error');
            if (r.success) location.reload();
        }).fail(function (x) { App.error(x.responseJSON?.message || 'Silinemedi'); });
    });
    $('#profil-form').on('submit', function (e) {
        e.preventDefault();
        $('#pf-tip').prop('disabled', false);
        $.post('/yetki_yonetimi/profil_kaydet', $(this).serialize()).done(function (r) {
            App.toast(r.message, r.success ? 'success' : 'error');
            if (r.success) location.href = '/yetki_yonetimi?profil=' + r.id;
        }).fail(function (x) { App.error(x.responseJSON?.message || 'Kaydedilemedi'); });
    });

    function rolAlanlari() {
        const tip = $('#ku-yetki-tipi').val();
        const role = tipRole[tip] || 'bayi';
        const kapsam = tipKapsam[tip] || '';
        $('#ku-role').val(role);
        $('#ku-kapsam').val(kapsam);
        $('.ku-bayi-alan').toggleClass('d-none', tip !== 'otopark' && tip !== 'otopark_personel');
        $('.ku-musteri-alan').toggleClass('d-none', tip !== 'musteri');

        $('#ku-profil option').each(function () {
            const $o = $(this);
            const pTip = String($o.data('tip') || '');
            const ad = String($o.data('ad') || '');
            let ok = pTip === role;
            if (ok && tip === 'otopark') {
                ok = !(ad.includes('personel') || ad.includes('kişisel') || ad.includes('kisisel'));
            }
            if (ok && tip === 'otopark_personel') {
                ok = ad.includes('personel') || ad.includes('kişisel') || ad.includes('kisisel');
            }
            $o.toggle(ok).prop('disabled', !ok);
            if (!ok && $o.is(':selected')) $o.prop('selected', false);
        });
        const first = $('#ku-profil option').filter(function () { return !$(this).prop('disabled'); }).first();
        if (first.length && ($('#ku-profil option:selected').prop('disabled') || !$('#ku-profil').val())) {
            first.prop('selected', true);
        }
    }
    $('#ku-yetki-tipi').on('change', rolAlanlari);

    function kullaniciFormAc(u) {
        $('#ku-id').val(u.id || '');
        $('#ku-baslik').text(u.id ? 'Kullanıcı düzenle' : 'Yeni kullanıcı');
        $('#ku-name').val(u.name || '');
        $('#ku-email').val(u.email || '');
        $('#ku-kullanici-adi').val(u.kullanici_adi || '');
        $('#ku-telefon').val(u.telefon || '');
        $('#ku-yetki-tipi').val(u.yetki_tipi || 'otopark');
        $('#ku-bayi').val(u.bayi_id || '');
        $('#ku-musteri').val(u.musteri_id || '');
        $('#ku-grup').val(u.kullanici_grubu_id || '');
        $('#ku-password').val('');
        $('#ku-password-tekrar').val('');
        $('#ku-sifre-label').text(u.id ? 'Şifre (değiştirmek için)' : 'Şifre');
        $('#ku-sifre-tekrar-label').text(u.id ? 'Şifre tekrarı (değiştirmek için)' : 'Şifre tekrarı');
        $('#ku-aktif').prop('checked', u.is_active !== false);
        rolAlanlari();
        if (u.yetki_profil_id) $('#ku-profil').val(String(u.yetki_profil_id));
        kullaniciModal.show();
    }
    $('#kullanici-yeni').on('click', function () {
        kullaniciFormAc({ yetki_tipi: 'otopark', is_active: true });
    });
    $('.kullanici-hizli').on('click', function () {
        kullaniciFormAc({ yetki_tipi: $(this).data('tip'), is_active: true });
    });
    $('#kullanici-tablo').on('click', '.kullanici-duzenle', function () {
        kullaniciFormAc($(this).closest('tr').data('user'));
    });
    $('#ku-email').on('blur', function () {
        if ($('#ku-kullanici-adi').val()) return;
        const local = String($(this).val() || '').split('@')[0].toLowerCase().replace(/[^a-z0-9._-]/g, '');
        if (local.length >= 3) $('#ku-kullanici-adi').val(local);
    });
    $('#kullanici-form').on('submit', function (e) {
        e.preventDefault();
        rolAlanlari();
        $.post('/yetki_yonetimi/kullanici_kaydet', $(this).serialize()).done(function (r) {
            App.toast(r.message, r.success ? 'success' : 'error');
            if (r.success) location.reload();
        }).fail(function (x) { App.error(x.responseJSON?.message || 'Kaydedilemedi'); });
    });

    if (secili && $('#sekme-izin').hasClass('active')) profilYukle(secili);
    $('button[data-bs-target="#sekme-izin"]').on('shown.bs.tab', function () {
        if (secili) profilYukle(secili);
    });
});
</script>
<?php $scripts = ob_get_clean(); ?>
