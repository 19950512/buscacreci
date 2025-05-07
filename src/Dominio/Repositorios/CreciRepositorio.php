<?php

declare(strict_types=1);

namespace App\Dominio\Repositorios;

use App\Dominio\Repositorios\EntradaESaida\SaidaInformacoesCreci;
use App\Dominio\Repositorios\EntradaESaida\EntradaSalvarNovaConsulta;
use App\Dominio\Repositorios\EntradaESaida\SaidaInformacoesDaConsulta;
use App\Dominio\Repositorios\EntradaESaida\EntradaSalvarCreciConsultado;

interface CreciRepositorio
{
	public function creciJaFoiConsultadoAntes(string $creci): bool;
	public function buscarInformacoesCreci(string $creciCodigo = '', string $creciCompleto = ''): SaidaInformacoesCreci;
	public function salvarCreciConsultado(EntradaSalvarCreciConsultado $parametros): void;
	public function salvarNovaConsulta(EntradaSalvarNovaConsulta $parametros): void;
	public function getConsultaByCodigoSolicitacao(string $codigoSolicitacao): SaidaInformacoesDaConsulta;
	public function atualizarConsultaCodigoSolicitacao(string $codigoSolicitacao, string $situacao, string $momento, string $creciCodigo, string $mensagemSucesso = '', string $mensagemErro = ''): void;
}