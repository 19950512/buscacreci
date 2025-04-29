<?php

declare(strict_types=1);

namespace App\Aplicacao\CasosDeUso\EntradaESaida;

final readonly class SaidaCreci
{
	public function __construct(
		public string $creciID,
		public string $creciCompleto,
		public string $creciEstado,
		public string $nomeCompleto,
		public string $atualizadoEm,
		public string $situacao,
		public string $cidade,
		public string $estado,
		public string $numeroDocumento,
		public string $data,
	){}
}
