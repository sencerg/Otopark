<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Log;
use App\Core\Request;
use App\Core\View;
use RuntimeException;

final class HesapController extends Controller
{
    public function index(): void
    {
        $this->view('hesap/index', ['pageTitle' => 'Hesabım', 'user' => Auth::user(), 'breadcrumb' => ['Hesabım' => null]]);
        unset($_SESSION['_old']);
    }

    public function update(): void
    {
        $this->form(function () {
            $ad = Request::str('ad');
            $soyad = Request::str('soyad');
            $mail = mb_strtolower((string) Request::str('mail'));
            if (!$ad || !$soyad || !filter_var($mail, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Ad, soyad ve geçerli bir e-posta zorunludur.');
            }
            $data = ['name' => $ad . ' ' . $soyad, 'email' => $mail, 'telefon' => Request::str('telefon')];

            $sifre = (string) Request::input('sifre', '');
            if ($sifre !== '') {
                if (mb_strlen($sifre) < 6) {
                    throw new RuntimeException('Şifre en az 6 karakter olmalıdır.');
                }
                if ($sifre !== (string) Request::input('sifre_tekrari', '')) {
                    throw new RuntimeException('Şifre ve şifre tekrarı aynı değil.');
                }
                $data['password_hash'] = password_hash($sifre, PASSWORD_DEFAULT);
            }

            $file = $_FILES['resim'] ?? null;
            if ($file && $file['error'] === UPLOAD_ERR_OK && is_uploaded_file($file['tmp_name'])) {
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true) || $file['size'] > 5 * 1024 * 1024) {
                    throw new RuntimeException('Profil görseli JPG/PNG/WEBP ve en fazla 5 MB olmalıdır.');
                }
                $dir = BASE_PATH . '/public/uploads/profil';
                if (!is_dir($dir)) {
                    mkdir($dir, 0775, true);
                }
                $name = bin2hex(random_bytes(10)) . '.' . $ext;
                move_uploaded_file($file['tmp_name'], $dir . '/' . $name);
                $data['resim'] = '/uploads/profil/' . $name;
            }

            $set = implode(', ', array_map(fn ($c) => "{$c} = :{$c}", array_keys($data)));
            Database::query("UPDATE users SET {$set}, updated_at = NOW() WHERE id = :_id", $data + ['_id' => Auth::id()]);
            Log::islem('kullanici', 'Hesap bilgileri güncellendi', Auth::id());
            flash('success', 'Hesap bilgileriniz güncellendi.');
            View::redirect('/kullanici/hesabim');
        }, '/kullanici/hesabim');
    }
}
