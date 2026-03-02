<?php

declare(strict_types=1);

namespace App\Infraestrutura\Adaptadores\PlataformasCreci\SP;

use Override;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Symfony\Component\DomCrawler\Crawler;
use App\Aplicacao\CasosDeUso\PlataformaCreci;
use App\Aplicacao\Compartilhado\Captcha\Captcha;
use App\Aplicacao\Compartilhado\Discord\Discord;
use App\Aplicacao\Compartilhado\Discord\Enums\CanalTexto;
use App\Infraestrutura\Adaptadores\PlataformasCreci\Robots;
use App\Aplicacao\CasosDeUso\EntradaESaida\SaidaConsultarCreciPlataforma;

/**
 * Implementação do scraper de CRECI para São Paulo.
 *
 * O CRECI SP utiliza reCAPTCHA Enterprise, que é resolvido via 2Captcha.
 * O token obtido é enviado via HTTP POST (Guzzle) para a busca, dispensando
 * headless Chrome.
 */
class CreciSPPlataformaImplementacao implements PlataformaCreci
{
	private string $baseURL = 'https://www.crecisp.gov.br';
	private Client $clientHttp;
	private CookieJar $cookieJar;

	/** reCAPTCHA Enterprise site key (extraído da página) */
	private const RECAPTCHA_SITE_KEY = '6LfUMMgqAAAAABG4tjE8VkT2wKZlqmAvV2YsId7a';

	public function __construct(
		private Discord $discord,
		private Captcha $captcha,
	){
		$this->cookieJar = new CookieJar();
		$this->clientHttp = new Client([
			'base_uri' => $this->baseURL,
			'timeout'  => 60.0,
			'cookies'  => $this->cookieJar,
			'headers' => [
				'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
				'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
				'Accept-Language' => 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
			],
			'allow_redirects' => true,
		]);
	}

	#[Override]
	public function consultarCreci(string $creci, string $tipoCreci): SaidaConsultarCreciPlataforma
	{
		$searchUrl = $this->baseURL . '/cidadao/buscaporcorretores';

		if (!Robots::isAllowedByRobotsTxt($searchUrl)) {
			$this->discord->enviarMensagem(
				canalTexto: CanalTexto::WORKERS,
				mensagem: 'Acesso negado pelo robots.txt - URL: ' . $searchUrl,
			);
			throw new Exception('Acesso negado pelo robots.txt');
		}

		$this->discord->enviarMensagem(
			canalTexto: CanalTexto::CONSULTAS,
			mensagem: "CRECI SP: Iniciando consulta do CRECI {$creci}-{$tipoCreci} via 2Captcha + HTTP",
		);

		// Step 1: Visita a página de busca para obter cookies de sessão
		$this->clientHttp->get('/cidadao/buscaporcorretores');

		// Step 2: Resolve o reCAPTCHA Enterprise via 2Captcha
		$this->discord->enviarMensagem(
			canalTexto: CanalTexto::CONSULTAS,
			mensagem: "CRECI SP: Resolvendo reCAPTCHA Enterprise via 2Captcha...",
		);

		$captchaResolvido = $this->captcha->resolverV3(
			siteKey: self::RECAPTCHA_SITE_KEY,
			pageUrl: $searchUrl,
			isEnterprise: true,
			pageAction: 'submit_broker_search',
		);

		$this->discord->enviarMensagem(
			canalTexto: CanalTexto::CONSULTAS,
			mensagem: "CRECI SP: reCAPTCHA resolvido. Enviando busca...",
		);

		// Step 3: Envia POST de busca com o token do reCAPTCHA
		$response = $this->clientHttp->post('/cidadao/buscaporcorretores', [
			'form_params' => [
				'IsFinding'      => 'True',
				'RegisterNumber' => $creci,
				'CPF'            => '',
				'Name'           => '',
				'City'           => '',
				'Area'           => '',
				'Language'       => '',
				'Avaliador'      => '',
				'ReCAPTCHAToken' => $captchaResolvido->get(),
			],
			'headers' => [
				'Referer' => $searchUrl,
				'Origin'  => $this->baseURL,
			],
		]);

		$respostaHTML = $response->getBody()->getContents();

		// Check for reCAPTCHA validation error
		if (
			str_contains($respostaHTML, 'Validação reCAPTCHA') ||
			str_contains($respostaHTML, 'erro na validação do capatcha')
		) {
			throw new Exception('Falha na validação do reCAPTCHA Enterprise pelo servidor CRECI SP');
		}

		// Step 4: Parse da lista de resultados
		$crawler = new Crawler($respostaHTML);

		// Look for form pointing to corretordetalhes matching our CRECI number
		$detailUrl = $this->findDetailUrl($crawler, $creci, $tipoCreci);

		if ($detailUrl === null) {
			// Try to get data directly from the list page
			$brokerFromList = $this->extractFromList($crawler, $creci);
			if ($brokerFromList !== null) {
				return $brokerFromList;
			}
			throw new Exception("CRECI {$creci}-{$tipoCreci} não encontrado nos resultados da busca");
		}

		// Step 5: POST para a página de detalhes
		$response = $this->clientHttp->post($detailUrl, [
			'form_params' => [],
			'headers' => [
				'Referer' => $searchUrl,
				'Origin'  => $this->baseURL,
			],
		]);

		$detailHTML = $response->getBody()->getContents();

		// Step 6: Extrai dados da página de detalhes
		$data = $this->extractDetailData($detailHTML, $creci);

		$this->discord->enviarMensagem(
			canalTexto: CanalTexto::CONSULTAS,
			mensagem: "CRECI SP: Corretor encontrado - {$data->nomeCompleto} (CRECI {$creci}-{$tipoCreci}) - Situação: {$data->situacao}",
		);

		return $data;
	}

	/**
	 * Procura a URL de detalhes para o CRECI específico na página de resultados.
	 */
	private function findDetailUrl(Crawler $crawler, string $creci, string $tipoCreci): ?string
	{
		$query = "{$creci}-{$tipoCreci}";

		try {
			$forms = $crawler->filter('form[action*="corretordetalhes"]');

			$firstAction = null;
			$forms->each(function (Crawler $node) use ($query, &$firstAction) {
				$action = $node->attr('action');
				if ($firstAction === null) {
					$firstAction = $action;
				}
				if (str_contains($action, $query)) {
					$firstAction = $action; // Exact match takes priority
				}
			});

			if ($firstAction !== null && str_contains($firstAction, $query)) {
				return $firstAction;
			}

			return $firstAction;
		} catch (Exception) {
			// No forms found
		}

		return null;
	}

	/**
	 * Tenta extrair dados básicos diretamente da lista de resultados.
	 */
	private function extractFromList(Crawler $crawler, string $creci): ?SaidaConsultarCreciPlataforma
	{
		try {
			$brokerDetails = $crawler->filter('.broker-details h6');
			if ($brokerDetails->count() > 0) {
				return new SaidaConsultarCreciPlataforma(
					inscricao: $creci,
					nomeCompleto: trim($brokerDetails->first()->text()),
					fantasia: '',
					situacao: 'Desconhecido',
					cidade: '',
					estado: 'SP',
				);
			}
		} catch (Exception) {
			// No data in list
		}

		return null;
	}

	/**
	 * Extrai dados do corretor da página de detalhes.
	 */
	private function extractDetailData(string $html, string $creci): SaidaConsultarCreciPlataforma
	{
		$crawler = new Crawler($html);
		$bodyText = strip_tags($html);
		$lines = array_values(array_filter(
			array_map('trim', explode("\n", $bodyText)),
			fn($l) => $l !== ''
		));

		// Extrair nome completo
		$nomeCompleto = '';
		foreach ($lines as $i => $line) {
			if (str_contains($line, 'Detalhes do') && isset($lines[$i + 1])) {
				$nomeCompleto = $lines[$i + 1];
				break;
			}
		}

		if (empty($nomeCompleto)) {
			// Fallback: try h5 or strong tags
			try {
				$h5 = $crawler->filter('h5');
				if ($h5->count() > 0) {
					$nomeCompleto = trim($h5->first()->text());
				}
			} catch (Exception) {}
		}

		if (empty($nomeCompleto)) {
			throw new Exception('Não foi possível extrair o nome do corretor da página de detalhes');
		}

		// Extrair CRECI
		$inscricao = $creci;
		foreach ($lines as $line) {
			if (str_starts_with($line, 'CRECI:')) {
				$creciValue = trim(str_replace('CRECI:', '', $line));
				$inscricao = preg_replace('/[^0-9]/', '', $creciValue) ?: $creci;
				break;
			}
		}

		// Extrair data de inscrição
		$dataInscricao = '';
		foreach ($lines as $line) {
			if (str_starts_with($line, 'Data de Inscrição:')) {
				$dataInscricao = trim(str_replace('Data de Inscrição:', '', $line));
				break;
			}
		}

		// Extrair situação
		$situacao = 'Desconhecido';
		foreach ($lines as $line) {
			if (str_starts_with($line, 'Situação:')) {
				$situacao = trim(str_replace('Situação:', '', $line));
				break;
			}
		}

		return new SaidaConsultarCreciPlataforma(
			inscricao: $inscricao,
			nomeCompleto: $nomeCompleto,
			fantasia: '',
			situacao: $situacao,
			cidade: '',
			estado: 'SP',
			data: $dataInscricao,
		);
	}
}
