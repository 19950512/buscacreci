<?php

declare(strict_types=1);

namespace App\Dominio\ObjetoValor;

interface DocumentoIdentificacao
{
    static function valido(string $numeroDocumento): bool;
    function get(): string;
}