<?php

declare(strict_types=1);

namespace App\Aplicacao\CasosDeUso;

use App\Aplicacao\CasosDeUso\EntradaESaida\ErroDomain;
use App\Dominio\ObjetoValor\IdentificacaoUnica;

interface ConsultarCreci
{

	public function consultarCreci(string $creci): IdentificacaoUnica | ErroDomain;

	public function consultarCreciPlataforma(IdentificacaoUnica $codigoSolicitacao): void;
}