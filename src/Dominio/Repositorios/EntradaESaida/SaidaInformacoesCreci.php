<?php

declare(strict_types=1);

namespace App\Dominio\Repositorios\EntradaESaida;

final readonly class SaidaInformacoesCreci
{
	public function __construct(
		public string $creciCodigo,
		public string $creciCompleto,
		public string $creciEstado,
		public string $nomeCompleto,
		public string $atualizadoEm,
		public string $situacao,
		public string $cidade,
		public string $estado,
		public string $numeroDocumento,
	) {}
}
