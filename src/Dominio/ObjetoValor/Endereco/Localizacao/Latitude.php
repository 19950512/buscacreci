<?php

declare(strict_types=1);

namespace App\Dominio\ObjetoValor\Endereco\Localizacao;

use Exception;

final readonly class Latitude
{
    public function __construct(
        private float $latitude
    )
    {

        if(!is_numeric($this->latitude) || ($this->latitude < -90 || $this->latitude > 90)){
            throw new Exception("Latitude informada não é válida. (".$this->latitude.")");
        }
    }

    public function get(): float
    {
        return $this->latitude;
    }
}