<?php

declare(strict_types=1);

namespace App\Dominio\ObjetoValor\Endereco\Localizacao;

use Exception;

final readonly class Longitude
{
    public function __construct(
        private float $longitude
    )
    {
        if(!is_numeric($this->longitude) || ($this->longitude < -180 || $this->longitude > 180)){
            throw new Exception("Longitude informada não é válida. (".$this->longitude.")");
        }
    }

    public function get(): float
    {
        return $this->longitude;
    }
}