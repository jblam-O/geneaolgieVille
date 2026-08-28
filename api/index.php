<?php

use App\Kernel;

$projectDir = dirname(__DIR__);

$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = $_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? 'prod';
$_SERVER['APP_DEBUG'] = $_ENV['APP_DEBUG'] = $_SERVER['APP_DEBUG'] ?? $_ENV['APP_DEBUG'] ?? '0';
$_SERVER['APP_SECRET'] = $_ENV['APP_SECRET'] = $_SERVER['APP_SECRET'] ?? $_ENV['APP_SECRET'] ?? 'public-demo-only';
$_SERVER['DATABASE_URL'] = $_ENV['DATABASE_URL'] = $_SERVER['DATABASE_URL']
    ?? $_ENV['DATABASE_URL']
    ?? 'sqlite:///'.$projectDir.'/data/demo.sqlite';

require_once $projectDir.'/vendor/autoload_runtime.php';

return static function (array $context): Kernel {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
