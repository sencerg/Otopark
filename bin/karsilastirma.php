<?php

declare(strict_types=1);

use App\Services\Karsilastirma;

require dirname(__DIR__) . '/bootstrap.php';

$hedef = BASE_PATH . '/docs/referans-md-karsilastirma.md';
file_put_contents($hedef, Karsilastirma::markdown());
echo "Yazıldı: docs/referans-md-karsilastirma.md\n";
