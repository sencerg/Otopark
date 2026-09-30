<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Core\Auth;

/** @var \App\Core\Router $router */

$guest = [[Auth::class, 'requireGuest']];
$auth = [[Auth::class, 'requireLogin']];

$router->get('/login', [AuthController::class, 'showLogin'], $guest);
$router->post('/login', [AuthController::class, 'login'], $guest);
$router->post('/logout', [AuthController::class, 'logout'], $auth);

$router->get('/', [DashboardController::class, 'index'], $auth);
