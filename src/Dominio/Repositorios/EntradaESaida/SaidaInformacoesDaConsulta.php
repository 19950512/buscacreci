<?php

declare(strict_types=1);

namespace App\Dominio\Repositorios\EntradaESaida;

final readonly class SaidaInformacoesDaConsulta
{
	public function __construct(
		public string $codigoSolicitacao,
		public string $creciCompleto,
	) {}
}
