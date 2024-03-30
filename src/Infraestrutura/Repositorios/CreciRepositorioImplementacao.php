<?php

declare(strict_types=1);

namespace App\Infraestrutura\Repositorios;

use App\Dominio\Repositorios\CreciRepositorio;
use App\Dominio\Repositorios\EntradaESaida\EntradaSalvarCreciConsultado;
use App\Dominio\Repositorios\EntradaESaida\SaidaInformacoesCreci;
use PDO;

readonly class CreciRepositorioImplementacao implements CreciRepositorio
{
	public function __construct(
		private PDO $conexao
	){}

	public function creciJaFoiConsultadoAntes(string $creci): bool
	{

		$consulta = $this->conexao->prepare('SELECT nome_completo FROM creci WHERE creci_completo = :creci_completo');
		$consulta->execute([
			':creci_completo' => $creci
		]);
		$creci = $consulta->fetch(PDO::FETCH_ASSOC);

		return isset($creci['nome_completo']) and !empty($creci['nome_completo']);
	}

	public function buscarInformacoesCreci(string $creci): SaidaInformacoesCreci
	{
		$consulta = $this->conexao->prepare('SELECT
                creci_completo,
                creci_estado,
                creci_id,
                nome_completo,
                cidade,
                estado,
                numero_documento,
                atualizado_em,
                situacao
    		FROM creci WHERE creci_completo = :creci_completo');
		$consulta->execute([
			':creci_completo' => $creci
		]);
		$creci = $consulta->fetch(PDO::FETCH_ASSOC);

		return new SaidaInformacoesCreci(
			creciCodigo: $creci['creci_id'],
			creciCompleto: $creci['creci_completo'],
			creciEstado: $creci['creci_estado'],
			nomeCompleto: $creci['nome_completo'],
			atualizadoEm: $creci['atualizado_em'],
			situacao: $creci['situacao'],
			cidade: $creci['cidade'],
			estado: $creci['estado'],
			numeroDocumento: $creci['numero_documento'],
		);
	}

	public function salvarCreciConsultado(EntradaSalvarCreciConsultado $parametros): void
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
