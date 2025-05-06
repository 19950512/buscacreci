<?php

declare(strict_types=1);

namespace App\Dominio\Repositorios\EntradaESaida;

final readonly class EntradaSalvarNovaConsulta
{
	public function __construct(
		public string $codigoSolicitacao,
		public string $creci,
		public string $momento,
		public string $situacao,
	) {}
}
