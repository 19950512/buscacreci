<?php

declare(strict_types=1);

//namespace App\Infraestrutura\APIs\BuscaCorretor\Public;

use App\Infraestrutura\APIs\Router;

// CORS headers are handled by nginx (nginx_api.conf)
// Do not add CORS headers here to avoid duplication

session_start([
    'cookie_httponly' => true,
    'cookie_secure' => true,
    'cookie_samesite' => 'Lax',
    'use_strict_mode' => true,
]);

$container = require __DIR__ . '/../../../../Aplicacao/Compartilhado/Container.php';

new Router(
    request_uri: ($_SERVER['REQUEST_URI'] ?? ''),
    container: $container,
    apiName: 'BuscaCorretor'
);