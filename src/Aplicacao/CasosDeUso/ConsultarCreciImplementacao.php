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
use App\Aplicacao\Compartilhado\Captcha\Captcha;
use App\Aplicacao\Compartilhado\Discord\Discord;
use App\Aplicacao\CasosDeUso\Enums\CreciImplementado;
use App\Aplicacao\CasosDeUso\EntradaESaida\ErroDomain;
use App\Aplicacao\Compartilhado\Mensageria\Mensageria;
use App\Aplicacao\Compartilhado\Discord\Enums\CanalTexto;
use App\Aplicacao\Compartilhado\Mensageria\Enumerados\Evento;
use App\Dominio\Repositorios\EntradaESaida\SaidaInformacoesCreci;
use App\Dominio\Repositorios\EntradaESaida\EntradaSalvarNovaConsulta;
use App\Dominio\Entidades\ConselhoNacionalCRECI\ConselhoNacionalCRECI;
use App\Dominio\Repositorios\EntradaESaida\EntradaSalvarCreciConsultado;
use App\Aplicacao\CasosDeUso\EntradaESaida\SaidaConsultarCreciPlataforma;
use App\Infraestrutura\Adaptadores\PlataformasCreci\ES\CreciESPlataformaImplementacao;
use App\Infraestrutura\Adaptadores\PlataformasCreci\RS\CreciRSPlataformaImplementacao;
use App\Infraestrutura\Adaptadores\PlataformasCreci\SP\CreciSPPlataformaImplementacao;
use App\Infraestrutura\Adaptadores\PlataformasCreci\Conselho\CreciConselhoPlataformaImplementacao;

readonly final class ConsultarCreciImplementacao implements ConsultarCreci
{
	public function __construct(
		private CreciRepositorio $creciRepositorio,
		private Discord $discord,
		private Mensageria $mensageria,
		private Cache $cache,
		private Captcha $captcha,
	) {}

	#[Override] public function consultarCreci(string $creci): IdentificacaoUnica | ErroDomain
	{

		$creci = mb_strtoupper($creci);

		$creci = preg_replace('/\s+/', '', $creci);

		// $creci só pode começar com 2 letras e depois números e no final pode ter J ou F
		if(!preg_match('/^[A-Z]{2}[0-9]{4,6}[JF]{1}$/', $creci)){
			$mensagem = 'Informe o Creci no formato correto. Exemplo: RS1234F';

			$this->discord->enviarMensagem(
				canalTexto: CanalTexto::CONSULTAS, 
				mensagem: $mensagem
			);
			return new ErroDomain(
				mensagem: $mensagem,
				codigo: 422
			);
		}

		$estadosDoBrasil = Estado::getEstados();
		$estadoEntity = $this->encontrarEstadoPorCreci($estadosDoBrasil, $creci);

		if($estadoEntity->getUF() == 'NN'){
			$mensagem = 'Informe o estado no Creci. Exemplo: RS12345F';
			$this->discord->enviarMensagem(
				canalTexto: CanalTexto::CONSULTAS, 
				mensagem: $mensagem
			);
			return new ErroDomain(
				mensagem: $mensagem,
				codigo: 422
			);
		}

		if($estadoEntity->getUF() == 'SP'){
			$this->discord->enviarMensagem(
				canalTexto: CanalTexto::CONSULTAS, 
				mensagem: "Atenção, alguem está tentando consultar o CRECI de São Paulo. - {$creci}"
			);
		}

		$creciTemporario = strtoupper($creci);
		// Vamos remover o estado do creci
		$creciTemporario = str_replace($estadoEntity->getUF(), '', $creciTemporario);
		$tipoCreci = str_contains($creciTemporario, 'J') ? 'J' : 'F';

		$numeroInscricao = preg_replace('/[^0-9]/', '', $creci);

		$numeroCreciMontado = "CRECI/{$estadoEntity->getUF()} {$numeroInscricao}-{$tipoCreci}";

		$uuidDaConsulta = new IdentificacaoUnica();

		$parametrosSalvarNovaConsulta = new EntradaSalvarNovaConsulta(
			codigoSolicitacao: $uuidDaConsulta->get(),
			creci: $numeroCreciMontado,
			momento: date('Y-m-d H:i:s'),
			situacao: 'AGUARDANDO SER PROCESSADO',
		);

		$this->creciRepositorio->salvarNovaConsulta($parametrosSalvarNovaConsulta);

		$this->mensageria->publicar(
			evento: Evento::ConsultaCreci,
			message: json_encode([
				'codigoSolicitacao' => $uuidDaConsulta->get(),
				'creci' => $numeroCreciMontado,
				'estado' => $estadoEntity->getUF(),
				'numeroInscricao' => $numeroInscricao,
				'tipoCreci' => $tipoCreci,
			]),
		);

		$this->discord->enviarMensagem(
			canalTexto: CanalTexto::CONSULTAS, 
			mensagem: "Creci {$numeroCreciMontado} foi enviado para consulta.\nUUID da consulta: {$uuidDaConsulta->get()}\nAguarde a resposta."
		);

		return $uuidDaConsulta;
	}

	#[Override] public function consultarCreciPlataforma(IdentificacaoUnica $codigoSolicitacao): void
	{

		$consultaInformacoes = $this->creciRepositorio->getConsultaByCodigoSolicitacao($codigoSolicitacao->get());

		if($this->creciRepositorio->creciJaFoiConsultadoAntes($consultaInformacoes->creciCompleto)){

			$creciData = $this->creciRepositorio->buscarInformacoesCreci($consultaInformacoes->creciCompleto);

			/*$saidaCreci = new SaidaCreci(
				creciID: $creciData->creciCodigo,
				creciCompleto: $creciData->creciCompleto,
				creciEstado: $creciData->creciCompleto,
				nomeCompleto: $creciData->nomeCompleto,
				atualizadoEm: $creciData->atualizadoEm,
				situacao: $creciData->situacao,
				cidade: $creciData->cidade,
				estado: $creciData->estado,
				numeroDocumento: $creciData->numeroDocumento,
				data: $creciData->data,
			); */

			$this->creciRepositorio->atualizarConsultaCodigoSolicitacao(
				codigoSolicitacao: $codigoSolicitacao->get(),
				situacao: 'FINALIZADO',
				momento: date('Y-m-d H:i:s'),
				creciCodigo: $creciData->creciCodigo,
				mensagemSucesso: 'Creci já foi consultado anteriormente.',
			);
		}


		$estadosDoBrasil = Estado::getEstados();
		$estadoEntity = $this->encontrarEstadoPorCreci($estadosDoBrasil, $consultaInformacoes->creciCompleto);

		if($estadoEntity->getUF() == 'NN'){
			$mensagem = 'Informe o estado no Creci. Exemplo: RS12345F';

			$this->creciRepositorio->atualizarConsultaCodigoSolicitacao(
				codigoSolicitacao: $codigoSolicitacao->get(),
				situacao: 'FINALIZADO',
				momento: date('Y-m-d H:i:s'),
				creciCodigo: $creciData->creciCodigo,
				mensagemErro: $mensagem,
			);
			$this->discord->enviarMensagem(
				canalTexto: CanalTexto::CONSULTA_CRECIC, 
				mensagem: $mensagem
			);
			return;
		}

		$numeroInscricao = preg_replace('/[^0-9]/', '', $consultaInformacoes->creciCompleto);
		$tipoCreci = str_contains($consultaInformacoes->creciCompleto, 'J') ? 'J' : 'F';

		try {

			$resposta = $this->consultarCreciNaPlataforma(
				estadoEntity: $estadoEntity,
				numeroInscricao: $numeroInscricao,
				tipoCreci: $tipoCreci
			);
			
			$paramsBuildCreciEntidade = new SaidaInformacoesCreci(
				creciCodigo: (new IdentificacaoUnica())->get(),
				creciCompleto: "CRECI/{$estadoEntity->getUF()} {$numeroInscricao}-{$tipoCreci}",
				creciEstado: $resposta->estado,
				nomeCompleto: $resposta->nomeCompleto,
				atualizadoEm: date('Y-m-d H:i:s'),
				situacao: $resposta->situacao,
				cidade: $resposta->cidade,
				estado: $resposta->estado,
				numeroDocumento: $resposta->numeroDocumento,
				data: $resposta->data,
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

			$this->creciRepositorio->atualizarConsultaCodigoSolicitacao(
				codigoSolicitacao: $codigoSolicitacao->get(),
				creciCodigo: $creciEntity->codigo->get(),
				situacao: 'FINALIZADO',
				momento: date('Y-m-d H:i:s'),
				mensagemSucesso: 'Creci consultado com sucesso.',
			);

		}catch (Exception $e){

			$this->creciRepositorio->atualizarConsultaCodigoSolicitacao(
				codigoSolicitacao: $codigoSolicitacao->get(),
				situacao: 'FINALIZADO',
				momento: date('Y-m-d H:i:s'),
				creciCodigo: '',
				mensagemErro: $e->getMessage(),
			);
			$this->discord->enviarMensagem(
				canalTexto: CanalTexto::CONSULTA_CRECIC, 
				mensagem: $e->getMessage()
			);
		}
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
			CreciImplementado::SP => new CreciSPPlataformaImplementacao(
				captcha: $this->captcha,
			),
			default => throw new Exception("Ainda não implementamos o estado informado! {$estadoEntity->getFull()} - ({$estadoEntity->getUF()})"),
		};

		try {

			$resposta = $plataformaCreci->consultarCreci(
				creci: $numeroInscricao,
				tipoCreci: $tipoCreci
			);

			return $resposta;

		}catch (Exception $e){

			$mensagem = "O número de inscrição {$numeroInscricao} não foi encontrado no CRECI {$creciImplementado->value}.";

			$this->discord->enviarMensagem(
				canalTexto: CanalTexto::CONSULTAS, 
				mensagem: $mensagem."\nErro: {$e->getMessage()}"
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
