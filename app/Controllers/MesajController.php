<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Upload;
use App\Core\View;
use App\Services\BildirimMerkezi;
use App\Services\MesajMerkezi;
use App\Services\SurumKatalog;
use RuntimeException;

final class MesajController extends Controller
{
    public function index(): void
    {
        $this->izin();
        $uid = (int) Auth::id();
        View::render('mesaj/index', [
            'pageTitle' => 'Mesaj Merkezi',
            'breadcrumb' => ['Mesaj Merkezi' => null],
            'konusmalar' => MesajMerkezi::liste(),
            'bildirimler' => Database::fetchAll(
                'SELECT * FROM bildirimler WHERE user_id = :u ORDER BY id DESC LIMIT 20',
                ['u' => $uid]
            ),
            'okunmamis' => (int) Database::fetch(
                'SELECT COUNT(*) AS c FROM bildirimler WHERE user_id = :u AND okundu_at IS NULL',
                ['u' => $uid]
            )['c'],
        ], $this->yerlesim());
    }

    public function ozet(): void
    {
        $this->izin();
        $say = (int) Database::fetch(
            'SELECT COUNT(*) AS c FROM bildirimler WHERE user_id = :u AND okundu_at IS NULL',
            ['u' => Auth::id()]
        )['c'];
        View::json(['success' => true, 'okunmamis' => $say]);
    }

    public function okundu(): void
    {
        $this->izin();
        $id = Request::int('id');
        if ($id) {
            Database::query(
                'UPDATE bildirimler SET okundu_at = COALESCE(okundu_at, NOW()) WHERE id = :id AND user_id = :u',
                ['id' => $id, 'u' => Auth::id()]
            );
        } else {
            Database::query(
                'UPDATE bildirimler SET okundu_at = NOW() WHERE user_id = :u AND okundu_at IS NULL',
                ['u' => Auth::id()]
            );
        }
        View::redirect('/mesaj');
    }

    public function yeni(): void
    {
        $this->izin();
        View::render('mesaj/yeni', [
            'pageTitle' => 'Yeni konu',
            'breadcrumb' => ['Mesaj Merkezi' => '/mesaj', 'Yeni konu' => null],
            'bayiler' => Auth::isAdmin() ? Database::fetchAll('SELECT id, ad FROM bayiler WHERE aktif ORDER BY ad') : [],
            'musteriler' => Auth::isAdmin() ? Database::fetchAll('SELECT id, ad FROM musteriler WHERE aktif ORDER BY ad') : [],
            'sorumlular' => Auth::isAdmin() ? Database::fetchAll("SELECT id, name FROM users WHERE is_active AND role = 'admin' ORDER BY name") : [],
        ], $this->yerlesim());
    }

    public function ac(): void
    {
        $this->form(function () {
            $this->izin();
            [$bayiId, $musteriId] = $this->taraf();
            $sorumlu = Auth::isAdmin() ? Request::int('sorumlu_id') : null;
            $acilan = MesajMerkezi::ac(
                (int) Auth::id(),
                (string) Request::str('tur'),
                (string) Request::str('konu'),
                (string) Request::str('metin'),
                $bayiId,
                $musteriId,
                $sorumlu,
                (string) (Request::str('oncelik') ?? 'normal'),
                $this->aracId($bayiId, $musteriId)
            );
            Upload::save('dosya', 'konusma', (int) $acilan['mesaj']);
            flash('success', 'Konu açıldı.');
            View::redirect('/mesaj/' . $acilan['konusma']);
        }, '/mesaj/yeni');
    }

    public function goster(string $id): void
    {
        $this->izin();
        $konusma = MesajMerkezi::getir((int) $id);
        foreach ($konusma['mesajlar'] as &$mesaj) {
            $mesaj['dosyalar'] = Upload::list('konusma', (int) $mesaj['id']);
        }
        unset($mesaj);
        View::render('mesaj/konusma', [
            'pageTitle' => $konusma['kod'] . ' · ' . $konusma['konu'],
            'breadcrumb' => ['Mesaj Merkezi' => '/mesaj', $konusma['kod'] => null],
            'konusma' => $konusma,
            'sorumlular' => Auth::isAdmin() ? Database::fetchAll("SELECT id, name FROM users WHERE is_active AND role = 'admin' ORDER BY name") : [],
        ], $this->yerlesim());
    }

    public function yaz(string $id): void
    {
        $this->form(function () use ($id) {
            $this->izin();
            $mesajId = MesajMerkezi::yaz((int) $id, (int) Auth::id(), (string) Request::str('metin'));
            Upload::save('dosya', 'konusma', $mesajId);
            flash('success', 'Mesaj gönderildi.');
            View::redirect('/mesaj/' . (int) $id);
        }, '/mesaj/' . (int) $id);
    }

    public function durum(string $id): void
    {
        $this->form(function () use ($id) {
            $this->izin();
            MesajMerkezi::durum((int) $id, (string) Request::str('durum'), Request::int('sorumlu_id'));
            flash('success', 'Konu güncellendi.');
            View::redirect('/mesaj/' . (int) $id);
        }, '/mesaj/' . (int) $id);
    }

    public function duyuru(): void
    {
        $this->izin();
        if (!Auth::isAdmin()) {
            http_response_code(403);
            exit('Duyuruları yalnız Moex hazırlar.');
        }
        $surumler = SurumKatalog::liste();
        $secili = (string) ($_GET['surum'] ?? ($surumler[0]['surum'] ?? ''));
        View::render('mesaj/duyuru', [
            'pageTitle' => 'Sürüm duyurusu',
            'breadcrumb' => ['Mesaj Merkezi' => '/mesaj', 'Duyuru' => null],
            'surumler' => $surumler,
            'secili' => $secili,
            'gruplar' => Database::fetchAll('SELECT * FROM duyuru_gruplari ORDER BY id'),
            'kullanicilar' => Database::fetchAll("SELECT id, name, email, role FROM users WHERE is_active ORDER BY name"),
            'bayiler' => Database::fetchAll('SELECT id, ad FROM bayiler WHERE aktif ORDER BY ad'),
            'musteriler' => Database::fetchAll('SELECT id, ad FROM musteriler WHERE aktif ORDER BY ad'),
            'gecmis' => Database::fetchAll(
                'SELECT d.*, u.name AS gonderen FROM surum_duyurulari d LEFT JOIN users u ON u.id = d.gonderen_id ORDER BY d.id DESC LIMIT 30'
            ),
        ], 'main');
    }

    public function duyurGonder(): void
    {
        $this->form(function () {
            $this->izin();
            if (!Auth::isAdmin()) {
                throw new RuntimeException('Duyuruları yalnız Moex gönderir.');
            }
            $surum = trim((string) Request::str('surum'));
            $kayit = null;
            foreach (SurumKatalog::liste() as $satir) {
                if ($satir['surum'] === $surum) {
                    $kayit = $satir;
                    break;
                }
            }
            if (!$kayit) {
                throw new RuntimeException('Sürüm proje notlarında yok.');
            }
            $metin = trim((string) Request::str('metin'));
            if ($metin === '') {
                $metin = implode("\n", array_map(static fn ($m) => '• ' . $m, $kayit['maddeler']));
            }
            $sonuc = BildirimMerkezi::surumDuyur([
                'surum' => $surum,
                'baslik' => trim((string) (Request::str('baslik') ?: $kayit['baslik'])),
                'metin' => $metin,
                'zorunlu' => Request::bool('zorunlu'),
                'yeniden' => Request::bool('yeniden'),
                'gonderen_id' => Auth::id(),
            ], $this->secim());
            flash('success', $sonuc['alici'] . ' kişiye bildirim yazıldı. ' . $sonuc['eposta'] . ' e-posta kuyruğa alındı.');
            View::redirect('/mesaj/duyuru');
        }, '/mesaj/duyuru');
    }

    public function gruplar(): void
    {
        $this->izin();
        if (!Auth::isAdmin()) {
            http_response_code(403);
            exit('Duyuru gruplarını yalnız Moex yönetir.');
        }
        $grupId = (int) ($_GET['grup'] ?? 0);
        $grup = $grupId ? Database::fetch('SELECT * FROM duyuru_gruplari WHERE id = :id', ['id' => $grupId]) : null;
        $uyeler = [];
        if ($grup && empty($grup['sistem_kod'])) {
            $uyeler = array_map('intval', array_column(Database::fetchAll(
                'SELECT user_id FROM duyuru_grup_uyeleri WHERE grup_id = :g',
                ['g' => $grupId]
            ), 'user_id'));
        }
        View::render('mesaj/gruplar', [
            'pageTitle' => 'Duyuru grupları',
            'breadcrumb' => ['Mesaj Merkezi' => '/mesaj', 'Gruplar' => null],
            'gruplar' => Database::fetchAll(
                'SELECT g.*, (SELECT COUNT(*) FROM duyuru_grup_uyeleri u WHERE u.grup_id = g.id) AS uye_sayisi FROM duyuru_gruplari g ORDER BY g.id'
            ),
            'grup' => $grup,
            'uyeler' => $uyeler,
            'kullanicilar' => Database::fetchAll("SELECT id, name, email, role FROM users WHERE is_active ORDER BY name"),
        ], 'main');
    }

    public function grupKaydet(): void
    {
        $this->form(function () {
            $this->izin();
            if (!Auth::isAdmin()) {
                throw new RuntimeException('Duyuru gruplarını yalnız Moex yönetir.');
            }
            $ad = trim((string) Request::str('ad'));
            if ($ad === '') {
                throw new RuntimeException('Grup adı zorunludur.');
            }
            $id = Request::int('id');
            if ($id) {
                $grup = Database::fetch('SELECT * FROM duyuru_gruplari WHERE id = :id', ['id' => $id]);
                if (!$grup || !empty($grup['sistem_kod'])) {
                    throw new RuntimeException('Sistem gruplarının adı değişmez.');
                }
                Database::query(
                    'UPDATE duyuru_gruplari SET ad = :ad, aciklama = :ac WHERE id = :id',
                    ['ad' => $ad, 'ac' => Request::str('aciklama'), 'id' => $id]
                );
            } else {
                $id = (int) Database::fetch(
                    'INSERT INTO duyuru_gruplari (ad, aciklama) VALUES (:ad, :ac) RETURNING id',
                    ['ad' => $ad, 'ac' => Request::str('aciklama')]
                )['id'];
            }
            flash('success', 'Grup kaydedildi.');
            View::redirect('/mesaj/gruplar?grup=' . $id);
        }, '/mesaj/gruplar');
    }

    public function uyeleriKaydet(): void
    {
        $this->form(function () {
            $this->izin();
            if (!Auth::isAdmin()) {
                throw new RuntimeException('Duyuru gruplarını yalnız Moex yönetir.');
            }
            $id = (int) Request::int('id');
            $grup = Database::fetch('SELECT * FROM duyuru_gruplari WHERE id = :id', ['id' => $id]);
            if (!$grup || !empty($grup['sistem_kod'])) {
                throw new RuntimeException('Sistem gruplarına üye eklenmez. Alıcılar rolden gelir.');
            }
            $uyeler = Request::ids('user_id');
            Database::transaction(function () use ($id, $uyeler) {
                Database::query('DELETE FROM duyuru_grup_uyeleri WHERE grup_id = :g', ['g' => $id]);
                foreach ($uyeler as $userId) {
                    Database::query(
                        'INSERT INTO duyuru_grup_uyeleri (grup_id, user_id) VALUES (:g, :u)',
                        ['g' => $id, 'u' => $userId]
                    );
                }
            });
            flash('success', 'Grup üyeleri güncellendi.');
            View::redirect('/mesaj/gruplar?grup=' . $id);
        }, '/mesaj/gruplar');
    }

    private function izin(): void
    {
        if (Auth::isAdmin() || Auth::can('menu.mesaj') || Auth::can('musteri.mesaj')) {
            return;
        }
        Auth::requireIzin('menu.mesaj');
    }

    private function yerlesim(): string
    {
        return Auth::isMusteri() ? 'musteri' : 'main';
    }

    /** @return array{0:?int,1:?int} */
    private function taraf(): array
    {
        if (Auth::isMusteri()) {
            return [null, Auth::musteriId()];
        }
        if ($bayiId = Auth::bayiId()) {
            return [$bayiId, null];
        }
        $taraf = (string) Request::str('taraf');
        if ($taraf === 'bayi') {
            $id = Request::int('bayi_id');
            if (!$id) {
                throw new RuntimeException('Lokasyon seçilmelidir.');
            }

            return [$id, null];
        }
        if ($taraf === 'musteri') {
            $id = Request::int('musteri_id');
            if (!$id) {
                throw new RuntimeException('Firma seçilmelidir.');
            }

            return [null, $id];
        }

        return [null, null];
    }

    private function aracId(?int $bayiId, ?int $musteriId): ?int
    {
        $sase = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', (string) Request::str('sase')));
        if ($sase === '') {
            return null;
        }
        $arac = Database::fetch('SELECT id, bayi_id, musteri_id FROM araclar WHERE sase = :s', ['s' => $sase]);
        if (!$arac) {
            throw new RuntimeException('Şasi kayıtlı değil.');
        }
        if ($bayiId && (int) $arac['bayi_id'] !== $bayiId) {
            throw new RuntimeException('Şasi bu lokasyona ait değil.');
        }
        if ($musteriId && (int) $arac['musteri_id'] !== $musteriId) {
            throw new RuntimeException('Şasi bu firmaya ait değil.');
        }

        return (int) $arac['id'];
    }

    /** @return array{hedef:list<string>,grup_id:list<int>,user_id:list<int>,bayi_id:?int,musteri_id:?int} */
    private function secim(): array
    {
        $hedef = array_values(array_filter(array_map(
            'strval',
            (array) ($_POST['hedef'] ?? [])
        ), static fn ($v) => in_array($v, ['moex', 'bayi', 'musteri', 'herkes'], true)));

        return [
            'hedef' => $hedef,
            'grup_id' => Request::ids('grup_id'),
            'user_id' => Request::ids('user_id'),
            'bayi_id' => Request::int('bayi_id'),
            'musteri_id' => Request::int('musteri_id'),
        ];
    }
}
