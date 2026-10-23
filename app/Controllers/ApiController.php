<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Tanim;
use App\Core\View;
use App\Services\HizmetFiyat;

final class ApiController extends Controller
{
    public function seriler(): void
    {
        View::json((object) Tanim::seriler(Request::int('marka_id') ?? 0));
    }

    public function modeller(): void
    {
        View::json((object) Tanim::modeller(Request::int('seri_id') ?? 0));
    }

    public function personeller(): void
    {
        $ids = Request::ids('departman');
        $rows = $ids
            ? Database::fetchAll(
                'SELECT id, ad_soyad FROM personeller WHERE aktif AND departman_id = ANY(:d::int[]) AND '
                . Auth::bayiKosulu('bayi_id') . ' ORDER BY ad_soyad',
                ['d' => '{' . implode(',', $ids) . '}']
            )
            : [];
        View::json((object) array_column($rows, 'ad_soyad', 'id'));
    }

    public function araclar(): void
    {
        $q = mb_strtoupper(Request::str('q') ?? '');
        if (mb_strlen($q) < 2) {
            View::json(['results' => []]);
        }

        $stok = Request::input('stokta') === '1' ? ' AND a.stokta' : '';
        $rows = Database::fetchAll(
            "SELECT a.id, a.sase, a.plaka, m.ad AS marka, s.ad AS seri, a.stokta
             FROM araclar a
             LEFT JOIN markalar m ON m.id = a.marka_id
             LEFT JOIN seriler s ON s.id = a.seri_id
             WHERE (a.sase ILIKE :q OR a.plaka ILIKE :q) AND NOT a.arsiv AND " . Auth::bayiKosulu('a.bayi_id') . $stok . '
             ORDER BY a.stokta DESC, a.id DESC LIMIT 20',
            ['q' => '%' . $q . '%']
        );

        View::json(['results' => array_map(fn ($r) => [
            'id' => $r['id'],
            'text' => $r['sase'] . ' — ' . ($r['plaka'] ?: '-') . ' — ' . trim(($r['marka'] ?? '') . ' ' . ($r['seri'] ?? '')) . ($r['stokta'] ? '' : ' (stok dışı)'),
        ], $rows)]);
    }

    public function arama(): void
    {
        $q = Request::str('q') ?? '';
        if ($q === '') {
            View::redirect('/');
        }
        $rows = Database::fetchAll(
            'SELECT id FROM araclar WHERE (sase ILIKE :q OR plaka ILIKE :q) AND ' . Auth::bayiKosulu('bayi_id') . ' LIMIT 2',
            ['q' => '%' . $q . '%']
        );
        if (count($rows) === 1) {
            View::redirect('/arac_yonetimi/duzenle/' . $rows[0]['id']);
        }
        View::redirect('/arac_yonetimi?q=' . urlencode($q));
    }

    /** Müşteri × otopark hizmet ücreti: arac_id veya musteri_id + bayi_id + maliyet_tipi_id */
    public function hizmetFiyat(): void
    {
        $tipId = Request::int('maliyet_tipi_id') ?? 0;
        if ($tipId <= 0) {
            View::json(['success' => false, 'tutar' => 0, 'kaynak' => null]);
        }
        $aracId = Request::int('arac_id');
        $musteriId = Request::int('musteri_id');
        $bayiId = Request::int('bayi_id');
        if ($aracId) {
            $arac = Database::fetch(
                'SELECT musteri_id, bayi_id FROM araclar WHERE id = :id AND ' . Auth::bayiKosulu('bayi_id'),
                ['id' => $aracId]
            );
            if (!$arac) {
                View::json(['success' => false, 'tutar' => 0, 'kaynak' => null], 404);
            }
            $musteriId = (int) ($arac['musteri_id'] ?? 0) ?: null;
            $bayiId = (int) ($arac['bayi_id'] ?? 0) ?: null;
        }
        $tutar = HizmetFiyat::tutar($musteriId, $tipId, $bayiId);
        $tanimli = HizmetFiyat::tanimli($musteriId, $tipId, $bayiId);
        View::json([
            'success' => true,
            'tutar' => $tutar,
            'tanimli' => $tanimli,
            'kaynak' => $tanimli ? 'firma_otopark' : null,
            'musteri_id' => $musteriId,
            'bayi_id' => $bayiId,
            'bayi_fiyat_degistirebilir' => HizmetFiyat::bayiDegistirebilir($tipId),
        ]);
    }
}
