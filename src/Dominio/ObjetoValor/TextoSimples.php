<?php

declare(strict_types=1);

namespace App\Dominio\ObjetoValor;

final readonly class TextoSimples
{
	private string $value;
    public function __construct(
        private string $string
    ){
        $string = trim($this->string);
        $string = strip_tags($string);
        $string = htmlspecialchars($string);
		$this->value = $string;
    }

    function get(): string
    {
        return $this->value;
    }
}