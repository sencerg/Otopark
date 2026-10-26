<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Tanim;
use App\Core\Upload;
use App\Core\View;
use App\Services\AracService;
use App\Services\DetayliAracStore;
use RuntimeException;

/**
 * Detaylı Araç Ekle — Teslim Alma (ana tasarım).
 * Araç adımında stok kaydı oluşur; ekspertiz üzerine yazılır.
 */
final class DetayliAracController extends Controller
{
    private function gate(): void
    {
        Auth::requireStaff();
        Auth::requireIzin('menu.detayli_arac');
    }

    private function sayfa(string $view, array $data = []): void
    {
        View::render('detayli_arac/' . $view, $data + [
            'pageTitle' => $data['pageTitle'] ?? 'Detaylı Araç Ekle',
            'breadcrumb' => $data['breadcrumb'] ?? [
                'Araç Yönetimi' => null,
                'Detaylı Araç Ekle' => null,
            ],
        ], 'main');
    }

    public function index(): void
    {
        $this->gate();
        $taslaklar = array_values(array_filter(
            DetayliAracStore::liste(),
            static fn (array $r) => ($r['durum'] ?? '') !== 'tamamlandi'
        ));
        $this->sayfa('index', [
            'pageTitle' => 'Detaylı Araç Ekle',
            'adimlar' => DetayliAracStore::adimlar(),
            'adim' => 'kontrol',
            'taslaklar' => array_slice($taslaklar, 0, 20),
            'headerActions' => '<a href="/arac_yonetimi/ekle" class="btn btn-outline-primary"><i class="mdi mdi-car-plus me-1"></i>Standart Araç Ekle</a>',
        ]);
    }

    public function baslat(): void
    {
        $this->gate();
        $saseHam = Request::str('sase');
        $plaka = Request::str('plaka');
        try {
            if (!Request::bool('kosul_sase') || !Request::bool('kosul_bilgi') || !Request::bool('kosul_sorumluluk')) {
                throw new RuntimeException('Devam etmek için tüm koşulları onaylayın.');
            }
            $sase = AracService::dogrulaSase($saseHam);
            $var = Database::fetch(
                'SELECT id, plaka, stokta FROM araclar WHERE sase = :s AND NOT COALESCE(arsiv, false) LIMIT 1',
                ['s' => $sase]
            );
            if ($var) {
                throw new RuntimeException(
                    'Bu şasi sistemde kayıtlı'
                    . (!empty($var['stokta']) ? ' ve stokta.' : '.')
                    . ' Detay: /arac_yonetimi/duzenle/' . (int) $var['id']
                );
            }
            $row = DetayliAracStore::olustur([
                'kullanici_id' => Auth::id(),
                'veri' => [
                    'sase' => $sase,
                    'plaka' => $plaka ? mb_strtoupper(trim($plaka)) : '',
                ],
                'kontrol' => [
                    'kosul_sase' => true,
                    'kosul_bilgi' => true,
                    'kosul_sorumluluk' => true,
                    'at' => date('c'),
                ],
                'adim' => 'taraflar',
            ]);
            if (!DetayliAracStore::bul((string) $row['id'])) {
                throw new RuntimeException('Taslak kaydedilemedi. Veritabanı migrasyonu (026) çalıştırılmış olmalı.');
            }
            View::redirect('/detayli-arac/kayit/' . $row['id'] . '?adim=taraflar');
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            $_SESSION['_old'] = ['sase' => $saseHam, 'plaka' => $plaka];
            View::redirect('/detayli-arac');
        }
    }

    public function yeni(): void
    {
        $this->gate();
        View::redirect('/detayli-arac');
    }

    public function kayit(string $id): void
    {
        $this->gate();
        $row = DetayliAracStore::bul($id);
        if (!$row) {
            flash('error', 'Taslak bulunamadı veya bu sunucuda yok. Yeni kayıt için kontrol adımından başlayın.');
            View::redirect('/detayli-arac');
        }
        $adim = Request::str('adim') ?: ($row['adim'] ?? 'taraflar');
        $gecerli = ['taraflar', 'arac', 'ekspertiz', 'ozet'];
        if (!in_array($adim, $gecerli, true)) {
            $adim = 'taraflar';
        }
        $eksAltKodlar = array_column(DetayliAracStore::ekspertizAdimlari(), 'kod');
        $eksAlt = Request::str('alt') ?: ($row['ekspertiz_alt'] ?? 'hasar');
        if (!in_array($eksAlt, $eksAltKodlar, true)) {
            $eksAlt = 'hasar';
        }
        $markaId = (int) ($row['veri']['marka_id'] ?? 0);
        $seriId = (int) ($row['veri']['seri_id'] ?? 0);
        $eksBaslik = '';
        foreach (DetayliAracStore::ekspertizAdimlari() as $ea) {
            if ($ea['kod'] === $eksAlt) {
                $eksBaslik = $ea['ad'];
                break;
            }
        }
        $this->sayfa('adim', [
            'pageTitle' => match ($adim) {
                'taraflar' => 'Taraflar',
                'arac' => 'Araç tanımı',
                'ekspertiz' => 'Ekspertiz · ' . $eksBaslik,
                'ozet' => 'Özet',
                default => 'Detaylı Araç Ekle',
            },
            'breadcrumb' => [
                'Araç Yönetimi' => null,
                'Detaylı Araç Ekle' => '/detayli-arac',
                match ($adim) {
                    'taraflar' => 'Taraflar',
                    'arac' => 'Araç tanımı',
                    'ekspertiz' => 'Ekspertiz',
                    default => 'Özet',
                } => null,
            ],
            'kayit' => $row,
            'adim' => $adim,
            'eksAlt' => $eksAlt,
            'adimlar' => DetayliAracStore::adimlar(),
            'ekspertizAdimlari' => DetayliAracStore::ekspertizAdimlari(),
            'hasarPanelleri' => DetayliAracStore::hasarPanelleri(),
            'hasarSeviyeleri' => DetayliAracStore::hasarSeviyeleri(),
            'ekspertizKatalog' => DetayliAracStore::ekspertizKatalog(),
            'mekanikAlanlari' => DetayliAracStore::mekanikAlanlari(),
            'mekanikSeviyeleri' => DetayliAracStore::mekanikSeviyeleri(),
            'fotoEtiketleri' => DetayliAracStore::fotoEtiketleri(),
            'fotoSlotlari' => DetayliAracStore::fotoSlotlari(),
            'kondisyonSeviyeleri' => DetayliAracStore::kondisyonSeviyeleri(),
            'lastikSiniflari' => DetayliAracStore::lastikSiniflari(),
            'lastikMarkalari' => DetayliAracStore::lastikMarkalari(),
            'tekerCekisleri' => DetayliAracStore::tekerCekisleri(),
            'kayitBelgesiSecenekleri' => DetayliAracStore::kayitBelgesiSecenekleri(),
            'lastikPozisyonlari' => DetayliAracStore::lastikPozisyonlari(),
            'musteriler' => Tanim::liste('musteriler'),
            'bayiler' => Tanim::liste('bayiler'),
            'markalar' => Tanim::liste('markalar'),
            'seriler' => $markaId ? Tanim::seriler($markaId) : [],
            'modeller' => $seriId ? Tanim::modeller($seriId) : [],
            'aracTipleri' => Tanim::liste('arac_tipleri'),
            'kasaTipleri' => Tanim::liste('kasa_tipleri'),
            'renkler' => Tanim::liste('renkler'),
            'yakitTipleri' => Tanim::liste('yakit_tipleri'),
            'vitesTipleri' => Tanim::liste('vites_tipleri'),
            'modelYillari' => Tanim::liste('model_yillari'),
            'envanterler' => Tanim::liste('envanterler'),
            'donanimGruplari' => Tanim::donanimGruplari(),
            'donanimSecili' => !empty($row['arac_id'])
                ? array_map('intval', array_column(Database::fetchAll('SELECT donanim_id FROM arac_donanimlari WHERE arac_id = :a', ['a' => (int) $row['arac_id']]), 'donanim_id'))
                : array_map('intval', (array) ($row['veri']['donanim_ids'] ?? [])),
            'envanterSecili' => !empty($row['arac_id'])
                ? array_map('intval', array_column(Database::fetchAll('SELECT envanter_id FROM arac_envanterleri WHERE arac_id = :a', ['a' => (int) $row['arac_id']]), 'envanter_id'))
                : array_map('intval', (array) ($row['veri']['envanter_ids'] ?? [])),
            'aracDosyalar' => !empty($row['arac_id']) ? Upload::list('arac', (int) $row['arac_id']) : [],
            'headerActions' => '<a href="/detayli-arac" class="btn btn-outline-secondary"><i class="mdi mdi-arrow-left me-1"></i>Taslaklar</a>',
            'scripts' => match (true) {
                $adim === 'ekspertiz' && $eksAlt === 'hasar' => '<script src="/assets/js/detayli-ekspertiz.js?v=' . filemtime(BASE_PATH . '/public/assets/js/detayli-ekspertiz.js') . '"></script>',
                $adim === 'ekspertiz' && $eksAlt === 'foto' => '<script src="/assets/js/detayli-foto.js?v=' . filemtime(BASE_PATH . '/public/assets/js/detayli-foto.js') . '"></script>',
                default => '',
            },
        ]);
    }

    public function kaydet(string $id): void
    {
        $this->gate();
        $row = DetayliAracStore::bul($id);
        if (!$row) {
            flash('error', 'Kayıt bulunamadı.');
            View::redirect('/detayli-arac');
        }
        $adim = Request::str('adim') ?: 'taraflar';
        try {
            if ($adim === 'taraflar') {
                $musteriId = Request::int('musteri_id');
                if (!$musteriId || !isset(Tanim::liste('musteriler')[$musteriId])) {
                    throw new RuntimeException('Müşteri (firma) seçilmelidir.');
                }
                $alt = trim((string) (Request::str('musteri_musterisi') ?? ''));
                if ($alt === '') {
                    throw new RuntimeException('Müşterinin müşterisi zorunludur.');
                }
                $bayiId = Request::int('bayi_id');
                if (!$bayiId || !isset(Tanim::liste('bayiler')[$bayiId])) {
                    throw new RuntimeException('Otopark / lokasyon seçilmelidir.');
                }
                DetayliAracStore::kaydet($id, [
                    'adim' => 'arac',
                    'taraflar' => [
                        'musteri_id' => $musteriId,
                        'musteri_ad' => Tanim::liste('musteriler')[$musteriId] ?? '',
                        'musteri_musterisi' => $alt,
                        'bayi_id' => $bayiId,
                        'bayi_ad' => Tanim::liste('bayiler')[$bayiId] ?? '',
                        'teslim_eden' => Request::str('teslim_eden') ?? '',
                        'teslim_eden_telefon' => Request::str('teslim_eden_telefon') ?? '',
                        'teslim_alan' => Request::str('teslim_alan') ?? '',
                        'teslim_alan_telefon' => Request::str('teslim_alan_telefon') ?? '',
                        'not' => Request::str('not') ?? '',
                    ],
                ]);
                flash('success', 'Taraflar kaydedildi.');
                View::redirect('/detayli-arac/kayit/' . $id . '?adim=arac');
            }

            if ($adim === 'arac') {
                $taraf = $row['taraflar'] ?? [];
                if (empty($taraf['musteri_id']) || empty($taraf['bayi_id']) || empty($taraf['musteri_musterisi'])) {
                    throw new RuntimeException('Önce taraflar adımını tamamlayın.');
                }
                $plaka = Request::str('plaka');
                $aracTipi = Request::int('arac_tipi_id');
                $markaId = Request::int('marka_id');
                $seriId = Request::int('seri_id');
                if (!$aracTipi) {
                    throw new RuntimeException('Araç tipi seçilmelidir.');
                }
                if (!$markaId || !$seriId) {
                    throw new RuntimeException('Marka ve seri seçilmelidir.');
                }

                $lastikler = [];
                foreach (DetayliAracStore::lastikPozisyonlari() as $poz => $_ad) {
                    $lastikler[$poz] = [
                        'sinif' => Request::str('lastik_sinif_' . $poz) ?? '',
                        'marka' => Request::str('lastik_marka_' . $poz) ?? '',
                    ];
                }
                $donanimIds = Request::ids('arac_donanimlari');
                $envanterIds = Request::ids('arac_envanterleri');

                $aracTanimi = [
                    'ilk_tescil' => Request::str('ilk_tescil') ?? '',
                    'koltuk_sayisi' => Request::int('koltuk_sayisi') ?: null,
                    'varyant' => Request::str('varyant') ?? '',
                    'motor_aciklama' => Request::str('motor_aciklama') ?? '',
                    'teker_cekisi' => Request::str('teker_cekisi') ?? '',
                    'lastikler' => $lastikler,
                    'yedek_lastik' => Request::str('yedek_lastik') ?? '',
                    'lastik_formu' => Request::str('lastik_formu') ?? '',
                    'lastik_not' => Request::str('lastik_not') ?? '',
                    'garanti' => Request::str('garanti') ?? '',
                    'kayit_belgesi' => Request::str('kayit_belgesi') ?? '',
                    'anahtarlar' => Request::str('anahtarlar') ?? '',
                    'doseme' => Request::str('doseme') ?? '',
                ];

                $veri = [
                    'plaka' => $plaka ? mb_strtoupper(trim($plaka)) : ($row['veri']['plaka'] ?? ''),
                    'arac_tipi_id' => $aracTipi,
                    'marka_id' => $markaId,
                    'seri_id' => $seriId,
                    'model_id' => Request::int('model_id') ?: null,
                    'model_yili_id' => Request::int('model_yili_id') ?: null,
                    'kasa_tipi_id' => Request::int('kasa_tipi_id') ?: null,
                    'renk_id' => Request::int('renk_id') ?: null,
                    'yakit_tipi_id' => Request::int('yakit_tipi_id') ?: null,
                    'vites_tipi_id' => Request::int('vites_tipi_id') ?: null,
                    'motor_hacmi' => Request::int('motor_hacmi') ?: null,
                    'motor_gucu' => Request::int('motor_gucu') ?: null,
                    'km' => Request::int('km'),
                    'yakit_durumu' => Request::int('yakit_durumu'),
                    'arac_tanimi' => $aracTanimi,
                    'donanim_ids' => $donanimIds,
                    'envanter_ids' => $envanterIds,
                ];

                $stokAlanlari = array_filter([
                    'plaka' => $veri['plaka'] ?: null,
                    'arac_tipi_id' => $veri['arac_tipi_id'],
                    'marka_id' => $veri['marka_id'],
                    'seri_id' => $veri['seri_id'],
                    'model_id' => $veri['model_id'],
                    'model_yili_id' => $veri['model_yili_id'],
                    'kasa_tipi_id' => $veri['kasa_tipi_id'],
                    'renk_id' => $veri['renk_id'],
                    'yakit_tipi_id' => $veri['yakit_tipi_id'],
                    'vites_tipi_id' => $veri['vites_tipi_id'],
                    'motor_hacmi' => $veri['motor_hacmi'],
                    'motor_gucu' => $veri['motor_gucu'],
                    'km' => $veri['km'],
                    'yakit_durumu' => $veri['yakit_durumu'],
                    'musteri_id' => (int) $taraf['musteri_id'],
                    'bayi_id' => (int) $taraf['bayi_id'],
                    'musteri_musterisi' => $taraf['musteri_musterisi'],
                ], static fn ($v) => $v !== null && $v !== '');

                $aracId = (int) ($row['arac_id'] ?? 0);
                if ($aracId > 0) {
                    AracService::guncelle($aracId, $stokAlanlari);
                    Database::query('DELETE FROM arac_donanimlari WHERE arac_id = :a', ['a' => $aracId]);
                    foreach ($donanimIds as $did) {
                        Database::query('INSERT INTO arac_donanimlari VALUES (:a, :d) ON CONFLICT DO NOTHING', ['a' => $aracId, 'd' => $did]);
                    }
                    Database::query('DELETE FROM arac_envanterleri WHERE arac_id = :a', ['a' => $aracId]);
                    foreach ($envanterIds as $eid) {
                        Database::query('INSERT INTO arac_envanterleri VALUES (:a, :e) ON CONFLICT DO NOTHING', ['a' => $aracId, 'e' => $eid]);
                    }
                } else {
                    $sonuc = AracService::kaydetVeHareket(
                        $stokAlanlari + [
                            'sase' => $row['veri']['sase'] ?? '',
                            'kayit_kaynagi' => 'detayli',
                        ],
                        [
                            'hareket_tipi' => 1,
                            'musteri_id' => (int) $taraf['musteri_id'],
                            'bayi_id' => (int) $taraf['bayi_id'],
                            'km' => $veri['km'],
                            'yakit_durumu' => $veri['yakit_durumu'],
                            'teslim_eden' => $taraf['teslim_eden'] ?? '',
                            'teslim_eden_telefon' => $taraf['teslim_eden_telefon'] ?? '',
                            'teslim_alan' => $taraf['teslim_alan'] ?? '',
                            'teslim_alan_telefon' => $taraf['teslim_alan_telefon'] ?? '',
                            'aciklama' => trim('Detaylı araç ekleme · ' . ($taraf['musteri_musterisi'] ?? '') . ($taraf['not'] ? ' — ' . $taraf['not'] : '')),
                            'hareket_tarihi' => date('Y-m-d H:i:s'),
                        ],
                        $envanterIds,
                        $donanimIds
                    );
                    $aracId = (int) $sonuc['arac_id'];
                }

                DetayliAracStore::kaydet($id, [
                    'adim' => 'ekspertiz',
                    'ekspertiz_alt' => 'hasar',
                    'arac_id' => $aracId,
                    'veri' => $veri + ['sase' => $row['veri']['sase'] ?? ''],
                ]);
                flash('success', 'Araç tanımı kaydedildi; stoğa yazıldı. Ekspertize devam edin.');
                View::redirect('/detayli-arac/kayit/' . $id . '?adim=ekspertiz&alt=hasar');
            }

            if ($adim === 'ekspertiz') {
                $aracId = (int) ($row['arac_id'] ?? 0);
                if ($aracId <= 0) {
                    throw new RuntimeException('Önce araç bilgilerini kaydedin (stok kaydı oluşmalı).');
                }
                $altKodlar = array_column(DetayliAracStore::ekspertizAdimlari(), 'kod');
                $alt = Request::str('alt') ?: 'hasar';
                if (!in_array($alt, $altKodlar, true)) {
                    throw new RuntimeException('Geçersiz ekspertiz adımı.');
                }
                $mevcut = $row['ekspertiz'] ?? [];
                if (!is_array($mevcut)) {
                    $mevcut = [];
                }

                if ($alt === 'hasar') {
                    $ham = Request::str('hasar_json') ?? '{}';
                    $decoded = json_decode($ham, true);
                    if (!is_array($decoded)) {
                        $decoded = [];
                    }
                    $izinliKodlar = array_column(DetayliAracStore::ekspertizParcaSatirlari(), 'kod');
                    $izinliTipler = array_column(DetayliAracStore::hasarTipleri(), 'kod');
                    $izinliSev = DetayliAracStore::hasarCSeviyeleri();
                    $hasar = [];
                    foreach ($decoded as $kod => $row) {
                        if (!is_string($kod) || !in_array($kod, $izinliKodlar, true) || !is_array($row)) {
                            continue;
                        }
                        $tipler = [];
                        foreach ((array) ($row['tipler'] ?? []) as $tipKod => $sev) {
                            if (in_array((string) $tipKod, $izinliTipler, true) && in_array((string) $sev, $izinliSev, true)) {
                                $tipler[(string) $tipKod] = (string) $sev;
                            }
                        }
                        $fotolar = [];
                        foreach ((array) ($row['fotolar'] ?? []) as $tipKod => $liste) {
                            if (!in_array((string) $tipKod, $izinliTipler, true) || !is_array($liste)) {
                                continue;
                            }
                            $temiz = [];
                            foreach ($liste as $f) {
                                if (!is_array($f) || empty($f['path'])) {
                                    continue;
                                }
                                $path = (string) $f['path'];
                                if (!str_starts_with($path, '/uploads/')) {
                                    continue;
                                }
                                $temiz[] = [
                                    'path' => $path,
                                    'id' => isset($f['id']) ? (int) $f['id'] : null,
                                ];
                            }
                            if ($temiz !== []) {
                                $fotolar[(string) $tipKod] = $temiz;
                            }
                        }
                        if ($tipler === [] && trim((string) ($row['not'] ?? '')) === '' && $fotolar === []) {
                            continue;
                        }
                        $hasar[$kod] = [
                            'ad' => (string) ($row['ad'] ?? $kod),
                            'ana' => (string) ($row['ana'] ?? ''),
                            'tipler' => $tipler,
                            'fotolar' => $fotolar,
                            'not' => trim((string) ($row['not'] ?? '')),
                        ];
                    }

                    // Seçili hasar tipi altına yüklenen görseller
                    foreach ($_FILES as $field => $info) {
                        if (!is_string($field) || !str_starts_with($field, 'hf__') || empty($info['name'])) {
                            continue;
                        }
                        $body = substr($field, 4);
                        $parcaKod = null;
                        $tipKod = null;
                        foreach ($izinliTipler as $tip) {
                            $suffix = '__' . $tip;
                            if (str_ends_with($body, $suffix)) {
                                $parcaKod = substr($body, 0, -strlen($suffix));
                                $tipKod = $tip;
                                break;
                            }
                        }
                        if ($parcaKod === null || $tipKod === null || !isset($hasar[$parcaKod])) {
                            continue;
                        }
                        $tanim = 'Hasar · ' . ($hasar[$parcaKod]['ad'] ?? $parcaKod) . ' · ' . $tipKod;
                        $names = (array) $info['name'];
                        $tanimlar = array_fill(0, count($names), $tanim);
                        $oncekiIds = array_column(
                            Database::fetchAll(
                                'SELECT id FROM dosyalar WHERE ilgili_tip = :t AND ilgili_id = :i',
                                ['t' => 'arac', 'i' => $aracId]
                            ),
                            'id'
                        );
                        Upload::save($field, 'arac', $aracId, 'fotograf', $tanimlar);
                        $yeni = Database::fetchAll(
                            'SELECT id, dosya_yolu FROM dosyalar
                             WHERE ilgili_tip = :t AND ilgili_id = :i AND tanim = :tm
                             ORDER BY id',
                            ['t' => 'arac', 'i' => $aracId, 'tm' => $tanim]
                        );
                        foreach ($yeni as $d) {
                            if (in_array((int) $d['id'], array_map('intval', $oncekiIds), true)) {
                                continue;
                            }
                            $hasar[$parcaKod]['fotolar'][$tipKod][] = [
                                'path' => (string) $d['dosya_yolu'],
                                'id' => (int) $d['id'],
                            ];
                        }
                    }

                    $mevcut['hasar'] = $hasar;
                    $mevcut['hasar_genel_not'] = Request::str('hasar_genel_not') ?? '';
                    $sonrakiAlt = 'mekanik';
                } elseif ($alt === 'mekanik') {
                    $mk = [];
                    $seviyeler = DetayliAracStore::mekanikSeviyeleri();
                    foreach (DetayliAracStore::mekanikAlanlari() as $kod => $ad) {
                        $sev = Request::str('mek_' . $kod) ?? 'Bilinmiyor';
                        if (!in_array($sev, $seviyeler, true)) {
                            $sev = 'Bilinmiyor';
                        }
                        $mk[$kod] = $sev;
                    }
                    $mk['not'] = Request::str('mekanik_not') ?? '';
                    $mevcut['mekanik'] = $mk;
                    $sonrakiAlt = 'foto';
                } elseif ($alt === 'foto') {
                    $fotolar = [];
                    $tanimlar = [];
                    foreach (DetayliAracStore::fotoEtiketleri() as $i => $etiket) {
                        $not = trim((string) (Request::str('foto_not_' . $i) ?? ''));
                        $fotolar[] = [
                            'etiket' => $etiket,
                            'not' => $not,
                        ];
                        // Her slot için ayrı alan: foto_file_0, foto_file_1…
                        $field = 'foto_file_' . $i;
                        if (!empty($_FILES[$field]['name'])) {
                            Upload::save($field, 'arac', $aracId, 'fotograf', [0 => $etiket . ($not !== '' ? ' — ' . $not : '')]);
                        }
                        $tanimlar[] = $etiket;
                    }
                    // Ek serbest belgeler
                    if (!empty($_FILES['dosya']['name'])) {
                        Upload::save('dosya', 'arac', $aracId, 'belge', (array) ($_POST['dosya_tanim'] ?? []));
                    }
                    $mevcut['fotograflar'] = $fotolar;
                    $sonrakiAlt = 'lokasyon';
                } elseif ($alt === 'lokasyon') {
                    $mevcut['lokasyon'] = [
                        'park_alani' => Request::str('park_alani') ?? '',
                        'blok' => Request::str('blok') ?? '',
                        'sira' => Request::str('sira') ?? '',
                        'detay' => Request::str('lokasyon_eks_detay') ?? '',
                    ];
                    $detay = trim(implode(' / ', array_filter([
                        $mevcut['lokasyon']['park_alani'] ?? '',
                        $mevcut['lokasyon']['blok'] ?? '',
                        $mevcut['lokasyon']['sira'] ?? '',
                        $mevcut['lokasyon']['detay'] ?? '',
                    ])));
                    if ($detay !== '') {
                        AracService::guncelle($aracId, ['lokasyon_detay' => $detay]);
                    }
                    $sonrakiAlt = 'kondisyon';
                } else { // kondisyon
                    $seviyeler = DetayliAracStore::kondisyonSeviyeleri();
                    $kond = [];
                    foreach (['genel' => 'Genel', 'ic' => 'İç', 'dis' => 'Dış', 'lastik' => 'Lastik'] as $kod => $ad) {
                        $sev = Request::str('kond_' . $kod) ?? '';
                        if ($sev !== '' && !in_array($sev, $seviyeler, true)) {
                            $sev = '';
                        }
                        $kond[$kod] = $sev;
                    }
                    $kond['not'] = Request::str('kondisyon_not') ?? '';
                    $mevcut['kondisyon'] = $kond;
                    $sonrakiAlt = null;
                }

                AracService::guncelle($aracId, ['ekspertiz' => $mevcut]);
                if ($sonrakiAlt === null) {
                    DetayliAracStore::kaydet($id, [
                        'adim' => 'ozet',
                        'ekspertiz_alt' => 'kondisyon',
                        'ekspertiz' => $mevcut,
                    ]);
                    flash('success', 'Ekspertiz tamamlandı.');
                    View::redirect('/detayli-arac/kayit/' . $id . '?adim=ozet');
                }
                DetayliAracStore::kaydet($id, [
                    'adim' => 'ekspertiz',
                    'ekspertiz_alt' => $sonrakiAlt,
                    'ekspertiz' => $mevcut,
                ]);
                flash('success', 'Kaydedildi.');
                View::redirect('/detayli-arac/kayit/' . $id . '?adim=ekspertiz&alt=' . $sonrakiAlt);
            }

            if ($adim === 'ozet') {
                $aracId = (int) ($row['arac_id'] ?? 0);
                DetayliAracStore::kaydet($id, [
                    'durum' => 'tamamlandi',
                    'adim' => 'ozet',
                    'ozet_not' => Request::str('not') ?? '',
                ]);
                flash('success', 'Detaylı araç ekleme tamamlandı.');
                View::redirect($aracId > 0 ? '/arac_yonetimi/duzenle/' . $aracId : '/arac_yonetimi');
            }

            throw new RuntimeException('Geçersiz adım.');
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            $_SESSION['_old'] = $_POST;
            View::redirect('/detayli-arac/kayit/' . $id . '?adim=' . urlencode($adim));
        }
    }
}
