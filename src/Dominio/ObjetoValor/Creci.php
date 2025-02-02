<?php

declare(strict_types=1);

namespace App\Dominio\ObjetoValor;

use Exception;

readonly final class Creci
{
	public function __construct(
		private string $creci
	) {

		if (empty($creci)) {
			throw new Exception('CRECI não pode ser vazio');
		}
	}

	public function get(): string
	{
		return $this->creci;
	}
}
