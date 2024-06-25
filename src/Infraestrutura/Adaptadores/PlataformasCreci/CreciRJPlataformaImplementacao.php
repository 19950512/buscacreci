<?php

declare(strict_types=1);

namespace App\Infraestrutura\Adaptadores\PlataformasCreci;

use App\Aplicacao\CasosDeUso\EntradaESaida\SaidaConsultarCreciPlataforma;
use App\Aplicacao\CasosDeUso\PlataformaCreci;
use Exception;


class CreciRJPlataformaImplementacao implements PlataformaCreci
{

	private \GuzzleHttp\Client $clientHttp;

	public function __construct(){

		$this->clientHttp = new \GuzzleHttp\Client([
		    'base_uri' => 'https://www.crecirj.conselho.net.br',
		    'timeout'  => 2.0,
		]);
	}

	public function consultarCreci(string $creci, string $tipoCreci): SaidaConsultarCreciPlataforma
	{

		// somente numeros
		$numeroInscricao = preg_replace('/[^0-9]/', '', $creci);

		$response = $this->clientHttp->post('/form_pesquisa_cadastro_geral_site.php', [
			'json' => [
				'inscricao' => $numeroInscricao,
			]
		]);

		$respostaHTML = json_decode($response->getBody()->getContents());

		if(count($respostaHTML->cadastros) == 0){
			throw new Exception('Ops, Creci não encontrado!');
		}

		foreach ($respostaHTML->cadastros as $cadastro) {
			if($cadastro->creci == $creci){

				if($cadastro->tipo == 1 and $tipoCreci == 'F'){
					$respostaHTML = $cadastro;
					break;
				}

				if($cadastro->tipo == 2 and $tipoCreci == 'J'){
					$respostaHTML = $cadastro;
					break;
				}
				throw new Exception('Ops, Creci não encontrado!');
			}
		}

		return new SaidaConsultarCreciPlataforma(
			inscricao: (string) $respostaHTML->creci,
			nomeCompleto: $respostaHTML->nome,
			fantasia: $tipoCreci == 'J' ? $respostaHTML->nome : '',
			situacao: $respostaHTML->situacao ? 'Ativo' : 'Inativo',
			cidade: 'Rio de Janeiro',
			estado: 'RJ',
			numeroDocumento: $respostaHTML->cpf,
		);
	}
}