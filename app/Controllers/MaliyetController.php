<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\DataTable;
use App\Core\Excel;
use App\Core\Log;
use App\Core\Request;
use App\Core\View;
use App\Services\AracService;
use App\Services\HizmetFiyat;
use RuntimeException;

final class MaliyetController extends Controller
{
    /** Hızlı Maliyet Ekle modalı ve araç kartındaki maliyet formu */
    public function saveModal(): void
    {
        $this->ajax(function () {
            $aracId = Request::int('arac_id');
            if (!$aracId) {
                throw new RuntimeException('Araç seçilmelidir.');
            }
            $ayri = array_key_exists('musteri_tutar', $_POST) || array_key_exists('bayi_tutar', $_POST);
            $payload = [
                'maliyet_tipi_id' => Request::int('maliyet_tipi_modal'),
            ];
            if ($ayri) {
                $payload['musteri_tutar'] = Request::decimal('musteri_tutar');
                $payload['bayi_tutar'] = Request::decimal('bayi_tutar');
            } else {
                $tutar = Request::decimal('tutar_ek');
                if ($tutar !== null && $tutar < 0) {
                    throw new RuntimeException('Tutar negatif olamaz.');
                }
                $payload['tutar'] = ($tutar !== null && $tutar > 0) ? $tutar : null;
            }
            AracService::maliyetEkle($aracId, $payload + [
                'aciklama' => Request::str('aciklama_ekstre'),
                'irsaliye' => Request::str('irsaliye'),
                'fatura_no' => Request::str('fatura_no'),
                'fatura_tarihi' => Request::date('fatura_tarihi'),
                'islem_tarihi' => Request::date('islem_tarihi') ?? date('Y-m-d'),
            ]);
            $this->ok('Ek maliyet kaydedildi.');
        });
    }

    private function bul(int $id): array
    {
        $row = Database::fetch(
            'SELECT e.* FROM arac_ekstreleri e JOIN araclar a ON a.id = e.arac_id WHERE e.id = :id AND ' . Auth::bayiKosulu('a.bayi_id'),
            ['id' => $id]
        );
        if (!$row) {
            throw new RuntimeException('Maliyet kaydı bulunamadı.');
        }

        return $row;
    }

    public function getir(string $id): void
    {
        $this->ajax(function () use ($id) {
            $row = $this->bul((int) $id);
            $deg = HizmetFiyat::bayiDegistirebilir((int) $row['maliyet_tipi_id']);
            $row['bayi_fiyat_degistirebilir'] = $deg;
            if (Auth::isAdmin()) {
                $row['musteri_tutar'] = $row['tutar'];
            }
            $row = HizmetFiyat::gorunenEkstre($row);
            View::json(['success' => true, 'data' => $row]);
        });
    }

    public function guncelle(string $id): void
    {
        $this->ajax(function () use ($id) {
            $row = $this->bul((int) $id);
            $tipId = Request::int('maliyet_tipi_id') ?: (int) $row['maliyet_tipi_id'];
            if (!$tipId) {
                throw new RuntimeException('Maliyet tipi zorunludur.');
            }
            $musteriId = (int) ($row['musteri_id'] ?? 0) ?: null;
            $bayiId = (int) ($row['bayi_id'] ?? 0) ?: null;
            if (!Auth::isAdmin()) {
                $tipId = (int) $row['maliyet_tipi_id'];
            }
            $tanimli = HizmetFiyat::tanimli($musteriId, $tipId, $bayiId);
            $deg = HizmetFiyat::bayiDegistirebilir($tipId);
            if (!$tanimli) {
                throw new RuntimeException('Bu hizmet için müşteri / otopark fiyatı belirlenmedi.');
            }
            $mevcutMusteri = round((float) $row['tutar'], 2);
            $mevcutBayi = round((float) ($row['bayi_tutar'] ?? $row['tutar']), 2);
            if (Auth::isAdmin()) {
                $musteri = Request::decimal('musteri_tutar');
                if ($musteri === null) {
                    $musteri = Request::decimal('tutar');
                }
                $bayi = Request::decimal('bayi_tutar');
                $yeniMusteri = $musteri ?? $mevcutMusteri;
                $yeniBayi = $bayi ?? $mevcutBayi;
            } else {
                $yeniMusteri = $mevcutMusteri;
                $bayi = Request::decimal('bayi_tutar');
                if ($bayi === null) {
                    $bayi = Request::decimal('tutar');
                }
                if (!$deg && $bayi !== null && abs($bayi - $mevcutBayi) > 0.009) {
                    throw new RuntimeException('Bu hizmetin bayi fiyatı kilitlidir; tutar değiştirilemez.');
                }
                $yeniBayi = ($deg && $bayi !== null) ? $bayi : $mevcutBayi;
            }
            if ($yeniMusteri < 0 || $yeniBayi < 0) {
                throw new RuntimeException('Geçerli bir tutar giriniz.');
            }
            AracService::guncelle((int) $row['id'], [
                'maliyet_tipi_id' => $tipId, 'tutar' => $yeniMusteri, 'bayi_tutar' => $yeniBayi,
                'islem_tarihi' => Request::date('islem_tarihi') ?? $row['islem_tarihi'],
                'fatura_no' => Request::str('fatura_no'), 'fatura_tarihi' => Request::date('fatura_tarihi'),
                'irsaliye' => Request::str('irsaliye'), 'aciklama' => Request::str('aciklama'),
            ], 'arac_ekstreleri');
            Log::islem('maliyet', 'Maliyet güncellendi', (int) $row['id']);
            $this->ok('Maliyet kaydı güncellendi.');
        });
    }

    public function sil(string $id): void
    {
        $this->ajax(function () use ($id) {
            $row = $this->bul((int) $id);
            Database::query('DELETE FROM arac_ekstreleri WHERE id = :id', ['id' => $row['id']]);
            Log::islem('maliyet', 'Maliyet silindi (' . $row['tutar'] . ' ₺)', (int) $row['arac_id']);
            $this->ok('Maliyet kaydı silindi.');
        });
    }

    /* ---------- İş Takibi: kim hangi araca hangi hizmeti yazdı ---------- */

    public function isTakibi(): void
    {
        $this->view('maliyet/is_takibi', ['pageTitle' => 'İş Takibi', 'breadcrumb' => ['Saha Operasyon' => null, 'İş Takibi' => null]]);
    }

    public static function ekstreTablosu(): DataTable
    {
        $dt = new DataTable(
            'arac_ekstreleri e
             JOIN araclar a ON a.id = e.arac_id
             JOIN maliyet_tipleri mt ON mt.id = e.maliyet_tipi_id
             LEFT JOIN musteriler mu ON mu.id = e.musteri_id
             LEFT JOIN bayiler b ON b.id = e.bayi_id
             LEFT JOIN markalar m ON m.id = a.marka_id
             LEFT JOIN modeller mo ON mo.id = a.model_id
             LEFT JOIN seriler s ON s.id = a.seri_id
             LEFT JOIN users u ON u.id = e.kullanici_id',
            [
                'id' => 'e.id', 'arac_id' => 'a.id', 'sase' => 'a.sase', 'plaka' => 'a.plaka', 'firma' => 'mu.ad', 'marka' => 'm.ad', 'model' => 'mo.ad', 'seri' => 's.ad',
                'lokasyon' => 'b.ad', 'maliyet_tipi' => 'mt.ad', 'tutar' => 'e.tutar', 'bayi_tutar' => 'COALESCE(e.bayi_tutar, e.tutar)', 'aciklama' => 'e.aciklama', 'fatura_no' => 'e.fatura_no',
                'bayi_fiyat_degistirebilir' => 'mt.bayi_fiyat_degistirebilir',
                'islem_tarihi' => "to_char(e.islem_tarihi, 'DD.MM.YYYY')", 'islem_tarihi_sort' => 'e.islem_tarihi',
                'cikis_sonrasi' => 'e.cikis_sonrasi', 'hareket_id' => 'e.hareket_id',
                'kullanici' => 'u.name', 'kayit_zamani' => "to_char(e.created_at AT TIME ZONE 'Europe/Istanbul', 'DD.MM.YYYY HH24:MI')", 'kayit_zamani_sort' => 'e.created_at',
            ],
            [Auth::bayiKosulu('e.bayi_id')],
            [],
            ['a.sase', 'a.plaka', 'mt.ad', 'mu.ad', 'e.aciklama', 'e.fatura_no', 'u.name'],
            'e.islem_tarihi DESC, e.id DESC'
        );
        foreach (['musteri_id' => 'e.musteri_id', 'bayi_id' => 'e.bayi_id', 'maliyet_tipi_id' => 'e.maliyet_tipi_id', 'kullanici_id' => 'e.kullanici_id', 'marka_id' => 'a.marka_id', 'model_id' => 'a.model_id', 'seri_id' => 'a.seri_id', 'arac_tipi_id' => 'a.arac_tipi_id'] as $key => $col) {
            $ids = Request::ids($key);
            if ($ids) {
                $dt->where("{$col} = ANY(:f_{$key}::int[])", ["f_{$key}" => '{' . implode(',', $ids) . '}']);
            }
        }
        if ($d = Request::date('baslangic')) {
            $dt->where('e.islem_tarihi >= :f_bas', ['f_bas' => $d]);
        }
        if ($d = Request::date('bitis')) {
            $dt->where('e.islem_tarihi <= :f_bit', ['f_bit' => $d]);
        }
        if (Request::str('cikis_sonrasi') === '1') {
            $dt->where('e.cikis_sonrasi');
        }

        return $dt;
    }

    public function isTakibiListe(): void
    {
        $dt = self::ekstreTablosu();
        $admin = Auth::isAdmin();
        $maske = Auth::fiyatMaskeli();
        $map = static fn (array $r): array => HizmetFiyat::gorunenEkstre($r);
        if (Request::input('export')) {
            $kolon = [
                'islem_tarihi' => 'İşlem Tarihi', 'sase' => 'Şase', 'plaka' => 'Plaka', 'firma' => 'Firma', 'lokasyon' => 'Lokasyon',
                'maliyet_tipi' => 'Hizmet', 'aciklama' => 'Açıklama', 'kullanici' => 'İşlemi Yapan', 'kayit_zamani' => 'Kayıt Zamanı',
            ];
            if ($admin) {
                $kolon['bayi_tutar'] = 'Bayi tutarı';
                $kolon['tutar'] = 'Müşteri tutarı';
                $kolon['kar'] = 'Kâr';
            } else {
                $kolon['tutar'] = $maske ? 'Bayi tutarı' : 'Müşteri tutarı';
            }
            Excel::download('is-takibi', $kolon, array_map($map, $dt->all()));
        }
        $extra = self::ekstreToplamlari($dt, $admin, $maske);
        $dt->response($map, $extra);
    }

    /** @return array<string,mixed> */
    public static function ekstreToplamlari(DataTable $dt, bool $admin, bool $maske): array
    {
        $extra = ['toplam_maskeli' => $maske, 'toplam_admin' => $admin];
        if ($admin) {
            $musteri = $dt->sum('e.tutar');
            $bayi = $dt->sum('COALESCE(e.bayi_tutar, e.tutar)');
            $extra['toplam'] = $musteri;
            $extra['toplam_bayi'] = $bayi;
            $extra['toplam_kar'] = round($musteri - $bayi, 2);
            $extra['ozet'] = $dt->groupSum('mt.ad', 'e.tutar');
        } elseif ($maske) {
            $bayi = $dt->sum('COALESCE(e.bayi_tutar, e.tutar)');
            $extra['toplam'] = $bayi;
            $extra['toplam_etiket'] = number_format($bayi, 2, ',', '.') . ' ₺';
            $extra['ozet'] = [];
        } else {
            $extra['toplam'] = $dt->sum('e.tutar');
            $extra['ozet'] = $dt->groupSum('mt.ad', 'e.tutar');
        }

        return $extra;
    }
}
