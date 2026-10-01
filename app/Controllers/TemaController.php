<?php

declare(strict_types=1);

namespace App\Controllers;

final class TemaController extends Controller
{
    public function index(): void
    {
        $this->view('tema/onizleme', [
            'pageTitle' => 'Tema Önizleme',
            'breadcrumb' => ['Proje Notları' => null, 'Tema Önizleme' => null],
        ]);
    }
}
