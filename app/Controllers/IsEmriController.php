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
use RuntimeException;

final class IsEmriController extends Controller
{
    private const TAMAMLANDI = 4;

    /** Bayi kullanıcısı: kendi lokasyonundaki kullanıcıların açtığı veya kendi lokasyonundaki araçları içeren iş emirleri */
    private static function erisimKosulu(string $alias = 'ie'): string
    {
        $bayi = Auth::bayiId();
        if ($bayi === null) {
            return 'TRUE';
        }

        return "(EXISTS (SELECT 1 FROM users u2 WHERE u2.id = {$alias}.olusturan_id AND u2.bayi_id = {$bayi})
                 OR EXISTS (SELECT 1 FROM is_emri_araclari x JOIN araclar a2 ON a2.id = x.arac_id WHERE x.is_emri_id = {$alias}.id AND a2.bayi_id = {$bayi}))";
    }

    public function index(): void
    {
        $durum = Request::int('durum');
        $baslik = $durum ? (Tanim::IS_EMRI_DURUM[$durum] ?? '') . ' İş Emirleri' : 'Tüm İş Emirleri';
        $this->view('is_emirleri/liste', [
            'pageTitle' => 'İş Emri Yönetimi', 'durum' => $durum, 'baslik' => $baslik,
            'breadcrumb' => ['Operasyon İş Emri Yönetimi' => null, $baslik => null],
        ]);
    }

    public function liste(): void
    {
        $dt = new DataTable(
            'is_emirleri ie
             JOIN musteriler mu ON mu.id = ie.musteri_id
             JOIN maliyet_tipleri mt ON mt.id = ie.maliyet_tipi_id
             LEFT JOIN users u ON u.id = ie.olusturan_id',
            [
                'id' => 'ie.id', 'kod' => 'ie.kod', 'musteri' => 'mu.ad', 'tur' => 'mt.ad',
                'talep_tarihi' => "to_char(ie.talep_tarihi, 'DD.MM.YYYY')", 'talep_tarihi_sort' => 'ie.talep_tarihi',
                'istenen_tarih' => "to_char(ie.istenen_tarih, 'DD.MM.YYYY')", 'istenen_tarih_sort' => 'ie.istenen_tarih',
                'durum' => 'ie.durum', 'arac_sayisi' => '(SELECT COUNT(*) FROM is_emri_araclari x WHERE x.is_emri_id = ie.id)',
                'olusturan' => 'u.name',
            ],
            ['NOT ie.arsiv', self::erisimKosulu()],
            [],
            ['ie.kod', 'mu.ad', 'mt.ad', 'ie.detaylar'],
            'ie.id DESC'
        );
        if ($d = Request::int('durum')) {
            $dt->where('ie.durum = :f_durum', ['f_durum' => $d]);
        }
        foreach (['musteri_id', 'maliyet_tipi_id'] as $key) {
            if ($v = Request::int($key)) {
                $dt->where("ie.{$key} = :f_{$key}", ["f_{$key}" => $v]);
            }
        }
        if ($d = Request::date('baslangic')) {
            $dt->where('ie.talep_tarihi >= :f_bas', ['f_bas' => $d]);
        }
        if ($d = Request::date('bitis')) {
            $dt->where('ie.talep_tarihi <= :f_bit', ['f_bit' => $d]);
        }

        $map = fn ($r) => $r + ['durum_ad' => Tanim::IS_EMRI_DURUM[$r['durum']] ?? '-', 'durum_renk' => Tanim::IS_EMRI_DURUM_RENK[$r['durum']] ?? 'secondary'];
        if (Request::input('export')) {
            Excel::download('is-emirleri', [
                'kod' => 'İş Emri Kodu', 'musteri' => 'Müşteri', 'tur' => 'İş Emri Türü', 'talep_tarihi' => 'Talep Tarihi',
                'istenen_tarih' => 'İstenen Tarih', 'durum_ad' => 'Durumu', 'arac_sayisi' => 'Araç Sayısı', 'olusturan' => 'Oluşturan',
            ], array_map($map, $dt->all()));
        }
        $dt->response($map);
    }

    private static function yeniKod(): string
    {
        $yil = date('Y');
        $son = Database::fetch("SELECT kod FROM is_emirleri WHERE kod LIKE :p ORDER BY id DESC LIMIT 1", ['p' => "IE-{$yil}-%"]);
        $n = $son ? (int) substr($son['kod'], -5) + 1 : 1;

        return sprintf('IE-%s-%05d', $yil, $n);
    }

    public function ekle(): void
    {
        $this->view('is_emirleri/form', [
            'pageTitle' => 'İş Emri Ekle', 'ie' => null, 'araclar' => [], 'departmanlar' => [], 'personeller' => [], 'dosyalar' => [],
            'kod' => self::yeniKod(),
            'breadcrumb' => ['İş Emri Yönetimi' => '/gorev_yonetimi', 'İş Emri Ekle' => null],
        ]);
        unset($_SESSION['_old']);
    }

    private function bul(int $id): array
    {
        $ie = Database::fetch('SELECT ie.* FROM is_emirleri ie WHERE ie.id = :id AND ' . self::erisimKosulu(), ['id' => $id]);
        if (!$ie) {
            $this->notFound();
        }

        return $ie;
    }

    public function duzenle(string $id): void
    {
        $ie = $this->bul((int) $id);
        $this->view('is_emirleri/form', [
            'pageTitle' => 'İş Emri Düzenle', 'ie' => $ie, 'kod' => $ie['kod'],
            'araclar' => Database::fetchAll(
                'SELECT x.arac_id, x.durum, a.sase, a.plaka, b.ad AS bayi, m.ad AS marka, s.ad AS seri,
                        EXISTS (SELECT 1 FROM arac_ekstreleri e WHERE e.is_emri_id = x.is_emri_id AND e.arac_id = x.arac_id) AS maliyet_yazildi
                 FROM is_emri_araclari x JOIN araclar a ON a.id = x.arac_id
                 LEFT JOIN bayiler b ON b.id = a.bayi_id LEFT JOIN markalar m ON m.id = a.marka_id LEFT JOIN seriler s ON s.id = a.seri_id
                 WHERE x.is_emri_id = :id ORDER BY x.id',
                ['id' => $id]
            ),
            'departmanlar' => array_column(Database::fetchAll('SELECT departman_id FROM is_emri_departmanlari WHERE is_emri_id = :id', ['id' => $id]), 'departman_id'),
            'personeller' => array_column(Database::fetchAll('SELECT personel_id FROM is_emri_personelleri WHERE is_emri_id = :id', ['id' => $id]), 'personel_id'),
            'dosyalar' => Upload::list('is_emri', (int) $id),
            'breadcrumb' => ['İş Emri Yönetimi' => '/gorev_yonetimi', $ie['kod'] => null],
        ]);
        unset($_SESSION['_old']);
    }

    public function save(): void
    {
        $this->form(fn () => $this->kaydet(null), '/gorev_yonetimi/ekle');
    }

    public function update(string $id): void
    {
        $ie = $this->bul((int) $id);
        $this->form(fn () => $this->kaydet($ie), '/gorev_yonetimi/duzenle/' . $ie['id']);
    }

    private function kaydet(?array $mevcut): never
    {
        $tur = Request::int('gorev_turu');
        $musteri = Request::int('musteri_id');
        if (!$tur || !$musteri) {
            throw new RuntimeException('İş emri türü ve müşteri zorunludur.');
        }
        $durum = Request::int('durum') ?? 1;
        if (!isset(Tanim::IS_EMRI_DURUM[$durum])) {
            throw new RuntimeException('Geçersiz durum.');
        }

        $araclar = $this->aracSatirlari($durum);

        $data = [
            'kod' => Request::str('gorev_kodu') ?? ($mevcut['kod'] ?? self::yeniKod()),
            'maliyet_tipi_id' => $tur, 'musteri_id' => $musteri,
            'talep_tarihi' => Request::date('talep_tarihi') ?? date('Y-m-d'),
            'istenen_tarih' => Request::date('istenen_tarih'),
            'durum' => $durum,
            'tamamlanma_tarihi' => Request::date('tamamlanma_tarihi') ?? ($durum === self::TAMAMLANDI ? date('Y-m-d') : null),
            'detaylar' => Request::str('detaylar'), 'yorum' => Request::str('yorum'),
        ];

        $id = Database::transaction(function () use ($mevcut, $data, $araclar) {
            if ($mevcut) {
                $id = (int) $mevcut['id'];
                $set = implode(', ', array_map(fn ($c) => "{$c} = :{$c}", array_keys($data)));
                Database::query("UPDATE is_emirleri SET {$set}, updated_at = NOW() WHERE id = :_id", $data + ['_id' => $id]);
            } else {
                $id = AracService::insert('is_emirleri', $data + ['olusturan_id' => Auth::id()]);
            }

            Database::query('DELETE FROM is_emri_araclari WHERE is_emri_id = :id AND NOT (arac_id = ANY(:a::bigint[]))', ['id' => $id, 'a' => '{' . implode(',', array_keys($araclar)) . '}']);
            foreach ($araclar as $aracId => $aDurum) {
                Database::query(
                    'INSERT INTO is_emri_araclari (is_emri_id, arac_id, durum) VALUES (:i, :a, :d) ON CONFLICT (is_emri_id, arac_id) DO UPDATE SET durum = EXCLUDED.durum',
                    ['i' => $id, 'a' => $aracId, 'd' => $aDurum]
                );
            }

            foreach (['is_emri_departmanlari' => ['departman_id', 'sorumlu_departman'], 'is_emri_personelleri' => ['personel_id', 'sorumlu_personel']] as $table => [$col, $field]) {
                Database::query("DELETE FROM {$table} WHERE is_emri_id = :id", ['id' => $id]);
                foreach (Request::ids($field) as $v) {
                    Database::query("INSERT INTO {$table} (is_emri_id, {$col}) VALUES (:i, :v)", ['i' => $id, 'v' => $v]);
                }
            }

            Upload::save('dosya', 'is_emri', $id, 'belge', (array) ($_POST['dosya_tanim'] ?? []));
            $yazilan = self::tamamlananlaraMaliyetYaz($id);
            Log::islem('is_emri', ($mevcut ? 'İş emri güncellendi: ' : 'İş emri oluşturuldu: ') . $data['kod'] . ($yazilan ? " ({$yazilan} araca maliyet yazıldı)" : ''), $id);

            return $id;
        });

        flash('success', $mevcut ? 'İş emri güncellendi.' : 'İş emri oluşturuldu.');
        View::redirect('/gorev_yonetimi/duzenle/' . $id);
    }

    /** @return array<int,int> arac_id => durum */
    private function aracSatirlari(int $genelDurum): array
    {
        $sonuc = [];
        $durumlar = (array) ($_POST['a_durum'] ?? []);
        foreach ((array) ($_POST['arac_id'] ?? []) as $i => $aracId) {
            $aracId = (int) $aracId;
            if ($aracId > 0) {
                AracService::bul($aracId);
                $sonuc[$aracId] = (int) ($durumlar[$i] ?? 0) ?: 1;
            }
        }

        if (!empty($_FILES['import_file']['name']) && ($_FILES['import_file']['error'] ?? 1) === UPLOAD_ERR_OK) {
            $bulunamayan = [];
            foreach (Excel::read($_FILES['import_file']['tmp_name']) as $row) {
                $sase = AracService::normalizeSase((string) ($row['sase'] ?? reset($row)));
                if ($sase === '') {
                    continue;
                }
                $arac = Database::fetch('SELECT id FROM araclar WHERE sase = :s AND ' . Auth::bayiKosulu('bayi_id'), ['s' => $sase]);
                if ($arac) {
                    $sonuc[(int) $arac['id']] ??= 1;
                } else {
                    $bulunamayan[] = $sase;
                }
            }
            if ($bulunamayan) {
                flash('error', 'Sistemde bulunamayan şasiler: ' . implode(', ', array_slice($bulunamayan, 0, 15)));
            }
        }

        if ($genelDurum === self::TAMAMLANDI) {
            $sonuc = array_map(fn () => self::TAMAMLANDI, $sonuc);
        }

        return $sonuc;
    }

    /** Tamamlanan araçlara iş emri türü tutarında ekstre yazar (her araç için bir kez). */
    private static function tamamlananlaraMaliyetYaz(int $isEmriId): int
    {
        $ie = Database::fetch(
            'SELECT ie.*, mt.varsayilan_tutar FROM is_emirleri ie JOIN maliyet_tipleri mt ON mt.id = ie.maliyet_tipi_id WHERE ie.id = :id',
            ['id' => $isEmriId]
        );
        $bekleyen = Database::fetchAll(
            'SELECT x.arac_id FROM is_emri_araclari x
             WHERE x.is_emri_id = :id AND x.durum = :d
               AND NOT EXISTS (SELECT 1 FROM arac_ekstreleri e WHERE e.is_emri_id = x.is_emri_id AND e.arac_id = x.arac_id)',
            ['id' => $isEmriId, 'd' => self::TAMAMLANDI]
        );
        foreach ($bekleyen as $row) {
            AracService::maliyetEkle((int) $row['arac_id'], [
                'maliyet_tipi_id' => $ie['maliyet_tipi_id'], 'tutar' => (float) $ie['varsayilan_tutar'],
                'aciklama' => 'İş emri: ' . $ie['kod'], 'islem_tarihi' => $ie['tamamlanma_tarihi'] ?? date('Y-m-d'),
                'is_emri_id' => $isEmriId,
            ]);
        }

        return count($bekleyen);
    }

    public function topluDurum(): void
    {
        $this->ajax(function () {
            $ids = Request::ids('ids');
            $durum = Request::int('durum');
            if (!$ids || !isset(Tanim::IS_EMRI_DURUM[$durum])) {
                throw new RuntimeException('Kayıt ve durum seçilmelidir.');
            }
            $n = 0;
            Database::transaction(function () use ($ids, $durum, &$n) {
                foreach ($ids as $id) {
                    $ie = Database::fetch('SELECT ie.id FROM is_emirleri ie WHERE ie.id = :id AND ' . self::erisimKosulu(), ['id' => $id]);
                    if (!$ie) {
                        continue;
                    }
                    Database::query(
                        'UPDATE is_emirleri SET durum = :d, tamamlanma_tarihi = CASE WHEN :d2 = 4 THEN COALESCE(tamamlanma_tarihi, CURRENT_DATE) ELSE tamamlanma_tarihi END, updated_at = NOW() WHERE id = :id',
                        ['d' => $durum, 'd2' => $durum, 'id' => $id]
                    );
                    if ($durum === self::TAMAMLANDI) {
                        Database::query('UPDATE is_emri_araclari SET durum = 4 WHERE is_emri_id = :id', ['id' => $id]);
                        self::tamamlananlaraMaliyetYaz((int) $id);
                    }
                    $n++;
                }
            });
            Log::islem('is_emri', "{$n} iş emri durumu: " . Tanim::IS_EMRI_DURUM[$durum]);
            $this->ok("{$n} iş emri güncellendi: " . Tanim::IS_EMRI_DURUM[$durum]);
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
                'UPDATE is_emirleri ie SET arsiv = TRUE, updated_at = NOW() WHERE ie.id = ANY(:ids::bigint[]) AND ' . self::erisimKosulu(),
                ['ids' => '{' . implode(',', $ids) . '}']
            )->rowCount();
            $this->ok("{$n} iş emri arşivlendi.");
        });
    }

    public function sablon(): void
    {
        Excel::download('is-emri-arac-sablonu', ['sase' => 'sase'], [['sase' => 'VF1RJA00776597244']]);
    }
}
