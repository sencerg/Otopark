<?php
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Tanim;

$v = $kayit['veri'] ?? [];
$t = $kayit['taraflar'] ?? [];
$ex = $kayit['ekspertiz'] ?? [];
$old = $_SESSION['_old'] ?? [];
$val = static function (string $key, mixed $fallback = '') use ($old, $v, $t, $ex) {
    if (array_key_exists($key, $old)) {
        return $old[$key];
    }
    if (array_key_exists($key, $t)) {
        return $t[$key];
    }
    if (array_key_exists($key, $ex)) {
        return $ex[$key];
    }
    if (array_key_exists($key, $v)) {
        return $v[$key];
    }

    return $fallback;
};
require __DIR__ . '/_adimlar.php';
unset($_SESSION['_old']);
?>

<?php
$eksAlt = $eksAlt ?? 'hasar';
$hasarData = is_array($ex['hasar'] ?? null) ? $ex['hasar'] : [];
$mekData = is_array($ex['mekanik'] ?? null) ? $ex['mekanik'] : [];
$fotoData = is_array($ex['fotograflar'] ?? null) ? $ex['fotograflar'] : [];
$lokData = is_array($ex['lokasyon'] ?? null) ? $ex['lokasyon'] : [];
$kondData = is_array($ex['kondisyon'] ?? null) ? $ex['kondisyon'] : [];
?>
<div class="row">
    <div class="<?= ($adim === 'ekspertiz' && in_array($eksAlt ?? '', ['hasar', 'foto'], true)) || $adim === 'ozet' ? 'col-xl-12' : 'col-xl-8' ?>">
        <?php if ($adim === 'taraflar'): ?>
            <div class="card">
                <div class="card-header">
                    <h5>Taraflar · Teslim Alma</h5>
                    <span class="badge badge-soft-primary"><?= e($v['sase'] ?? '') ?></span>
                </div>
                <div class="card-body">
                    <p class="text-muted small">Araç müşteri firmaya aittir. Müşterinin müşterisi getiren / nihai tarafıdır.</p>
                    <form method="post" action="/detayli-arac/kayit/<?= e($kayit['id']) ?>/kaydet">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="adim" value="taraflar">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label required">Müşteri (firma)</label>
                                <select name="musteri_id" class="form-select select2" required>
                                    <?= Tanim::options($musteriler, $val('musteri_id')) ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Müşterinin müşterisi</label>
                                <input type="text" name="musteri_musterisi" class="form-control" required maxlength="150"
                                       value="<?= e((string) $val('musteri_musterisi')) ?>" placeholder="Ad soyad / firma">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Otopark / lokasyon</label>
                                <select name="bayi_id" class="form-select" required>
                                    <?= Tanim::options($bayiler, $val('bayi_id', Auth::bayiId())) ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-section">Teslim Eden</div>
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label">Ad soyad</label>
                                <input type="text" name="teslim_eden" class="form-control" value="<?= e((string) $val('teslim_eden')) ?>"></div>
                            <div class="col-md-6"><label class="form-label">Telefon</label>
                                <input type="text" name="teslim_eden_telefon" class="form-control" value="<?= e((string) $val('teslim_eden_telefon')) ?>"></div>
                        </div>

                        <div class="form-section">Teslim Alan</div>
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label">Ad soyad / personel</label>
                                <input type="text" name="teslim_alan" class="form-control" value="<?= e((string) $val('teslim_alan')) ?>"></div>
                            <div class="col-md-6"><label class="form-label">Telefon</label>
                                <input type="text" name="teslim_alan_telefon" class="form-control" value="<?= e((string) $val('teslim_alan_telefon')) ?>"></div>
                            <div class="col-12"><label class="form-label">Not</label>
                                <textarea name="not" class="form-control" rows="2"><?= e((string) $val('not')) ?></textarea></div>
                        </div>

                        <div class="detayli-form-actions mt-4">
                            <a href="/detayli-arac" class="btn btn-outline-secondary">İptal</a>
                            <button type="submit" class="btn btn-primary">Kaydet ve devam <i class="mdi mdi-arrow-right ms-1"></i></button>
                        </div>
                    </form>
                </div>
            </div>

        <?php elseif ($adim === 'arac'): ?>
            <?php
            $tanimi = is_array($v['arac_tanimi'] ?? null) ? $v['arac_tanimi'] : [];
            $lastikler = is_array($tanimi['lastikler'] ?? null) ? $tanimi['lastikler'] : [];
            $donanimSecili = $donanimSecili ?? [];
            $envanterSecili = $envanterSecili ?? [];
            $lastikSiniflari = $lastikSiniflari ?? \App\Services\DetayliAracStore::lastikSiniflari();
            $lastikMarkalari = $lastikMarkalari ?? \App\Services\DetayliAracStore::lastikMarkalari();
            $tekerCekisleri = $tekerCekisleri ?? \App\Services\DetayliAracStore::tekerCekisleri();
            $kayitBelgesiSecenekleri = $kayitBelgesiSecenekleri ?? \App\Services\DetayliAracStore::kayitBelgesiSecenekleri();
            $lastikPozisyonlari = $lastikPozisyonlari ?? \App\Services\DetayliAracStore::lastikPozisyonlari();
            $kasaTipleri = $kasaTipleri ?? [];
            $donanimGruplari = $donanimGruplari ?? [];
            $envanterler = $envanterler ?? [];
            $tVal = static function (string $key, mixed $fallback = '') use ($old, $tanimi) {
                if (array_key_exists($key, $old)) {
                    return $old[$key];
                }

                return $tanimi[$key] ?? $fallback;
            };
            ?>
            <div class="card">
                <div class="card-header">
                    <h5>Araç tanımı</h5>
                    <span class="badge badge-soft-primary"><?= e($v['sase'] ?? '') ?></span>
                </div>
                <div class="card-body">
                    <?php if (!empty($t['musteri_ad'])): ?>
                        <div class="alert alert-light border small mb-3">
                            <b><?= e($t['musteri_ad']) ?></b>
                            <?php if (!empty($t['musteri_musterisi'])): ?> · <?= e($t['musteri_musterisi']) ?><?php endif; ?>
                            <?php if (!empty($t['bayi_ad'])): ?> · <?= e($t['bayi_ad']) ?><?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <p class="text-muted small mb-3">VIS Araç tanımı alanları — tek formda; kayıt kapanmadan ekspertize geçilir.</p>
                    <form method="post" action="/detayli-arac/kayit/<?= e($kayit['id']) ?>/kaydet">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="adim" value="arac">

                        <div class="form-section mt-0">Kimlik</div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Şasi</label>
                                <input type="text" class="form-control" value="<?= e($v['sase'] ?? '') ?>" disabled>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Plaka</label>
                                <input type="text" name="plaka" class="form-control text-uppercase" value="<?= e((string) $val('plaka', $v['plaka'] ?? '')) ?>" placeholder="Yoksa boş bırakın">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label required">Araç tipi</label>
                                <select name="arac_tipi_id" class="form-select" required>
                                    <?= Tanim::options($aracTipleri, $val('arac_tipi_id')) ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label required">Marka</label>
                                <select name="marka_id" class="form-select select2" required>
                                    <?= Tanim::options($markalar, $val('marka_id')) ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label required">Seri</label>
                                <select name="seri_id" class="form-select select2" required>
                                    <?= !empty($seriler) ? Tanim::options($seriler, $val('seri_id')) : '<option value="">Önce marka seçiniz</option>' ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Model</label>
                                <select name="model_id" class="form-select select2">
                                    <?= !empty($modeller) ? Tanim::options($modeller, $val('model_id')) : '<option value="">Önce seri seçiniz</option>' ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">İlk tescil tarihi</label>
                                <input type="month" name="ilk_tescil" class="form-control" value="<?= e((string) $tVal('ilk_tescil')) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Gövde / kasa tipi</label>
                                <select name="kasa_tipi_id" class="form-select"><?= Tanim::options($kasaTipleri, $val('kasa_tipi_id', $v['kasa_tipi_id'] ?? null)) ?></select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Koltuk sayısı</label>
                                <input type="number" name="koltuk_sayisi" min="1" max="99" class="form-control" value="<?= e((string) $tVal('koltuk_sayisi')) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Model yılı</label>
                                <select name="model_yili_id" class="form-select"><?= Tanim::options($modelYillari, $val('model_yili_id')) ?></select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Renk</label>
                                <select name="renk_id" class="form-select select2"><?= Tanim::options($renkler, $val('renk_id')) ?></select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Varyant</label>
                                <input type="text" name="varyant" class="form-control" value="<?= e((string) $tVal('varyant')) ?>" placeholder="Bilinmiyor">
                            </div>
                        </div>

                        <div class="form-section">Teknik</div>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Motor</label>
                                <input type="text" name="motor_aciklama" class="form-control" value="<?= e((string) $tVal('motor_aciklama')) ?>" placeholder="Örn. 60 kWh / 1.5 TSI">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Motor hacmi (cc)</label>
                                <input type="number" name="motor_hacmi" min="0" class="form-control" value="<?= e((string) $val('motor_hacmi', $v['motor_hacmi'] ?? '')) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Motor gücü (hp)</label>
                                <input type="number" name="motor_gucu" min="0" class="form-control" value="<?= e((string) $val('motor_gucu', $v['motor_gucu'] ?? '')) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Teker çekişi</label>
                                <select name="teker_cekisi" class="form-select"><?= Tanim::options($tekerCekisleri, $tVal('teker_cekisi'), 'Seçiniz') ?></select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Yakıt tipi</label>
                                <select name="yakit_tipi_id" class="form-select"><?= Tanim::options($yakitTipleri, $val('yakit_tipi_id')) ?></select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Vites</label>
                                <select name="vites_tipi_id" class="form-select"><?= Tanim::options($vitesTipleri, $val('vites_tipi_id')) ?></select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">KM</label>
                                <input type="number" name="km" min="0" class="form-control" value="<?= e((string) $val('km')) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Yakıt / şarj (%)</label>
                                <input type="number" name="yakit_durumu" min="0" max="100" class="form-control" value="<?= e((string) $val('yakit_durumu')) ?>">
                            </div>
                        </div>

                        <div class="form-section">Lastikler</div>
                        <div class="table-responsive mb-3">
                            <table class="table table-sm align-middle">
                                <thead><tr><th>Pozisyon</th><th>Sınıf</th><th>Marka</th></tr></thead>
                                <tbody>
                                <?php foreach ($lastikPozisyonlari as $poz => $pozAd):
                                    $rowL = $lastikler[$poz] ?? [];
                                    $sinif = $old['lastik_sinif_' . $poz] ?? ($rowL['sinif'] ?? '');
                                    $markaL = $old['lastik_marka_' . $poz] ?? ($rowL['marka'] ?? '');
                                    ?>
                                    <tr>
                                        <td class="fw-semibold text-nowrap"><?= e($pozAd) ?></td>
                                        <td><select name="lastik_sinif_<?= e($poz) ?>" class="form-select form-select-sm"><?= Tanim::options($lastikSiniflari, $sinif, 'Seçiniz') ?></select></td>
                                        <td><select name="lastik_marka_<?= e($poz) ?>" class="form-select form-select-sm select2"><?= Tanim::options($lastikMarkalari, $markaL, 'Seçiniz') ?></select></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Yedek lastik (spare)</label>
                                <select name="yedek_lastik" class="form-select">
                                    <?= Tanim::options(['Var' => 'Var', 'Yok' => 'Yok', 'Tamir kiti' => 'Tamir kiti'], $tVal('yedek_lastik'), 'Seçiniz') ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Lastik formu</label>
                                <select name="lastik_formu" class="form-select">
                                    <?= Tanim::options(['Evet' => 'Evet', 'Hayır' => 'Hayır'], $tVal('lastik_formu'), 'Seçiniz') ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Lastik notu</label>
                                <input type="text" name="lastik_not" class="form-control" value="<?= e((string) $tVal('lastik_not')) ?>">
                            </div>
                        </div>

                        <div class="form-section">Belge ve durum</div>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Garanti</label>
                                <input type="text" name="garanti" class="form-control" value="<?= e((string) $tVal('garanti')) ?>" placeholder="Var / Yok / süre">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Kayıt belgesi</label>
                                <select name="kayit_belgesi" class="form-select"><?= Tanim::options($kayitBelgesiSecenekleri, $tVal('kayit_belgesi'), 'Seçiniz') ?></select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Anahtarlar</label>
                                <input type="text" name="anahtarlar" class="form-control" value="<?= e((string) $tVal('anahtarlar')) ?>" placeholder="Örn. 2 adet">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Döşeme</label>
                                <input type="text" name="doseme" class="form-control" value="<?= e((string) $tVal('doseme')) ?>" placeholder="Kumaş / deri…">
                            </div>
                        </div>

                        <?php if ($envanterler): ?>
                            <div class="form-section">Envanter</div>
                            <div class="check-grid mb-2">
                                <?php foreach ($envanterler as $eid => $ead): ?>
                                    <label class="form-check">
                                        <input class="form-check-input" type="checkbox" name="arac_envanterleri[]" value="<?= (int) $eid ?>" <?= in_array((int) $eid, $envanterSecili, true) ? 'checked' : '' ?>>
                                        <span class="form-check-label"><?= e($ead) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($donanimGruplari): ?>
                            <div class="form-section">Donanım</div>
                            <?php foreach ($donanimGruplari as $grup => $items): ?>
                                <div class="small fw-semibold text-muted mb-2"><?= e((string) $grup) ?></div>
                                <div class="check-grid mb-3">
                                    <?php foreach ($items as $did => $dad): ?>
                                        <label class="form-check">
                                            <input class="form-check-input" type="checkbox" name="arac_donanimlari[]" value="<?= (int) $did ?>" <?= in_array((int) $did, $donanimSecili, true) ? 'checked' : '' ?>>
                                            <span class="form-check-label"><?= e($dad) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <div class="detayli-form-actions mt-4">
                            <a href="/detayli-arac/kayit/<?= e($kayit['id']) ?>?adim=taraflar" class="btn btn-outline-secondary">Geri</a>
                            <button type="submit" class="btn btn-primary">Kaydet · ekspertize geç <i class="mdi mdi-arrow-right ms-1"></i></button>
                        </div>
                    </form>
                </div>
            </div>

        <?php elseif ($adim === 'ekspertiz'): ?>
            <?php require __DIR__ . '/_ekspertiz_adimlar.php'; ?>

            <?php if ($eksAlt === 'hasar'): ?>
                <?php
                $eksKatalog = $ekspertizKatalog ?? \App\Services\DetayliAracStore::ekspertizKatalog();
                $hasarTipleri = $eksKatalog['hasar_tipleri'] ?? [];
                $cSeviyeler = $eksKatalog['seviyeler'] ?? ['C0', 'C1', 'C2', 'C3', 'C4', 'C5'];
                $parcaAgaci = $eksKatalog['parcalar'] ?? [];
                $kayitliHasar = is_array($hasarData) ? $hasarData : [];
                ?>
                <form method="post" action="/detayli-arac/kayit/<?= e($kayit['id']) ?>/kaydet" data-hasar-form id="detayli-hasar-form" enctype="multipart/form-data">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="adim" value="ekspertiz">
                    <input type="hidden" name="alt" value="hasar">
                    <input type="hidden" name="hasar_json" id="hasar-json" value="<?= e(json_encode($kayitliHasar, JSON_UNESCAPED_UNICODE)) ?>">

                    <div class="row g-3 detayli-hasar-layout">
                        <div class="col-lg-4">
                            <div class="card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Parçalar</h5>
                                    <span class="badge badge-soft-secondary" id="hasar-dolu-sayac">0</span>
                                </div>
                                <div class="card-body p-0">
                                    <div class="detayli-parca-liste" id="detayli-parca-liste">
                                        <?php foreach ($parcaAgaci as $ana):
                                            $anaKod = (string) ($ana['kod'] ?? '');
                                            $anaAd = (string) ($ana['ad'] ?? '');
                                            $altlar = $ana['alt'] ?? [];
                                            $anaKayit = $kayitliHasar[$anaKod] ?? null;
                                            $anaDolu = is_array($anaKayit) && !empty($anaKayit['tipler']);
                                            ?>
                                            <div class="detayli-parca-grup" data-ana="<?= e($anaKod) ?>">
                                                <?php if ($altlar === []): ?>
                                                    <button type="button" class="detayli-parca-item<?= $anaDolu ? ' has-data' : '' ?>"
                                                            data-kod="<?= e($anaKod) ?>" data-ad="<?= e($anaAd) ?>" data-ana-ad="<?= e($anaAd) ?>">
                                                        <span class="ad"><?= e($anaAd) ?></span>
                                                        <span class="cnt"><?= $anaDolu ? count($anaKayit['tipler']) : '' ?></span>
                                                    </button>
                                                <?php else: ?>
                                                    <div class="detayli-parca-ana"><?= e($anaAd) ?></div>
                                                    <?php foreach ($altlar as $alt):
                                                        $altKod = (string) ($alt['kod'] ?? '');
                                                        $altAd = (string) ($alt['ad'] ?? '');
                                                        $altKayit = $kayitliHasar[$altKod] ?? null;
                                                        $altDolu = is_array($altKayit) && !empty($altKayit['tipler']);
                                                        ?>
                                                        <button type="button" class="detayli-parca-item alt<?= $altDolu ? ' has-data' : '' ?>"
                                                                data-kod="<?= e($altKod) ?>" data-ad="<?= e($altAd) ?>" data-ana-ad="<?= e($anaAd) ?>">
                                                            <span class="ad"><?= e($altAd) ?></span>
                                                            <span class="cnt"><?= $altDolu ? count($altKayit['tipler']) : '' ?></span>
                                                        </button>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-8">
                            <div class="card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div>
                                        <h5 class="mb-0">Hasar tespiti</h5>
                                        <div class="small text-muted" id="hasar-breadcrumb">Soldan parça seçin</div>
                                    </div>
                                    <span class="badge badge-soft-primary"><?= e($v['sase'] ?? '') ?></span>
                                </div>
                                <div class="card-body">
                                    <div id="hasar-bos" class="text-muted small py-5 text-center">
                                        Excel listesindeki parçalardan birini seçin. Hasar tipi + C0–C5 seçince altına görsel ekleyebilirsiniz (zorunlu değil).
                                    </div>
                                    <div id="hasar-panel" class="d-none">
                                        <div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
                                            <div class="fw-semibold" id="hasar-panel-baslik"></div>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-secondary" id="hasar-onceki">Önceki</button>
                                                <button type="button" class="btn btn-outline-primary" id="hasar-sonraki">Sonraki</button>
                                            </div>
                                        </div>
                                        <div id="hasar-tip-satirlari" class="detayli-hasar-tipler mb-3"></div>
                                        <label class="form-label">Parça notu</label>
                                        <input type="text" class="form-control mb-3" id="hasar-parca-not" placeholder="Opsiyonel">
                                    </div>

                                    <hr class="my-4">
                                    <label class="form-label">Genel hasar notu</label>
                                    <textarea name="hasar_genel_not" class="form-control mb-3" rows="2"><?= e((string) ($old['hasar_genel_not'] ?? ($ex['hasar_genel_not'] ?? ''))) ?></textarea>
                                    <div class="detayli-form-actions">
                                        <a href="/detayli-arac/kayit/<?= e($kayit['id']) ?>?adim=arac" class="btn btn-outline-secondary">Geri</a>
                                        <button type="submit" class="btn btn-primary">Kaydet · mekanik <i class="mdi mdi-arrow-right ms-1"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
                <script type="application/json" id="hasar-meta"><?= json_encode([
                    'tipler' => $hasarTipleri,
                    'seviyeler' => $cSeviyeler,
                ], JSON_UNESCAPED_UNICODE) ?></script>

            <?php elseif ($eksAlt === 'mekanik'): ?>
                <div class="card">
                    <div class="card-header">
                        <h5>Mekanik ve elektrik</h5>
                        <span class="badge badge-soft-primary"><?= e($v['sase'] ?? '') ?></span>
                    </div>
                    <div class="card-body">
                        <form method="post" action="/detayli-arac/kayit/<?= e($kayit['id']) ?>/kaydet">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="adim" value="ekspertiz">
                            <input type="hidden" name="alt" value="mekanik">
                            <div class="row g-3">
                                <?php foreach ($mekanikAlanlari as $kod => $ad):
                                    $sev = $old['mek_' . $kod] ?? ($mekData[$kod] ?? 'Bilinmiyor');
                                    ?>
                                    <div class="col-md-3">
                                        <label class="form-label"><?= e($ad) ?></label>
                                        <select name="mek_<?= e($kod) ?>" class="form-select">
                                            <?php foreach ($mekanikSeviyeleri as $s): ?>
                                                <option value="<?= e($s) ?>" <?= (string) $sev === $s ? 'selected' : '' ?>><?= e($s) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                <?php endforeach; ?>
                                <div class="col-12">
                                    <label class="form-label">Mekanik not</label>
                                    <textarea name="mekanik_not" class="form-control" rows="3"><?= e((string) ($old['mekanik_not'] ?? ($mekData['not'] ?? ''))) ?></textarea>
                                </div>
                            </div>
                            <div class="detayli-form-actions mt-4">
                                <a href="/detayli-arac/kayit/<?= e($kayit['id']) ?>?adim=ekspertiz&alt=hasar" class="btn btn-outline-secondary">Geri</a>
                                <button type="submit" class="btn btn-primary">Kaydet · fotoğraflar <i class="mdi mdi-arrow-right ms-1"></i></button>
                            </div>
                        </form>
                    </div>
                </div>

            <?php elseif ($eksAlt === 'foto'): ?>
                <?php
                $aracDosyalar = $aracDosyalar ?? [];
                $fotoSlotlari = $fotoSlotlari ?? \App\Services\DetayliAracStore::fotoSlotlari();
                $fotoToplam = count($fotoSlotlari);
                $fotoDolu = 0;
                foreach ($fotoSlotlari as $i => $slot) {
                    foreach ($aracDosyalar as $d) {
                        if (($d['tur'] ?? '') === 'fotograf' && str_starts_with((string) ($d['tanim'] ?? ''), $slot['ad'])) {
                            $fotoDolu++;
                            break;
                        }
                    }
                }
                ?>
                <form method="post" action="/detayli-arac/kayit/<?= e($kayit['id']) ?>/kaydet" enctype="multipart/form-data" id="detayli-foto-form">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="adim" value="ekspertiz">
                    <input type="hidden" name="alt" value="foto">

                    <div class="card mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <h5 class="mb-0">Fotoğraflar</h5>
                                <div class="small text-muted" id="foto-rehber-baslik">Açı ipucunu takip ederek çekin</div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge badge-soft-primary"><?= e($v['sase'] ?? '') ?></span>
                                <span class="badge badge-soft-secondary" id="foto-sayac"><?= (int) $fotoDolu ?> / <?= (int) $fotoToplam ?></span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="detayli-foto-sahne mb-3" id="detayli-foto-sahne">
                                <?php foreach ($fotoSlotlari as $i => $slot):
                                    $etiket = $slot['ad'];
                                    $not = $old['foto_not_' . $i] ?? ($fotoData[$i]['not'] ?? '');
                                    $mevcutDosya = null;
                                    foreach ($aracDosyalar as $d) {
                                        if (($d['tur'] ?? '') === 'fotograf' && str_starts_with((string) ($d['tanim'] ?? ''), $etiket)) {
                                            $mevcutDosya = $d;
                                            break;
                                        }
                                    }
                                    ?>
                                    <div class="detayli-foto-panel<?= $i === 0 ? ' is-active' : '' ?>" data-foto-ix="<?= (int) $i ?>" data-ad="<?= e($etiket) ?>" data-ipucu="<?= e($slot['ipucu']) ?>">
                                        <div class="detayli-foto-canvas mb-3">
                                            <?php if ($mevcutDosya): ?>
                                                <a href="<?= e($mevcutDosya['dosya_yolu']) ?>" target="_blank" class="detayli-foto-yuklenen">
                                                    <img src="<?= e($mevcutDosya['dosya_yolu']) ?>" alt="">
                                                </a>
                                            <?php else: ?>
                                                <img class="detayli-foto-rehber" src="<?= e($slot['rehber']) ?>" alt="<?= e($etiket) ?> açı ipucu">
                                            <?php endif; ?>
                                        </div>
                                        <p class="small text-muted mb-3" data-foto-ipucu><?= e($slot['ipucu']) ?></p>
                                        <div class="row g-2 align-items-end">
                                            <div class="col-md-7">
                                                <label class="form-label mb-1">Görsel seç</label>
                                                <input type="file" name="foto_file_<?= (int) $i ?>" class="form-control" accept="image/*" data-foto-file>
                                            </div>
                                            <div class="col-md-5">
                                                <label class="form-label mb-1">Not</label>
                                                <input type="text" name="foto_not_<?= (int) $i ?>" class="form-control" value="<?= e((string) $not) ?>" placeholder="Opsiyonel">
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="foto-onceki"><i class="mdi mdi-chevron-left"></i> Önceki</button>
                                <span class="small text-muted" id="foto-ix-etiket">1 / <?= (int) $fotoToplam ?></span>
                                <button type="button" class="btn btn-outline-primary btn-sm" id="foto-sonraki">Sonraki <i class="mdi mdi-chevron-right"></i></button>
                            </div>

                            <div class="detayli-foto-serit" id="detayli-foto-serit">
                                <?php foreach ($fotoSlotlari as $i => $slot):
                                    $etiket = $slot['ad'];
                                    $mevcutDosya = null;
                                    foreach ($aracDosyalar as $d) {
                                        if (($d['tur'] ?? '') === 'fotograf' && str_starts_with((string) ($d['tanim'] ?? ''), $etiket)) {
                                            $mevcutDosya = $d;
                                            break;
                                        }
                                    }
                                    ?>
                                    <button type="button" class="detayli-foto-thumb<?= $i === 0 ? ' active' : '' ?><?= $mevcutDosya ? ' has-photo' : '' ?>" data-foto-jump="<?= (int) $i ?>" title="<?= e($etiket) ?>">
                                        <?php if ($mevcutDosya): ?>
                                            <img src="<?= e($mevcutDosya['dosya_yolu']) ?>" alt="">
                                        <?php else: ?>
                                            <img src="<?= e($slot['rehber']) ?>" alt="">
                                        <?php endif; ?>
                                        <span><?= e($etiket) ?></span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div class="form-section mt-0">Ek belgeler</div>
                            <?php
                            $dosyalar = array_values(array_filter($aracDosyalar, static fn ($d) => ($d['tur'] ?? '') !== 'fotograf'));
                            $readonly = false;
                            require BASE_PATH . '/views/partials/dosyalar.php';
                            ?>
                            <div class="detayli-form-actions mt-4">
                                <a href="/detayli-arac/kayit/<?= e($kayit['id']) ?>?adim=ekspertiz&alt=mekanik" class="btn btn-outline-secondary">Geri</a>
                                <button type="submit" class="btn btn-primary">Kaydet · lokasyon <i class="mdi mdi-arrow-right ms-1"></i></button>
                            </div>
                        </div>
                    </div>
                </form>
                <?php
                $dosyalar = $aracDosyalar;
                require BASE_PATH . '/views/partials/dosya_sil_formlari.php';
                ?>

            <?php elseif ($eksAlt === 'lokasyon'): ?>
                <div class="card">
                    <div class="card-header">
                        <h5>Park / lokasyon detayı</h5>
                        <span class="badge badge-soft-primary"><?= e($v['sase'] ?? '') ?></span>
                    </div>
                    <div class="card-body">
                        <form method="post" action="/detayli-arac/kayit/<?= e($kayit['id']) ?>/kaydet">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="adim" value="ekspertiz">
                            <input type="hidden" name="alt" value="lokasyon">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Park alanı</label>
                                    <input type="text" name="park_alani" class="form-control" value="<?= e((string) ($old['park_alani'] ?? ($lokData['park_alani'] ?? ''))) ?>" placeholder="Örn. Açık alan A">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Blok</label>
                                    <input type="text" name="blok" class="form-control" value="<?= e((string) ($old['blok'] ?? ($lokData['blok'] ?? ''))) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Sıra / yer</label>
                                    <input type="text" name="sira" class="form-control" value="<?= e((string) ($old['sira'] ?? ($lokData['sira'] ?? ''))) ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Ek detay</label>
                                    <input type="text" name="lokasyon_eks_detay" class="form-control" value="<?= e((string) ($old['lokasyon_eks_detay'] ?? ($lokData['detay'] ?? ''))) ?>">
                                </div>
                            </div>
                            <div class="detayli-form-actions mt-4">
                                <a href="/detayli-arac/kayit/<?= e($kayit['id']) ?>?adim=ekspertiz&alt=foto" class="btn btn-outline-secondary">Geri</a>
                                <button type="submit" class="btn btn-primary">Kaydet · kondisyon <i class="mdi mdi-arrow-right ms-1"></i></button>
                            </div>
                        </form>
                    </div>
                </div>

            <?php else: /* kondisyon */ ?>
                <div class="card">
                    <div class="card-header">
                        <h5>Kondisyon</h5>
                        <span class="badge badge-soft-primary"><?= e($v['sase'] ?? '') ?></span>
                    </div>
                    <div class="card-body">
                        <form method="post" action="/detayli-arac/kayit/<?= e($kayit['id']) ?>/kaydet">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="adim" value="ekspertiz">
                            <input type="hidden" name="alt" value="kondisyon">
                            <div class="row g-3">
                                <?php foreach (['genel' => 'Genel', 'ic' => 'İç', 'dis' => 'Dış', 'lastik' => 'Lastik'] as $kod => $ad):
                                    $sev = $old['kond_' . $kod] ?? ($kondData[$kod] ?? '');
                                    $opts = array_combine($kondisyonSeviyeleri, $kondisyonSeviyeleri);
                                    ?>
                                    <div class="col-md-3">
                                        <label class="form-label"><?= e($ad) ?></label>
                                        <select name="kond_<?= e($kod) ?>" class="form-select">
                                            <?= Tanim::options($opts, $sev, 'Seçiniz') ?>
                                        </select>
                                    </div>
                                <?php endforeach; ?>
                                <div class="col-12">
                                    <label class="form-label">Kondisyon notu</label>
                                    <textarea name="kondisyon_not" class="form-control" rows="3"><?= e((string) ($old['kondisyon_not'] ?? ($kondData['not'] ?? ''))) ?></textarea>
                                </div>
                            </div>
                            <div class="detayli-form-actions mt-4">
                                <a href="/detayli-arac/kayit/<?= e($kayit['id']) ?>?adim=ekspertiz&alt=lokasyon" class="btn btn-outline-secondary">Geri</a>
                                <button type="submit" class="btn btn-primary">Kaydet · özete geç <i class="mdi mdi-arrow-right ms-1"></i></button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

        <?php else: /* ozet */ ?>
            <?php
            $hasarOzet = [];
            $hasarTipAdlari = array_column(\App\Services\DetayliAracStore::hasarTipleri(), 'ad', 'kod');
            foreach ($hasarData as $kod => $h) {
                if (!is_array($h)) {
                    continue;
                }
                // Yeni format: tipler[C seviyesi]
                if (!empty($h['tipler']) && is_array($h['tipler'])) {
                    $parcalar = [];
                    foreach ($h['tipler'] as $tipKod => $sev) {
                        $parcalar[] = ($hasarTipAdlari[$tipKod] ?? $tipKod) . ' ' . $sev;
                    }
                    $hasarOzet[] = [
                        'ad' => trim(($h['ana'] ?? '') !== ($h['ad'] ?? '') ? (($h['ana'] ?? '') . ' · ' . ($h['ad'] ?? $kod)) : ($h['ad'] ?? $kod)),
                        'sev' => implode(', ', $parcalar),
                    ];
                    continue;
                }
                // Eski format
                if (($h['seviye'] ?? 'Yok') !== 'Yok' && ($h['seviye'] ?? '') !== '') {
                    $hasarOzet[] = ['ad' => $h['ad'] ?? $kod, 'sev' => $h['seviye'] ?? ''];
                }
            }
            $mekOzet = [];
            foreach ($mekanikAlanlari as $kod => $ad) {
                if (!empty($mekData[$kod]) && $mekData[$kod] !== 'İyi' && $mekData[$kod] !== 'Bilinmiyor') {
                    $mekOzet[] = $ad . ': ' . $mekData[$kod];
                }
            }
            $fotoSayisi = 0;
            foreach ($fotoData as $f) {
                if (!empty($f['path']) || !empty($f['url'])) {
                    $fotoSayisi++;
                }
            }
            $markaSeri = trim(($markalar[$v['marka_id'] ?? 0] ?? '') . ' ' . ($seriler[$v['seri_id'] ?? 0] ?? ''));
            ?>
            <div class="row g-3 mb-3">
                <div class="col-lg-4">
                    <div class="card h-100">
                        <div class="card-header"><h5>Hasar şeması</h5></div>
                        <div class="card-body">
                            <?php
                            $semaReadonly = true;
                            require __DIR__ . '/_arac_sema.php';
                            ?>
                            <?php if ($hasarOzet): ?>
                                <ul class="list-unstyled small mb-0 mt-3 detayli-ozet-hasar-liste">
                                    <?php foreach ($hasarOzet as $ho): ?>
                                        <li class="d-flex justify-content-between gap-2 py-1 border-bottom border-light">
                                            <span><?= e($ho['ad']) ?></span>
                                            <span class="badge badge-soft-warning"><?= e($ho['sev']) ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="text-muted small mb-0 mt-3 text-center">İşaretli hasar yok.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5>Özet · Teslim Alma</h5>
                            <span class="badge badge-soft-primary"><?= e($v['sase'] ?? '') ?></span>
                        </div>
                        <div class="card-body">
                            <div class="form-section mt-0">Taraflar</div>
                            <div class="detail-grid mb-3">
                                <div><div class="label">Müşteri</div><div class="value"><?= e($t['musteri_ad'] ?? '—') ?></div></div>
                                <div><div class="label">Müşterinin müşterisi</div><div class="value"><?= e($t['musteri_musterisi'] ?? '—') ?></div></div>
                                <div><div class="label">Otopark</div><div class="value"><?= e($t['bayi_ad'] ?? '—') ?></div></div>
                                <div><div class="label">Teslim eden</div><div class="value"><?= e($t['teslim_eden'] ?: '—') ?></div></div>
                            </div>

                            <div class="form-section">Araç</div>
                            <div class="detail-grid mb-3">
                                <div><div class="label">Şasi</div><div class="value"><?= e($v['sase'] ?? '—') ?></div></div>
                                <div><div class="label">Plaka</div><div class="value"><?= e($v['plaka'] ?: '—') ?></div></div>
                                <div><div class="label">Marka / Seri</div><div class="value"><?= e($markaSeri !== '' ? $markaSeri : '—') ?></div></div>
                                <div><div class="label">KM</div><div class="value"><?= e(isset($v['km']) && $v['km'] !== '' && $v['km'] !== null ? (string) $v['km'] : '—') ?></div></div>
                            </div>

                            <div class="form-section">Ekspertiz</div>
                            <div class="detail-grid mb-3">
                                <div><div class="label">Kondisyon</div><div class="value">
                                    <?= e(trim(($kondData['genel'] ?? '') . ' / ' . ($kondData['ic'] ?? '') . ' / ' . ($kondData['dis'] ?? '') . ' / ' . ($kondData['lastik'] ?? ''), ' /') ?: '—') ?>
                                </div></div>
                                <div><div class="label">Hasarlı panel</div><div class="value"><?= $hasarOzet ? count($hasarOzet) . ' adet' : 'Yok' ?></div></div>
                                <div><div class="label">Mekanik uyarı</div><div class="value"><?= e($mekOzet ? implode(', ', $mekOzet) : 'Yok') ?></div></div>
                                <div><div class="label">Fotoğraf</div><div class="value"><?= $fotoSayisi > 0 ? $fotoSayisi . ' adet' : 'Yüklenmedi' ?></div></div>
                                <div><div class="label">Park</div><div class="value">
                                    <?= e(trim(($lokData['park_alani'] ?? '') . ' ' . ($lokData['blok'] ?? '') . ' ' . ($lokData['sira'] ?? '')) ?: '—') ?>
                                </div></div>
                            </div>

                            <?php if (!empty($kayit['arac_id'])): ?>
                                <div class="alert alert-success small">
                                    Stok kaydı oluştu.
                                    <a href="/arac_yonetimi/duzenle/<?= (int) $kayit['arac_id'] ?>">Araç kartını aç</a>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning small mb-3">Stok kaydı henüz oluşmadı; araç adımını kontrol edin.</div>
                            <?php endif; ?>

                            <form method="post" action="/detayli-arac/kayit/<?= e($kayit['id']) ?>/kaydet">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="adim" value="ozet">
                                <label class="form-label">Özet notu</label>
                                <textarea name="not" class="form-control mb-3" rows="2" placeholder="İsteğe bağlı"><?= e((string) ($kayit['ozet_not'] ?? '')) ?></textarea>
                                <div class="detayli-form-actions">
                                    <a href="/detayli-arac/kayit/<?= e($kayit['id']) ?>?adim=ekspertiz&alt=kondisyon" class="btn btn-outline-secondary">Geri</a>
                                    <button type="submit" class="btn btn-primary"><i class="mdi mdi-check me-1"></i>Tamamla · araç kartı</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
