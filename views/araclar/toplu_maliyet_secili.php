<?php
use App\Core\Csrf;
use App\Core\Tanim;

$secili = $secili ?? [];
?>
<?php $tipSecenek = Tanim::options(Tanim::liste('maliyet_tipleri')); ?>
<form method="post" action="/arac_yonetimi/secili_maliyet" data-confirm="Yazılan maliyet satırları seçilen her araca eklensin mi?">
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
                <div class="card-header">
                    <h5>Maliyetler</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-add-row="#maliyet-satir-tpl" data-target="#maliyet-satirlari"><i class="mdi mdi-plus"></i> Satır ekle</button>
                </div>
                <div class="card-body">
                    <p class="small text-muted">Her satır soldaki tüm araçlara yazılır. Birden fazla maliyet ekleyebilirsiniz. Tutar boşsa o aracın tanımlı fiyatı kullanılır.</p>
                    <div id="maliyet-satirlari">
                        <?php
                        $maliyetSatir = static function (string $tipSecenek, bool $tarihDolu) {
                            $tarih = $tarihDolu ? date('Y-m-d') : '';
                            ?>
                            <div class="row-item border rounded p-3 mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="small fw-semibold">Maliyet satırı</span>
                                    <button type="button" class="btn btn-sm btn-light text-danger" data-remove-row><i class="mdi mdi-delete-outline"></i></button>
                                </div>
                                <div class="row g-2">
                                    <div class="col-12">
                                        <label class="form-label required">Maliyet tipi</label>
                                        <select name="maliyet_tipi_id[]" class="form-select select2"><?= $tipSecenek ?></select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Bayi fiyatı</label>
                                        <input type="number" step="0.01" min="0" name="bayi_tutar[]" data-fiyat-rol="bayi" class="form-control" placeholder="Boşsa anlaşma fiyatı">
                                    </div>
                                    <?php if (\App\Core\Auth::isAdmin()): ?>
                                    <div class="col-md-6">
                                        <label class="form-label">Müşteri fiyatı</label>
                                        <input type="number" step="0.01" min="0" name="musteri_tutar[]" data-fiyat-rol="musteri" class="form-control" placeholder="Boşsa Moex fiyatı">
                                    </div>
                                    <?php endif; ?>
                                    <div class="col-md-6">
                                        <label class="form-label">İşlem tarihi</label>
                                        <input type="date" name="islem_tarihi[]" class="form-control" value="<?= e($tarih) ?>">
                                    </div>
                                    <div class="col-md-4"><label class="form-label">Fatura no</label><input type="text" name="fatura_no[]" class="form-control"></div>
                                    <div class="col-md-4"><label class="form-label">Fatura tarihi</label><input type="date" name="fatura_tarihi[]" class="form-control"></div>
                                    <div class="col-md-4"><label class="form-label">İrsaliye</label><input type="text" name="irsaliye[]" class="form-control"></div>
                                    <div class="col-12"><label class="form-label">Açıklama</label><input type="text" name="aciklama[]" class="form-control"></div>
                                </div>
                            </div>
                            <?php
                        };
                        $maliyetSatir($tipSecenek, true);
                        ?>
                    </div>
                    <template id="maliyet-satir-tpl"><?php $maliyetSatir($tipSecenek, true); ?></template>
                    <button type="submit" class="btn btn-primary"><i class="mdi mdi-cash-plus me-1"></i>Seçilenlere ekle</button>
                    <a href="/arac_yonetimi/toplu_ek_maliyet_girisi" class="btn btn-outline-secondary">Excel ile yükle</a>
                </div>
            </div>
        </div>
    </div>
</form>
