<?php

declare(strict_types=1);

namespace App\Aplicacao\CasosDeUso;

use App\Aplicacao\CasosDeUso\EntradaESaida\SaidaCreci;

interface ConsultarCreci
{

	public function consultarCreci(string $creci): SaidaCreci;
}