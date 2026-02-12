<?php

declare(strict_types=1);

session_start();

require __DIR__ . '/Core/Autoloader.php';

\App\Core\Autoloader::register();

$config = require __DIR__ . '/../config/app.php';
\App\Core\App::setConfig($config);

$timezone = (string) (\App\Core\App::config('timezone', 'UTC'));
if ($timezone !== '') {
    date_default_timezone_set($timezone);
}

$databaseConfig = require __DIR__ . '/../config/database.php';
\App\Core\DB::configure($databaseConfig);

\App\Core\Csrf::boot();
