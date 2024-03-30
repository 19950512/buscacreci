<?php

declare(strict_types=1);

namespace App\Aplicacao\CasosDeUso;

use App\Aplicacao\CasosDeUso\EntradaESaida\SaidaConsultarCreciPlataforma;

interface PlataformaCreci
{
	public function consultarCreci(string $creci): SaidaConsultarCreciPlataforma;
}
