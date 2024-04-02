<?php

declare(strict_types=1);

namespace App\Infraestrutura\Adaptadores\PlataformasCreci\ES;

use App\Aplicacao\CasosDeUso\EntradaESaida\SaidaConsultarCreciPlataforma;
use App\Aplicacao\CasosDeUso\PlataformaCreci;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;
use Symfony\Component\DomCrawler\Crawler;

class CreciESPlataformaImplementacao implements PlataformaCreci
{

	private Client $clientHttp;

	public function __construct(){

		$this->clientHttp = new Client([
		    'base_uri' => 'https://area-restrita.crecies.gov.br',
		    'timeout'  => 2.0,
		]);
	}

	public function consultarCreci(string $creci, string $tipoCreci): SaidaConsultarCreciPlataforma
	{

		/*
		 * O CRECI ES trabalha com CSRF-Token, então é necessário fazer uma requisição GET para obter o token
		 * e depois fazer a requisição POST com o token obtido na requisição GET para obter os dados do creci
		 * */

		$csrfToken = $this->getCSRFToken();

		if(empty($csrfToken)){
			throw new Exception('Parece que o CRECI ES mudou algo em relação ao CSRF-Token');
		}

		try {

			$cookieJar = new FileCookieJar(__DIR__.'/cookies.txt', true);
			$response = $this->clientHttp->post('/resultado_de_pesquisa_por_creci', [
				'cookies' => $cookieJar,
				'headers' => [
					'Content-Type' => 'application/x-www-form-urlencoded',
					'Cookie' => 'XSRF-TOKEN=' . $csrfToken,
					'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3',
					'X-CSRF-TOKEN' => $csrfToken
				],
				'form_params' => [
					'_token' => $csrfToken,
					'creci' => $creci,
				]
			]);

			$respostaHTML = $response->getBody()->getContents();

			$crawler = new Crawler($respostaHTML);

			$crawler = $crawler->filter('table.table-striped')->each(function (Crawler $node, $i) {

				$inscricao = $node->filter('td')->eq(1)->filter('dd')->text();

				$inscricaoList = explode(' ', $inscricao);
				$inscricao = $inscricaoList[0];

				$tipoCreci = str_contains($inscricao,'J') ? 'J' : 'F';
				$creciStatus = $node->filter('td')->eq(1)->filter('dd')->filter('span')->text();

				if($tipoCreci == 'J'){
					// há um H5 com o nome da pessoa
					$nome = $node->filter('h5 span')->text();

					$nomeFantasia = '';
					if(str_contains($inscricao,'J')){
						$nomeFantasia = $nome;
					}
				}elseif($tipoCreci == 'F'){
					$nome = $node->filter('h5 strong')->text();
					$nomeFantasia = '';
				}

				return [
					'inscricao' => $inscricao,
					'nomeCompleto' => $nome,
					'fantasia' => $nomeFantasia,
					'situacao' => $creciStatus,
					'cidade' => 'Espirito Santo',
					'estado' => 'ES',
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

		}catch (Exception $e) {
			if($e->getCode() == 419){
				throw new Exception('Ops, CSRF-Token expirado!');
			}
			throw new Exception($e->getMessage());
		}
	}

	private function getCSRFToken(): string
	{

		$cookieJar = new FileCookieJar(__DIR__.'/cookies.txt', true);

		$response = $this->clientHttp->get('/resultado_de_pesquisa_por_creci', [
			'cookies' => $cookieJar
		]);

		$respostaHTML = $response->getBody()->getContents();

		// Vamos pegar o token CSRF no meta name="csrf-token"
		$pattern = '/<meta name="csrf-token" content="(.*?)">/';
		preg_match($pattern, $respostaHTML, $matchesToken);

		$csrfToken = $matchesToken[1] ?? '';

		return $csrfToken;
	}

}