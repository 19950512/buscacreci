<?php

declare(strict_types=1);

namespace App\Dominio\ObjetoValor;

final readonly class Descricao
{
    function __construct(
        private string $texto = ''
    ){}

    function get(): string
    {
        return $this->texto;
    }
}