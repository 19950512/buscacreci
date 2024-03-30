<?php

declare(strict_types=1);

namespace App\Dominio\ObjetoValor\Endereco;

use App\Dominio\ObjetoValor\Mascara;
use Exception;

final class CEP
{
    public function __construct(
        private string $cep
    )
    {
        if(!self::validation($this->cep)){
            throw new Exception('O CEP informado não é válido. ('.$this->cep.')');
        }

        $this->cep = (new Mascara($this->cep, '#####-###'))->get();
    }

    public function get(): string
    {
        return $this->cep;
    }

    public static function validation(string $cep): bool
    {
        return !!preg_match('/^[0-9]{5,5}([- ]?[0-9]{3,3})?$/', $cep);
    }
}