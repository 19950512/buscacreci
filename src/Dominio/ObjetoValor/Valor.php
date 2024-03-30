<?php

declare(strict_types=1);

namespace App\Dominio\ObjetoValor;

final readonly class Valor
{

    public function __construct(
        private float $valor
    ){}

    function get(): float
    {
        return $this->valor;
    }
}