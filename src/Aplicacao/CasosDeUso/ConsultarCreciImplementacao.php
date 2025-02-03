<?php

declare(strict_types=1);

namespace App\Aplicacao\CasosDeUso;

use Override;
use Exception;
use App\Aplicacao\Compartilhado\Cache;
use App\Dominio\Entidades\CreciEntidade;
use App\Dominio\ObjetoValor\Endereco\Estado;
use App\Dominio\Repositorios\CreciRepositorio;
use App\Dominio\ObjetoValor\IdentificacaoUnica;
use App\Aplicacao\Compartilhado\Discord\Discord;
use App\Aplicacao\CasosDeUso\Enums\CreciImplementado;
use App\Aplicacao\CasosDeUso\EntradaESaida\SaidaCreci;
use App\Aplicacao\Compartilhado\Discord\Enums\CanalTexto;
use App\Dominio\Repositorios\EntradaESaida\SaidaInformacoesCreci;
use App\Dominio\Entidades\ConselhoNacionalCRECI\ConselhoNacionalCRECI;
use App\Dominio\Repositorios\EntradaESaida\EntradaSalvarCreciConsultado;
use App\Aplicacao\CasosDeUso\EntradaESaida\SaidaConsultarCreciPlataforma;
use App\Infraestrutura\Adaptadores\PlataformasCreci\ES\CreciESPlataformaImplementacao;
use App\Infraestrutura\Adaptadores\PlataformasCreci\PR\CreciPRPlataformaImplementacao;
use App\Infraestrutura\Adaptadores\PlataformasCreci\RS\CreciRSPlataformaImplementacao;
use App\Infraestrutura\Adaptadores\PlataformasCreci\Conselho\CreciConselhoPlataformaImplementacao;

readonly final class ConsultarCreciImplementacao implements ConsultarCreci
{
	public function __construct(
		private CreciRepositorio $creciRepositorio,
		private Discord $discord,
		private Cache $cache
	) {}

	#[Override] public function consultarCreci(string $creci): SaidaCreci
	{

		$estadosDoBrasil = Estado::getEstados();
		$estadoEntity = $this->encontrarEstadoPorCreci($estadosDoBrasil, $creci);

		if($estadoEntity->getUF() == 'NN'){
			$mensagem = 'Informe o estado no Creci. Exemplo: RS 12345';
			$this->discord->enviarMensagem(
				canalTexto: CanalTexto::CONSULTAS, 
				mensagem: $mensagem
			);
			throw new Exception($mensagem);
		}

		$creciTemporario = strtoupper($creci);
		// Vamos remover o estado do creci
		$creciTemporario = str_replace($estadoEntity->getUF(), '', $creciTemporario);
		$tipoCreci = str_contains($creciTemporario, 'J') ? 'J' : 'F';

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

			$this->discord->enviarMensagem(
				canalTexto: CanalTexto::CONSULTAS, 
				mensagem: "Creci {$numeroCreciMontado} já foi consultado antes e está em cache.\nResposta:\n```json\n".json_encode($saidaCreci, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."```"
			);

			return $saidaCreci;
		}

		$resposta = $this->consultarCreciNaPlataforma(
			estadoEntity: $estadoEntity,
			numeroInscricao: $numeroInscricao,
			tipoCreci: $tipoCreci
		);
		
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

		$saidaCreci = new SaidaCreci(
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

		$this->discord->enviarMensagem(
			canalTexto: CanalTexto::CONSULTAS, 
			mensagem: "Creci {$numeroCreciMontado} foi consultado e salvo no banco de dados.\nResposta:\n```json\n".json_encode($saidaCreci, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."```"
		);

		return $saidaCreci;
	}

	private function consultarCreciNaPlataforma(Estado $estadoEntity, string $numeroInscricao, string $tipoCreci): SaidaConsultarCreciPlataforma
	{

		$conselhoNacionalCRECI = new ConselhoNacionalCRECI();

		if($conselhoNacionalCRECI->estadoPossuiMembroAtivo($estadoEntity->getUF())){
			$plataformaCreci = new CreciConselhoPlataformaImplementacao(
				uf: $estadoEntity->getUF(),
			);

			return $plataformaCreci->consultarCreci(
				creci: $numeroInscricao,
				tipoCreci: $tipoCreci
			);
		}

		$creciImplementado = CreciImplementado::tryFrom($estadoEntity->getUF());
		if(!is_a($creciImplementado, CreciImplementado::class)){
			$mensagem = "Ainda não implementamos o estado informado. {$estadoEntity->getFull()} - ({$estadoEntity->getUF()})";

			$this->discord->enviarMensagem(
				canalTexto: CanalTexto::CONSULTAS, 
				mensagem: $mensagem
			);
			throw new Exception($mensagem);
		}

		$plataformaCreci = match ($creciImplementado) {
			CreciImplementado::RS => new CreciRSPlataformaImplementacao(),
			CreciImplementado::ES => new CreciESPlataformaImplementacao(),
			default => throw new Exception("Ainda não implementamos o estado informado! {$estadoEntity->getFull()} - ({$estadoEntity->getUF()})"),
		};

		try {

			$resposta = $plataformaCreci->consultarCreci(
				creci: $numeroInscricao,
				tipoCreci: $tipoCreci
			);

			return $resposta;

		}catch (Exception $e){

			$mensagem = "O número de inscrição {$numeroInscricao} não foi encontrado no CRECI {$creciImplementado->value}. - ".$e->getMessage();

			$this->discord->enviarMensagem(
				canalTexto: CanalTexto::CONSULTAS, 
				mensagem: $mensagem
			);

			throw new Exception($mensagem);
		}
	}

	private function encontrarEstadoPorCreci(array $estadosDoBrasil, string $creci): Estado{
		$estadoEntity = new Estado('NN');
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
		return $estadoEntity;
	}
}
