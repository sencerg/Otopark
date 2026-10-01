<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\DataTable;
use App\Core\Excel;
use App\Core\Log;
use App\Core\Request;
use App\Core\Tanim;
use App\Core\Upload;
use App\Core\View;
use App\Services\AracService;
use App\Services\DepolamaHesap;
use RuntimeException;

final class HareketController extends Controller
{
    public function giris(): void
    {
        $this->view('hareketler/liste', ['pageTitle' => 'Stoktaki Araçlar', 'tip' => 'giris', 'breadcrumb' => ['Araç Yönetimi' => '/arac_yonetimi', 'Stoktaki Araçlar' => null]]);
    }

    public function cikis(): void
    {
        $this->view('hareketler/liste', ['pageTitle' => 'Stoktan Çıkanlar', 'tip' => 'cikis', 'breadcrumb' => ['Araç Yönetimi' => '/arac_yonetimi', 'Stoktan Çıkanlar' => null]]);
    }

    private function ortakFiltreler(DataTable $dt, string $tarihAlani): void
    {
        $map = ['marka_id' => 'a.marka_id', 'seri_id' => 'a.seri_id', 'musteri_id' => 'a.musteri_id', 'arac_tipi_id' => 'a.arac_tipi_id', 'lokasyon_turu' => 'a.lokasyon_turu', 'bayi_id' => 'a.bayi_id'];
        foreach ($map as $key => $col) {
            if ($v = Request::int($key)) {
                $dt->where("{$col} = :f_{$key}", ["f_{$key}" => $v]);
            }
        }
        if ($d = Request::date('baslangic')) {
            $dt->where("{$tarihAlani}::date >= :f_bas", ['f_bas' => $d]);
        }
        if ($d = Request::date('bitis')) {
            $dt->where("{$tarihAlani}::date <= :f_bit", ['f_bit' => $d]);
        }
        if ($d = Request::date('kayit_tarihi')) {
            $dt->where("{$tarihAlani}::date = :f_kt", ['f_kt' => $d]);
        }
    }

    public function girisListe(): void
    {
        $dt = new DataTable(
            'araclar a
             LEFT JOIN musteriler mu ON mu.id = a.musteri_id
             LEFT JOIN markalar m ON m.id = a.marka_id
             LEFT JOIN seriler s ON s.id = a.seri_id
             LEFT JOIN bayiler b ON b.id = a.bayi_id
             LEFT JOIN arac_tipleri at ON at.id = a.arac_tipi_id
             LEFT JOIN LATERAL (SELECT h.id FROM arac_hareketleri h WHERE h.arac_id = a.id AND h.hareket_tipi = 1 ORDER BY h.hareket_tarihi DESC LIMIT 1) sh ON TRUE',
            [
                'id' => 'a.id', 'hareket_id' => 'sh.id', 'sase' => 'a.sase', 'plaka' => 'a.plaka', 'firma' => 'mu.ad', 'marka' => 'm.ad',
                'seri' => 's.ad', 'lokasyon' => 'b.ad', 'lokasyon_detay' => 'a.lokasyon_detay', 'arac_tipi' => 'at.ad',
                'giris_tarihi' => "to_char(a.stoga_giris_tarihi, 'DD.MM.YYYY HH24:MI')", 'giris_tarihi_sort' => 'a.stoga_giris_tarihi',
                'depolama_suresi' => '(CURRENT_DATE - a.stoga_giris_tarihi::date + 1)', 'etiket_fiyati' => 'a.etiket_fiyati',
            ],
            ['a.stokta', 'NOT a.arsiv', Auth::bayiKosulu('a.bayi_id')],
            [],
            ['a.sase', 'a.plaka', 'b.ad', 'a.lokasyon_detay', 'mu.ad', 'sh.id'],
            'a.stoga_giris_tarihi DESC'
        );
        $this->ortakFiltreler($dt, 'a.stoga_giris_tarihi');

        if (Request::input('export')) {
            Excel::download('stoktaki-araclar', [
                'sase' => 'Şase', 'plaka' => 'Plaka', 'firma' => 'Firma', 'marka' => 'Marka', 'seri' => 'Seri', 'arac_tipi' => 'Araç Tipi',
                'lokasyon' => 'Lokasyon', 'lokasyon_detay' => 'Lokasyon Detay', 'giris_tarihi' => 'Giriş Tarihi', 'depolama_suresi' => 'Depolama Süresi (Gün)',
            ], $dt->all());
        }
        $dt->response();
    }

    public function cikisListe(): void
    {
        $dt = new DataTable(
            'arac_hareketleri h
             JOIN araclar a ON a.id = h.arac_id
             LEFT JOIN musteriler mu ON mu.id = h.musteri_id
             LEFT JOIN markalar m ON m.id = a.marka_id
             LEFT JOIN seriler s ON s.id = a.seri_id
             LEFT JOIN bayiler b ON b.id = h.bayi_id
             LEFT JOIN hareket_nedenleri hn ON hn.id = h.hareket_nedeni_id
             LEFT JOIN LATERAL (SELECT g.hareket_tarihi FROM arac_hareketleri g WHERE g.arac_id = h.arac_id AND g.hareket_tipi = 1 AND g.hareket_tarihi <= h.hareket_tarihi ORDER BY g.hareket_tarihi DESC LIMIT 1) gh ON TRUE',
            [
                'id' => 'h.id', 'arac_id' => 'a.id', 'sase' => 'a.sase', 'plaka' => 'a.plaka', 'firma' => 'mu.ad', 'marka' => 'm.ad', 'seri' => 's.ad',
                'lokasyon' => 'b.ad', 'hareket_nedeni' => 'hn.ad',
                'hareket_tarihi' => "to_char(h.hareket_tarihi, 'DD.MM.YYYY HH24:MI')", 'hareket_tarihi_sort' => 'h.hareket_tarihi',
                'depolama_suresi' => '(h.hareket_tarihi::date - gh.hareket_tarihi::date + 1)',
                'sevkiyat_tipi' => 'h.sevkiyat_tipi', 'sevkiyat_durumu' => 'h.sevkiyat_durumu', 'teslim_alan' => 'h.teslim_alan',
            ],
            ['h.hareket_tipi = 2', 'NOT h.arsiv', Auth::bayiKosulu('h.bayi_id')],
            [],
            ['a.sase', 'a.plaka', 'b.ad', 'mu.ad', 'h.id'],
            'h.hareket_tarihi DESC'
        );
        $this->ortakFiltreler($dt, 'h.hareket_tarihi');

        $map = fn (array $r) => $r + [
            'sevkiyat_tipi_ad' => Tanim::SEVKIYAT_TIPI[$r['sevkiyat_tipi'] ?? 0] ?? '-',
            'sevkiyat_durumu_ad' => Tanim::SEVKIYAT_DURUMU[$r['sevkiyat_durumu'] ?? 0] ?? '-',
        ];
        if (Request::input('export')) {
            Excel::download('stoktan-cikanlar', [
                'sase' => 'Şase', 'plaka' => 'Plaka', 'firma' => 'Firma', 'marka' => 'Marka', 'seri' => 'Seri', 'lokasyon' => 'Lokasyon',
                'hareket_nedeni' => 'Hareket Nedeni', 'hareket_tarihi' => 'Hareket Tarihi', 'depolama_suresi' => 'Depolama Süresi (Gün)',
                'sevkiyat_tipi_ad' => 'Sevkiyat Tipi', 'sevkiyat_durumu_ad' => 'Sevkiyat Durumu', 'teslim_alan' => 'Teslim Alan',
            ], array_map($map, $dt->all()));
        }
        $dt->response($map);
    }

    private function hareket(int $id): array
    {
        $h = Database::fetch(
            'SELECT h.*, a.sase, a.plaka AS arac_plaka, a.marka_id, a.seri_id, a.model_id, a.model_yili_id, a.arac_durumu, a.arac_tipi_id,
                    a.kasa_tipi_id, a.renk_id, a.yakit_tipi_id, a.vites_tipi_id, a.konsinye, a.sigorta_tarihi, a.kasko_tarihi, a.muayene_tarihi
             FROM arac_hareketleri h JOIN araclar a ON a.id = h.arac_id
             WHERE h.id = :id AND ' . Auth::bayiKosulu('h.bayi_id'),
            ['id' => $id]
        );
        if (!$h) {
            $this->notFound();
        }

        return $h;
    }

    public function goruntule(string $id): void
    {
        $this->hareketSayfasi((int) $id, false);
    }

    public function duzenle(string $id): void
    {
        $this->hareketSayfasi((int) $id, true);
    }

    private function hareketSayfasi(int $id, bool $duzenle): void
    {
        $h = $this->hareket($id);
        $this->view('hareketler/form', [
            'pageTitle' => $duzenle ? 'Araç Hareketi Düzenle' : 'Hareket Kaydı Detay',
            'breadcrumb' => ['Araç Hareketleri' => $h['hareket_tipi'] == 1 ? '/arac_hareketleri/giris' : '/arac_hareketleri/cikis', $h['sase'] => null],
            'h' => $h,
            'readonly' => !$duzenle,
            'envanter' => array_column(Database::fetchAll('SELECT envanter_id FROM arac_envanterleri WHERE arac_id = :a', ['a' => $h['arac_id']]), 'envanter_id'),
            'dosyalar' => Upload::list('hareket', $id),
        ]);
    }

    public function guncelle(string $id): void
    {
        $h = $this->hareket((int) $id);
        $this->form(function () use ($h) {
            Database::transaction(function () use ($h) {
                $alanlar = [
                    'hareket_tipi' => Request::int('hareket_tipi') ?? $h['hareket_tipi'],
                    'hareket_nedeni_id' => Request::int('hareket_nedeni'),
                    'km' => Request::int('km'), 'yakit_durumu' => Request::int('yakit_durumu'),
                    'musteri_id' => Request::int('musteri_id'), 'bayi_id' => AracService::bayiKontrol(Request::int('bayi_id')),
                    'lokasyon_turu' => Request::int('lokasyon_turu'), 'lokasyon_detay' => Request::str('lokasyon_detay'),
                    'teslim_eden' => Request::str('teslim_eden'), 'teslim_eden_telefon' => Request::str('teslim_eden_telefon'), 'teslim_eden_eposta' => Request::str('teslim_eden_eposta'),
                    'teslim_alan' => Request::str('teslim_alan'), 'teslim_alan_telefon' => Request::str('teslim_alan_telefon'), 'teslim_alan_eposta' => Request::str('teslim_alan_eposta'),
                    'teslim_alan_personel_id' => Request::int('teslim_alan_personel'),
                    'sevkiyat_tipi' => Request::int('sevkiyat_tipi'), 'sevkiyat_durumu' => Request::int('sevkiyat_durumu'),
                    'sofor_adi_soyadi' => Request::str('sofor_adi_soyadi'), 'sofor_telefon' => Request::str('sofor_telefon'), 'cekici_plakasi' => Request::str('cekici_plakasi'),
                    'sevkiyat_kodu' => Request::str('sevkiyat_kodu'), 'irsaliye_kodu' => Request::str('irsaliye_kodu'), 'aciklama' => Request::str('aciklama'),
                    'hareket_tarihi' => Request::dateTime('hareket_tarihi', 'hareket_saati') ?? $h['hareket_tarihi'],
                ];
                if (!$alanlar['musteri_id']) {
                    throw new RuntimeException('Müşteri seçilmelidir.');
                }
                AracService::guncelle((int) $h['id'], $alanlar, 'arac_hareketleri');

                $arac = [
                    'plaka' => ($p = Request::str('plaka')) ? mb_strtoupper($p) : null, 'marka_id' => Request::int('marka_id'), 'seri_id' => Request::int('seri_id'),
                    'model_id' => Request::int('model_id'), 'model_yili_id' => Request::int('yil'), 'arac_durumu' => Request::int('arac_durumu'),
                    'arac_tipi_id' => Request::int('arac_tipi'), 'kasa_tipi_id' => Request::int('kasa_tipi'), 'renk_id' => Request::int('renk'),
                    'yakit_tipi_id' => Request::int('yakit_tipi'), 'vites_tipi_id' => Request::int('vites_tipi'),
                    'sigorta_tarihi' => Request::date('sigorta_tarihi'), 'kasko_tarihi' => Request::date('kasko_tarihi'), 'muayene_tarihi' => Request::date('muayene_tarihi'),
                    'km' => $alanlar['km'], 'yakit_durumu' => $alanlar['yakit_durumu'], 'lokasyon_detay' => $alanlar['lokasyon_detay'],
                ];
                if (isset($_POST['konsinye'])) {
                    $arac['konsinye'] = Request::bool('konsinye');
                }
                $sonHareket = Database::fetch('SELECT id FROM arac_hareketleri WHERE arac_id = :a AND NOT arsiv ORDER BY hareket_tarihi DESC LIMIT 1', ['a' => $h['arac_id']]);
                if ($sonHareket && (int) $sonHareket['id'] === (int) $h['id']) {
                    $arac += ['musteri_id' => $alanlar['musteri_id'], 'bayi_id' => $alanlar['bayi_id'], 'stokta' => $alanlar['hareket_tipi'] == 1];
                    $arac[$alanlar['hareket_tipi'] == 1 ? 'stoga_giris_tarihi' : 'stoktan_cikis_tarihi'] = $alanlar['hareket_tarihi'];
                }
                AracService::guncelle((int) $h['arac_id'], array_filter($arac, fn ($v) => $v !== null));

                $envanter = Request::ids('arac_envanterleri');
                Database::query('DELETE FROM arac_envanterleri WHERE arac_id = :a', ['a' => $h['arac_id']]);
                foreach ($envanter as $e) {
                    Database::query('INSERT INTO arac_envanterleri VALUES (:a, :e)', ['a' => $h['arac_id'], 'e' => $e]);
                }

                Upload::save('dosya', 'hareket', (int) $h['id'], 'belge', (array) ($_POST['dosya_tanim'] ?? []));
                Log::islem('hareket', 'Hareket güncellendi: ' . $h['sase'], (int) $h['id']);
            });
            flash('success', 'Hareket kaydı güncellendi.');
            View::redirect('/arac_hareketleri/hareket_duzenle/' . $h['id']);
        }, '/arac_hareketleri/hareket_duzenle/' . $h['id']);
    }

    public function stoktanCikarForm(string $aracId): void
    {
        try {
            $arac = AracService::bul((int) $aracId);
        } catch (RuntimeException) {
            $this->notFound();
        }
        if (!$arac['stokta']) {
            flash('error', 'Bu araç zaten stokta değil.');
            View::redirect('/arac_hareketleri/cikis');
        }
        $konaklama = array_values(array_filter(DepolamaHesap::arac((int) $arac['id']), fn ($d) => $d['cikis_tarihi'] === null))[0] ?? null;
        $this->view('hareketler/stoktan_cikar', [
            'pageTitle' => 'Stoktan Çıkar',
            'breadcrumb' => ['Stoktaki Araçlar' => '/arac_hareketleri/giris', $arac['sase'] => null],
            'hizmetler' => Database::fetchAll('SELECT id, ad, varsayilan_tutar FROM maliyet_tipleri WHERE aktif ORDER BY ad'),
            'konaklama' => $konaklama,
            'eklenenler' => $konaklama ? Database::fetchAll(
                'SELECT mt.ad, e.tutar, e.islem_tarihi FROM arac_ekstreleri e JOIN maliyet_tipleri mt ON mt.id = e.maliyet_tipi_id
                 WHERE e.arac_id = :a AND e.islem_tarihi >= :g::date ORDER BY e.islem_tarihi, e.id',
                ['a' => $arac['id'], 'g' => $konaklama['giris_tarihi']]
            ) : [],
            'arac' => Database::fetch(
                'SELECT a.*, m.ad AS marka, s.ad AS seri, mu.ad AS musteri, b.ad AS bayi FROM araclar a
                 LEFT JOIN markalar m ON m.id = a.marka_id LEFT JOIN seriler s ON s.id = a.seri_id
                 LEFT JOIN musteriler mu ON mu.id = a.musteri_id LEFT JOIN bayiler b ON b.id = a.bayi_id WHERE a.id = :id',
                ['id' => $arac['id']]
            ),
        ]);
    }

    public function stoktanCikar(string $aracId): void
    {
        $back = '/arac_hareketleri/stoktan_cikar/' . (int) $aracId;
        $this->form(function () use ($aracId) {
            $arac = AracService::bul((int) $aracId);
            if (!$arac['stokta']) {
                throw new RuntimeException('Bu araç zaten stokta değil.');
            }
            $hizmetler = self::cikisHizmetleri();
            $cikisTarihi = Request::dateTime('hareket_tarihi', 'hareket_saati') ?? date('Y-m-d H:i:s');
            $hareketId = Database::transaction(function () use ($arac, $hizmetler, $cikisTarihi) {
                $hareketId = AracService::hareketEkle((int) $arac['id'], [
                'hareket_tipi' => 2,
                'hareket_nedeni_id' => Request::int('hareket_nedeni'),
                'musteri_id' => $arac['musteri_id'], 'bayi_id' => $arac['bayi_id'],
                'km' => Request::int('km'), 'yakit_durumu' => Request::int('yakit_durumu'),
                'teslim_alan' => Request::str('teslim_alan'), 'teslim_alan_telefon' => Request::str('teslim_alan_telefon'), 'teslim_alan_eposta' => Request::str('teslim_alan_eposta'),
                'teslim_alan_personel_id' => Request::int('teslim_eden_personel'),
                'sevkiyat_tipi' => Request::int('sevkiyat_tipi'), 'sevkiyat_durumu' => Request::int('sevkiyat_durumu') ?? 3,
                'sofor_adi_soyadi' => Request::str('sofor_adi_soyadi'), 'sofor_telefon' => Request::str('sofor_telefon'), 'cekici_plakasi' => Request::str('cekici_plakasi'),
                'sevkiyat_kodu' => Request::str('sevkiyat_kodu'), 'irsaliye_kodu' => Request::str('irsaliye_kodu'), 'aciklama' => Request::str('aciklama'),
                'hareket_tarihi' => $cikisTarihi,
                ]);
                foreach ($hizmetler as [$tipId, $tutar]) {
                    AracService::maliyetEkle((int) $arac['id'], [
                        'maliyet_tipi_id' => $tipId, 'tutar' => $tutar, 'aciklama' => 'Stoktan çıkış hizmeti',
                        'islem_tarihi' => substr($cikisTarihi, 0, 10),
                    ]);
                }

                return $hareketId;
            });
            Upload::save('dosya', 'hareket', $hareketId, 'belge', (array) ($_POST['dosya_tanim'] ?? []));
            Log::islem('hareket', 'Stoktan çıkış: ' . $arac['sase'] . ($hizmetler ? ' (' . count($hizmetler) . ' hizmet)' : ''), $hareketId);
            flash('success', $arac['sase'] . ' stoktan çıkarıldı.' . ($hizmetler ? ' ' . count($hizmetler) . ' hizmet ücreti araca yansıtıldı.' : ''));
            View::redirect('/arac_hareketleri/cikis');
        }, $back);
    }

    /** @return list<array{int, float}> [maliyet_tipi_id, tutar] */
    private static function cikisHizmetleri(): array
    {
        $tutarlar = (array) ($_POST['hizmet_tutar'] ?? []);
        $sonuc = [];
        foreach ((array) ($_POST['hizmet_id'] ?? []) as $i => $tipId) {
            if (!is_scalar($tipId) || (int) $tipId <= 0) {
                continue;
            }
            $tip = Database::fetch('SELECT ad, varsayilan_tutar FROM maliyet_tipleri WHERE id = :id AND aktif', ['id' => (int) $tipId]);
            if (!$tip) {
                throw new RuntimeException('Seçilen hizmet bulunamadı veya pasif.');
            }
            $ham = $tutarlar[$i] ?? '';
            $tutar = is_scalar($ham) && trim((string) $ham) !== '' ? Request::parseDecimal((string) $ham) : (float) $tip['varsayilan_tutar'];
            if ($tutar === null || $tutar < 0) {
                throw new RuntimeException("{$tip['ad']} için geçerli bir ücret giriniz.");
            }
            $sonuc[] = [(int) $tipId, $tutar];
        }

        return $sonuc;
    }

    public function etiketFiyati(): void
    {
        $this->ajax(function () {
            $arac = AracService::bul(Request::int('arac_id') ?? 0);
            AracService::guncelle((int) $arac['id'], ['etiket_fiyati' => Request::decimal('fiyat')]);
            $this->ok('Etiket fiyatı kaydedildi.');
        });
    }

    public function topluArsiv(): void
    {
        $this->ajax(function () {
            $ids = Request::ids('ids');
            if (!$ids) {
                throw new RuntimeException('Kayıt seçilmedi.');
            }
            $n = Database::query(
                'UPDATE arac_hareketleri SET arsiv = TRUE WHERE id = ANY(:ids::bigint[]) AND ' . Auth::bayiKosulu('bayi_id'),
                ['ids' => '{' . implode(',', $ids) . '}']
            )->rowCount();
            Log::islem('hareket', "{$n} hareket arşivlendi");
            $this->ok("{$n} kayıt arşivlendi.");
        });
    }
}
