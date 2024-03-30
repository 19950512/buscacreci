<?php

declare(strict_types=1);

namespace App\Aplicacao\CasosDeUso;

use App\Aplicacao\CasosDeUso\EntradaESaida\SaidaCreci;
use App\Aplicacao\CasosDeUso\Enums\CreciImplementado;
use App\Aplicacao\Compartilhado\Cache;
use App\Dominio\Entidades\CreciEntidade;
use App\Dominio\ObjetoValor\Creci;
use App\Dominio\ObjetoValor\Endereco\Estado;
use App\Dominio\ObjetoValor\IdentificacaoUnica;
use App\Dominio\Repositorios\CreciRepositorio;
use App\Dominio\Repositorios\EntradaESaida\EntradaSalvarCreciConsultado;
use App\Dominio\Repositorios\EntradaESaida\SaidaInformacoesCreci;
use App\Infraestrutura\Adaptadores\PlataformasCreci\CreciRJPlataformaImplementacao;
use App\Infraestrutura\Adaptadores\PlataformasCreci\CreciRSPlataformaImplementacao;
use Cassandra\Uuid;
use Exception;

class ConsultarCreciImplementacao implements ConsultarCreci
{
	public function __construct(
		readonly private CreciRepositorio $creciRepositorio,
		readonly private Cache $cache
	) {}

	public function consultarCreci(string $creci): SaidaCreci
	{

		$estadosDoBrasil = Estado::getEstados();

		$estadoEntity = new Estado('NN');
		$tipoCreci = str_contains($creci, 'J') ? 'J' : 'F';
		foreach($estadosDoBrasil as $estado => $nomeCompletoEstado){
			$creciTemp = strtoupper($creci);
			if(str_contains($creciTemp, $estado)){

				try {
					$estadoEntity = new Estado($estado);
					break;
				}catch (Exception $e){
					throw new Exception("Ainda não implementamos o estado informado. $estado");
				}
			}
		}

		if($estadoEntity->getUF() == 'NN'){
			throw new Exception('Informe o estado no Creci. Exemplo: RS 12345');
		}

		$creciImplementado = CreciImplementado::tryFrom($estadoEntity->getUF());
		if(!is_a($creciImplementado, CreciImplementado::class)){
			throw new Exception("Ainda não implementamos o estado informado. {$estadoEntity->getFull()} - ({$estadoEntity->getUF()})");
		}

		$plataformaCreci = match ($creciImplementado) {
			CreciImplementado::RS => new CreciRSPlataformaImplementacao(),
			CreciImplementado::RJ => new CreciRJPlataformaImplementacao(),
			default => throw new Exception("Ainda não implementamos o estado informado! {$estadoEntity->getFull()} - ({$estadoEntity->getUF()})"),
		};

		$numeroInscricao = preg_replace('/[^0-9]/', '', $creci);

		$numeroCreciMontado = "CRECI/{$estadoEntity->getUF()} {$numeroInscricao}-{$tipoCreci}";

		if($this->creciRepositorio->creciJaFoiConsultadoAntes($numeroCreciMontado)){

			$creciData = $this->creciRepositorio->buscarInformacoesCreci($numeroCreciMontado);

			$saidaCreci = new SaidaCreci(
				creciID: $creciData->creciCodigo,
				creciCompleto: $creciData->creciCompleto,
				creciEstado: $creciData->creciCompleto,
				nomeCompleto: $creciData->nomeCompleto,
				atualizadoEm: $creciData->atualizadoEm,
				situacao: $creciData->situacao,
				cidade: $creciData->cidade,
				estado: $creciData->estado,
				numeroDocumento: $creciData->numeroDocumento,
			);

			return $saidaCreci;
		}

		try {
			$resposta = $plataformaCreci->consultarCreci($numeroInscricao);
		}catch (Exception $e){
			throw new Exception("O número de inscrição {$numeroInscricao} não foi encontrado no CRECI {$creciImplementado->value}.");
		}

		$tipoCreciFantasia = 'J';
		if(empty($resposta->fantasia)){
			$tipoCreciFantasia = 'F';
		}
		$paramsBuildCreciEntidade = new SaidaInformacoesCreci(
			creciCodigo: (new IdentificacaoUnica())->get(),
			creciCompleto: "CRECI/{$estadoEntity->getUF()} {$numeroInscricao}-{$tipoCreciFantasia}",
			creciEstado: $resposta->estado,
			nomeCompleto: $resposta->nomeCompleto,
			atualizadoEm: date('Y-m-d H:i:s'),
			situacao: $resposta->situacao,
			cidade: $resposta->cidade,
			estado: $resposta->estado,
			numeroDocumento: $resposta->numeroDocumento,
 		);
		$creciEntity = CreciEntidade::build($paramsBuildCreciEntidade);

		$parametrosSalvarCreciConsultado = new EntradaSalvarCreciConsultado(
			codigo: $creciEntity->codigo->get(),
			creci: $creciEntity->creci->get(),
			momento: $creciEntity->atualizadoEm->format('Y-m-d H:i:s'),
			nomeCompleto: $creciEntity->nomeCompleto->get(),
			cidade: $creciEntity->cidade->get(),
			estado: $creciEntity->estado->get(),
			situacao: $creciEntity->situacao->get() ? 'Ativo' : 'Inativo',
			numeroDocumento: $creciEntity->numeroDocumento->get(),
		);

		$this->creciRepositorio->salvarCreciConsultado($parametrosSalvarCreciConsultado);

		return new SaidaCreci(
			creciID: $creciEntity->codigo->get(),
			creciCompleto: $creciEntity->creci->get(),
			creciEstado: $creciEntity->estado->get(),
			nomeCompleto: $creciEntity->nomeCompleto->get(),
			atualizadoEm: $creciEntity->atualizadoEm->format('Y-m-d H:i:s'),
			situacao: $creciEntity->situacao->get() ? 'Ativo' : 'Inativo',
			cidade: $creciEntity->cidade->get(),
			estado: $creciEntity->estado->get(),
			numeroDocumento: $creciEntity->numeroDocumento->get(),
		);
	}
}
