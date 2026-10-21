<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;

final class TemaController extends Controller
{
    public function index(): void
    {
        Auth::requireAdmin();
        $this->view('tema/onizleme', [
            'pageTitle' => 'Tema Önizleme',
            'breadcrumb' => ['Proje Notları' => null, 'Tema Önizleme' => null],
        ]);
    }
}
