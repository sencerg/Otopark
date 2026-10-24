<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\View;
use App\Services\VisStore;
use RuntimeException;

/**
 * Autorola VIS tarzı araç ekspertiz — bağımsız modül.
 * Mevcut stok / hareket / maliyet akışlarına bağlanmaz.
 */
final class VisController extends Controller
{
    private function gate(): void
    {
        Auth::requireStaff();
        Auth::requireIzin('menu.vis');
    }

    private function visView(string $view, array $data = []): void
    {
        View::render('vis/' . $view, $data + [
            'hidePageHeader' => true,
            'pageTitle' => $data['pageTitle'] ?? 'Araç Ekspertiz',
        ], 'vis');
    }

    public function index(): void
    {
        $this->gate();
        $q = trim((string) (Request::str('q') ?? ''));
        $liste = VisStore::liste();
        if ($q !== '') {
            $liste = array_values(array_filter($liste, static function (array $r) use ($q): bool {
                $hay = mb_strtolower(($r['veri']['plaka'] ?? '') . ' ' . ($r['veri']['sase'] ?? '') . ' ' . ($r['veri']['marka'] ?? '') . ' ' . ($r['veri']['model'] ?? ''));

                return str_contains($hay, mb_strtolower($q));
            }));
        }
        $this->visView('index', [
            'pageTitle' => 'Araç ara',
            'liste' => $liste,
            'q' => $q,
        ]);
    }

    public function yeni(): void
    {
        $this->gate();
        $row = VisStore::olustur(['kullanici_id' => Auth::id()]);
        View::redirect('/vis/kayit/' . $row['id'] . '?modul=tanim&alan=plaka');
    }

    public function kayit(string $id): void
    {
        $this->gate();
        $row = VisStore::bul($id);
        if (!$row) {
            http_response_code(404);
            exit('Kayıt bulunamadı.');
        }
        $modul = Request::str('modul') ?: ($row['modul'] ?? 'tanim');
        $alan = Request::str('alan') ?: ($row['alan'] ?? 'plaka');
        $alanlar = VisStore::tanimAlanlari();
        if ($modul === 'tanim' && !isset($alanlar[$alan])) {
            $alan = array_key_first($alanlar) ?: 'plaka';
        }
        $this->visView('wizard', [
            'pageTitle' => match ($modul) {
                'hasar' => 'Hasar tespiti',
                'mekanik' => 'Mekanik ve Elektrik',
                'foto' => 'Fotoğraflar ve Dokümanlar',
                'lokasyon' => 'Lokasyon',
                'kondisyon' => 'Kondisyon',
                'cikis' => 'Çıkış',
                default => ($alanlar[$alan][0] ?? 'Araç tanımı'),
            },
            'kayit' => $row,
            'modul' => $modul,
            'alan' => $alan,
            'alanlar' => $alanlar,
            'moduller' => VisStore::moduller(),
            'hasarPanelleri' => VisStore::hasarPanelleri(),
            'hasarSeviyeleri' => VisStore::hasarSeviyeleri(),
        ]);
    }

    public function kaydet(string $id): void
    {
        $this->ajax(function () use ($id) {
            $this->gate();
            if (!VisStore::bul($id)) {
                throw new RuntimeException('Kayıt bulunamadı.');
            }
            $modul = Request::str('modul') ?: 'tanim';
            $alan = Request::str('alan') ?: '';
            $patch = ['modul' => $modul];

            if ($modul === 'tanim') {
                $alanlar = VisStore::tanimAlanlari();
                if (!isset($alanlar[$alan])) {
                    throw new RuntimeException('Geçersiz alan.');
                }
                $deger = Request::str('deger');
                if (($alanlar[$alan][3] ?? false) && ($deger === null || trim((string) $deger) === '')) {
                    throw new RuntimeException($alanlar[$alan][0] . ' zorunludur.');
                }
                $patch['veri'] = [$alan => $deger];
                $patch['alan'] = $alan;
                $keys = array_keys($alanlar);
                $i = array_search($alan, $keys, true);
                $sonrakiAlan = ($i !== false && isset($keys[$i + 1])) ? $keys[$i + 1] : null;
                $kayit = VisStore::kaydet($id, $patch);
                $this->ok('Kaydedildi.', [
                    'kayit' => $kayit,
                    'sonraki' => $sonrakiAlan
                        ? '/vis/kayit/' . $id . '?modul=tanim&alan=' . urlencode($sonrakiAlan)
                        : '/vis/kayit/' . $id . '?modul=hasar',
                ]);
            }

            if ($modul === 'hasar') {
                $panel = Request::str('panel');
                $seviye = Request::str('seviye');
                $not = Request::str('not');
                if (!$panel || !isset(VisStore::hasarPanelleri()[$panel])) {
                    throw new RuntimeException('Panel seçiniz.');
                }
                $hasar = VisStore::bul($id)['hasar'] ?? [];
                $hasar[$panel] = ['seviye' => $seviye ?: 'Yok', 'not' => $not];
                $kayit = VisStore::kaydet($id, ['hasar' => $hasar, 'modul' => 'hasar']);
                $this->ok('Hasar kaydı güncellendi.', ['kayit' => $kayit]);
            }

            if ($modul === 'mekanik') {
                $kayit = VisStore::kaydet($id, [
                    'mekanik' => [
                        'motor' => Request::str('motor'),
                        'elektrik' => Request::str('elektrik'),
                        'suspansiyon' => Request::str('suspansiyon'),
                        'fren' => Request::str('fren'),
                        'not' => Request::str('not'),
                    ],
                    'modul' => 'mekanik',
                ]);
                $this->ok('Mekanik durum kaydedildi.', ['kayit' => $kayit]);
            }

            if ($modul === 'lokasyon') {
                $kayit = VisStore::kaydet($id, [
                    'lokasyon' => [
                        'otopark' => Request::str('otopark'),
                        'detay' => Request::str('detay'),
                    ],
                    'modul' => 'lokasyon',
                ]);
                $this->ok('Lokasyon kaydedildi.', ['kayit' => $kayit]);
            }

            if ($modul === 'kondisyon') {
                $kayit = VisStore::kaydet($id, [
                    'kondisyon' => [
                        'genel' => Request::str('genel'),
                        'ic' => Request::str('ic'),
                        'dis' => Request::str('dis'),
                        'not' => Request::str('not'),
                    ],
                    'modul' => 'kondisyon',
                ]);
                $this->ok('Kondisyon kaydedildi.', ['kayit' => $kayit]);
            }

            if ($modul === 'cikis') {
                $kayit = VisStore::kaydet($id, [
                    'durum' => 'tamamlandi',
                    'modul' => 'cikis',
                    'cikis_not' => Request::str('not'),
                ]);
                $this->ok('Ekspertiz tamamlandı (bağımsız kayıt — stoka yazılmaz).', [
                    'kayit' => $kayit,
                    'sonraki' => '/vis',
                ]);
            }

            if ($modul === 'foto') {
                $kayit = VisStore::kaydet($id, [
                    'fotograflar' => array_values(array_filter((array) Request::input('notlar'))),
                    'modul' => 'foto',
                ]);
                $this->ok('Fotoğraf notları kaydedildi.', ['kayit' => $kayit]);
            }

            throw new RuntimeException('Bilinmeyen modül.');
        });
    }
}
