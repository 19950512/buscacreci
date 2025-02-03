<?php

declare(strict_types=1);

namespace App\Dominio\Entidades\ConselhoNacionalCRECI;

use App\Dominio\ObjetoValor\Endereco\Estado;

final readonly class MembroDoConselho
{
    public function __construct(
        public Estado $estado,
        public bool $ativo
    ){}
}