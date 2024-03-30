<?php

declare(strict_types=1);

namespace App\Dominio\ObjetoValor\Endereco\Localizacao;

use App\Dominio\ObjetoValor\Endereco\Localizacao\Latitude;
use App\Dominio\ObjetoValor\Endereco\Localizacao\Longitude;

final readonly class Localizacao
{
    public function __construct(
        private Latitude $latitude,
        private Longitude $longitude
    ){}

    public function getLatitude(): Latitude
    {
        return $this->latitude;
    }

    public function getLongitude(): Longitude
    {
        return $this->longitude;
    }

    public function get(): array
    {
        return [
            'latitude' => $this->latitude->get(),
            'longitude' => $this->longitude->get(),
        ];
    }
}