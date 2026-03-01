<?php

declare(strict_types=1);

namespace App\Infraestrutura\Adaptadores\PlataformasCreci\SP;

use Override;
use Exception;
use App\Aplicacao\CasosDeUso\PlataformaCreci;
use App\Aplicacao\Compartilhado\Discord\Discord;
use App\Aplicacao\Compartilhado\Discord\Enums\CanalTexto;
use App\Infraestrutura\Adaptadores\PlataformasCreci\Robots;
use App\Aplicacao\CasosDeUso\EntradaESaida\SaidaConsultarCreciPlataforma;

/**
 * Implementação do scraper de CRECI para São Paulo.
 *
 * O CRECI SP utiliza reCAPTCHA Enterprise, que não pode ser resolvido por
 * serviços como 2Captcha. A solução é usar um navegador Chrome headless
 * real (via Node.js + Puppeteer) para executar o reCAPTCHA Enterprise
 * nativamente no contexto do navegador.
 *
 * A página de detalhes do CRECI SP só aceita POST (não GET), por isso
 * é necessário navegar pela busca → lista → detalhes dentro do browser.
 */
class CreciSPPlataformaImplementacao implements PlataformaCreci
{
	private string $baseURL = 'https://www.crecisp.gov.br';

	/** Caminho para o script Node.js que faz o scraping */
	private string $scraperScript;

	public function __construct(
		private Discord $discord,
	){
		$this->scraperScript = __DIR__ . '/creci_sp_scraper.js';
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
			mensagem: "CRECI SP: Iniciando consulta do CRECI {$creci}-{$tipoCreci} via headless Chrome",
		);

		// Verificar se Node.js está disponível
		$nodePath = $this->findNodePath();
		if ($nodePath === null) {
			throw new Exception('Node.js não encontrado. Necessário para consultar CRECI SP (reCAPTCHA Enterprise).');
		}

		// Verificar se o script existe
		if (!file_exists($this->scraperScript)) {
			throw new Exception("Script do scraper SP não encontrado: {$this->scraperScript}");
		}

		// Verificar se puppeteer-core está instalado
		$projectRoot = dirname($this->scraperScript, 6); // Go up to project root
		$puppeteerPath = $projectRoot . '/node_modules/puppeteer-core';
		if (!is_dir($puppeteerPath)) {
			throw new Exception(
				"puppeteer-core não instalado. Execute: cd {$projectRoot} && npm install puppeteer-core"
			);
		}

		// Executar o script Node.js
		$creciEscaped = escapeshellarg($creci);
		$tipoCreciEscaped = escapeshellarg($tipoCreci);
		$scriptEscaped = escapeshellarg($this->scraperScript);
		$nodeEscaped = escapeshellarg($nodePath);

		$command = "{$nodeEscaped} {$scriptEscaped} {$creciEscaped} {$tipoCreciEscaped} 2>/dev/null";

		$this->discord->enviarMensagem(
			canalTexto: CanalTexto::CONSULTAS,
			mensagem: "CRECI SP: Executando headless Chrome para CRECI {$creci}-{$tipoCreci}...",
		);

		$output = [];
		$returnCode = 0;
		exec($command, $output, $returnCode);

		$jsonOutput = implode('', $output);

		if (empty($jsonOutput)) {
			$this->discord->enviarMensagem(
				canalTexto: CanalTexto::CONSULTAS,
				mensagem: "CRECI SP: Script retornou saída vazia (código: {$returnCode})",
			);
			throw new Exception("Scraper SP retornou saída vazia. Código de saída: {$returnCode}");
		}

		$result = json_decode($jsonOutput, true);

		if (!is_array($result)) {
			throw new Exception("Scraper SP retornou JSON inválido: {$jsonOutput}");
		}

		if (!($result['success'] ?? false)) {
			$errorMsg = $result['error'] ?? 'Erro desconhecido';
			$this->discord->enviarMensagem(
				canalTexto: CanalTexto::CONSULTAS,
				mensagem: "CRECI SP: Erro na consulta - {$errorMsg}",
			);
			throw new Exception("CRECI SP: {$errorMsg}");
		}

		$data = $result['data'];

		$this->discord->enviarMensagem(
			canalTexto: CanalTexto::CONSULTAS,
			mensagem: "CRECI SP: Corretor encontrado - {$data['nomeCompleto']} (CRECI {$creci}-{$tipoCreci}) - Situação: {$data['situacao']}",
		);

		return new SaidaConsultarCreciPlataforma(
			inscricao: $data['inscricao'] ?? $creci,
			nomeCompleto: $data['nomeCompleto'],
			fantasia: '',
			situacao: $data['situacao'] ?? 'Desconhecido',
			cidade: $data['cidade'] ?? '',
			estado: 'SP',
			data: $data['dataInscricao'] ?? '',
		);
	}

	/**
	 * Localiza o executável do Node.js no sistema.
	 */
	private function findNodePath(): ?string
	{
		// Tenta via which
		$whichOutput = trim(shell_exec('which node 2>/dev/null') ?? '');
		if (!empty($whichOutput) && file_exists($whichOutput)) {
			return $whichOutput;
		}

		// Tenta caminhos comuns
		$paths = [
			'/usr/bin/node',
			'/usr/local/bin/node',
		];

		// Verifica NVM paths
		$homeDir = getenv('HOME') ?: '/root';
		$nvmPaths = glob("{$homeDir}/.nvm/versions/node/*/bin/node");
		if (!empty($nvmPaths)) {
			rsort($nvmPaths);
			$paths = array_merge($nvmPaths, $paths);
		}

		foreach ($paths as $path) {
			if (file_exists($path) && is_executable($path)) {
				return $path;
			}
		}

		return null;
	}
}
