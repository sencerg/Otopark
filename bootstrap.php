<?php

declare(strict_types=1);

use App\Core\Env;

define('BASE_PATH', __DIR__);

require BASE_PATH . '/vendor/autoload.php';
require BASE_PATH . '/app/Core/helpers.php';

Env::load(BASE_PATH . '/.env');

date_default_timezone_set('Europe/Istanbul');
mb_internal_encoding('UTF-8');

error_reporting(E_ALL);
ini_set('display_errors', Env::get('APP_DEBUG', false) ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', BASE_PATH . '/storage/logs/php-error.log');
