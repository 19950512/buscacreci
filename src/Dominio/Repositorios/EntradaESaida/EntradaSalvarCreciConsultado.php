<?php

declare(strict_types=1);

namespace App\Dominio\Repositorios\EntradaESaida;

final readonly class EntradaSalvarCreciConsultado
{
	public function __construct(
		public string $codigo,
		public string $creci,
		public string $momento,
		public string $nomeCompleto,
		public string $cidade,
		public string $estado,
		public string $situacao,
		public string $numeroDocumento,
	) {}
}
