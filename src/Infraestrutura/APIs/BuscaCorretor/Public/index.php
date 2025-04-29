<?php

declare(strict_types=1);

//namespace App\Infraestrutura\APIs\BuscaCorretor\Public;

use App\Infraestrutura\APIs\Router;

$allowedOrigins = [
    'https://buscacreci.com.br',
    'http://localhost:8052',
    'http://localhost:3000',
];

session_start();

if (in_array(($_SERVER['HTTP_ORIGIN'] ?? ''), $allowedOrigins)) {
    header("Access-Control-Allow-Origin: " . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
}

header("Access-Control-Allow-Methods: GET, POST, OPTIONS"); // Métodos permitidos
header("Access-Control-Allow-Headers: Content-Type, Authorization"); // Cabeçalhos permitidos

// Se for uma requisição OPTIONS (preflight), responde com 200 e encerra
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$container = require __DIR__ . '/../../../../Aplicacao/Compartilhado/Container.php';

new Router(
    request_uri: ($_SERVER['REQUEST_URI'] ?? ''),
    container: $container,
    apiName: 'BuscaCorretor'
);