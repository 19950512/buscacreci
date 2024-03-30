<?php

declare(strict_types=1);

namespace App\Dominio\ObjetoValor;

use Exception;

final readonly class Telefone
{

	private string $value;
    function __construct(
        private string $numero
    ){

		$numero = $this->numero;
        if(!$this->_validation($numero)){
            throw new Exception('O número do telefone informado ("'.$numero.'") não é válido.');
        }

        $numero = (new Mascara(
            texto: $numero,
            mascara: '(##) #####-####'
        ))->get();

		$this->value = $numero;
    }

    private function _validation(string $number): bool
    {
        return !!preg_match("/^\((\d{2})?\)?|(\d{2})? ?9\d{4}-?\d{4}$/i", $number);
    }

    function get(): string
    {
        return $this->value;
    }
}