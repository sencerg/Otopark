<?php
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Tanim;

$yeni = $arac === null;
$v = function (string $key, ?string $aracKey = null) use ($arac) {
    if (isset($_SESSION['_old'][$key])) {
        return $_SESSION['_old'][$key];
    }
    return $arac[$aracKey ?? $key] ?? null;
};
$oldList = fn (string $key, array $default) => isset($_SESSION['_old'][$key]) ? (array) $_SESSION['_old'][$key] : $default;
$envanter = $oldList('arac_envanterleri', $envanter);
$donanim = $oldList('arac_donanimlari', $donanim);
$markaId = $v('marka_id');
$seriId = $v('seri_id');

if (!$yeni) {
    $headerActions = '<a href="/arac_yonetimi/tesellum_formu/' . $arac['id'] . '" target="_blank" class="btn btn-outline-primary"><i class="mdi mdi-file-document-outline me-1"></i>Tesellüm Formu</a>'
        . '<a href="/arac_yonetimi/qr_toplu?ids[]=' . $arac['id'] . '" target="_blank" class="btn btn-outline-dark"><i class="mdi mdi-qrcode me-1"></i>QR Yazdır</a>'
        . ($arac['stokta'] ? '<a href="/arac_hareketleri/stoktan_cikar/' . $arac['id'] . '" class="btn btn-danger"><i class="mdi mdi-car-off me-1"></i>Stoktan Çıkar</a>' : '');
}
?>
<form method="post" action="<?= $yeni ? '/arac_yonetimi/save' : '/arac_yonetimi/update/' . $arac['id'] ?>" enctype="multipart/form-data">
    <?= Csrf::field() ?>
    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header">
                    <h5><?= $yeni ? 'Araç Bilgileri' : 'Araç Bilgileri — <span class="text-primary">' . e($arac['sase']) . '</span>' ?></h5>
                    <?php if (!$yeni): ?>
                        <span class="badge <?= $arac['stokta'] ? 'badge-soft-success' : 'badge-soft-danger' ?>"><?= $arac['stokta'] ? 'Stokta' : 'Stokta Değil' ?></span>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label required">Şasi Numarası</label><input type="text" name="sase" class="form-control text-uppercase" maxlength="30" value="<?= e($v('sase')) ?>" required></div>
                        <div class="col-md-4"><label class="form-label">Plaka No</label><input type="text" name="plaka" class="form-control text-uppercase" value="<?= e($v('plaka')) ?>"></div>
                        <div class="col-md-4"><label class="form-label">Önceki Plaka</label><input type="text" name="onceki_plaka" class="form-control text-uppercase" value="<?= e($v('onceki_plaka')) ?>"></div>
                        <div class="col-md-3"><label class="form-label">Araç Durumu</label><select name="arac_durumu" class="form-select"><?= Tanim::options(Tanim::ARAC_DURUMU, $v('arac_durumu')) ?></select></div>
                        <div class="col-md-3"><label class="form-label required">Araç Tipi</label><select name="arac_tipi" class="form-select" required><?= Tanim::options(Tanim::liste('arac_tipleri'), $v('arac_tipi', 'arac_tipi_id')) ?></select></div>
                        <div class="col-md-3"><label class="form-label">Kasa Tipi</label><select name="kasa_tipi" class="form-select"><?= Tanim::options(Tanim::liste('kasa_tipleri'), $v('kasa_tipi', 'kasa_tipi_id')) ?></select></div>
                        <div class="col-md-3"><label class="form-label">Park Kodu</label><input type="text" name="park_kodu" class="form-control" value="<?= e($v('park_kodu')) ?>"></div>
                    </div>

                    <div class="form-section">Detay Bilgiler</div>
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label required">Marka</label><select name="marka_id" class="form-select select2" required><?= Tanim::options(Tanim::liste('markalar'), $markaId) ?></select></div>
                        <div class="col-md-4"><label class="form-label required">Seri</label><select name="seri_id" class="form-select select2" required><?= $markaId ? Tanim::options(Tanim::seriler((int) $markaId), $seriId) : '<option value="">Önce Marka Seçiniz</option>' ?></select></div>
                        <div class="col-md-4"><label class="form-label">Model</label><select name="model_id" class="form-select select2"><?= $seriId ? Tanim::options(Tanim::modeller((int) $seriId), $v('model_id')) : '<option value="">Önce Seri Seçiniz</option>' ?></select></div>
                        <div class="col-md-3"><label class="form-label">Model Yılı</label><select name="yil" class="form-select"><?= Tanim::options(Tanim::liste('model_yillari'), $v('yil', 'model_yili_id')) ?></select></div>
                        <div class="col-md-3"><label class="form-label">Renk</label><select name="renk" class="form-select select2"><?= Tanim::options(Tanim::liste('renkler'), $v('renk', 'renk_id')) ?></select></div>
                        <div class="col-md-3"><label class="form-label">Yakıt Tipi</label><select name="yakit_tipi" class="form-select"><?= Tanim::options(Tanim::liste('yakit_tipleri'), $v('yakit_tipi', 'yakit_tipi_id')) ?></select></div>
                        <div class="col-md-3"><label class="form-label">Vites Tipi</label><select name="vites_tipi" class="form-select"><?= Tanim::options(Tanim::liste('vites_tipleri'), $v('vites_tipi', 'vites_tipi_id')) ?></select></div>
                        <div class="col-md-3"><label class="form-label">Motor Hacmi (cc)</label><input type="number" name="motor_hacmi" class="form-control" value="<?= e($v('motor_hacmi')) ?>"></div>
                        <div class="col-md-3"><label class="form-label">Motor Gücü (hp)</label><input type="number" name="motor_gucu" class="form-control" value="<?= e($v('motor_gucu')) ?>"></div>
                        <div class="col-md-3"><label class="form-label">Güncel KM</label><input type="number" name="km" min="0" class="form-control" value="<?= e($v('km')) ?>"></div>
                        <div class="col-md-3"><label class="form-label">Yakıt Durumu (%)</label><input type="number" name="yakit_durumu" min="0" max="100" class="form-control" value="<?= e($v('yakit_durumu')) ?>"></div>
                    </div>

                    <div class="form-section">Müşteri ve Lokasyon</div>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label required">Müşteri Adı</label><select name="musteri_id" class="form-select select2" required><?= Tanim::options(Tanim::liste('musteriler'), $v('musteri_id')) ?></select></div>
                        <div class="col-md-6"><label class="form-label required">Lokasyon</label><select name="bayi_id" class="form-select" required><?= Tanim::options(Tanim::liste('bayiler'), $v('bayi_id') ?? Auth::bayiId(), Auth::bayiId() ? null : 'Seçiniz') ?></select></div>
                        <div class="col-md-4"><label class="form-label">Lokasyon Türü</label><select name="lokasyon_turu" class="form-select"><?= Tanim::options(Tanim::LOKASYON_TURU, $v('lokasyon_turu')) ?></select></div>
                        <div class="col-md-4"><label class="form-label">Lokasyon Detay</label><input type="text" name="lokasyon_detay" class="form-control" placeholder="Örn. A Blok 12" value="<?= e($v('lokasyon_detay')) ?>"></div>
                        <div class="col-md-4"><label class="form-label">Konsinye Araç Mı?</label><select name="konsinye" class="form-select"><?= Tanim::options([1 => 'Evet', 0 => 'Hayır'], $v('konsinye') ? 1 : 0, null) ?></select></div>
                        <div class="col-md-8"><label class="form-label">Proje Adı</label><input type="text" name="proje_adi" class="form-control" value="<?= e($v('proje_adi')) ?>"></div>
                        <?php if (!$yeni): ?>
                            <div class="col-md-4"><label class="form-label">Stoğa Giriş Tarihi</label><input type="date" name="stoga_giris_tarihi" class="form-control" value="<?= $arac['stoga_giris_tarihi'] ? date('Y-m-d', strtotime($arac['stoga_giris_tarihi'])) : '' ?>"></div>
                        <?php endif; ?>
                    </div>

                    <?php if ($yeni): ?>
                        <div class="form-section">Hareket (Stok Giriş) Bilgileri</div>
                        <div class="row g-3">
                            <div class="col-md-4"><label class="form-label">Hareket Tipi</label><select name="hareket_tipi" class="form-select"><?= Tanim::options(Tanim::HAREKET_TIPI, $v('hareket_tipi') ?? 1, 'Hareket Kaydı Oluşturma') ?></select></div>
                            <div class="col-md-4"><label class="form-label">Hareket Nedeni</label><select name="hareket_nedeni" class="form-select"><?= Tanim::options(Tanim::liste('hareket_nedenleri'), $v('hareket_nedeni')) ?></select></div>
                            <div class="col-md-4"><label class="form-label">Giriş Tarihi</label>
                                <div class="input-group"><input type="date" name="hareket_tarihi" class="form-control" value="<?= e($v('hareket_tarihi') ?? date('Y-m-d')) ?>"><input type="time" name="hareket_saati" class="form-control" value="<?= e($v('hareket_saati') ?? date('H:i')) ?>"></div>
                            </div>
                            <div class="col-md-4"><label class="form-label">Teslim Eden</label><input type="text" name="teslim_eden" class="form-control" value="<?= e($v('teslim_eden')) ?>"></div>
                            <div class="col-md-4"><label class="form-label">Teslim Eden Telefon</label><input type="text" name="teslim_eden_telefon" class="form-control" value="<?= e($v('teslim_eden_telefon')) ?>"></div>
                            <div class="col-md-4"><label class="form-label">Teslim Eden E-posta</label><input type="email" name="teslim_eden_eposta" class="form-control" value="<?= e($v('teslim_eden_eposta')) ?>"></div>
                            <div class="col-md-6"><label class="form-label">Teslim Alan Personel</label><select name="teslim_alan_personel" class="form-select select2"><?= Tanim::options(Tanim::liste('personeller'), $v('teslim_alan_personel')) ?></select></div>
                            <div class="col-md-6"><label class="form-label">Açıklama</label><input type="text" name="aciklama" class="form-control" value="<?= e($v('aciklama')) ?>"></div>
                        </div>
                    <?php endif; ?>

                    <div class="form-section">Araç Envanteri</div>
                    <div class="check-grid">
                        <?php foreach (Tanim::liste('envanterler') as $id => $ad): ?>
                            <label class="form-check"><input class="form-check-input" type="checkbox" name="arac_envanterleri[]" value="<?= $id ?>" <?= in_array($id, $envanter) ? 'checked' : '' ?>> <span class="form-check-label"><?= e($ad) ?></span></label>
                        <?php endforeach; ?>
                    </div>

                    <div class="form-section">Araç Donanımları</div>
                    <?php foreach (Tanim::donanimGruplari() as $grup => $items): ?>
                        <div class="fw-semibold small text-uppercase text-muted mt-2 mb-1"><?= e($grup) ?></div>
                        <div class="check-grid">
                            <?php foreach ($items as $id => $ad): ?>
                                <label class="form-check"><input class="form-check-input" type="checkbox" name="arac_donanimlari[]" value="<?= $id ?>" <?= in_array($id, $donanim) ? 'checked' : '' ?>> <span class="form-check-label"><?= e($ad) ?></span></label>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card">
                <div class="card-header"><h5>Tarihler</h5></div>
                <div class="card-body">
                    <div class="mb-2"><label class="form-label">Trafik Sigortası Tarihi</label><input type="date" name="sigorta_tarihi" class="form-control" value="<?= e($v('sigorta_tarihi')) ?>"></div>
                    <div class="mb-2"><label class="form-label">Kasko Tarihi</label><input type="date" name="kasko_tarihi" class="form-control" value="<?= e($v('kasko_tarihi')) ?>"></div>
                    <div><label class="form-label">Muayene Tarihi</label><input type="date" name="muayene_tarihi" class="form-control" value="<?= e($v('muayene_tarihi')) ?>"></div>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h5>Araç Fotoğrafları</h5></div>
                <div class="card-body">
                    <?php if ($fotograflar): ?>
                        <div class="photo-grid mb-3">
                            <?php foreach ($fotograflar as $f): ?>
                                <div class="position-relative">
                                    <a href="<?= e($f['dosya_yolu']) ?>" target="_blank"><img src="<?= e($f['dosya_yolu']) ?>" alt=""></a>
                                    <button type="submit" form="dosya-sil-<?= $f['id'] ?>" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1 py-0 px-1" title="Sil"><i class="mdi mdi-close"></i></button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="fotograf[]" class="form-control" accept="image/*" multiple>
                    <div class="form-text">Birden fazla fotoğraf seçebilirsiniz.</div>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h5>Araç Belgeleri</h5></div>
                <div class="card-body"><?php $readonly = false; require BASE_PATH . '/views/partials/dosyalar.php'; ?></div>
            </div>
            <button type="submit" class="btn btn-primary w-100 btn-lg mb-4"><i class="mdi mdi-content-save me-1"></i> <?= $yeni ? 'Kaydet' : 'Güncelle' ?></button>
        </div>
    </div>
</form>
<?php
$dosyalar = array_merge($dosyalar, $fotograflar);
require BASE_PATH . '/views/partials/dosya_sil_formlari.php';
?>

<?php if (!$yeni): ?>
<div class="card">
    <div class="card-header p-0 border-0">
        <ul class="nav nav-tabs px-3 pt-2 w-100" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-ekstre" type="button">Araç Ekstresi (Ek Maliyetler) <span class="badge bg-primary ms-1"><?= count($ekstre) ?></span></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-hareket" type="button">Hareket Geçmişi <span class="badge bg-secondary ms-1"><?= count($hareketler) ?></span></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-depolama" type="button">Depolama</button></li>
        </ul>
    </div>
    <div class="card-body tab-content">
        <div class="tab-pane fade show active" id="tab-ekstre">
            <form method="post" action="/arac_ekstreleri/ek_maliyet_save_modal" data-ajax class="row g-2 align-items-end mb-3 p-3 bg-light rounded" id="arac-maliyet-form">
                <?= Csrf::field() ?>
                <input type="hidden" name="arac_id" value="<?= $arac['id'] ?>">
                <div class="col-md-3"><label class="form-label required">Maliyet Tipi</label>
                    <select name="maliyet_tipi_modal" class="form-select select2" data-tutar-target="#arac-maliyet-tutar" required>
                        <option value="">Seçiniz</option>
                        <?php foreach (Database::fetchAll('SELECT id, ad, varsayilan_tutar FROM maliyet_tipleri WHERE aktif ORDER BY id') as $mt): ?>
                            <option value="<?= $mt['id'] ?>" data-tutar="<?= e($mt['varsayilan_tutar']) ?>"><?= e($mt['ad']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2"><label class="form-label required">Tutar (₺)</label><input type="number" step="0.01" min="0" name="tutar_ek" id="arac-maliyet-tutar" class="form-control" required></div>
                <div class="col-md-2"><label class="form-label">İşlem Tarihi</label><input type="date" name="islem_tarihi" class="form-control" data-default-today></div>
                <div class="col-md-2"><label class="form-label">Fatura No</label><input type="text" name="fatura_no" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">Açıklama</label><input type="text" name="aciklama_ekstre" class="form-control"></div>
                <div class="col-12 text-end"><button type="submit" class="btn btn-primary"><i class="mdi mdi-plus me-1"></i>Maliyet Ekle</button></div>
            </form>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead><tr><th>İşlem Tarihi</th><th>Maliyet Tipi</th><th>Açıklama</th><th>Fatura No</th><th>Lokasyon</th><th>Ekleyen</th><th class="text-end">Tutar</th><th></th></tr></thead>
                    <tbody>
                    <?php $toplam = 0; foreach ($ekstre as $x): $toplam += (float) $x['tutar']; ?>
                        <tr>
                            <td><?= date('d.m.Y', strtotime($x['islem_tarihi'])) ?></td>
                            <td><?= e($x['maliyet_tipi']) ?></td>
                            <td><?= e($x['aciklama'] ?: '-') ?></td>
                            <td><?= e($x['fatura_no'] ?: '-') ?></td>
                            <td><?= e($x['bayi'] ?: '-') ?></td>
                            <td><?= e($x['kullanici'] ?: '-') ?></td>
                            <td class="text-end fw-semibold"><?= number_format((float) $x['tutar'], 2, ',', '.') ?> ₺</td>
                            <td class="text-end text-nowrap">
                                <button type="button" class="btn btn-sm btn-primary btn-icon" data-maliyet-duzenle="<?= $x['id'] ?>" title="Düzenle"><i class="mdi mdi-pencil"></i></button>
                                <button type="button" class="btn btn-sm btn-danger btn-icon" data-maliyet-sil="<?= $x['id'] ?>" title="Sil"><i class="mdi mdi-delete"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$ekstre): ?><tr><td colspan="8" class="text-center text-muted py-4">Bu araca yazılmış ek maliyet yok.</td></tr><?php endif; ?>
                    </tbody>
                    <?php if ($ekstre): ?>
                        <tfoot><tr><th colspan="6" class="text-end">Toplam</th><th class="text-end"><?= number_format($toplam, 2, ',', '.') ?> ₺</th><th></th></tr></tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-hareket">
            <table class="table table-hover align-middle">
                <thead><tr><th>#</th><th>Hareket Tipi</th><th>Hareket Nedeni</th><th>Lokasyon</th><th>Tarih</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($hareketler as $h): ?>
                    <tr>
                        <td><?= $h['id'] ?></td>
                        <td><span class="badge <?= (int) $h['hareket_tipi'] === 1 ? 'badge-soft-success' : 'badge-soft-danger' ?>"><?= Tanim::HAREKET_TIPI[$h['hareket_tipi']] ?></span></td>
                        <td><?= e($h['neden'] ?: '-') ?></td>
                        <td><?= e($h['bayi'] ?: '-') ?></td>
                        <td><?= date('d.m.Y H:i', strtotime($h['hareket_tarihi'])) ?></td>
                        <td class="text-end">
                            <a href="/arac_hareketleri/hareket_view/<?= $h['id'] ?>" class="btn btn-sm btn-success btn-icon" title="Görüntüle"><i class="mdi mdi-eye"></i></a>
                            <a href="/arac_hareketleri/hareket_duzenle/<?= $h['id'] ?>" class="btn btn-sm btn-primary btn-icon" title="Düzenle"><i class="mdi mdi-pencil-box"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$hareketler): ?><tr><td colspan="6" class="text-center text-muted py-4">Hareket kaydı yok.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="tab-pane fade" id="tab-depolama">
            <table class="table align-middle">
                <thead><tr><th>Lokasyon</th><th>Giriş</th><th>Çıkış</th><th class="text-end">Gün</th><th class="text-end">Günlük Fiyat</th><th class="text-end">Tutar</th></tr></thead>
                <tbody>
                <?php $depToplam = 0; foreach ($depolama as $d): $depToplam += (float) $d['tutar']; ?>
                    <tr>
                        <td><?= e($d['bayi'] ?: '-') ?></td>
                        <td><?= date('d.m.Y', strtotime($d['giris_tarihi'])) ?></td>
                        <td><?= $d['cikis_tarihi'] ? date('d.m.Y', strtotime($d['cikis_tarihi'])) : '<span class="badge badge-soft-success">Stokta</span>' ?></td>
                        <td class="text-end"><?= (int) $d['gun'] ?></td>
                        <td class="text-end"><?= number_format((float) $d['gunluk_fiyat'], 2, ',', '.') ?> ₺</td>
                        <td class="text-end fw-semibold"><?= number_format((float) $d['tutar'], 2, ',', '.') ?> ₺</td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$depolama): ?><tr><td colspan="6" class="text-center text-muted py-4">Depolama kaydı yok.</td></tr><?php endif; ?>
                </tbody>
                <?php if ($depolama): ?><tfoot><tr><th colspan="5" class="text-end">Toplam Depolama</th><th class="text-end"><?= number_format($depToplam, 2, ',', '.') ?> ₺</th></tr></tfoot><?php endif; ?>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="maliyetDuzenleModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="post" data-ajax>
            <?= Csrf::field() ?>
            <div class="modal-header"><h5 class="modal-title">Maliyet Düzenle</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body row g-3">
                <div class="col-12"><label class="form-label required">Maliyet Tipi</label><select name="maliyet_tipi_id" class="form-select" required><?= Tanim::options(Tanim::liste('maliyet_tipleri')) ?></select></div>
                <div class="col-6"><label class="form-label required">Tutar (₺)</label><input type="number" step="0.01" min="0" name="tutar" class="form-control" required></div>
                <div class="col-6"><label class="form-label">İşlem Tarihi</label><input type="date" name="islem_tarihi" class="form-control"></div>
                <div class="col-6"><label class="form-label">Fatura No</label><input type="text" name="fatura_no" class="form-control"></div>
                <div class="col-6"><label class="form-label">Fatura Tarihi</label><input type="date" name="fatura_tarihi" class="form-control"></div>
                <div class="col-6"><label class="form-label">İrsaliye</label><input type="text" name="irsaliye" class="form-control"></div>
                <div class="col-12"><label class="form-label">Açıklama</label><textarea name="aciklama" class="form-control" rows="2"></textarea></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary">Güncelle</button></div>
        </form>
    </div>
</div>

<?php ob_start(); ?>
<script>
$(function () {
    $(document).on('ajax-form-saved', () => setTimeout(() => location.reload(), 900));

    $('[data-maliyet-duzenle]').on('click', function () {
        const id = this.dataset.maliyetDuzenle;
        $.getJSON('/arac_ekstreleri/maliyet_getir/' + id, (r) => {
            const $m = $('#maliyetDuzenleModal');
            const $f = $m.find('form').attr('action', '/arac_ekstreleri/maliyet_guncelle/' + id);
            ['maliyet_tipi_id', 'tutar', 'islem_tarihi', 'fatura_no', 'fatura_tarihi', 'irsaliye', 'aciklama'].forEach((k) => $f.find(`[name=${k}]`).val(r.data[k] ?? ''));
            bootstrap.Modal.getOrCreateInstance($m[0]).show();
        }).fail((x) => App.error(x.responseJSON?.message));
    });

    $('[data-maliyet-sil]').on('click', function () {
        const id = this.dataset.maliyetSil;
        Swal.fire({ icon: 'warning', title: 'Maliyet kaydı silinsin mi?', showCancelButton: true, confirmButtonText: 'Sil', cancelButtonText: 'Vazgeç', confirmButtonColor: '#dc2626' })
            .then((r) => {
                if (!r.isConfirmed) return;
                $.post('/arac_ekstreleri/maliyet_sil/' + id, { _csrf: $('meta[name="csrf-token"]').attr('content') })
                    .done((res) => { App.toast(res.message); setTimeout(() => location.reload(), 700); })
                    .fail((x) => App.error(x.responseJSON?.message));
            });
    });
});
</script>
<?php $scripts = ob_get_clean(); ?>
<?php endif; ?>
