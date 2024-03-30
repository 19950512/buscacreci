<?php

declare(strict_types=1);

namespace App\Aplicacao\CasosDeUso\EntradaESaida;

final readonly class SaidaCreci
{
	public function __construct(
		public string $inscricao,
		public string $nomeCompleto,
		public string $fantasia,
		public string $situacao,
		public string $cidade,
		public string $estado,
		public string $cpf,
	){}
}
