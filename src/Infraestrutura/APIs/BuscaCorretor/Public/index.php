<?php

declare(strict_types=1);

//namespace App\Infraestrutura\APIs\BuscaCorretor\Public;

use App\Infraestrutura\APIs\Router;

header("Access-Control-Allow-Origin: https://buscacreci.com.br"); // Permitir apenas buscacreci.com.br
header("Access-Control-Allow-Methods: GET, POST, OPTIONS"); // Métodos permitidos
header("Access-Control-Allow-Headers: Content-Type, Authorization"); // Cabeçalhos permitidos

// Se for uma requisição OPTIONS (preflight), responde com 200 e encerra
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$container = require __DIR__ . '/../../../../Aplicacao/Compartilhado/Container.php';

new Router(
    request_uri: $_SERVER['REQUEST_URI'] ?? '',
    container: $container,
    apiName: 'BuscaCorretor'
);