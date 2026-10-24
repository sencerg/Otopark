<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Services\SurumKatalog;

final class SurumController extends Controller
{
    public function index(): void
    {
        Auth::requireIzin('menu.surumler');
        $liste = SurumKatalog::liste();
        $this->view('surumler/index', [
            'pageTitle' => 'Proje Notları — Sürümler',
            'breadcrumb' => ['Proje Notları' => null],
            'surumler' => $liste,
            'guncel' => $liste[0] ?? null,
        ]);
    }
}
