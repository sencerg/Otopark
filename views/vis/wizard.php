<?php
use App\Core\Csrf;
$alanMeta = ($modul === 'tanim') ? ($alanlar[$alan] ?? null) : null;
$baslik = $pageTitle;
?>
<header class="vis-top">
    <button type="button" class="vis-icon-btn" id="vis-menu-toggle" aria-label="Menü"><i class="mdi mdi-menu"></i></button>
    <h1><?= e($baslik) ?></h1>
    <a href="/vis/kayit/<?= e($kayit['id']) ?>?modul=tanim&alan=ozet" class="vis-icon-btn" title="Özet"><i class="mdi mdi-file-document-edit-outline"></i></a>
</header>

<div class="vis-shell">
    <aside class="vis-side" id="vis-side">
        <div class="vis-side-group">Modüller</div>
        <?php foreach ($moduller as $m): ?>
            <a class="vis-side-item <?= $modul === $m['kod'] ? 'active' : '' ?>"
               href="/vis/kayit/<?= e($kayit['id']) ?>?modul=<?= e($m['kod']) ?>"><?= e($m['ad']) ?></a>
        <?php endforeach; ?>
        <?php if ($modul === 'tanim'): ?>
            <div class="vis-side-group">Araç tanımı</div>
            <?php foreach ($alanlar as $kod => $meta): ?>
                <a class="vis-side-item <?= $alan === $kod ? 'active' : '' ?>"
                   href="/vis/kayit/<?= e($kayit['id']) ?>?modul=tanim&alan=<?= e($kod) ?>"><?= e($meta[0]) ?></a>
            <?php endforeach; ?>
        <?php endif; ?>
        <div class="vis-side-foot">
            <a href="/vis"><i class="mdi mdi-magnify"></i></a>
            <a href="/"><i class="mdi mdi-home-outline"></i></a>
        </div>
    </aside>

    <section class="vis-panel">
        <?php if ($modul === 'tanim' && $alanMeta): ?>
            <form class="vis-form" data-vis-save data-modul="tanim" data-alan="<?= e($alan) ?>" data-id="<?= e($kayit['id']) ?>">
                <?= Csrf::field() ?>
                <?php
                [$etiket, $tur, $ph, $zorunlu] = [$alanMeta[0], $alanMeta[1], $alanMeta[2] ?? '', $alanMeta[3] ?? false];
                $secenekler = $alanMeta[4] ?? [];
                $deger = $kayit['veri'][$alan] ?? '';
                ?>
                <?php if ($tur === 'select'): ?>
                    <select name="deger" class="vis-input" <?= $zorunlu ? 'required' : '' ?>>
                        <option value="">Seçiniz</option>
                        <?php foreach ($secenekler as $s): ?>
                            <option value="<?= e($s) ?>" <?= (string) $deger === (string) $s ? 'selected' : '' ?>><?= e($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php elseif ($tur === 'textarea'): ?>
                    <textarea name="deger" class="vis-input" rows="6" placeholder="<?= e((string) $ph) ?>"><?= e((string) $deger) ?></textarea>
                <?php else: ?>
                    <input type="<?= e($tur) ?>" name="deger" class="vis-input" value="<?= e((string) $deger) ?>"
                           placeholder="<?= e((string) ($ph ?: $etiket)) ?>" <?= $zorunlu ? 'required' : '' ?>>
                <?php endif; ?>

                <?php if ($alan === 'plaka'): ?>
                    <label class="vis-check"><input type="checkbox" name="plakasiz" value="1"> Plakasız alındı</label>
                    <label class="vis-check"><input type="checkbox" name="plaka_ara" value="1" checked> Plaka aramasını etkinleştir</label>
                <?php endif; ?>

                <button type="submit" class="vis-btn primary block">Kaydet ve devam et</button>
            </form>
            <button type="button" class="vis-cam" title="Kamera (yakında)"><i class="mdi mdi-camera-outline"></i></button>

        <?php elseif ($modul === 'hasar'): ?>
            <form class="vis-form" data-vis-save data-modul="hasar" data-id="<?= e($kayit['id']) ?>">
                <?= Csrf::field() ?>
                <label class="vis-label">Panel</label>
                <select name="panel" class="vis-input" required>
                    <option value="">Seçiniz</option>
                    <?php foreach ($hasarPanelleri as $kod => $ad): ?>
                        <option value="<?= e($kod) ?>"><?= e($ad) ?></option>
                    <?php endforeach; ?>
                </select>
                <label class="vis-label">Durum</label>
                <select name="seviye" class="vis-input">
                    <?php foreach ($hasarSeviyeleri as $s): ?>
                        <option value="<?= e($s) ?>"><?= e($s) ?></option>
                    <?php endforeach; ?>
                </select>
                <label class="vis-label">Not</label>
                <textarea name="not" class="vis-input" rows="3"></textarea>
                <button type="submit" class="vis-btn primary block">Hasarı kaydet</button>
            </form>
            <ul class="vis-mini-list">
                <?php foreach (($kayit['hasar'] ?? []) as $kod => $h): ?>
                    <li><b><?= e($hasarPanelleri[$kod] ?? $kod) ?></b> — <?= e($h['seviye'] ?? '') ?> <?= e($h['not'] ?? '') ?></li>
                <?php endforeach; ?>
                <?php if (empty($kayit['hasar'])): ?><li class="muted">Henüz hasar kaydı yok.</li><?php endif; ?>
            </ul>
            <a class="vis-btn ghost block" href="/vis/kayit/<?= e($kayit['id']) ?>?modul=mekanik">Mekanik adıma geç</a>

        <?php elseif ($modul === 'mekanik'): ?>
            <?php $mk = $kayit['mekanik'] ?? []; ?>
            <form class="vis-form" data-vis-save data-modul="mekanik" data-id="<?= e($kayit['id']) ?>">
                <?= Csrf::field() ?>
                <?php foreach (['motor' => 'Motor', 'elektrik' => 'Elektrik', 'suspansiyon' => 'Süspansiyon', 'fren' => 'Fren'] as $k => $ad): ?>
                    <label class="vis-label"><?= e($ad) ?></label>
                    <select name="<?= e($k) ?>" class="vis-input">
                        <?php foreach (['İyi', 'Orta', 'Kötü', 'Bilinmiyor'] as $s): ?>
                            <option value="<?= e($s) ?>" <?= ($mk[$k] ?? '') === $s ? 'selected' : '' ?>><?= e($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endforeach; ?>
                <label class="vis-label">Not</label>
                <textarea name="not" class="vis-input" rows="3"><?= e($mk['not'] ?? '') ?></textarea>
                <button type="submit" class="vis-btn primary block">Kaydet</button>
            </form>
            <a class="vis-btn ghost block" href="/vis/kayit/<?= e($kayit['id']) ?>?modul=foto">Fotoğraflara geç</a>

        <?php elseif ($modul === 'foto'): ?>
            <form class="vis-form" data-vis-save data-modul="foto" data-id="<?= e($kayit['id']) ?>">
                <?= Csrf::field() ?>
                <p class="vis-hint">Dosya yükleme sonra bağlanacak. Şimdilik çekilecek kareler için not bırakın.</p>
                <?php foreach (['Ön', 'Arka', 'Sol', 'Sağ', 'İç', 'Gösterge', 'Hasar detay'] as $i => $etiket): ?>
                    <label class="vis-label"><?= e($etiket) ?></label>
                    <input type="text" name="notlar[]" class="vis-input" value="<?= e($kayit['fotograflar'][$i] ?? '') ?>" placeholder="Not / dosya adı">
                <?php endforeach; ?>
                <button type="submit" class="vis-btn primary block">Kaydet</button>
            </form>
            <a class="vis-btn ghost block" href="/vis/kayit/<?= e($kayit['id']) ?>?modul=lokasyon">Lokasyona geç</a>

        <?php elseif ($modul === 'lokasyon'): ?>
            <?php $lok = $kayit['lokasyon'] ?? []; ?>
            <form class="vis-form" data-vis-save data-modul="lokasyon" data-id="<?= e($kayit['id']) ?>">
                <?= Csrf::field() ?>
                <label class="vis-label">Otopark / lokasyon</label>
                <input type="text" name="otopark" class="vis-input" value="<?= e($lok['otopark'] ?? '') ?>" placeholder="Örn. MOEX ANKARA">
                <label class="vis-label">Detay</label>
                <input type="text" name="detay" class="vis-input" value="<?= e($lok['detay'] ?? '') ?>" placeholder="Blok / sıra">
                <button type="submit" class="vis-btn primary block">Kaydet</button>
            </form>
            <a class="vis-btn ghost block" href="/vis/kayit/<?= e($kayit['id']) ?>?modul=kondisyon">Kondisyona geç</a>

        <?php elseif ($modul === 'kondisyon'): ?>
            <?php $k = $kayit['kondisyon'] ?? []; ?>
            <form class="vis-form" data-vis-save data-modul="kondisyon" data-id="<?= e($kayit['id']) ?>">
                <?= Csrf::field() ?>
                <?php foreach (['genel' => 'Genel', 'ic' => 'İç', 'dis' => 'Dış'] as $kod => $ad): ?>
                    <label class="vis-label"><?= e($ad) ?></label>
                    <select name="<?= e($kod) ?>" class="vis-input">
                        <?php foreach (['Çok iyi', 'İyi', 'Orta', 'Kötü'] as $s): ?>
                            <option value="<?= e($s) ?>" <?= ($k[$kod] ?? '') === $s ? 'selected' : '' ?>><?= e($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endforeach; ?>
                <label class="vis-label">Not</label>
                <textarea name="not" class="vis-input" rows="3"><?= e($k['not'] ?? '') ?></textarea>
                <button type="submit" class="vis-btn primary block">Kaydet</button>
            </form>
            <a class="vis-btn ghost block" href="/vis/kayit/<?= e($kayit['id']) ?>?modul=cikis">Çıkış adımına geç</a>

        <?php else: /* cikis */ ?>
            <div class="vis-summary">
                <p><b>Plaka:</b> <?= e($kayit['veri']['plaka'] ?? '—') ?></p>
                <p><b>Şasi:</b> <?= e($kayit['veri']['sase'] ?? '—') ?></p>
                <p><b>Araç:</b> <?= e(trim(($kayit['veri']['marka'] ?? '') . ' ' . ($kayit['veri']['model'] ?? '')) ?: '—') ?></p>
                <p class="vis-hint">Bu modül bağımsızdır. «Tamamla» stok / hareket kaydı oluşturmaz.</p>
            </div>
            <form class="vis-form" data-vis-save data-modul="cikis" data-id="<?= e($kayit['id']) ?>">
                <?= Csrf::field() ?>
                <textarea name="not" class="vis-input" rows="3" placeholder="Çıkış notu"><?= e($kayit['cikis_not'] ?? '') ?></textarea>
                <button type="submit" class="vis-btn primary block">Ekspertizi tamamla</button>
            </form>
            <a class="vis-btn ghost block" href="/vis">Detaylı Araç Ekle’ye dön</a>
        <?php endif; ?>
    </section>
</div>

<div class="vis-toast" id="vis-toast" hidden></div>
