<?php

declare(strict_types=1);

namespace App\Aplicacao\CasosDeUso\EntradaESaida;

final readonly class SaidaCodigoSolicitacao
{
	public function __construct(
		public string $codigoSolicitacao,
		public string $status,
		public string $mensagem,
		public string $creciID,
		public string $creciCompleto,
	){}
}
