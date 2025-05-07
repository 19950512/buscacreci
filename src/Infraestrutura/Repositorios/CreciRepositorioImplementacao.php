<?php

declare(strict_types=1);

namespace App\Infraestrutura\Repositorios;

use PDO;
use Override;
use Exception;
use App\Dominio\Repositorios\CreciRepositorio;
use App\Dominio\Repositorios\EntradaESaida\SaidaInformacoesCreci;
use App\Dominio\Repositorios\EntradaESaida\EntradaSalvarNovaConsulta;
use App\Dominio\Repositorios\EntradaESaida\SaidaInformacoesDaConsulta;
use App\Dominio\Repositorios\EntradaESaida\EntradaSalvarCreciConsultado;

readonly class CreciRepositorioImplementacao implements CreciRepositorio
{
	public function __construct(
		private PDO $conexao
	){}

	#[Override] public function atualizarStatusConsulta(string $codigoSolicitacao, string $situacao): void
	{
		$consulta = $this->conexao->prepare('UPDATE consultas_creci SET
			situacao = :situacao
			WHERE codigo_solicitacao = :codigo_solicitacao');
		$consulta->execute([
			':codigo_solicitacao' => $codigoSolicitacao,
			':situacao' => $situacao,
		]);
	}


	#[Override] public function atualizarConsultaCodigoSolicitacao(string $codigoSolicitacao, string $situacao, string $momento, string $creciCodigo, string $mensagemSucesso = '', string $mensagemErro = ''): void
	{
		$consulta = $this->conexao->prepare('UPDATE consultas_creci SET
			codigo_solicitacao = :codigo_solicitacao,
			mensagem_sucesso = :mensagem_sucesso,
			mensagem_erro = :mensagem_erro,
			creci_id = :creci_id,
			situacao = :situacao,
			data_finalizacao = :data_finalizacao
			WHERE codigo_solicitacao = :codigo_solicitacao');
		$consulta->execute([
			':codigo_solicitacao' => $codigoSolicitacao,
			':situacao' => $situacao,
			':mensagem_sucesso' => $mensagemSucesso,
			':mensagem_erro' => $mensagemErro,
			':creci_id' => $creciCodigo,
			':data_finalizacao' => $momento,
		]);
	}


	#[Override] public function getConsultaByCodigoSolicitacao(string $codigoSolicitacao): SaidaInformacoesDaConsulta
	{
		$consulta = $this->conexao->prepare('SELECT
				codigo_solicitacao,
				creci_id,
				situacao,
				mensagem_erro,
				mensagem_sucesso,
				creci
			FROM consultas_creci WHERE codigo_solicitacao = :codigo_solicitacao');
		$consulta->execute([
			':codigo_solicitacao' => $codigoSolicitacao
		]);
		$consulta = $consulta->fetch(PDO::FETCH_ASSOC);

		if(!isset($consulta['codigo_solicitacao']) OR empty($consulta['codigo_solicitacao'])){
			throw new Exception('Não encontramos nenhuma consulta com o código de solicitação informado.');
		}

		$mensagem = 'Desconhecido';
		if(isset($consulta['mensagem_erro']) and !empty($consulta['mensagem_erro'])){
			$mensagem = $consulta['mensagem_erro'];
		}
		if(isset($consulta['mensagem_sucesso']) and !empty($consulta['mensagem_sucesso'])){
			$mensagem = $consulta['mensagem_sucesso'];
		}

		return new SaidaInformacoesDaConsulta(
			codigoSolicitacao: $consulta['codigo_solicitacao'],
			creciCompleto: $consulta['creci'],
			creciID: (string) ($consulta['creci_id'] ?? ''),
			status: $consulta['situacao'],
			mensagem: $mensagem,
		);
	}


	#[Override] public function salvarNovaConsulta(EntradaSalvarNovaConsulta $parametros): void
	{
		$consulta = $this->conexao->prepare('INSERT INTO consultas_creci (
			creci,
			usuario_codigo,
			codigo_solicitacao,
			data_cadastro,
			data_finalizacao,
			situacao,
			mensagem_erro,
			mensagem_sucesso
		) VALUES (
			:creci,
			:usuario_codigo,
			:codigo_solicitacao,
			:data_cadastro,
			:data_finalizacao,
			:situacao,
			:mensagem_erro,
			:mensagem_sucesso
		)');
		$consulta->execute([
			':creci' => $parametros->creci,
			':usuario_codigo' => null,
			':codigo_solicitacao' => $parametros->codigoSolicitacao,
			':data_cadastro' => $parametros->momento,
			':data_finalizacao' => null,
			':situacao' => $parametros->situacao,
			':mensagem_erro' => null,
			':mensagem_sucesso' => null
		]);

	}


	#[Override] public function creciJaFoiConsultadoAntes(string $creciCodigo): bool
	{

		$consulta = $this->conexao->prepare('SELECT nome_completo FROM creci WHERE creci_id = :creci_id');
		$consulta->execute([
			':creci_id' => $creciCodigo
		]);
		$creci = $consulta->fetch(PDO::FETCH_ASSOC);

		return isset($creci['nome_completo']) and !empty($creci['nome_completo']);
	}

	#[Override] public function buscarInformacoesCreci(string $creciCodigo = '', string $creciCompleto = ''): SaidaInformacoesCreci
	{

		$where = '';
		$creci = '';
		if(!empty($creciCodigo)){
			$where = 'WHERE creci_id = :creci';
			$creci = $creciCodigo;
		}
		if(!empty($creciCompleto)){
			$where = 'WHERE creci_completo = :creci';
			$creci = $creciCompleto;
		}
		if(empty($creciCodigo) and empty($creciCompleto)){
			throw new Exception('Informe o CRECI completo ou o código do CRECI.');
		}

		$consulta = $this->conexao->prepare("SELECT
                creci_completo,
                creci_estado,
                creci_id,
                nome_completo,
                cidade,
                estado,
                numero_documento,
                atualizado_em,
                situacao
    		FROM creci $where");
		$consulta->execute([
			':creci' => $creci
		]);
		$creci = $consulta->fetch(PDO::FETCH_ASSOC);

		return new SaidaInformacoesCreci(
			creciCodigo: (string) ($creci['creci_id'] ?? ''),
			creciCompleto: (string) ($creci['creci_completo'] ?? ''),
			creciEstado: (string) ($creci['creci_estado'] ?? ''),
			nomeCompleto: (string) ($creci['nome_completo'] ?? ''),
			atualizadoEm: (string) ($creci['atualizado_em'] ?? ''),
			situacao: (string) ($creci['situacao'] ?? ''),
			cidade: (string) ($creci['cidade'] ?? ''),
			estado: (string) ($creci['estado'] ?? ''),
			numeroDocumento: (string) ($creci['numero_documento'] ?? ''),
			data: (string) ($creci['atualizado_em'] ?? ''),
		);
	}

	#[Override] public function salvarCreciConsultado(EntradaSalvarCreciConsultado $parametros): void
	{
		$consulta = $this->conexao->prepare('INSERT INTO creci (
			creci_id,
			creci_completo,
			creci_estado,
			nome_completo,
			cidade,
			estado,
			numero_documento,
			atualizado_em,
                   situacao
		) VALUES (
		    :creci_id,
			:creci_completo,
			:creci_estado,
			:nome_completo,
			:cidade,
			:estado,
			:numero_documento,
			:atualizado_em,
		          :situacao
		)');
		$consulta->execute([
			':creci_id' => $parametros->codigo,
			':creci_completo' => $parametros->creci,
			':creci_estado' => $parametros->estado,
			':nome_completo' => $parametros->nomeCompleto,
			':cidade' => $parametros->cidade,
			':estado' => $parametros->estado,
			':numero_documento' => $parametros->numeroDocumento,
			':atualizado_em' => $parametros->momento,
			':situacao' => $parametros->situacao
		]);
	}
}
