<?php

declare(strict_types=1);

namespace App\Aplicacao\CasosDeUso;

use App\Dominio\ObjetoValor\IdentificacaoUnica;
use App\Aplicacao\CasosDeUso\EntradaESaida\ErroDomain;
use App\Aplicacao\CasosDeUso\EntradaESaida\SaidaCreci;
use App\Aplicacao\CasosDeUso\EntradaESaida\SaidaCodigoSolicitacao;

interface ConsultarCreci
{

	public function consultarCreci(string $creci): IdentificacaoUnica | ErroDomain;

	public function consultarCreciPlataforma(IdentificacaoUnica $codigoSolicitacao): void;

	public function consultarCodigoSolicitacao(string $codigoSolicitacao): SaidaCodigoSolicitacao | ErroDomain;

	public function consultarCreciCodigo(string $codigoCreci): SaidaCreci | ErroDomain;
}