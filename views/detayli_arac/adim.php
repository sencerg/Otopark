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
            <div class="card">
                <div class="card-header">
                    <h5>Araç Bilgileri</h5>
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
                    <form method="post" action="/detayli-arac/kayit/<?= e($kayit['id']) ?>/kaydet">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="adim" value="arac">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Şasi</label>
                                <input type="text" class="form-control" value="<?= e($v['sase'] ?? '') ?>" disabled>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Plaka</label>
                                <input type="text" name="plaka" class="form-control text-uppercase" value="<?= e((string) $val('plaka', $v['plaka'] ?? '')) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label required">Araç Tipi</label>
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
                                <label class="form-label">Model Yılı</label>
                                <select name="model_yili_id" class="form-select"><?= Tanim::options($modelYillari, $val('model_yili_id')) ?></select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Renk</label>
                                <select name="renk_id" class="form-select select2"><?= Tanim::options($renkler, $val('renk_id')) ?></select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Yakıt</label>
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
                                <label class="form-label">Yakıt (%)</label>
                                <input type="number" name="yakit_durumu" min="0" max="100" class="form-control" value="<?= e((string) $val('yakit_durumu')) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Lokasyon detay</label>
                                <input type="text" name="lokasyon_detay" class="form-control" placeholder="Blok / sıra"
                                       value="<?= e((string) $val('lokasyon_detay')) ?>">
                            </div>
                        </div>
                        <div class="detayli-form-actions mt-4">
                            <a href="/detayli-arac/kayit/<?= e($kayit['id']) ?>?adim=taraflar" class="btn btn-outline-secondary">Geri</a>
                            <button type="submit" class="btn btn-primary">Kaydet ve ekspertize geç <i class="mdi mdi-arrow-right ms-1"></i></button>
                        </div>
                    </form>
                </div>
            </div>

        <?php elseif ($adim === 'ekspertiz'): ?>
            <?php require __DIR__ . '/_ekspertiz_adimlar.php'; ?>

            <?php if ($eksAlt === 'hasar'): ?>
                <form method="post" action="/detayli-arac/kayit/<?= e($kayit['id']) ?>/kaydet" data-hasar-form>
                    <?= Csrf::field() ?>
                    <input type="hidden" name="adim" value="ekspertiz">
                    <input type="hidden" name="alt" value="hasar">
                    <div class="row g-3">
                        <div class="col-lg-4">
                            <div class="card h-100">
                                <div class="card-header"><h5>Araç şeması</h5></div>
                                <div class="card-body">
                                    <?php
                                    $semaReadonly = false;
                                    require __DIR__ . '/_arac_sema.php';
                                    ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Hasar tespiti</h5>
                                    <span class="badge badge-soft-primary"><?= e($v['sase'] ?? '') ?></span>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted small mb-3">Şemadan panel seçin veya tablodan durum girin.</p>
                                    <div class="table-responsive">
                                        <table class="table table-sm hasar-tablo align-middle">
                                            <thead>
                                            <tr><th>Panel</th><th style="width:11rem">Durum</th><th>Not</th></tr>
                                            </thead>
                                            <tbody>
                                            <?php foreach ($hasarPanelleri as $kod => $ad):
                                                $rowH = $hasarData[$kod] ?? [];
                                                $sev = $old['hasar_sev_' . $kod] ?? ($rowH['seviye'] ?? 'Yok');
                                                $not = $old['hasar_not_' . $kod] ?? ($rowH['not'] ?? '');
                                                ?>
                                                <tr data-panel-row="<?= e($kod) ?>">
                                                    <td class="fw-semibold"><?= e($ad) ?></td>
                                                    <td>
                                                        <select name="hasar_sev_<?= e($kod) ?>" class="form-select form-select-sm">
                                                            <?php foreach ($hasarSeviyeleri as $s): ?>
                                                                <option value="<?= e($s) ?>" <?= (string) $sev === $s ? 'selected' : '' ?>><?= e($s) ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </td>
                                                    <td><input type="text" name="hasar_not_<?= e($kod) ?>" class="form-control form-control-sm" value="<?= e((string) $not) ?>" placeholder="Opsiyonel"></td>
                                                </tr>
                                            <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
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
                <?php $aracDosyalar = $aracDosyalar ?? []; ?>
                <div class="card">
                    <div class="card-header">
                        <h5>Fotoğraflar ve dokümanlar</h5>
                        <span class="badge badge-soft-primary"><?= e($v['sase'] ?? '') ?></span>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">Her kareye foto ekleyin; not isteğe bağlı. Ek belgeler aşağıda.</p>
                        <form method="post" action="/detayli-arac/kayit/<?= e($kayit['id']) ?>/kaydet" enctype="multipart/form-data">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="adim" value="ekspertiz">
                            <input type="hidden" name="alt" value="foto">
                            <div class="row g-3">
                                <?php foreach ($fotoEtiketleri as $i => $etiket):
                                    $not = $old['foto_not_' . $i] ?? ($fotoData[$i]['not'] ?? '');
                                    $mevcutDosya = null;
                                    foreach ($aracDosyalar as $d) {
                                        if (($d['tur'] ?? '') === 'fotograf' && str_starts_with((string) ($d['tanim'] ?? ''), $etiket)) {
                                            $mevcutDosya = $d;
                                            break;
                                        }
                                    }
                                    ?>
                                    <div class="col-md-6 col-xl-4">
                                        <div class="foto-slot">
                                            <div class="fw-semibold small mb-2"><?= e($etiket) ?></div>
                                            <?php if ($mevcutDosya): ?>
                                                <a href="<?= e($mevcutDosya['dosya_yolu']) ?>" target="_blank">
                                                    <img class="foto-onizleme mb-2" src="<?= e($mevcutDosya['dosya_yolu']) ?>" alt="">
                                                </a>
                                            <?php else: ?>
                                                <div class="foto-bos mb-2"><i class="mdi mdi-camera-outline fs-3"></i></div>
                                            <?php endif; ?>
                                            <input type="file" name="foto_file_<?= (int) $i ?>" class="form-control form-control-sm mb-2" accept="image/*">
                                            <input type="text" name="foto_not_<?= (int) $i ?>" class="form-control form-control-sm" value="<?= e((string) $not) ?>" placeholder="Not (opsiyonel)">
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="form-section mt-4">Ek belgeler</div>
                            <?php
                            $dosyalar = array_values(array_filter($aracDosyalar, static fn ($d) => ($d['tur'] ?? '') !== 'fotograf'));
                            $readonly = false;
                            require BASE_PATH . '/views/partials/dosyalar.php';
                            ?>

                            <div class="detayli-form-actions mt-4">
                                <a href="/detayli-arac/kayit/<?= e($kayit['id']) ?>?adim=ekspertiz&alt=mekanik" class="btn btn-outline-secondary">Geri</a>
                                <button type="submit" class="btn btn-primary">Kaydet · lokasyon <i class="mdi mdi-arrow-right ms-1"></i></button>
                            </div>
                        </form>
                        <?php
                        $dosyalar = $aracDosyalar;
                        require BASE_PATH . '/views/partials/dosya_sil_formlari.php';
                        ?>
                    </div>
                </div>

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
            foreach ($hasarData as $kod => $h) {
                if (($h['seviye'] ?? 'Yok') !== 'Yok') {
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
