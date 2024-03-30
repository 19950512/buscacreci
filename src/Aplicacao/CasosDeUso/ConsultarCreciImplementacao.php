<?php

declare(strict_types=1);

namespace App\Aplicacao\CasosDeUso;

use App\Aplicacao\CasosDeUso\EntradaESaida\SaidaCreci;
use App\Aplicacao\CasosDeUso\Enums\CreciImplementado;
use App\Infraestrutura\Adaptadores\PlataformasCreci\CreciRJPlataformaImplementacao;
use App\Infraestrutura\Adaptadores\PlataformasCreci\CreciRSPlataformaImplementacao;
use Exception;

class ConsultarCreciImplementacao implements ConsultarCreci
{
	public function __construct() {}

	public function consultarCreci(string $creci): SaidaCreci
	{

		$estadosDoBrasil = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];

		$estadoCreci = '';
		$pessoaFisica = str_contains($creci, 'J') ? 'J' : 'F';
		foreach($estadosDoBrasil as $estado){
			$creciTemp = strtoupper($creci);
			if(str_contains($creciTemp, $estado)){
				$estadoCreci = $estado;
				break;
			}
		}

		if($estadoCreci == ''){
			throw new Exception('Ops, informe o estado no Creci. Exemplo: RS12345');
		}

		$creciImplementado = CreciImplementado::tryFrom($estadoCreci);
		if(!is_a($creciImplementado, CreciImplementado::class)){
			throw new Exception('Ops, ainda não implementamos o estado informado. '.$estadoCreci);
		}

		$plataformaCreci = match ($creciImplementado) {
			CreciImplementado::RS => new CreciRSPlataformaImplementacao(),
			CreciImplementado::RJ => new CreciRJPlataformaImplementacao(),
			default => throw new Exception('Ops, ainda não implementamos o estado informado! '.$estadoCreci),
		};

		$numeroInscricao = preg_replace('/[^0-9]/', '', $creci);

		try {
			$resposta = $plataformaCreci->consultarCreci($numeroInscricao);
		}catch (Exception $e){
			throw new Exception("Ops, o CRECI {$numeroInscricao} não foi encontrado na plataforma {$creciImplementado->value}.");
		}

		return new SaidaCreci(
			inscricao: $resposta->inscricao,
			nomeCompleto: $resposta->nomeCompleto,
			fantasia: $resposta->fantasia,
			situacao: $resposta->situacao,
			cidade: $resposta->cidade,
			estado: $resposta->estado,
			cpf: $resposta->cpf,
		);
	}
}
