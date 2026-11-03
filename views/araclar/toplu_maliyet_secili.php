<?php
use App\Core\Csrf;
use App\Core\Tanim;

$secili = $secili ?? [];
?>
<form method="post" action="/arac_yonetimi/secili_maliyet" data-confirm="Seçilen araçlara bu maliyet eklensin mi?">
    <?= Csrf::field() ?>
    <?php foreach ($secili as $arac): ?>
        <input type="hidden" name="ids[]" value="<?= (int) $arac['id'] ?>">
    <?php endforeach; ?>
    <div class="row">
        <div class="col-xl-5">
            <div class="card">
                <div class="card-header">
                    <h5>Seçilen araçlar</h5>
                    <span class="badge badge-soft-primary"><?= count($secili) ?></span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>Şasi</th><th>Plaka</th><th>Firma</th><th>Durum</th></tr></thead>
                            <tbody>
                            <?php foreach ($secili as $arac):
                                $stokta = ($arac['stokta'] ?? false) === true || in_array($arac['stokta'] ?? null, [1, '1', 't', 'true'], true);
                                ?>
                                <tr>
                                    <td class="fw-semibold"><?= e((string) ($arac['sase'] ?? '')) ?></td>
                                    <td><?= e((string) (($arac['plaka'] ?? '') !== '' ? $arac['plaka'] : '-')) ?></td>
                                    <td><?= e((string) (($arac['firma'] ?? '') !== '' ? $arac['firma'] : '-')) ?></td>
                                    <td><?= $stokta ? 'Stokta' : 'Stoktan çıktı' ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <a href="/arac_yonetimi" class="btn btn-outline-secondary">Listeye dön</a>
        </div>
        <div class="col-xl-7">
            <div class="card">
                <div class="card-header"><h5>Ortak maliyet</h5></div>
                <div class="card-body row g-3">
                    <p class="small text-muted mb-0">Aynı hizmet ve tutar soldaki her araca yazılır. Tanımlı fiyat varsa tutar boş bırakılabilir.</p>
                    <div class="col-12">
                        <label class="form-label required">Maliyet tipi</label>
                        <select name="maliyet_tipi_id" class="form-select select2" required><?= Tanim::options(Tanim::liste('maliyet_tipleri')) ?></select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tutar</label>
                        <input type="number" step="0.01" min="0" name="tutar" class="form-control" placeholder="Boşsa tanımlı fiyat">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">İşlem tarihi</label>
                        <input type="date" name="islem_tarihi" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-6"><label class="form-label">Fatura no</label><input type="text" name="fatura_no" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label">Fatura tarihi</label><input type="date" name="fatura_tarihi" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label">İrsaliye</label><input type="text" name="irsaliye" class="form-control"></div>
                    <div class="col-12"><label class="form-label">Açıklama</label><textarea name="aciklama" class="form-control" rows="2"></textarea></div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary"><i class="mdi mdi-cash-plus me-1"></i>Seçilenlere ekle</button>
                        <a href="/arac_yonetimi/toplu_ek_maliyet_girisi" class="btn btn-outline-secondary">Excel ile yükle</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
