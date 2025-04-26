<?php

declare(strict_types=1);

namespace App\Aplicacao\CasosDeUso\EntradaESaida;

final class ErroDomain
{
    public function __construct(
        public string $mensagem,
        public int $codigo,
    ){}
}