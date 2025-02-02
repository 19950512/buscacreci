<?php

declare(strict_types=1);

namespace App\Infraestrutura\Adaptadores\PlataformasCreci;

use Override;
use Exception;
use App\Aplicacao\CasosDeUso\EntradaESaida\SaidaConsultarCreciPlataforma;
use App\Aplicacao\CasosDeUso\PlataformaCreci;
use Symfony\Component\DomCrawler\Crawler;

class CreciRSPlataformaImplementacao implements PlataformaCreci
{

	private \GuzzleHttp\Client $clientHttp;

	public function __construct(){

		$this->clientHttp = new \GuzzleHttp\Client([
		    'base_uri' => 'https://www.creci-rs.gov.br',
		    'timeout'  => 9999.0,
			'origin' => 'www.creci-rs.gov.br'
		]);
	}

	#[Override] public function consultarCreci(string $creci, string $tipoCreci): SaidaConsultarCreciPlataforma
	{

		// somente numeros
		$numeroInscricao = preg_replace('/[^0-9]/', '', $creci);

		$tipoPessoa = match($tipoCreci){
			'F' => 1,
			'J' => 2,
			default => 0 // Todos
		};

		$response = $this->clientHttp->post('/siteNovo/pesquisaInscrito.php', [
			'form_params' => [
				'acao' => 'pesquisar',
				'busca' => $numeroInscricao,
				'cd_cidade' => 0,
				'fg_tipo_pessoa' => $tipoPessoa
			]
		]);

		$respostaHTML = $response->getBody()->getContents();

		$crawler = new Crawler($respostaHTML);

		$crawler = $crawler->filter('table.table-striped')->each(function (Crawler $node, $i) {

			$cidadeEstado = explode('/', $node->filter('td')->eq(4)->text());

			$inscricao = $node->filter('td')->eq(0)->text();

			$nomeFantasia = '';
			if(str_contains($inscricao,'J')){
				$nomeFantasia = $node->filter('td')->eq(2)->text();
			}

			$inscricao = preg_replace('/[^0-9]/', '', $inscricao);

			return [
				'inscricao' => $inscricao,
				'nomeCompleto' => $node->filter('td')->eq(1)->text(),
				'fantasia' => $nomeFantasia,
				'situacao' => $node->filter('td')->eq(3)->text(),
				'cidade' => $cidadeEstado[0],
				'estado' => $cidadeEstado[1],
			];
		});

		if(isset($crawler[0], $crawler[0]['inscricao']) and str_contains($crawler[0]['inscricao'], $creci)){
			return new SaidaConsultarCreciPlataforma(
				inscricao: $crawler[0]['inscricao'],
				nomeCompleto: $crawler[0]['nomeCompleto'],
				fantasia: $crawler[0]['fantasia'],
				situacao: $crawler[0]['situacao'],
				cidade: $crawler[0]['cidade'],
				estado: $crawler[0]['estado']
			);
		}

		throw new Exception('Creci não encontrado');
	}
}
