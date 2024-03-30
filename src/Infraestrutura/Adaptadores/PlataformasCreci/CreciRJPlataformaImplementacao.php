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

	public function consultarCreci(string $creci): SaidaConsultarCreciPlataforma
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

		return new SaidaConsultarCreciPlataforma(
			inscricao: (string) $respostaHTML->cadastros[0]->creci,
			nomeCompleto: $respostaHTML->cadastros[0]->nome,
			fantasia: '',
			situacao: $respostaHTML->cadastros[0]->situacao ? 'Ativo' : 'Inativo',
			cidade: 'Rio de Janeiro',
			estado: 'RJ',
			cpf: $respostaHTML->cadastros[0]->cpf,
		);
	}
}