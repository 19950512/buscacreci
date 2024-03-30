<?php

declare(strict_types=1);

namespace App\Dominio\ObjetoValor;

use Exception;

final readonly class URL
{
	public string $dominio;
	public string $subDominio;
	public string $protocolo;
	public string $TLD;
	public string $uri;

	public function __construct(
		private string $url
	){

		if(!$this->validacao()){
			throw new Exception('URL informada está inválida. '.$this->url);
		}
		// Extrai as partes da URL usando parse_url
		$parts = parse_url($this->url);

		// Obtém o host da URL
		$host = $parts['host'];

		// Divide o host em partes (subdomínio, domínio e TLD)
		$host_parts = explode('.', $host);

		// Conta o número de partes no host
		$num_parts = count($host_parts);

		// Se houver mais de 2 partes, então o primeiro é o subdomínio
		$this->subDominio = ($num_parts > 2) ? $host_parts[0] : '';

		// O domínio é a parte imediatamente anterior à última parte
		$dominio = $host_parts[$num_parts - 2];

		// O TLD é a última parte
		$this->TLD = $host_parts[$num_parts - 1];

		$this->dominio = $dominio.'.'.$this->TLD;

		$this->protocolo = parse_url($this->url, PHP_URL_SCHEME) ?? 'http';
		$this->uri = parse_url($this->url, PHP_URL_PATH) ?? '/';
	}

	public function validacao(): bool
	{
		return !!filter_var($this->url, FILTER_VALIDATE_URL);
	}

	public function get(): string
	{
		return $this->url;
	}
}