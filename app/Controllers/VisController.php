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
use App\Services\VisStore;
use RuntimeException;

/**
 * Detaylı Araç Ekle — Teslim Alma (ana tasarım).
 * Araç adımında stok kaydı oluşur; ekspertiz üzerine yazılır.
 */
final class VisController extends Controller
{
    private function gate(): void
    {
        Auth::requireStaff();
        Auth::requireIzin('menu.vis');
    }

    private function sayfa(string $view, array $data = []): void
    {
        View::render('vis/' . $view, $data + [
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
            VisStore::liste(),
            static fn (array $r) => ($r['durum'] ?? '') !== 'tamamlandi'
        ));
        $this->sayfa('index', [
            'pageTitle' => 'Detaylı Araç Ekle',
            'adimlar' => VisStore::adimlar(),
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
            $row = VisStore::olustur([
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
            View::redirect('/vis/kayit/' . $row['id'] . '?adim=taraflar');
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            $_SESSION['_old'] = ['sase' => $saseHam, 'plaka' => $plaka];
            View::redirect('/vis');
        }
    }

    public function yeni(): void
    {
        $this->gate();
        View::redirect('/vis');
    }

    public function kayit(string $id): void
    {
        $this->gate();
        $row = VisStore::bul($id);
        if (!$row) {
            $this->notFound();
        }
        $adim = Request::str('adim') ?: ($row['adim'] ?? 'taraflar');
        $gecerli = ['taraflar', 'arac', 'ekspertiz', 'ozet'];
        if (!in_array($adim, $gecerli, true)) {
            $adim = 'taraflar';
        }
        $eksAltKodlar = array_column(VisStore::ekspertizAdimlari(), 'kod');
        $eksAlt = Request::str('alt') ?: ($row['ekspertiz_alt'] ?? 'hasar');
        if (!in_array($eksAlt, $eksAltKodlar, true)) {
            $eksAlt = 'hasar';
        }
        $markaId = (int) ($row['veri']['marka_id'] ?? 0);
        $seriId = (int) ($row['veri']['seri_id'] ?? 0);
        $eksBaslik = '';
        foreach (VisStore::ekspertizAdimlari() as $ea) {
            if ($ea['kod'] === $eksAlt) {
                $eksBaslik = $ea['ad'];
                break;
            }
        }
        $this->sayfa('adim', [
            'pageTitle' => match ($adim) {
                'taraflar' => 'Taraflar',
                'arac' => 'Araç Bilgileri',
                'ekspertiz' => 'Ekspertiz · ' . $eksBaslik,
                'ozet' => 'Özet',
                default => 'Detaylı Araç Ekle',
            },
            'breadcrumb' => [
                'Araç Yönetimi' => null,
                'Detaylı Araç Ekle' => '/vis',
                match ($adim) {
                    'taraflar' => 'Taraflar',
                    'arac' => 'Araç',
                    'ekspertiz' => 'Ekspertiz',
                    default => 'Özet',
                } => null,
            ],
            'kayit' => $row,
            'adim' => $adim,
            'eksAlt' => $eksAlt,
            'adimlar' => VisStore::adimlar(),
            'ekspertizAdimlari' => VisStore::ekspertizAdimlari(),
            'hasarPanelleri' => VisStore::hasarPanelleri(),
            'hasarSeviyeleri' => VisStore::hasarSeviyeleri(),
            'mekanikAlanlari' => VisStore::mekanikAlanlari(),
            'mekanikSeviyeleri' => VisStore::mekanikSeviyeleri(),
            'fotoEtiketleri' => VisStore::fotoEtiketleri(),
            'kondisyonSeviyeleri' => VisStore::kondisyonSeviyeleri(),
            'musteriler' => Tanim::liste('musteriler'),
            'bayiler' => Tanim::liste('bayiler'),
            'markalar' => Tanim::liste('markalar'),
            'seriler' => $markaId ? Tanim::seriler($markaId) : [],
            'modeller' => $seriId ? Tanim::modeller($seriId) : [],
            'aracTipleri' => Tanim::liste('arac_tipleri'),
            'renkler' => Tanim::liste('renkler'),
            'yakitTipleri' => Tanim::liste('yakit_tipleri'),
            'vitesTipleri' => Tanim::liste('vites_tipleri'),
            'modelYillari' => Tanim::liste('model_yillari'),
            'aracDosyalar' => !empty($row['arac_id']) ? Upload::list('arac', (int) $row['arac_id']) : [],
            'headerActions' => '<a href="/vis" class="btn btn-outline-secondary"><i class="mdi mdi-arrow-left me-1"></i>Taslaklar</a>',
            'scripts' => ($adim === 'ekspertiz' && $eksAlt === 'hasar')
                ? '<script src="/assets/js/vis-ekspertiz.js?v=' . filemtime(BASE_PATH . '/public/assets/js/vis-ekspertiz.js') . '"></script>'
                : '',
        ]);
    }

    public function kaydet(string $id): void
    {
        $this->gate();
        $row = VisStore::bul($id);
        if (!$row) {
            flash('error', 'Kayıt bulunamadı.');
            View::redirect('/vis');
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
                VisStore::kaydet($id, [
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
                View::redirect('/vis/kayit/' . $id . '?adim=arac');
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

                $veri = [
                    'plaka' => $plaka ? mb_strtoupper(trim($plaka)) : ($row['veri']['plaka'] ?? ''),
                    'arac_tipi_id' => $aracTipi,
                    'marka_id' => $markaId,
                    'seri_id' => $seriId,
                    'model_id' => Request::int('model_id') ?: null,
                    'model_yili_id' => Request::int('model_yili_id') ?: null,
                    'renk_id' => Request::int('renk_id') ?: null,
                    'yakit_tipi_id' => Request::int('yakit_tipi_id') ?: null,
                    'vites_tipi_id' => Request::int('vites_tipi_id') ?: null,
                    'km' => Request::int('km'),
                    'yakit_durumu' => Request::int('yakit_durumu'),
                    'lokasyon_detay' => Request::str('lokasyon_detay') ?? '',
                ];

                $aracId = (int) ($row['arac_id'] ?? 0);
                if ($aracId > 0) {
                    AracService::guncelle($aracId, array_filter([
                        'plaka' => $veri['plaka'] ?: null,
                        'arac_tipi_id' => $veri['arac_tipi_id'],
                        'marka_id' => $veri['marka_id'],
                        'seri_id' => $veri['seri_id'],
                        'model_id' => $veri['model_id'],
                        'model_yili_id' => $veri['model_yili_id'],
                        'renk_id' => $veri['renk_id'],
                        'yakit_tipi_id' => $veri['yakit_tipi_id'],
                        'vites_tipi_id' => $veri['vites_tipi_id'],
                        'km' => $veri['km'],
                        'yakit_durumu' => $veri['yakit_durumu'],
                        'lokasyon_detay' => $veri['lokasyon_detay'] ?: null,
                        'musteri_id' => (int) $taraf['musteri_id'],
                        'bayi_id' => (int) $taraf['bayi_id'],
                        'musteri_musterisi' => $taraf['musteri_musterisi'],
                    ], static fn ($v) => $v !== null && $v !== ''));
                } else {
                    $sonuc = AracService::kaydetVeHareket(
                        [
                            'sase' => $row['veri']['sase'] ?? '',
                            'plaka' => $veri['plaka'] ?: null,
                            'arac_tipi_id' => $veri['arac_tipi_id'],
                            'marka_id' => $veri['marka_id'],
                            'seri_id' => $veri['seri_id'],
                            'model_id' => $veri['model_id'],
                            'model_yili_id' => $veri['model_yili_id'],
                            'renk_id' => $veri['renk_id'],
                            'yakit_tipi_id' => $veri['yakit_tipi_id'],
                            'vites_tipi_id' => $veri['vites_tipi_id'],
                            'km' => $veri['km'],
                            'yakit_durumu' => $veri['yakit_durumu'],
                            'musteri_id' => (int) $taraf['musteri_id'],
                            'bayi_id' => (int) $taraf['bayi_id'],
                            'lokasyon_detay' => $veri['lokasyon_detay'] ?: null,
                            'musteri_musterisi' => $taraf['musteri_musterisi'],
                            'kayit_kaynagi' => 'detayli',
                        ],
                        [
                            'hareket_tipi' => 1,
                            'musteri_id' => (int) $taraf['musteri_id'],
                            'bayi_id' => (int) $taraf['bayi_id'],
                            'km' => $veri['km'],
                            'yakit_durumu' => $veri['yakit_durumu'],
                            'lokasyon_detay' => $veri['lokasyon_detay'] ?: null,
                            'teslim_eden' => $taraf['teslim_eden'] ?? '',
                            'teslim_eden_telefon' => $taraf['teslim_eden_telefon'] ?? '',
                            'teslim_alan' => $taraf['teslim_alan'] ?? '',
                            'teslim_alan_telefon' => $taraf['teslim_alan_telefon'] ?? '',
                            'aciklama' => trim('Detaylı araç ekleme · ' . ($taraf['musteri_musterisi'] ?? '') . ($taraf['not'] ? ' — ' . $taraf['not'] : '')),
                            'hareket_tarihi' => date('Y-m-d H:i:s'),
                        ]
                    );
                    $aracId = (int) $sonuc['arac_id'];
                }

                VisStore::kaydet($id, [
                    'adim' => 'ekspertiz',
                    'ekspertiz_alt' => 'hasar',
                    'arac_id' => $aracId,
                    'veri' => $veri + ['sase' => $row['veri']['sase'] ?? ''],
                ]);
                flash('success', 'Araç stoğa kaydedildi. Ekspertize devam edin.');
                View::redirect('/vis/kayit/' . $id . '?adim=ekspertiz&alt=hasar');
            }

            if ($adim === 'ekspertiz') {
                $aracId = (int) ($row['arac_id'] ?? 0);
                if ($aracId <= 0) {
                    throw new RuntimeException('Önce araç bilgilerini kaydedin (stok kaydı oluşmalı).');
                }
                $altKodlar = array_column(VisStore::ekspertizAdimlari(), 'kod');
                $alt = Request::str('alt') ?: 'hasar';
                if (!in_array($alt, $altKodlar, true)) {
                    throw new RuntimeException('Geçersiz ekspertiz adımı.');
                }
                $mevcut = $row['ekspertiz'] ?? [];
                if (!is_array($mevcut)) {
                    $mevcut = [];
                }

                if ($alt === 'hasar') {
                    $hasar = [];
                    $seviyeler = VisStore::hasarSeviyeleri();
                    foreach (VisStore::hasarPanelleri() as $kod => $ad) {
                        $sev = Request::str('hasar_sev_' . $kod) ?? 'Yok';
                        if (!in_array($sev, $seviyeler, true)) {
                            $sev = 'Yok';
                        }
                        $not = trim((string) (Request::str('hasar_not_' . $kod) ?? ''));
                        $hasar[$kod] = ['seviye' => $sev, 'not' => $not, 'ad' => $ad];
                    }
                    $mevcut['hasar'] = $hasar;
                    $mevcut['hasar_genel_not'] = Request::str('hasar_genel_not') ?? '';
                    $sonrakiAlt = 'mekanik';
                } elseif ($alt === 'mekanik') {
                    $mk = [];
                    $seviyeler = VisStore::mekanikSeviyeleri();
                    foreach (VisStore::mekanikAlanlari() as $kod => $ad) {
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
                    foreach (VisStore::fotoEtiketleri() as $i => $etiket) {
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
                    $seviyeler = VisStore::kondisyonSeviyeleri();
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
                    VisStore::kaydet($id, [
                        'adim' => 'ozet',
                        'ekspertiz_alt' => 'kondisyon',
                        'ekspertiz' => $mevcut,
                    ]);
                    flash('success', 'Ekspertiz tamamlandı.');
                    View::redirect('/vis/kayit/' . $id . '?adim=ozet');
                }
                VisStore::kaydet($id, [
                    'adim' => 'ekspertiz',
                    'ekspertiz_alt' => $sonrakiAlt,
                    'ekspertiz' => $mevcut,
                ]);
                flash('success', 'Kaydedildi.');
                View::redirect('/vis/kayit/' . $id . '?adim=ekspertiz&alt=' . $sonrakiAlt);
            }

            if ($adim === 'ozet') {
                $aracId = (int) ($row['arac_id'] ?? 0);
                VisStore::kaydet($id, [
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
            View::redirect('/vis/kayit/' . $id . '?adim=' . urlencode($adim));
        }
    }
}
