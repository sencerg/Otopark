<?php

declare(strict_types=1);

use App\Services\Eposta;

require dirname(__DIR__) . '/bootstrap.php';

$n = Eposta::isle(50);
echo $n . " e-posta işlendi.\n";
