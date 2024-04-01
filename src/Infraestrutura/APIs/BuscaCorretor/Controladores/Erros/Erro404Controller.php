<?php

declare(strict_types=1);

namespace App\Infraestrutura\APIs\BuscaCorretor\Controladores\Erros;

use DI\Container;

final readonly class Erro404Controller
{

    public function __construct(
	    private Container $container
    ){}

    public function index()
    {
        header("HTTP/1.0 404 Not Found");
    }
}

