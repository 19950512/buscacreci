<?php

declare(strict_types=1);

namespace App\Infraestrutura\APIs\Autenticacao\Public;

use App\Infraestrutura\APIs\Router;

$container = require __DIR__ . '/../../../../Aplicacao/Compartilhado/Container.php';

new Router(
    request_uri: $_SERVER['REQUEST_URI'] ?? '',
    container: $container,
    apiName: 'BuscaCorretor'
);