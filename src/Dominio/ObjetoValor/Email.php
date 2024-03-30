<?php

declare(strict_types=1);

namespace App\Dominio\ObjetoValor;

use Exception;

final readonly class Email
{
	private string $value;
    public function __construct(
        private string $email
    ){
        if(!filter_var($this->email, FILTER_VALIDATE_EMAIL)){
            throw new Exception("O e-mail informado não é válido. (".$this->email.")");
        }

        $this->value = strtolower($this->email);
    }

    function get(): string
    {
        return $this->value;
    }
}