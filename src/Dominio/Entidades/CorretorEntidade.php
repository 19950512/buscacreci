<?php

declare(strict_types=1);

namespace App\Dominio\Entidades;

class CorretorEntidade implements AgenteImobiliario
{
	public function __construct(
		public string $creci,
		public string $nomeCompleto,
		public string $fantasia,
		public string $situacao,
		public string $cidade,
		public string $estado,
	){}
}