<?php

declare(strict_types=1);

namespace App\Dominio\ObjetoValor;

final readonly class Ativo
{
    public function __construct(
        private bool $ativo
    ){}

    function get(): bool
    {
        return $this->ativo;
    }
}