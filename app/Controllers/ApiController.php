<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Tanim;
use App\Core\View;

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
            ? Database::fetchAll('SELECT id, ad_soyad FROM personeller WHERE aktif AND departman_id = ANY(:d::int[]) ORDER BY ad_soyad', ['d' => '{' . implode(',', $ids) . '}'])
            : [];
        View::json((object) array_column($rows, 'ad_soyad', 'id'));
    }

    public function araclar(): void
    {
        $q = mb_strtoupper(trim((string) Request::input('q', '')));
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
        $q = trim((string) Request::input('q', ''));
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
}
