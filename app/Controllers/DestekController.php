<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\DataTable;
use App\Core\Log;
use App\Core\Request;
use App\Core\Tanim;
use App\Core\Upload;
use RuntimeException;

final class DestekController extends Controller
{
    /** Yönetici tüm talepleri, diğer kullanıcılar kendi lokasyonundaki kullanıcıların taleplerini görür. */
    private static function erisim(string $alias = 'd'): string
    {
        $bayi = Auth::bayiId();

        return $bayi === null ? 'TRUE' : "{$alias}.talep_eden_id IN (SELECT id FROM users WHERE bayi_id = {$bayi})";
    }

    public function index(): void
    {
        $this->view('destek/liste', ['pageTitle' => 'Destek Talepleri', 'breadcrumb' => ['İK' => null, 'Destek Talepleri' => null]]);
    }

    public function liste(): void
    {
        $dt = new DataTable(
            'destek_talepleri d LEFT JOIN users u ON u.id = d.talep_eden_id',
            [
                'id' => 'd.id', 'kod' => 'd.kod', 'konu' => 'd.konu', 'tur' => 'd.tur', 'durum' => 'd.durum', 'talep_eden' => 'u.name',
                'tarih' => "to_char(d.created_at AT TIME ZONE 'Europe/Istanbul', 'DD.MM.YYYY HH24:MI')", 'tarih_sort' => 'd.created_at',
            ],
            ['NOT d.arsiv', self::erisim()],
            [],
            ['d.kod', 'd.konu', 'u.name'],
            'd.id DESC'
        );
        if ($v = Request::int('durum')) {
            $dt->where('d.durum = :f_durum', ['f_durum' => $v]);
        }
        $dt->response(fn ($r) => $r + ['tur_ad' => Tanim::DESTEK_TUR[$r['tur']] ?? '-', 'durum_ad' => Tanim::DESTEK_DURUM[$r['durum']] ?? '-']);
    }

    public function save(): void
    {
        $this->ajax(function () {
            $konu = Request::str('konu');
            if (!$konu) {
                throw new RuntimeException('Talep konusu zorunludur.');
            }
            $id = Database::transaction(function () use ($konu) {
                $id = (int) Database::fetch(
                    "INSERT INTO destek_talepleri (kod, konu, tur, aciklama, talep_eden_id) VALUES ('TMP-' || substr(md5(random()::text), 1, 24), :k, :t, :a, :u) RETURNING id",
                    ['k' => $konu, 't' => Request::int('tur') ?? 1, 'a' => Request::str('aciklama'), 'u' => Auth::id()]
                )['id'];
                Database::query('UPDATE destek_talepleri SET kod = :kod WHERE id = :id', ['kod' => sprintf('DT-%05d', $id), 'id' => $id]);
                Upload::save('dosya', 'destek', $id);

                return $id;
            });
            Log::islem('destek', 'Destek talebi açıldı: ' . $konu, $id);
            $this->ok('Destek talebiniz oluşturuldu.');
        });
    }

    private function bul(int $id): array
    {
        $row = Database::fetch(
            'SELECT d.*, u.name AS talep_eden FROM destek_talepleri d LEFT JOIN users u ON u.id = d.talep_eden_id WHERE d.id = :id AND ' . self::erisim(),
            ['id' => $id]
        );
        if (!$row) {
            throw new RuntimeException('Talep bulunamadı.');
        }

        return $row;
    }

    public function getir(string $id): void
    {
        $this->ajax(function () use ($id) {
            $row = $this->bul((int) $id);
            $row['dosyalar'] = Upload::list('destek', (int) $id);
            $row['tur_ad'] = Tanim::DESTEK_TUR[$row['tur']] ?? '-';
            $row['tarih'] = date('d.m.Y H:i', strtotime($row['created_at']));
            $this->ok('', ['data' => $row]);
        });
    }

    public function cevapla(string $id): void
    {
        $this->ajax(function () use ($id) {
            if (!Auth::isAdmin()) {
                throw new RuntimeException('Talepleri yalnızca yönetici yanıtlayabilir.');
            }
            $row = $this->bul((int) $id);
            $durum = Request::int('durum');
            if (!isset(Tanim::DESTEK_DURUM[$durum])) {
                throw new RuntimeException('Geçersiz durum.');
            }
            Database::query('UPDATE destek_talepleri SET durum = :d, cevap = :c, updated_at = NOW() WHERE id = :id', ['d' => $durum, 'c' => Request::str('cevap'), 'id' => $row['id']]);
            Log::islem('destek', 'Destek talebi güncellendi: ' . $row['kod'] . ' → ' . Tanim::DESTEK_DURUM[$durum], (int) $row['id']);
            $this->ok('Talep güncellendi.');
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
                'UPDATE destek_talepleri d SET arsiv = TRUE WHERE d.id = ANY(:ids::bigint[]) AND ' . self::erisim() . (Auth::isAdmin() ? '' : ' AND d.talep_eden_id = ' . (int) Auth::id()),
                ['ids' => '{' . implode(',', $ids) . '}']
            )->rowCount();
            $this->ok("{$n} talep arşivlendi.");
        });
    }
}
