<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Log;
use App\Core\Request;
use App\Services\Karsilastirma;
use RuntimeException;

final class KarsilastirmaController extends Controller
{
    public function index(): void
    {
        $this->view('karsilastirma/index', [
            'pageTitle' => 'BİA ↔ MD Karşılaştırma',
            'breadcrumb' => ['Proje Notları' => null, 'BİA ↔ MD Karşılaştırma' => null],
            'veri' => Karsilastirma::veri(),
            'maddeler' => Karsilastirma::maddeler(),
        ]);
    }

    public function kaydet(): void
    {
        $this->ajax(function () {
            if (!Auth::isAdmin()) {
                throw new RuntimeException('Kararları yalnızca yönetici güncelleyebilir.');
            }
            $kod = (string) Request::str('kod');
            $karar = (string) Request::str('karar');
            if (!Karsilastirma::madde($kod)) {
                throw new RuntimeException('Madde bulunamadı.');
            }
            if (!isset(Karsilastirma::veri()['kararlar'][$karar])) {
                throw new RuntimeException('Geçersiz karar.');
            }
            $notlar = mb_substr((string) Request::str('notlar'), 0, 2000);

            Database::query(
                'INSERT INTO karsilastirma_kararlari (kod, karar, notlar, guncelleyen_id, updated_at)
                 VALUES (:k, :karar, :n, :u, NOW())
                 ON CONFLICT (kod) DO UPDATE SET karar = EXCLUDED.karar, notlar = EXCLUDED.notlar,
                     guncelleyen_id = EXCLUDED.guncelleyen_id, updated_at = NOW()',
                ['k' => $kod, 'karar' => $karar, 'n' => $notlar !== '' ? $notlar : null, 'u' => Auth::id()]
            );
            Log::islem('Karşılaştırma', "{$kod} kararı: " . Karsilastirma::veri()['kararlar'][$karar][0]);

            $this->ok('Karar kaydedildi.', ['guncelleyen' => Auth::user()['name'] ?? '', 'tarih' => date('d.m.Y H:i')]);
        });
    }

    public function indir(): void
    {
        header('Content-Type: text/markdown; charset=utf-8');
        header('Content-Disposition: attachment; filename="bia-md-karsilastirma.md"');
        echo Karsilastirma::markdown();
    }
}
