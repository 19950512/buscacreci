<?php

declare(strict_types=1);

namespace App\Infraestrutura\Adaptadores\PlataformasCreci\ES;

use Override;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;
use Symfony\Component\DomCrawler\Crawler;
use App\Aplicacao\CasosDeUso\PlataformaCreci;
use App\Aplicacao\Compartilhado\Discord\Discord;
use App\Aplicacao\Compartilhado\Discord\Enums\CanalTexto;
use App\Infraestrutura\Adaptadores\PlataformasCreci\Robots;
use App\Aplicacao\CasosDeUso\EntradaESaida\SaidaConsultarCreciPlataforma;

final readonly class CreciESPlataformaImplementacao implements PlataformaCreci
{

	private Client $clientHttp;

	private string $baseURL;

	public function __construct(
		private Discord $discord
	){
		$this->baseURL = 'https://www.creci-es.gov.br';

		$this->clientHttp = new Client([
		    'base_uri' => $this->baseURL,
		    'timeout'  => 2.0,
		]);
	}

	#[Override] public function consultarCreci(string $creci, string $tipoCreci): SaidaConsultarCreciPlataforma
	{

        if(!Robots::isAllowedByRobotsTxt($this->baseURL. '/resultado_de_pesquisa_por_creci')){
            $this->discord->enviarMensagem(
                canalTexto: CanalTexto::WORKERS,
                mensagem: 'Acesso negado pelo robots.txt - URL: '.$this->baseURL. '/resultado_de_pesquisa_por_creci',
            );
            throw new Exception('Acesso negado pelo robots.txt');
        }

		/*
		 * O CRECI ES trabalha com CSRF-Token, então é necessário fazer uma requisição GET para obter o token
		 * e depois fazer a requisição POST com o token obtido na requisição GET para obter os dados do creci
		 * */

		$csrfToken = $this->getCSRFToken();

		if(empty($csrfToken)){
			throw new Exception('Parece que o CRECI ES mudou algo em relação ao CSRF-Token');
		}

		try {

			$cookiePath = sys_get_temp_dir() . '/cookies.txt'; // Usa o diretório temporário do sistema
			$cookieJar = new FileCookieJar($cookiePath, true);
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

		$cookiePath = sys_get_temp_dir() . '/cookies.txt'; // Usa o diretório temporário do sistema
		$cookieJar = new FileCookieJar($cookiePath, true);

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