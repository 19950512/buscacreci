<?php

declare(strict_types=1);

namespace App\Dominio\ObjetoValor;

class DocumentoNaoInformado implements DocumentoIdentificacao
{
	static function valido(string $numeroDocumento): bool
	{
		return true;
	}
	function get(): string
	{
		return '';
	}
}
