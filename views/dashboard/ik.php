<div class="row g-3 mb-3">
    <?php foreach ([['Açık Destek Talebi', $destek['acik'], '#ef4444', 'mdi-lifebuoy'], ['İşlemdeki Talepler', $destek['islemde'], '#f59e0b', 'mdi-progress-clock'], ['Çözülen / Kapanan', $destek['kapali'], '#10b981', 'mdi-check-circle-outline']] as [$ad, $n, $renk, $icon]): ?>
        <div class="col-md-4">
            <a href="/destek_talepleri" class="kpi-card">
                <div class="kpi-head"><span class="kpi-icon" style="background:<?= $renk ?>"><i class="mdi <?= $icon ?>"></i></span><?= $ad ?></div>
                <i class="mdi mdi-chevron-right kpi-arrow"></i>
                <div class="kpi-value"><?= (int) $n ?></div>
            </a>
        </div>
    <?php endforeach; ?>
</div>
<div class="row g-3">
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header"><h5>Departman Bazında Personel</h5><a href="/arac_yonetimi/tanimlar/personeller" class="btn btn-sm btn-light">Personeller</a></div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Departman</th><th class="text-end">Personel</th></tr></thead>
                    <tbody>
                    <?php foreach ($personelSayisi as $p): ?>
                        <tr><td><?= e($p['ad']) ?></td><td class="text-end fw-semibold"><?= (int) $p['adet'] ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header"><h5>Son İşlemler</h5></div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Açıklama</th><th>İlgili Modül</th><th>Kullanıcı</th><th>Tarih</th></tr></thead>
                    <tbody>
                    <?php
                    $moduller = ['arac' => 'Araç Yönetimi', 'hareket' => 'Araç Hareketleri', 'maliyet' => 'Ek Maliyet', 'is_emri' => 'İş Emri', 'destek' => 'Destek Talebi', 'tanim' => 'Tanımlar', 'kullanici' => 'Kullanıcı'];
                    foreach ($loglar as $l): ?>
                        <tr>
                            <td><?= e($l['aciklama']) ?></td>
                            <td><span class="badge badge-soft-primary"><?= e($moduller[$l['modul']] ?? $l['modul']) ?></span></td>
                            <td><?= e($l['name'] ?? 'Sistem') ?></td>
                            <td class="text-nowrap"><?= date('d.m.Y H:i', strtotime($l['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$loglar): ?><tr><td colspan="4" class="text-center text-muted py-4">Kayıt bulunamadı.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
